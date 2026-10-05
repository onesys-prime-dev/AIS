<?php
use App\Libraries\AttendanceWindow as Window;
use CodeIgniter\Test\CIUnitTestCase;

final class AttendanceWindowTest extends CIUnitTestCase
{
    public function testOvernightShiftKeepsThePreviousShiftDate(): void
    {
        $schedule = ['start_time' => '22:00:00', 'end_time' => '06:00:00'];
        self::assertSame('2026-01-15', Window::shiftDate('2026-01-16 01:00:00', $schedule));
        self::assertSame('2026-01-16', Window::shiftDate('2026-01-16 22:00:00', $schedule));
        [$start, $end] = Window::bounds(['time_in' => '2026-01-16 01:00:00', 'shift_date' => '2026-01-15', 'schedule_snapshot' => json_encode($schedule)]);
        self::assertSame('2026-01-15 22:00:00', $start->format('Y-m-d H:i:s'));
        self::assertSame('2026-01-16 06:00:00', $end->format('Y-m-d H:i:s'));
    }

    public function testNightOverlapIncludesEarlyMorningAndMultipleNights(): void
    {
        self::assertSame(3.0, Window::nightHours('2026-01-16 02:00:00', '2026-01-16 05:00:00'));
        self::assertSame(8.0, Window::nightHours('2026-01-15 22:00:00', '2026-01-16 06:00:00'));
        self::assertSame(16.0, Window::nightHours('2026-01-15 22:00:00', '2026-01-17 06:00:00'));
        self::assertSame(0.0, Window::nightHours('2026-01-15 08:00:00', '2026-01-15 17:00:00'));
    }

    public function testElapsedHoursRetainDatesAndClampBreakDeductions(): void
    {
        self::assertSame(31.0, Window::elapsedHours('2026-01-15 22:00:00', '2026-01-17 06:00:00', 1));
        self::assertSame(0.0, Window::elapsedHours('2026-01-15 22:00:00', '2026-01-15 22:01:00', 1));
    }

    public function testSnapshotWinsOverCurrentSchedule(): void
    {
        $snapshot = ['start_time' => '22:00:00', 'end_time' => '06:00:00', 'break_schedule' => [15, 60]];
        $row = ['schedule_snapshot' => json_encode($snapshot), 'scheduled_start' => '08:00:00'];
        self::assertSame($snapshot, Window::schedule($row, ['start_time' => '09:00:00']));
        self::assertSame([15, 60], Window::breaks($snapshot));
        self::assertSame([60], Window::breaks([]));
    }

    public function testInvalidCalendarDatesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Window::dateTime('2026-02-30 08:00:00');
    }

    public function testNegativeElapsedTimeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Window::elapsedHours('2026-01-16 08:00:00', '2026-01-15 17:00:00');
    }
}
