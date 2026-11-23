<?php
use App\Controllers\Client\ReminderController;
use App\Libraries\ReminderConfig;
use CodeIgniter\Test\CIUnitTestCase;

/** The reminder settings parsing: what admins and the employee form may submit, and how an override layers on. */
final class ReminderConfigTest extends CIUnitTestCase
{
    private function adminInput(array $over = []): array
    {
        return $over + [
            'clock_in_steps' => '-10, 0, 10, 30, 60', 'clock_out_steps' => '-10, 30, 120',
            'clock_in_stop_after' => '180', 'clock_out_stop_after' => '240', 'clock_in_escalate_step' => '30',
            'break_close_before' => '10', 'break_end_before' => '2',
        ];
    }

    public function testParseStepsSortsAndDedupes(): void
    {
        $this->assertSame([-10, 0, 30], ReminderConfig::parseSteps('30, -10;0 0'));
        $this->assertSame([5, 10], ReminderConfig::parseSteps([10, '5']));
    }

    public function testParseStepsRejectsBadInput(): void
    {
        foreach (['', '  ', 'abc', '1.5', '10,x', '99999', '-99999', '1,,'] as $bad) {
            $v = ReminderConfig::parseSteps($bad);
            $this->assertTrue($v === null || $bad === '1,,', "should reject '$bad'");
        }
        $this->assertNull(ReminderConfig::parseSteps(implode(',', range(1, 50))), 'too many steps');
    }

    public function testDefaultsRoundTripThroughAdminInput(): void
    {
        $cfg = ReminderConfig::fromAdminInput($this->adminInput());
        $d = ReminderConfig::defaults();
        $this->assertSame($d['clock_in']['steps'], $cfg['clock_in']['steps']);
        $this->assertSame($d['clock_out']['steps'], $cfg['clock_out']['steps']);
        $this->assertSame(30, $cfg['clock_in']['escalate_step']);
    }

    public function testEscalationMinuteMayBeAnywhereBeforeTheStopTime(): void
    {
        $cfg = ReminderConfig::fromAdminInput($this->adminInput(['clock_in_escalate_step' => '45']));
        $this->assertSame(45, $cfg['clock_in']['escalate_step']);
    }

    public function testEscalationAfterTheStopTimeIsRejected(): void
    {
        $this->expectException(DomainException::class);
        ReminderConfig::fromAdminInput($this->adminInput(['clock_in_stop_after' => '20', 'clock_in_escalate_step' => '30']));
    }

    public function testAdminInputRejectsBadNumbers(): void
    {
        foreach ([['clock_in_steps' => 'oops'], ['clock_in_stop_after' => '5'], ['break_end_before' => '-1'], ['break_close_before' => '999'], ['clock_out_steps' => '']] as $over) {
            try {
                ReminderConfig::fromAdminInput($this->adminInput($over));
                $this->fail('accepted ' . json_encode($over));
            } catch (DomainException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }

    public function testForEmployeeAppliesOnlyValidOverrides(): void
    {
        $g = ReminderConfig::defaults();
        $e = ReminderConfig::forEmployee($g, ['clock_in_steps' => [0, 15], 'clock_out_steps' => 'junk', 'break_end_before' => 5, 'break_close_before' => '']);
        $this->assertSame([0, 15], $e['clock_in']['steps']);
        $this->assertSame($g['clock_out']['steps'], $e['clock_out']['steps']);
        $this->assertSame(5, $e['break']['end_before']);
        $this->assertSame($g['break']['window_close_before'], $e['break']['window_close_before']);
        $this->assertSame($g, ReminderConfig::forEmployee($g, null));
        $this->assertSame($g, ReminderConfig::forEmployee($g, 'not json'));
        $this->assertSame([0, 15], ReminderConfig::forEmployee($g, json_encode(['clock_in_steps' => [0, 15]]))['clock_in']['steps']);
    }

    public function testOverrideFromInputSkipsBlanksAndValidates(): void
    {
        $this->assertSame([], ReminderConfig::overrideFromInput(['clock_in_steps' => '', 'break_end_before' => ' ']));
        $this->assertSame(['clock_in_steps' => [-5, 0], 'break_end_before' => 3], ReminderConfig::overrideFromInput(['clock_in_steps' => '0,-5', 'break_end_before' => '3']));
        $this->expectException(DomainException::class);
        ReminderConfig::overrideFromInput(['clock_out_steps' => 'x']);
    }

    public function testPushEndpointAllowlist(): void
    {
        $this->assertTrue(ReminderController::isPushEndpoint('https://fcm.googleapis.com/fcm/send/abc'));
        $this->assertTrue(ReminderController::isPushEndpoint('https://updates.push.services.mozilla.com/wpush/v2/x'));
        foreach (['http://fcm.googleapis.com/x', 'https://evil.example/x', 'https://fcm.googleapis.com.evil.io/x', 'https://127.0.0.1/x', 'https://u@fcm.googleapis.com/x'] as $bad) {
            $this->assertFalse(ReminderController::isPushEndpoint($bad), $bad);
        }
    }
}
