<?php

namespace Zapi\Monitoring\Storage;

use Zapi\Monitoring\Services\TrafficSpoolQueueWriterService;

final class TrafficLogWriter
{
    private static ?TrafficSpoolQueueWriterService $queueWriter = null;

    private static function writer(): TrafficSpoolQueueWriterService
    {
        if (self::$queueWriter === null) {
            self::$queueWriter = new TrafficSpoolQueueWriterService();
        }
        return self::$queueWriter;
    }

    /**
     * Append a record, passing the pre-built stamp so the spool writer
     * doesn't have to re-parse the created_at string.
     */
    public static function appendWithStamp(array $record, \DateTimeImmutable $stamp): void
    {
        // Strip the internal __stamp key before writing to disk.
        unset($record['__stamp']);
        self::writer()->appendWithStamp($record, $stamp);
    }

    /**
     * Legacy append path (no stamp — writer re-parses created_at).
     * Kept for any call sites that build records outside the filter.
     */
    public static function append(array $record): void
    {
        unset($record['__stamp']);
        self::writer()->append($record);
    }

    public function write(array $record): void
    {
        self::append($record);
    }
}
