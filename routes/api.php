<?php

use App\Http\Controllers\Api\Apicontroller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/info-bulles', [Apicontroller::class, 'getInfoBulles']);
Route::get('/playlist-du-jour', [ApiController::class, 'getPlaylistDuJour']);
Route::get('/videos', [ApiController::class, 'getVideos']);
Route::get('/search/videos', [ApiController::class, 'getVideossearch']);

Route::get('/videos/{id}', [ApiController::class, 'getVideo']);
Route::get('temoignages', [ApiController::class, 'getTemoignages']);
Route::get('temoignages/{id}', [ApiController::class, 'getTemoignageDetail']);
// routes/api.php
Route::get('temoignages/{id}/similaires', [ApiController::class, 'similairesParNom']);
Route::get('/public/videos', [ApiController::class, 'video']);
Route::get('/public/videos/{id}', [ApiController::class, 'show']);
Route::get('/public/videos/{id}/details', [ApiController::class, 'showAvecSimilaires']);
Route::get('public/podcasts', [ApiController::class, 'podcast']); // liste avec pagination
Route::get('public/podcasts/{id}/details', [ApiController::class, 'showWithSimilairesPodcast']);

Route::get('public/emissions', [ApiController::class, 'emissions']); // liste avec pagination
Route::get('public/emissions/{id}/details', [ApiController::class, 'showWithSimilairesEmission']);
Route::get('public/emissions/{id}/items', [ApiController::class, 'getEmissionItems']);

Route::get('public/pdf/{fichier}', [ApiController::class, 'afficherPdf']);
Route::get('/public/etablissements', [ApiController::class, 'etablisement']);
Route::get('/public/prieres', [Apicontroller::class, 'priere']);
Route::get('/public/prieres/{id}/similaires', [Apicontroller::class, 'showWithSimilaires']);

Route::get('/public/home-charities', [Apicontroller::class, 'getHomeCharities']);
Route::get('/public/home-charities/similaires/{id}', [Apicontroller::class, 'showHomeCharities']);

Route::get('/public/enseignements', [Apicontroller::class, 'getEnseignements']);
Route::get('/public/enseignements/{id}/similaires', [Apicontroller::class, 'showEnseignement']);

Route::get('/public/programmes', [Apicontroller::class, 'getProgrammes']);
Route::get('/public/programmes/{id}', [Apicontroller::class, 'showProgramme']);

Route::get('/search', [Apicontroller::class, 'search']);
Route::get('/global-search', [ApiController::class, 'globalSearch']);

Route::get('public/propheties', [ApiController::class, 'getPropheties']);
Route::get('public/propheties/{id}', [ApiController::class, 'showProphetie']);
