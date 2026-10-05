<?php

use App\Services\Client\TimesheetsService;
use CodeIgniter\Test\CIUnitTestCase;

final class TimesheetRemarksTest extends CIUnitTestCase
{
    private function serviceWithoutDatabase(): TimesheetsService
    {
        return (new ReflectionClass(TimesheetsService::class))->newInstanceWithoutConstructor();
    }

    private function invoke(string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionMethod(TimesheetsService::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($this->serviceWithoutDatabase(), ...$arguments);
    }

    public function testApprovedRequestRemarksUseSpecificLabelsAndReasons(): void
    {
        self::assertSame(
            'Sick Leave (Medical appointment)',
            $this->invoke('formatApprovedRequestRemark', [
                'request_type' => 'leave',
                'leave_category' => 'sick',
                'reason' => 'Medical appointment',
            ])
        );

        self::assertSame(
            'Approved Overtime (Month-end processing)',
            $this->invoke('formatApprovedRequestRemark', [
                'request_type' => 'overtime',
                'reason' => 'Month-end processing',
            ])
        );
    }

    public function testAttendanceRemarksKeepSessionSpecificExplanations(): void
    {
        self::assertSame(
            'Approved Overtime (Month-end processing); Unapproved Overtime; '
                . 'Unapproved Undertime (Emergency); Missed Clock-out (Forgot to clock out)',
            $this->invoke(
                'getRemarksForAttendance',
                [
                    'unapproved_ut_reason' => 'Emergency',
                    'missed_clockout_reason' => 'Forgot to clock out',
                ],
                ['Approved Overtime (Month-end processing)'],
                0.5,
                1.0
            )
        );
    }

    public function testAttendanceRemarksRemoveDuplicatesAndEmptyValues(): void
    {
        self::assertSame(
            'Approved Undertime (Appointment)',
            $this->invoke(
                'getRemarksForAttendance',
                [],
                ['', 'Approved Undertime (Appointment)', 'Approved Undertime (Appointment)'],
                0.0,
                0.0
            )
        );
    }
}
