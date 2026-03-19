<?php

namespace App\Controllers;

class SettingsController extends BaseController
{
    public function customization()
    {
        // Optional: pass data to view if needed
        $data = [
            'title' => 'Customization'
        ];

        return view('settings/customization', $data);
    }
}