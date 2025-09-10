<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\PodcastController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\InfoBulleController;
use App\Http\Controllers\LienUtileController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\TemoignageController;
use App\Http\Controllers\PlaylistItemController;
use App\Http\Controllers\Auth\AuthViewController;
use App\Http\Controllers\InfoImportanteController;
use App\Http\Controllers\EmissionController;

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

Route::get('/dashboard', function () {
    return view('admin.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// Routes des ressources (CRUD)
Route::middleware('auth')->group(function () {

    Route::resource('videos', VideoController::class);
    Route::resource('podcasts', PodcastController::class);
    Route::resource('temoignages', TemoignageController::class);
    Route::resource('emissions', EmissionController::class);
    Route::post('emissions/{id}/toggle_status', [EmissionController::class, 'toggleStatus'])
        ->name('emissions.toggle_status');
    Route::resource('playlists', PlaylistController::class)->except(['show']);
    Route::get('playlists/{id}/show', [PlaylistController::class, 'show'])->name('playlists.show');
    Route::resource('info-bulles', InfoBulleController::class);
    Route::resource('parametres', ParametreController::class);
    Route::resource('liens-utiles', LienUtileController::class);
    Route::patch('/{id}/toggle-status', [InfoBulleController::class, 'toggleStatus'])->name('info-bulles.toggle-status');
    Route::resource('info_importantes', InfoImportanteController::class);
    Route::post('info_importantes/{id}/toggle_status', [InfoImportanteController::class, 'toggleStatus'])
        ->name('info_importantes.toggle_status');
});

require __DIR__ . '/auth.php';
