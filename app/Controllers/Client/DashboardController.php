<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function clientDashboard()
    {
        if (! auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        $data = [
            'title'         => 'Dashboard',
            'isClockedIn'   => true,                // ← get from your attendance/clock model
            'currentTime'   => date('H:i'),         // or real clock logic
            'currentDate'   => date('l, F j, Y')
            /* ,'notifications' => $this->getNotifications(), */ // your logic here
        ];

        return view('client_portal/dashboard', $data);
    }

    /* private function getNotifications()
    {
        // dummy data — replace with real query
        return [
            ['type' => 'accepted', 'date' => '02/26/26', 'time_ago' => '4h ago'],
            ['type' => 'denied',   'date' => '02/25/26', 'time_ago' => '2h ago'],
            ['type' => 'accepted', 'date' => '02/27/26', 'time_ago' => '30m ago'],
        ];
    } */
}