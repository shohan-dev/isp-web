<?php

namespace Zapi\Monitoring\Services;

use Zapi\Monitoring\Storage\TrafficPathResolver;

class TrafficSpoolQueueWriterService
{
    private TrafficPathResolver $pathResolver;

    public function __construct(?TrafficPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new TrafficPathResolver();
    }

    /**
     * Hot path: accepts a pre-built DateTimeImmutable so we never re-parse
     * the created_at string.  Called by TrafficLogWriter::appendWithStamp().
     */
    public function appendWithStamp(array $record, \DateTimeImmutable $stamp): bool
    {
        return $this->write($record, $stamp);
    }

    /**
     * Legacy path: derives the stamp from the record's created_at string.
     */
    public function append(array $record): bool
    {
        $stamp = isset($record['created_at'])
            ? new \DateTimeImmutable((string) $record['created_at'])
            : new \DateTimeImmutable('now');
        return $this->write($record, $stamp);
    }

    private function write(array $record, \DateTimeImmutable $stamp): bool
    {
        $dir  = $this->pathResolver->spoolHourDir($stamp);
        $file = $this->pathResolver->spoolMinuteFile($stamp);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        $json = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            return false;
        }

        // Fail-open, low-contention append — no LOCK_EX by design.
        return @file_put_contents($file, $json . "\n", FILE_APPEND) !== false;
    }
}
