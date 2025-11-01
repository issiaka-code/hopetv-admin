<?php

namespace App\Jobs;

use App\Models\Media;
use App\Models\Video;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tempPath;
    protected $uniqueName;
    protected $mediaData;
    protected $thumbnailFile;

    // Timeout et nombre de tentatives
    public $tries = 3;
    public $timeout = 1800; // 30 minutes pour les vidéos lourdes

    public function __construct($tempPath, $uniqueName, $mediaData, $thumbnailFile = null)
    {
        $this->tempPath = $tempPath;
        $this->uniqueName = $uniqueName;
        $this->mediaData = $mediaData;
        $this->thumbnailFile = $thumbnailFile;
    }

    public function handle()
    {
        try {
            Log::info("ProcessVideoJob démarré pour media_id: {$this->mediaData['media_id']}");

            $filePath = 'videos/' . $this->uniqueName;

            // Conversion vidéo
            FFMpeg::fromDisk('local')
                ->open($this->tempPath)
                ->export()
                ->toDisk('public')
                ->inFormat(new \FFMpeg\Format\Video\X264('aac', 'libx264'))
                ->resize(1280, 720)
                ->save($filePath);

            // Suppression du fichier temporaire
            Storage::disk('local')->delete($this->tempPath);

          
            // Mise à jour du média
            $media = Media::find($this->mediaData['media_id']);
            if ($media) {
                $media->update([
                    'url_fichier' => $filePath,
                    'thumbnail' => $this->thumbnailFile ?? null,
                    'status' => 'ready'
                ]);
            }

            // Mise à jour du statut de la vidéo
            // $video = Video::find($this->mediaData['video_id']);
            // if ($video) {
            //     $video->update(['status' => 'ready']);
            // }

            // Log::info("ProcessVideoJob terminé pour la vidéo: { $video->nom}");

        } catch (\Throwable $e) {
            Log::error("Erreur ProcessVideoJob media_id {$this->mediaData['media_id']}: " . $e->getMessage());

            // Marquer la vidéo comme échouée
            $video = Video::find($this->mediaData['video_id']);
            if ($video) {
                $video->update(['status' => 'failed']);
            }

            // Relancer l'exception pour que Laravel gère les retries
            throw $e;
        }
    }
}
