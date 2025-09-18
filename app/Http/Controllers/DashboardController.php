<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Temoignage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $ttlSeconds = 60; // cache léger

        $totalMedias = Cache::remember('dash.totalMedias', $ttlSeconds, function () {
            return Media::where('is_deleted', false)->count();
        });
        
        $totalTemoignages = Cache::remember('dash.totalTemoignages', $ttlSeconds, function () {
            return Temoignage::where('is_deleted', false)->count();
        });
        $totalVideos = Cache::remember('dash.totalVideos', $ttlSeconds, function () {
            return \App\Models\Video::where('is_deleted', false)->count();
        });
        $totalPodcasts = Cache::remember('dash.totalPodcasts', $ttlSeconds, function () {
            return \App\Models\Podcast::where('is_deleted', false)->count();
        });
        $totalEmissions = Cache::remember('dash.totalEmissions', $ttlSeconds, function () {
            return \App\Models\Emission::where('is_deleted', false)->count();
        });
        
        $totalVideosFiles = Cache::remember('dash.totalVideosFiles', $ttlSeconds, function () {
            return Media::where('is_deleted', false)->where('type', 'video')->count();
        });
        
        $totalVideoLinks = Cache::remember('dash.totalVideoLinks', $ttlSeconds, function () {
            return Media::where('is_deleted', false)->where('type', 'link')->count();
        });
        
        $totalAudios = Cache::remember('dash.totalAudios', $ttlSeconds, function () {
            return Media::where('is_deleted', false)->where('type', 'audio')->count();
        });
        
        $totalPdfs = Cache::remember('dash.totalPdfs', $ttlSeconds, function () {
            return Media::where('is_deleted', false)->where('type', 'pdf')->count();
        });
        
        $totalImages = Cache::remember('dash.totalImages', $ttlSeconds, function () {
            return Media::where('is_deleted', false)->where('type', 'images')->count();
        });
        
        $videosNonPubliees = Cache::remember('dash.videosNonPubliees', $ttlSeconds, function () {
            return Media::where('is_deleted', false)
                ->whereIn('type', ['video','link'])
                ->where('is_published', false)
                ->count();
        });

        $temoignagesRecents = Cache::remember('dash.temoignagesRecents', $ttlSeconds, function () {
            return Temoignage::with('media')
                ->where('is_deleted', false)
                ->latest()
                ->take(5)
                ->get();
        });
        
        $mediasRecents = Cache::remember('dash.mediasRecents', $ttlSeconds, function () {
            return Media::where('is_deleted', false)
                ->latest()
                ->take(5)
                ->get();
        });

        // Qualité de base - médias sans miniature
        $mediasSansThumbnail = Cache::remember('dash.mediasSansThumbnail', $ttlSeconds, function () {
            return Media::where('is_deleted', false)
                ->whereIn('type', ['audio','video','pdf'])
                ->whereNull('thumbnail')
                ->count();
        });

        // Statistiques du mois en cours
        $mediasCeMois = Cache::remember('dash.mediasCeMois', $ttlSeconds, function () {
            return Media::where('is_deleted', false)
                ->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->count();
        });
        
        $temoignagesCeMois = Cache::remember('dash.temoignagesCeMois', $ttlSeconds, function () {
            return Temoignage::where('is_deleted', false)
                ->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->count();
        });

        return view('admin.index', compact(
            'totalMedias',
            'totalTemoignages',
            'totalVideos',
            'totalPodcasts',
            'totalEmissions',
            'totalVideosFiles',
            'totalVideoLinks',
            'totalAudios',
            'totalPdfs',
            'totalImages',
            'videosNonPubliees',
            'temoignagesRecents',
            'mediasRecents',
            'mediasSansThumbnail',
            'mediasCeMois',
            'temoignagesCeMois'
        ));
    }
}