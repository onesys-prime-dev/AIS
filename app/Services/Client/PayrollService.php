<?php

namespace App\Services\Client;

use App\Models\Client\PayrollModel;

class PayrollService
{
    public function createPayroll(array $data)
    {
        $model = new PayrollModel();
        $model->save($data);
    }

    public function updatePayroll($id, array $data)
    {
        $model = new PayrollModel();
        $model->update($id, $data);
    }

    public function deletePayroll($id)
    {
        $model = new PayrollModel();
        $model->delete($id);
    }
}