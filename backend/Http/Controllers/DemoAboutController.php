<?php

namespace Modules\Demo\Http\Controllers;

use App\Http\Controllers\Api\AboutController;
use App\Http\Requests\About\ManageStorageRequest;
use Illuminate\Http\JsonResponse;

/**
 * Replaces the core controller for the storage endpoints, so the demo never reveals the real disk
 * size or usage of the host and nobody can start a real cleanup. Authorization still comes from
 * ManageStorageRequest, the other About endpoints are inherited unchanged.
 */
class DemoAboutController extends AboutController
{
    private const GIB = 1024 ** 3;

    private const TOTAL_SPACE = 200 * self::GIB;

    private const USED_SPACE = 64 * self::GIB;

    private const THRESHOLD = 75;

    private const SCREENSHOTS_AVAILABLE = 1280;

    /** Dummy values only: nothing here is read from the disk, the config or the database */
    public function storage(ManageStorageRequest $request): JsonResponse
    {
        $finishedAt = now()->subDay()->setTime(3, 0)->toIso8601String();

        return responder()->success([
            'space' => [
                'left' => self::TOTAL_SPACE - self::USED_SPACE,
                'used' => self::USED_SPACE,
                'total' => self::TOTAL_SPACE,
            ],
            'threshold' => self::THRESHOLD,
            'need_thinning' => false,
            'rotation_supported' => true,
            'screenshots_available' => self::SCREENSHOTS_AVAILABLE,
            'thinning' => [
                'now' => false,
                'last' => $finishedAt,
                'status' => 'completed',
                'requested_at' => $finishedAt,
                'finished_at' => $finishedAt,
            ],
        ])->respond();
    }

    /** No-op: nothing is queued, so the status above stays the same */
    public function startStorageClean(ManageStorageRequest $request): JsonResponse
    {
        return responder()->success()->respond(204);
    }
}
