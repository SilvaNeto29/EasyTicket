<?php

use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use Livewire\Volt\Volt;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        $dbStatus = 'ok';
    } catch (\Throwable) {
        $dbStatus = 'error';
    }

    $status = $dbStatus === 'ok' ? 'ok' : 'degraded';
    $code   = $status === 'ok' ? 200 : 503;

    return response()->json(['status' => $status, 'db' => $dbStatus], $code);
})->name('health');

Route::middleware(['auth', 'verified'])->group(function () {

    Volt::route('/dashboard', 'dashboard')->name('dashboard');

    // Projects
    Volt::route('/projects', 'projects.index')->name('projects.index');
    Volt::route('/projects/create', 'projects.create')->name('projects.create');
    Volt::route('/projects/{project}', 'projects.show')->name('projects.show');
    Volt::route('/projects/{project}/edit', 'projects.edit')->name('projects.edit');

    // Tickets
    Volt::route('/tickets/create', 'tickets.create')->name('tickets.create');
    Volt::route('/tickets/{ticket}', 'tickets.show')->name('tickets.show');

    // Export
    Route::get('/export', [ExportController::class, 'download'])->name('export.download');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Mcp::web('/mcp', \App\Mcp\Servers\EasyTicketServer::class)
    ->middleware(['auth:sanctum']);

require __DIR__.'/auth.php';
