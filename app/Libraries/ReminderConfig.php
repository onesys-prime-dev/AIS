<?php

namespace App\Libraries;

use DomainException;

/**
 * Settings for the attendance reminders: the defaults, merging the global settings with one employee's own
 * minutes, and validating what an admin or employee types. Pure (no database), so every rule is unit-testable.
 *
 * Step lists are minutes relative to an anchor: clock in = the shift start, clock out = the shift end (or the end of
 * approved overtime). Negative = before the anchor ("-10" = 10 minutes before).
 */
final class ReminderConfig
{
    public const MIN_MINUTES = -180;
    public const MAX_MINUTES = 720;
    public const MAX_STEPS   = 8;

    public static function defaults(): array
    {
        return [
            'enabled'    => true,
            'clock_in'   => ['enabled' => true, 'steps' => [-10, 0, 10, 30, 60], 'stop_after' => 180, 'escalate_step' => 30],
            'clock_out'  => ['enabled' => true, 'steps' => [-10, 30, 120], 'stop_after' => 240],
            'break'      => ['enabled' => true, 'window_close_before' => 10, 'end_before' => 2],
            'escalation' => ['enabled' => true],
            'email'      => ['enabled' => true, 'before' => false],
            'popup'      => ['enabled' => true],
        ];
    }

    /** Defaults overlaid with whatever was saved (unknown or malformed values fall back to the defaults). */
    public static function fromStored($stored): array
    {
        $out = self::defaults();
        if (is_string($stored)) $stored = json_decode($stored, true);
        if (! is_array($stored)) return $out;

        if (array_key_exists('enabled', $stored)) $out['enabled'] = (bool) $stored['enabled'];
        foreach (['clock_in', 'clock_out', 'break', 'escalation', 'email', 'popup'] as $section) {
            if (! isset($stored[$section]) || ! is_array($stored[$section])) continue;
            foreach ($out[$section] as $key => $default) {
                if (! array_key_exists($key, $stored[$section])) continue;
                $value = $stored[$section][$key];
                if (is_bool($default))      $out[$section][$key] = (bool) $value;
                elseif (is_array($default)) $out[$section][$key] = self::parseSteps($value) ?? $default;
                elseif (is_int($default))   $out[$section][$key] = is_numeric($value) ? (int) $value : $default;
            }
        }
        return $out;
    }

    /**
     * "-10, 0, 10" (or an array) -> sorted unique ints; null when blank or invalid. Out-of-range numbers or more
     * than MAX_STEPS entries make the whole list invalid.
     */
    public static function parseSteps($value): ?array
    {
        if (is_array($value)) $parts = $value;
        else {
            $value = trim((string) $value);
            if ($value === '') return null;
            $parts = preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        }
        $out = [];
        foreach ($parts as $p) {
            $p = trim((string) $p);
            if (! preg_match('/^-?\d+$/', $p)) return null;
            $n = (int) $p;
            if ($n < self::MIN_MINUTES || $n > self::MAX_MINUTES) return null;
            $out[$n] = true;
        }
        $out = array_keys($out);
        if (! $out || count($out) > self::MAX_STEPS) return null;
        sort($out);
        return $out;
    }

    /** One employee's own minutes on top of the global settings. Only the four overridable values exist. */
    public static function forEmployee(array $global, $override): array
    {
        if (is_string($override)) $override = json_decode($override, true);
        if (! is_array($override)) return $global;

        if (($s = self::parseSteps($override['clock_in_steps'] ?? null)) !== null) $global['clock_in']['steps'] = $s;
        if (($s = self::parseSteps($override['clock_out_steps'] ?? null)) !== null) $global['clock_out']['steps'] = $s;
        foreach (['break_close_before' => 'window_close_before', 'break_end_before' => 'end_before'] as $from => $to) {
            if (isset($override[$from]) && $override[$from] !== '' && is_numeric($override[$from])) {
                $global['break'][$to] = max(0, min(120, (int) $override[$from]));
            }
        }
        return $global;
    }

