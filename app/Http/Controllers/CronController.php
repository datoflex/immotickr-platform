<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CronController extends Controller
{
    public function ingestListingEmails(Request $request): JsonResponse
    {
        $this->authorize($request);

        return $this->runLocked('cron:ingest-listing-emails', 'app:ingest-listing-emails');
    }

    public function runSearchAgents(Request $request): JsonResponse
    {
        $this->authorize($request);

        return $this->runLocked('cron:run-search-agents', 'app:run-search-agents');
    }

    private function runLocked(string $lockKey, string $command): JsonResponse
    {
        $lock = Cache::lock($lockKey, 300);

        if (! $lock->get()) {
            return response()->json(['status' => 'skipped', 'reason' => 'already running']);
        }

        try {
            Artisan::call($command);
            $output = Artisan::output();
        } finally {
            $lock->release();
        }

        return response()->json([
            'status' => 'ok',
            'command' => $command,
            'output' => trim($output),
        ]);
    }

    private function authorize(Request $request): void
    {
        $secret = config('services.cron.secret');
        $provided = $request->header('X-Cron-Secret') ?? $request->query('token');

        if (! $secret || ! $provided || ! hash_equals($secret, $provided)) {
            abort(403);
        }
    }
}
