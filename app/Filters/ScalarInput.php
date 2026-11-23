<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rejects malformed request bodies before a controller can trip over them.
 *
 * Controllers read most form fields as text. Sending a field as an array (`name[]=x`) or nesting one
 * inside another (`name[a][b]=x`) turns every `(string) $post['name']` or `trim($post['name'])` into a
 * fatal "Array to string conversion" — a 500 from a request no screen of ours could ever send.
 *
 * Only the fields below are legitimately lists, and only a flat list of plain values is accepted
 * for them. The JSON task and attendance-correction endpoints must carry plain values only.
 * Everything else that arrives as an array gets a clear 400 instead.
 */
class ScalarInput implements FilterInterface
{
    /** Form fields that really are lists (checkbox groups, multi-selects, per-row settings). */
    private const LIST_FIELDS = [
        // Access Control
        'roles', 'permissions', 'team_head_user_ids', 'exempt_user_ids',
        // Clients
        'department_ids', 'user_ids',
        // Employee form (working schedule)
        'working_days', 'wfh_days',
        // Loan settings: one value per loan type row
        'loan_type', 'loan_category', 'interest_rate', 'max_salary_multiplier', 'max_installments',
        'deduction_frequency', 'grace_period_days', 'penalty_rate', 'allow_multiple_loans',
    ];

    /** JSON endpoints whose whole body is plain values (no nested objects or lists). */
    private const FLAT_JSON_ROUTES = '~^client_portal/timesheet/(?:tasks/|update-attendance/)~i';

    public function before(RequestInterface $request, $arguments = null)
    {
        $method = strtolower($request->getMethod());
        if (! in_array($method, ['post', 'put', 'patch', 'delete'], true)) {
            return null;
        }

        $bad = false;
        foreach ((array) $request->getPost() as $name => $value) {
            if (! is_array($value)) continue;
            if (! in_array($name, self::LIST_FIELDS, true) || $this->hasNested($value)) {
                $bad = true;
                break;
            }
        }

        if (! $bad && preg_match(self::FLAT_JSON_ROUTES, trim($request->getUri()->getRoutePath(), '/'))) {
            $json = null;
            try {
                $json = $request->getJSON(true);
            } catch (\Throwable $e) {
                // Not valid JSON — the controller already treats that as an empty body.
            }
            if (is_array($json) && $this->hasNested($json)) {
                $bad = true;
            }
        }

        return $bad ? $this->reject($request) : null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    private function hasNested(array $values): bool
    {
        foreach ($values as $v) {
            if (is_array($v) || is_object($v)) return true;
        }
        return false;
    }

    private function reject(RequestInterface $request)
    {
        $message = 'That request was not valid. Please reload the page and try again.';
        $wantsJson = $request->isAJAX()
            || str_contains(strtolower($request->getHeaderLine('Accept')), 'json')
            || str_contains(strtolower($request->getHeaderLine('Content-Type')), 'json');

        if ($wantsJson) {
            return service('response')->setStatusCode(400)->setJSON(['success' => false, 'error' => $message, 'message' => $message]);
        }
        return redirect()->back()->with('error', $message);
    }
}
