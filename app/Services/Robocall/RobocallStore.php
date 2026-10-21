<?php namespace App\Services\Robocall;

/**
 * File-backed store for robocall webhook payloads.
 *
 * Deliberately dependency-free (no DB, no container) so the receiver keeps
 * working before/without a migration, and so the logic can be unit tested.
 * Payloads are kept as newline-delimited JSON, newest last on disk, newest
 * first when read back.
 */
class RobocallStore
{
    /** Rows the page lists by default / hard cap on the file. */
    const PAGE_ROWS = 50;
    const MAX_ROWS = 5000;

    /**
     * Trimming rewrites the whole file, so it only runs once the log passes
     * this size (stat, cheap) instead of on every single payload.
     */
    const MAX_BYTES = 4194304; // 4 MiB

    /** @var string */
    protected $path;

    public function __construct($path = null)
    {
        $this->path = $path ?: $this->defaultPath();
    }

    public function path()
    {
        return $this->path;
    }

    /**
     * Append one payload. Returns false when it could not be written, so the
     * caller can answer with an error instead of silently dropping data.
     */
    public function append(array $record)
    {
        $dir = dirname($this->path);

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $bytes = @file_put_contents(
            $this->path,
            json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        if ($bytes === false) {
            return false;
        }

        $this->trim();

        return true;
    }

    /** Can payloads actually be written right now? */
    public function writable()
    {
        if (file_exists($this->path)) {
            return is_writable($this->path);
        }

        // Walk up to the nearest existing component. If that component exists
        // but is not a directory (something is in the way), the path can never
        // be written to - report false rather than a misleading true.
        $dir = dirname($this->path);

        while (!file_exists($dir) && strlen($dir) > 1) {
            $dir = dirname($dir);
        }

        return is_dir($dir) && is_writable($dir);
    }

    /** Newest first. */
    public function read($limit = self::PAGE_ROWS)
    {
        $lines = $this->lines();

        if (!$lines) {
            return [];
        }

        $lines = array_reverse(array_slice($lines, -$limit));

        $out = [];

        foreach ($lines as $line) {
            $row = json_decode($line, true);

            $out[] = is_array($row) ? $row : ['raw' => $line, 'payload' => null];
        }

        return $out;
    }

    public function count()
    {
        return count($this->lines());
    }

    public function clear()
    {
        if (file_exists($this->path)) {
            @unlink($this->path);
        }
    }

    /* ------------------------------------------------------------------ */

    /** Decode whatever the provider sent: JSON, form data, or plain text. */
    public static function decodePayload($raw, array $input = [])
    {
        $decoded = json_decode((string) $raw, true);

        if (json_last_error() === JSON_ERROR_NONE && !is_null($decoded)) {
            return $decoded;
        }

        return empty($input) ? (string) $raw : $input;
    }

    /**
     * First non-empty scalar among the candidate keys, searching nested
     * objects too - providers nest the call object differently every time.
     */
    public static function pick(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== '' && is_scalar($data[$key])) {
                return (string) $data[$key];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = static::pick($value, $keys);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    protected function lines()
    {
        if (!file_exists($this->path)) {
            return [];
        }

        $lines = @file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return $lines ? $lines : [];
    }

    /** Keep the file bounded so the page and the disk stay sane. */
    protected function trim()
    {
        if (file_exists($this->path) && filesize($this->path) < static::MAX_BYTES) {
            return;
        }

        $lines = $this->lines();

        if (count($lines) <= static::MAX_ROWS) {
            return;
        }

        $keep = array_slice($lines, -static::MAX_ROWS);

        file_put_contents($this->path, implode(PHP_EOL, $keep) . PHP_EOL, LOCK_EX);
    }

    protected function defaultPath()
    {
        if (function_exists('storage_path')) {
            return storage_path('app/robocall/calls.jsonl');
        }

        return sys_get_temp_dir() . '/robocall/calls.jsonl';
    }
}
