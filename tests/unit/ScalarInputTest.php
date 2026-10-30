<?php
use App\Filters\ScalarInput;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;

/** A text field sent as an array must get a 400, while the real list fields keep working. */
final class ScalarInputTest extends CIUnitTestCase
{
    private function request(string $method, array $post, string $path = 'client_portal/help/store', ?array $json = null): IncomingRequest
    {
        $uri = new class($path) {
            public function __construct(private string $path) {}
            public function getRoutePath(): string { return $this->path; }
        };
        $request = $this->createMock(IncomingRequest::class);
        $request->method('getMethod')->willReturn($method);
        $request->method('getPost')->willReturn($post);
        $request->method('getUri')->willReturn($uri);
        $request->method('getJSON')->willReturn($json);
        $request->method('isAJAX')->willReturn(true);
        $request->method('getHeaderLine')->willReturn('');
        return $request;
    }

    private function runFilter(IncomingRequest $request)
    {
        return (new ScalarInput())->before($request);
    }

    public function testArrayInATextFieldIsRejectedWith400(): void
    {
        $response = $this->runFilter($this->request('POST', ['subject' => ['x'], 'message' => 'ok']));
        self::assertNotNull($response);
        self::assertSame(400, $response->getStatusCode());
    }

    public function testNestedArraysAreRejectedEvenInListFields(): void
    {
        self::assertSame(400, $this->runFilter($this->request('POST', ['roles' => [['x']]]))->getStatusCode());
        self::assertSame(400, $this->runFilter($this->request('POST', ['subject' => ['a' => ['b']]]))->getStatusCode());
    }

    public function testTheListFieldsOurScreensSendAreAllowed(): void
    {
        $lists = [
            'roles' => ['employee'], 'permissions' => ['payroll'], 'team_head_user_ids' => ['1'], 'exempt_user_ids' => ['1'],
            'department_ids' => ['1'], 'user_ids' => ['2'], 'working_days' => ['mon'], 'wfh_days' => ['fri'],
            'loan_type' => ['Salary'], 'allow_multiple_loans' => [0 => '1'],
        ];
        foreach ($lists as $field => $value) {
            self::assertNull($this->runFilter($this->request('POST', [$field => $value, 'name' => 'plain'])), $field);
        }
    }

    public function testPlainFormsAndReadOnlyRequestsPass(): void
    {
        self::assertNull($this->runFilter($this->request('POST', ['subject' => 'Hi', 'message' => 'there'])));
        self::assertNull($this->runFilter($this->request('GET', ['subject' => ['x']])));
    }

    public function testTaskJsonMustBePlainValues(): void
    {
        $route = 'client_portal/timesheet/tasks/5/create';
        self::assertSame(400, $this->runFilter($this->request('POST', [], $route, ['task_performed' => ['x']]))->getStatusCode());
        self::assertNull($this->runFilter($this->request('POST', [], $route, ['task_performed' => 'ok', 'client_id' => 3])));
        // Other JSON endpoints (payroll adjustments etc.) legitimately carry nested objects.
        self::assertNull($this->runFilter($this->request('POST', [], 'client_portal/payroll/compute', ['adjustments' => ['a' => 1]])));
    }
}
