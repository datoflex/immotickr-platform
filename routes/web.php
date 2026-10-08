<?php

use App\Http\Controllers\CronController;
use App\Http\Controllers\SearchAgentResultsController;
use App\Http\Controllers\SearchResultsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::view('search-agents', 'search-agents')
    ->middleware(['auth', 'verified'])
    ->name('search-agents.index');

Route::view('search-agents/create', 'search-agents')
    ->middleware(['auth', 'verified'])
    ->name('search-agents.create');

Route::view('search-agents/{searchAgent}/edit', 'search-agents')
    ->middleware(['auth', 'verified'])
    ->whereNumber('searchAgent')
    ->name('search-agents.edit');

Route::get('suchergebnisse', [SearchResultsController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('search-results.index');

Route::get('suchergebnisse/merkliste', [SearchResultsController::class, 'saved'])
    ->middleware(['auth', 'verified'])
    ->name('search-results.saved');

Route::get('search-agent-results/{token}', [SearchAgentResultsController::class, 'show'])
    ->where('token', '[0-9a-f]{32}')
    ->name('search-agent-results.show');

Route::get('cron/ingest-listing-emails', [CronController::class, 'ingestListingEmails'])
    ->name('cron.ingest-listing-emails');

Route::get('cron/run-search-agents', [CronController::class, 'runSearchAgents'])
    ->name('cron.run-search-agents');

require __DIR__.'/auth.php';
