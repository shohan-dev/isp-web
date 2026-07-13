<?php

namespace Zapi\Monitoring\Storage;

final class TrafficPathResolver
{
    public function basePath(): string
    {
        return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'log_api' . DIRECTORY_SEPARATOR . 'store';
    }

    public function hourDir(\DateTimeImmutable $at): string
    {
        return $this->rawBaseDir()
            . DIRECTORY_SEPARATOR . $at->format('Y')
            . DIRECTORY_SEPARATOR . $at->format('m')
            . DIRECTORY_SEPARATOR . $at->format('d')
            . DIRECTORY_SEPARATOR . $at->format('H');
    }

    public function minuteFile(\DateTimeImmutable $at): string
    {
        return $this->hourDir($at) . DIRECTORY_SEPARATOR . 'traffic_' . $at->format('Ymd_Hi') . '.jsonl';
    }

    public function indexBaseDir(): string
    {
        return $this->basePath() . DIRECTORY_SEPARATOR . '_index';
    }

    public function rawBaseDir(): string
    {
        return $this->basePath() . DIRECTORY_SEPARATOR . 'raw';
    }

    public function spoolBaseDir(): string
    {
        return $this->basePath() . DIRECTORY_SEPARATOR . 'spool';
    }

    public function spoolHourDir(\DateTimeImmutable $at): string
    {
        return $this->spoolBaseDir()
            . DIRECTORY_SEPARATOR . $at->format('Y')
            . DIRECTORY_SEPARATOR . $at->format('m')
            . DIRECTORY_SEPARATOR . $at->format('d')
            . DIRECTORY_SEPARATOR . $at->format('H');
    }

    public function spoolMinuteFile(\DateTimeImmutable $at): string
    {
        return $this->spoolHourDir($at) . DIRECTORY_SEPARATOR . 'spool_' . $at->format('Ymd_Hi') . '.jsonl';
    }

    public function compactBaseDir(): string
    {
        return $this->indexBaseDir() . DIRECTORY_SEPARATOR . 'compact';
    }

    public function compactDailyDir(): string
    {
        return $this->compactBaseDir() . DIRECTORY_SEPARATOR . 'daily';
    }

    public function compactDailyFile(\DateTimeImmutable $at): string
    {
        return $this->compactDailyDir() . DIRECTORY_SEPARATOR . $at->format('Ymd') . '.json';
    }

    public function compactMetaFile(): string
    {
        return $this->compactBaseDir() . DIRECTORY_SEPARATOR . 'meta.json';
    }

    public function summaryBaseDir(): string
    {
        return $this->indexBaseDir() . DIRECTORY_SEPARATOR . 'summary_day';
    }

    public function indexRecentFile(): string
    {
        return $this->indexBaseDir() . DIRECTORY_SEPARATOR . 'recent.jsonl';
    }

    public function indexMetaFile(): string
    {
        return $this->indexBaseDir() . DIRECTORY_SEPARATOR . 'meta.json';
    }

    public function indexMinuteAggFile(\DateTimeImmutable $at): string
    {
        return $this->indexBaseDir()
            . DIRECTORY_SEPARATOR . 'agg_minute'
            . DIRECTORY_SEPARATOR . $at->format('Ymd_H') . '.json';
    }

    public function indexEndpointAggFile(\DateTimeImmutable $at): string
    {
        return $this->indexBaseDir()
            . DIRECTORY_SEPARATOR . 'agg_endpoint'
            . DIRECTORY_SEPARATOR . $at->format('Ymd_H') . '.json';
    }

    public function indexDeviceAggFile(\DateTimeImmutable $at): string
    {
        return $this->indexBaseDir()
            . DIRECTORY_SEPARATOR . 'agg_device'
            . DIRECTORY_SEPARATOR . $at->format('Ymd_H') . '.json';
    }

    public function indexDaySummaryFile(\DateTimeImmutable $at): string
    {
        return $this->summaryBaseDir()
            . DIRECTORY_SEPARATOR . $at->format('Ymd') . '.json';
    }

    public function indexBudgetMetaFile(\DateTimeImmutable $at): string
    {
        return $this->indexBaseDir()
            . DIRECTORY_SEPARATOR . 'budget'
            . DIRECTORY_SEPARATOR . $at->format('Ymd') . '.json';
    }
}

