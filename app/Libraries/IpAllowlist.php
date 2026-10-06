<?php

namespace App\Libraries;

/**
 * Matches a request IP against an admin-configured allowlist of exact IPs
 * and/or CIDR ranges, IPv4 and IPv6 (e.g. "203.0.113.4", "203.0.113.0/24",
 * "2001:db8::1", "2001:db8::/32"). Used to restrict Onsite clock-ins to the
 * office network — see AttendanceSessionService::clockIn().
 */
class IpAllowlist
{
    /** Splits a stored allowlist string (comma and/or newline separated) into entries. */
    public static function parse(string $raw): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[,\r\n]+/', $raw) ?: [])));
    }

    /** True if $entry is a valid IPv4/IPv6 address, or one with a valid /prefix. */
    public static function isValid(string $entry): bool
    {
        [$addr, $bits] = array_pad(explode('/', $entry, 2), 2, null);
        $packed = self::pack($addr);
        if ($packed === null) {
            return false;
        }
        if ($bits === null) {
            return true;
        }
        return ctype_digit($bits) && (int) $bits <= strlen($packed) * 8;
    }

    public static function matches(string $ip, array $ranges): bool
    {
        $packedIp = self::pack($ip);
        if ($packedIp === null) {
            return false;
        }
        foreach ($ranges as $range) {
            if (self::matchesOne($packedIp, $range)) {
                return true;
            }
        }
        return false;
    }

    private static function matchesOne(string $packedIp, string $range): bool
    {
        [$addr, $bits] = array_pad(explode('/', trim($range), 2), 2, null);
        $packedNet = self::pack($addr);
        if ($packedNet === null || strlen($packedNet) !== strlen($packedIp)) {
            return false;
        }

        $maxBits = strlen($packedNet) * 8;
        if ($bits === null) {
            $bits = $maxBits;
        } elseif (!ctype_digit($bits) || (int) $bits > $maxBits) {
            return false;
        }
        $bits = (int) $bits;

        $fullBytes = intdiv($bits, 8);
        if (substr($packedIp, 0, $fullBytes) !== substr($packedNet, 0, $fullBytes)) {
            return false;
        }
        $remainder = $bits % 8;
        if ($remainder === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $remainder)) & 0xFF;
        return (ord($packedIp[$fullBytes]) & $mask) === (ord($packedNet[$fullBytes]) & $mask);
    }

    /**
     * Binary form of an address (4 bytes for IPv4, 16 for IPv6), or null if
     * invalid. An IPv4-mapped IPv6 address (::ffff:203.0.113.4 — how a
     * dual-stack server can report an IPv4 client) is folded back to plain
     * IPv4 so it matches the IPv4 entries an admin typed.
     */
    private static function pack(?string $ip): ?string
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return null;
        }
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }
        if (strlen($packed) === 16 && substr($packed, 0, 12) === "\0\0\0\0\0\0\0\0\0\0\xff\xff") {
            return substr($packed, 12);
        }
        return $packed;
    }
}