    /**
     * Validates what an admin submitted in the Reminders tab and returns the settings to store. Step lists come in
     * as comma-separated text; everything else as simple values. Throws DomainException with a message to show.
     *
     * @param array<string, mixed> $in
     */
    public static function fromAdminInput(array $in): array
    {
        $cfg = self::defaults();
        $bool = static fn ($v) => in_array((string) $v, ['1', 'true', 'on', 'yes'], true);

        $cfg['enabled']                    = $bool($in['enabled'] ?? '1');
        $cfg['clock_in']['enabled']        = $bool($in['clock_in_enabled'] ?? '1');
        $cfg['clock_out']['enabled']       = $bool($in['clock_out_enabled'] ?? '1');
        $cfg['break']['enabled']           = $bool($in['break_enabled'] ?? '1');
        $cfg['escalation']['enabled']      = $bool($in['escalation_enabled'] ?? '1');
        $cfg['email']['enabled']           = $bool($in['email_enabled'] ?? '1');
        $cfg['email']['before']            = $bool($in['email_before'] ?? '0');
        $cfg['popup']['enabled']           = $bool($in['popup_enabled'] ?? '1');

        foreach ([['clock_in', 'clock_in_steps', 'Clock-in'], ['clock_out', 'clock_out_steps', 'Clock-out']] as [$section, $field, $label]) {
            $steps = self::parseSteps($in[$field] ?? '');
            if ($steps === null) {
                throw new DomainException($label . ' minutes must be whole numbers between ' . self::MIN_MINUTES . ' and ' . self::MAX_MINUTES . ', separated by commas (at most ' . self::MAX_STEPS . ').');
            }
            $cfg[$section]['steps'] = $steps;
        }

        $cfg['clock_in']['stop_after']  = self::intInRange($in['clock_in_stop_after'] ?? '', 15, 720, 'Clock-in stop time');
        $cfg['clock_out']['stop_after'] = self::intInRange($in['clock_out_stop_after'] ?? '', 15, 720, 'Clock-out stop time');
        $cfg['clock_in']['escalate_step'] = self::intInRange($in['clock_in_escalate_step'] ?? '', self::MIN_MINUTES, self::MAX_MINUTES, 'Escalation minute');
        $cfg['break']['window_close_before'] = self::intInRange($in['break_close_before'] ?? '', 0, 120, 'Break window warning');
        $cfg['break']['end_before']          = self::intInRange($in['break_end_before'] ?? '', 0, 120, 'Break ending warning');

        if ($cfg['clock_in']['escalate_step'] > $cfg['clock_in']['stop_after']) {
            throw new DomainException('The Team Head / admin copy minute must be no later than the clock-in stop time (' . $cfg['clock_in']['stop_after'] . ' minutes), or it would never be sent.');
        }
        return $cfg;
    }

    /** What the employee form posts for the per-employee override (all optional); returns the JSON-ready array. */
    public static function overrideFromInput(array $in): array
    {
        $out = [];
        foreach (['clock_in_steps' => 'Clock-in minutes', 'clock_out_steps' => 'Clock-out minutes'] as $key => $label) {
            $raw = trim((string) ($in[$key] ?? ''));
            if ($raw === '') continue;
            $steps = self::parseSteps($raw);
            if ($steps === null) {
                throw new DomainException($label . ' must be whole numbers between ' . self::MIN_MINUTES . ' and ' . self::MAX_MINUTES . ', separated by commas.');
            }
            $out[$key] = $steps;
        }
        foreach (['break_close_before' => 'Break window warning', 'break_end_before' => 'Break ending warning'] as $key => $label) {
            $raw = trim((string) ($in[$key] ?? ''));
            if ($raw === '') continue;
            $out[$key] = self::intInRange($raw, 0, 120, $label);
        }
        return $out;
    }

    private static function intInRange($value, int $min, int $max, string $label): int
    {
        $value = trim((string) $value);
        if ($value === '' || ! preg_match('/^-?\d+$/', $value) || (int) $value < $min || (int) $value > $max) {
            throw new DomainException($label . ' must be a whole number between ' . $min . ' and ' . $max . '.');
        }
        return (int) $value;
    }
}
