<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\PodcastController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\InfoBulleController;
use App\Http\Controllers\LienUtileController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\TemoignageController;
use App\Http\Controllers\Auth\AuthViewController;
use App\Http\Controllers\InfoImportanteController;
use App\Http\Controllers\EmissionController;
use App\Http\Controllers\EtablissementController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('view.login');
});

Route::get('login', [AuthViewController::class, 'createLogin'])
    ->name('view.login');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Routes des ressources (CRUD)
Route::middleware('auth')->group(function () {

    Route::resource('videos', VideoController::class);
    Route::post('videos/{id}/publish', [VideoController::class, 'publish'])->name('videos.publish');
    Route::post('videos/{id}/unpublish', [VideoController::class, 'unpublish'])->name('videos.unpublish');
    Route::resource('podcasts', PodcastController::class);
    Route::post('podcasts/{id}/publish', [PodcastController::class, 'publish'])->name('podcasts.publish');
    Route::post('podcasts/{id}/unpublish', [PodcastController::class, 'unpublish'])->name('podcasts.unpublish');
    Route::resource('temoignages', TemoignageController::class);
    Route::post('temoignages/{id}/publish', [TemoignageController::class, 'publish'])->name('temoignages.publish');
    Route::post('temoignages/{id}/unpublish', [TemoignageController::class, 'unpublish'])->name('temoignages.unpublish');
    Route::resource('emissions', EmissionController::class);
    Route::post('emissions/{id}/publish', [EmissionController::class, 'publish'])->name('emissions.publish');
    Route::post('emissions/{id}/unpublish', [EmissionController::class, 'unpublish'])->name('emissions.unpublish');
    Route::post('emissions/{id}/toggle_status', [EmissionController::class, 'toggleStatus'])->name('emissions.toggle_status');
    Route::resource('playlists', PlaylistController::class)->except(['show']);
    Route::get('playlists/{id}/show', [PlaylistController::class, 'show'])->name('playlists.show');
    Route::resource('info-bulles', InfoBulleController::class);
    Route::resource('parametres', ParametreController::class);
    Route::resource('liens-utiles', LienUtileController::class);
    Route::patch('/{id}/toggle-status', [InfoBulleController::class, 'toggleStatus'])->name('info-bulles.toggle-status');
    Route::resource('info_importantes', InfoImportanteController::class);
    Route::post('info_importantes/{id}/toggle_status', [InfoImportanteController::class, 'toggleStatus'])->name('info_importantes.toggle_status');

    // Etablissements (Siège / Annexe)
    Route::resource('etablissements', EtablissementController::class);
    Route::post('etablissements/{id}/toggle_status', [EtablissementController::class, 'toggleStatus'])->name('etablissements.toggle_status');
});

require __DIR__ . '/auth.php';
