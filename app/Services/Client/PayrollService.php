<?php

namespace App\Services\Client;

use App\Models\Client\PayrollModel;

class PayrollService
{
    public function createPayroll(array $data)
    {
        $model = new PayrollModel();
        $model->save($data);

        service('activityLogger')->payrollCreated($model->getInsertID(), $data);
    }

    public function updatePayroll($id, array $data)
    {
        $model = new PayrollModel();

        // Fetch old state before updating
        $old = $model->find($id);

        $model->update($id, $data);

        // Log daily_rate change via its dedicated method
        if (isset($data['daily_rate']) && (string) $old['daily_rate'] !== (string) $data['daily_rate']) {
            service('activityLogger')->payrollRateUpdated((int) $id, $old['daily_rate'], $data['daily_rate']);
        }

        // Log status change via its dedicated method
        if (isset($data['status']) && $old['status'] !== $data['status']) {
            service('activityLogger')->payrollStatusChanged((int) $id, $old['status'], $data['status']);
        }

        // Log any other changed fields that aren't covered by the two dedicated methods above
        $otherFields = array_diff_key($data, array_flip(['daily_rate', 'status']));
        if (!empty($otherFields)) {
            $changed = array_filter(
                $otherFields,
                fn($v, $k) => (string) $v !== (string) ($old[$k] ?? ''),
                ARRAY_FILTER_USE_BOTH
            );

            if (!empty($changed)) {
                service('activityLogger')->updated(
                    'payroll',
                    'Payroll record updated',
                    (int) $id,
                    array_intersect_key($old, $changed),
                    $changed
                );
            }
        }
    }

    public function deletePayroll($id)
    {
        $model = new PayrollModel();

        // Fetch before deleting so we have something to log
        $old = $model->find($id);

        $model->delete($id);

        if ($old) {
            service('activityLogger')->payrollDeleted((int) $id, $old);
        }
    }
}
