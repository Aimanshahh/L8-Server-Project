<?php namespace App\Services\Robocall;

class RobocallNumbers
{
    const MAX_NUMBERS = 2;

    /**
     * Normalise to the local format the call API expects (03XXXXXXXXX).
     * Accepts the same forms the engine's formatPhoneNumber() accepts.
     * Returns null when the number is not valid.
     */
    public static function normalize($raw)
    {
        $digits = preg_replace('/\D/', '', (string) $raw);

        if (preg_match('/^92(\d{10})$/', $digits, $m)) {
            return '0' . $m[1];
        }

        if (preg_match('/^03\d{9}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^3\d{9}$/', $digits)) {
            return '0' . $digits;
        }

        return null;
    }

    /** Valid numbers found in a free-text phone field (split on / _ = , | ;). */
    public static function fromPhoneField($field)
    {
        $field = str_replace(['-', ' '], '', (string) $field);
        $parts = preg_split('/[\/_=,|;]+/', $field);
        $out = [];

        foreach ($parts as $part) {
            $n = static::normalize($part);

            if ($n !== null && !in_array($n, $out, true)) {
                $out[] = $n;
            }
        }

        return $out;
    }

    /**
     * Ordered dial list for a user row/array: [primary] or [primary, secondary].
     * Legacy users (nothing configured in the Robocall tab) keep their first
     * phone_number only, exactly as before.
     */
    public static function forUser($user)
    {
        $user = (array) $user;
        $stored = !empty($user['robo_phone_numbers']) ? json_decode($user['robo_phone_numbers'], true) : null;
        $out = [];

        if (is_array($stored)) {
            foreach ($stored as $raw) {
                $n = static::normalize($raw);

                if ($n !== null && !in_array($n, $out, true)) {
                    $out[] = $n;
                }
            }
        }

        if ($out) {
            return array_slice($out, 0, static::MAX_NUMBERS);
        }

        return array_slice(static::fromPhoneField($user['phone_number'] ?? ''), 0, 1);
    }
}
