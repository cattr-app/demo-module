<?php

namespace Modules\Demo\Services;

use App\Contracts\ScreenshotService;
use App\Models\TimeInterval;
use Intervention\Image\ImageManagerStatic as Image;
use Storage;

/**
 * Serves every interval one of a small pool of colored placeholders instead of a real screenshot.
 *
 * The placeholder is chosen by the interval ID, so the number of files on the disk (local or S3)
 * stays at POOL_SIZE originals and POOL_SIZE thumbnails however many intervals there are.
 * Uploaded screenshots are discarded, and deleting an interval keeps the shared files.
 */
class DemoScreenshotService extends ScreenshotService
{
    public const POOL_SIZE = 10;
    public const DEMO_FOLDER = 'demo/';

    private const WIDTH = 1920;
    private const HEIGHT = 1080;
    private const THUMB_WIDTH = 280;
    private const THUMB_HEIGHT = 158;
    private const QUALITY = 50;

    private const COLORS = [
        '#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f',
        '#edc948', '#b07aa1', '#ff9da7', '#9c755f', '#bab0ac',
    ];

    public function getScreenshotPath(TimeInterval|int $interval): string
    {
        return self::PARENT_FOLDER . self::DEMO_FOLDER . $this->slot($interval) . '.' . self::FILE_FORMAT;
    }

    public function getThumbPath(TimeInterval|int $interval): string
    {
        return self::PARENT_FOLDER . self::DEMO_FOLDER . self::THUMBS_FOLDER
            . $this->slot($interval) . '.' . self::FILE_FORMAT;
    }

    public function saveScreenshot($file, $timeInterval): void
    {
        $this->ensurePlaceholder($this->slot($timeInterval));
    }

    public function createThumbnail(TimeInterval|int $timeInterval): void
    {
        $this->ensurePlaceholder($this->slot($timeInterval));
    }

    public function destroyScreenshot(TimeInterval|int $interval): void
    {
        // The placeholder is shared with other intervals
    }

    /** Create the placeholders that are missing on the disk */
    public function ensurePool(): void
    {
        for ($slot = 0; $slot < self::POOL_SIZE; $slot++) {
            $this->ensurePlaceholder($slot);
        }
    }

    private function ensurePlaceholder(int $slot): void
    {
        $color = self::COLORS[$slot % count(self::COLORS)];

        if (!Storage::exists($this->getScreenshotPath($slot))) {
            Storage::put(
                $this->getScreenshotPath($slot),
                (string)Image::canvas(self::WIDTH, self::HEIGHT, $color)->encode(self::FILE_FORMAT, self::QUALITY)
            );
        }

        if (!Storage::exists($this->getThumbPath($slot))) {
            Storage::put(
                $this->getThumbPath($slot),
                (string)Image::canvas(self::THUMB_WIDTH, self::THUMB_HEIGHT, $color)
                    ->encode(self::FILE_FORMAT, self::QUALITY)
            );
        }
    }

    /** Slot numbers are the IDs below POOL_SIZE, so a slot maps to itself */
    private function slot(TimeInterval|int $interval): int
    {
        $id = $interval instanceof TimeInterval ? $interval->id : $interval;

        return $id % self::POOL_SIZE;
    }
}
