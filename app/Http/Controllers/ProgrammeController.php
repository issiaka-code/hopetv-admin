<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Programme;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class ProgrammeController extends Controller
{
    public function index(Request $request)
    {
        $query = Programme::with('media')->where('is_deleted', false)->latest();

        // Recherche
        if ($request->filled('search')) {
            $query->where('nom', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%");
        }

        // Filtrage par type
        if ($request->filled('type')) {
            $query->whereHas('media', function ($q) use ($request) {
                if ($request->type === 'audio') {
                    $q->where('type', 'audio');
                } elseif ($request->type === 'video_file') {
                    $q->where('type', 'video');
                } elseif ($request->type === 'video_link') {
                    $q->where('type', 'link');
                } elseif ($request->type === 'pdf') {
                    $q->where('type', 'pdf');
                } elseif ($request->type === 'images') {
                    $q->where('type', 'images');
                }
            });
        }

        $programmes = $query->paginate(12);

        // Préparer chaque variable pour la vue et JS
        $programmesData = collect($programmes->items())->map(function ($programme) {
            $isAudio = $programme->media && $programme->media->type === 'audio';
            $isVideoLink = $programme->media && $programme->media->type === 'link';
            $isVideoFile = $programme->media && $programme->media->type === 'video';
            $isPdf = $programme->media && $programme->media->type === 'pdf';
            $isImages = $programme->media && $programme->media->type === 'images';
            

            $thumbnailUrl = null;

            if ($isVideoLink) {
                $rawUrl = $programme->media->url_fichier;

                if (Str::contains($rawUrl, 'youtube.com/watch?v=')) {
                    $videoId = explode('v=', parse_url($rawUrl, PHP_URL_QUERY))[1] ?? null;
                    $videoId = explode('&', $videoId)[0];
                    $thumbnailUrl = $videoId ? "https://www.youtube.com/embed/$videoId" : $rawUrl;
                } elseif (Str::contains($rawUrl, 'youtu.be/')) {
                    $videoId = basename(parse_url($rawUrl, PHP_URL_PATH));
                    $thumbnailUrl = "https://www.youtube.com/embed/$videoId";
                } else {
                    $thumbnailUrl = $rawUrl;
                }
            } elseif ($isVideoFile) {
                // Pour les vidéos fichiers, utiliser l'image de couverture si disponible
                if ($programme->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $programme->media->thumbnail);
                } else {
                    $thumbnailUrl = asset('storage/' . $programme->media->url_fichier);
                }
            } elseif ($isAudio || $isPdf) {
                // Pour les audios et PDFs, utiliser l'image de couverture si disponible
                if ($programme->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $programme->media->thumbnail);
                } else {
                    $thumbnailUrl = null; // Pas d'image, on utilisera l'icône par défaut
                }
            } elseif ($isImages) {
                // Pour les images, utiliser la couverture si dispo, sinon la première image de url_fichier (JSON)
                if ($programme->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $programme->media->thumbnail);
                } else {
                    $imagesArr = [];
                    if (!empty($programme->media->url_fichier)) {
                        $decoded = json_decode($programme->media->url_fichier, true);
                        $imagesArr = is_array($decoded) ? $decoded : [];
                    }
                    $first = count($imagesArr) > 0 ? $imagesArr[0] : null;
                    $thumbnailUrl = $first ? asset('storage/' . $first) : null;
                }
            }
            return (object)[
                'id' => $programme->id,
                'nom' => $programme->nom,
                'description' => $programme->description,
                'created_at' => $programme->created_at,
                'media_type' => $isAudio ? 'audio' : ($isVideoLink ? 'video_link' : ($isVideoFile ? 'video_file' : ($isPdf ? 'pdf' : ($isImages ? 'images' : null)))),
                'thumbnail_url' => $thumbnailUrl,
                'video_url' => $isVideoFile ? asset('storage/' . $programme->media->url_fichier) : $thumbnailUrl,
                'media_url' => $programme->media && !$isImages ? asset('storage/' . $programme->media->url_fichier) : null,
                'has_thumbnail' => $programme->media && $programme->media->thumbnail ? true : ($isImages && !empty(json_decode($programme->media->url_fichier ?? '[]', true))),
                'is_published' => $programme->media->is_published ?? true,
                'images' => $isImages ? array_map(function ($p) { return asset('storage/' . $p); }, (array)(json_decode($programme->media->url_fichier ?? '[]', true) ?: [])) : [],
            ];
        });
        // Envoyer chaque programme comme variable séparée
        return view('admin.medias.programmes.index', [
            'programmes' => $programmes,
            'programmesData' => $programmesData,
        ]);
    }
    public function store(Request $request)
    {
        Log::info('ProgrammeController@store: début', [
            'media_type' => $request->input('media_type'),
            'nom' => $request->input('nom'),
        ]);
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:audio,video_file,video_link,pdf,images',
        ]);
        try {
            if ($request->media_type === 'audio') {
                $request->validate([
                    'fichier_audio' => 'required|file|mimes:mp3,wav,aac,ogg,flac|max:512000',
                    'image_couverture_audio' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                $file = $request->file('fichier_audio');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp3';

                // Stockage direct sans optimisation
                $filePath = $file->storeAs('audios', $uniqueName, 'public');
            } elseif ($request->media_type === 'video_file') {
                $request->validate([
                    'fichier_video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
                    'image_couverture_video' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                $file = $request->file('fichier_video');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp4';

                // Stockage temporaire
                $tempPath = $file->storeAs('temp/videos', "tmp_{$uniqueName}");

                // Traitement avec FFmpeg
                FFMpeg::fromDisk('local')
                    ->open($tempPath)
                    ->export()
                    ->toDisk('public')
                    ->inFormat(new \FFMpeg\Format\Video\X264('aac', 'libx264'))
                    ->resize(1280, 720)
                    ->save('videos/' . $uniqueName);

                // Nettoyage du fichier temporaire
                Storage::disk('local')->delete($tempPath);

                $filePath = 'videos/' . $uniqueName;
            } elseif ($request->media_type === 'video_link') {
                $request->validate([
                    'lien_video' => 'required|url',
                ]);
                $filePath = $request->lien_video;
            } elseif ($request->media_type === 'pdf') {
                $request->validate([
                    'fichier_pdf' => 'required|file|mimes:pdf|max:20480',
                    'image_couverture_pdf' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                $file = $request->file('fichier_pdf');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.pdf';

                // Stockage direct du PDF
                $filePath = $file->storeAs('pdfs', $uniqueName, 'public');
            } elseif ($request->media_type === 'images') {
                Log::info('ProgrammeController@store: type images détecté');
                $request->validate([
                    'images' => 'required|array|min:1',
                    'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                    'image_couverture_images' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                ]);

                // Stocker images multiples
                $storedImages = [];
                if ($request->hasFile('images')) {
                    Log::info('ProgrammeController@store: nombre de fichiers images', [
                        'count' => is_countable($request->file('images')) ? count($request->file('images')) : null
                    ]);
                    foreach ($request->file('images') as $imgFile) {
                        if ($imgFile && $imgFile->isValid()) {
                            Log::info('ProgrammeController@store: fichier image valide', [
                                'original_name' => $imgFile->getClientOriginalName(),
                                'size' => $imgFile->getSize(),
                                'mime' => $imgFile->getMimeType(),
                            ]);
                            $base = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $ext = $imgFile->getClientOriginalExtension();
                            $unique = Str::slug($base, '_') . '_' . now()->format('Ymd_Hisv') . '.' . $ext;
                            $path = $imgFile->storeAs('images/programmes', $unique, 'public');
                            $storedImages[] = $path;
                            Log::info('ProgrammeController@store: image stockée', ['path' => $path]);
                        }
                    }
                }
                
                // Debug: vérifier si des images ont été stockées
                if (empty($storedImages)) {
                    throw new \Exception('Aucune image valide n\'a été trouvée dans la requête');
                }

                // Pour images, url_fichier contiendra le JSON des chemins
                $filePath = json_encode($storedImages);

                // Couverture
                $thumbnailPath = null;
                if ($request->hasFile('image_couverture_images')) {
                    $thumbnailFile = $request->file('image_couverture_images');
                    if ($thumbnailFile->isValid()) {
                        $thumbName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $thumbUniqueName = 'thumb_' . Str::slug($thumbName, '_') . '_' . now()->format('Ymd_Hisv') . '.' . $thumbnailFile->getClientOriginalExtension();
                        $thumbnailPath = $thumbnailFile->storeAs('thumbnails/images', $thumbUniqueName, 'public');
                        Log::info('ProgrammeController@store: image de couverture stockée', ['thumbnail' => $thumbnailPath]);
                    }
                }
            }

            // Déterminer le type pour la base de données
            $type = $request->media_type === 'audio' ? 'audio' : ($request->media_type === 'video_file' ? 'video' : ($request->media_type === 'video_link' ? 'link' : ($request->media_type === 'pdf' ? 'pdf' : 'images')));

            // Traitement de l'image de couverture (seulement si pas déjà traité pour images)
            if ($request->media_type !== 'images') {
                $thumbnailPath = null;
            }

            if ($request->media_type === 'audio' && $request->hasFile('image_couverture_audio')) {
                $thumbnailFile = $request->file('image_couverture_audio');
                if ($thumbnailFile->isValid()) {
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = 'thumb_' . $thumbnailName . '_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails/audios', $thumbnailUniqueName, 'public');
                }
            } elseif ($request->media_type === 'video_file' && $request->hasFile('image_couverture_video')) {
                $thumbnailFile = $request->file('image_couverture_video');
                if ($thumbnailFile->isValid()) {
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = 'thumb_' . $thumbnailName . '_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails/videos', $thumbnailUniqueName, 'public');
                }
            } elseif ($request->media_type === 'pdf' && $request->hasFile('image_couverture_pdf')) {
                $thumbnailFile = $request->file('image_couverture_pdf');
                if ($thumbnailFile->isValid()) {
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = 'thumb_' . $thumbnailName . '_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails/pdfs', $thumbnailUniqueName, 'public');
                }
            }

            // Créer l'enregistrement média
            $mediaData = [
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $type,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ];

            Log::info('ProgrammeController@store: création du média', [
                'type' => $type,
                'has_thumbnail' => (bool) $thumbnailPath,
                'images_count' => $type === 'images' ? (is_countable($storedImages ?? null) ? count($storedImages) : null) : null,
            ]);
            $media = Media::create($mediaData);
            Log::info('ProgrammeController@store: média créé', ['media_id' => $media->id]);

            // Créer le programme
            $programme = Programme::create([
                'id_media' => $media->id,
                'nom' => $request->nom,
                'description' => $request->description,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);
            Log::info('ProgrammeController@store: programme créé', ['programme_id' => $programme->id]);

            notify()->success('Succès', 'Programme ajouté avec succès.');
            return redirect()->route('programmes.index');
        } catch (\Exception $e) {
            Log::error('ProgrammeController@store: erreur', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Alert::error('Erreur', 'Impossible de créer le programme: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function edit(Programme $programme)
    {
        $programme->load('media');
        return response()->json([
            'nom' => $programme->nom,
            'description' => $programme->description,
            'media' => $programme->media
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info('ProgrammeController@update: début', [
            'id' => $id,
            'media_type' => $request->input('media_type'),
        ]);
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:audio,video_file,video_link,pdf,images',
            'image_couverture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Récupérer le programme existant
            $programme = Programme::findOrFail($id);
            $media = $programme->media;

            if (!$media) {
                throw new \Exception('Média introuvable pour ce programme');
            }

            $filePath = $media->url_fichier; // par défaut, garder l'ancien fichier
            $thumbnailPath = $media->thumbnail; // par défaut, garder l'ancienne image
            $type = $media->type;

            if ($request->media_type === 'audio') {
                $request->validate([
                    'fichier_audio' => 'nullable|file|mimes:mp3,wav,aac,ogg,flac|max:512000',
                ]);

                if ($request->hasFile('fichier_audio')) {
                    $file = $request->file('fichier_audio');
                    $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp3';

                    // Supprimer ancien fichier audio
                    if ($media->type === 'audio' && Storage::disk('public')->exists($media->url_fichier)) {
                        Storage::disk('public')->delete($media->url_fichier);
                    }

                    // Stockage
                    $filePath = $file->storeAs('audios', $uniqueName, 'public');
                    $type = 'audio';
                }

                // Traitement de l'image de couverture pour les audios
                if ($request->hasFile('image_couverture_audio')) {
                    // Supprimer l'ancienne image de couverture
                    if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                        Storage::disk('public')->delete($media->thumbnail);
                    }

                    $thumbnailFile = $request->file('image_couverture_audio');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }
            } elseif ($request->media_type === 'video_file') {
                $request->validate([
                    'fichier_video' => 'nullable|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
                ]);

                if ($request->hasFile('fichier_video')) {
                    $file = $request->file('fichier_video');
                    $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp4';

                    // Supprimer ancien fichier vidéo
                    if ($media->type === 'video' && Storage::disk('public')->exists($media->url_fichier)) {
                        Storage::disk('public')->delete($media->url_fichier);
                    }

                    // Stockage temporaire
                    $tempPath = $file->storeAs('temp/videos', "tmp_{$uniqueName}");

                    // Compression et export avec FFmpeg
                    FFMpeg::fromDisk('local')
                        ->open($tempPath)
                        ->export()
                        ->toDisk('public')
                        ->inFormat(new \FFMpeg\Format\Video\X264('aac', 'libx264'))
                        ->resize(1280, 720)
                        ->save('videos/' . $uniqueName);

                    Storage::disk('local')->delete($tempPath);

                    $filePath = 'videos/' . $uniqueName;
                    $type = 'video';
                }

                // Traitement de l'image de couverture pour les vidéos fichiers
                if ($request->hasFile('image_couverture_video')) {
                    // Supprimer l'ancienne image de couverture
                    if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                        Storage::disk('public')->delete($media->thumbnail);
                    }

                    $thumbnailFile = $request->file('image_couverture_video');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }
            } elseif ($request->media_type === 'video_link') {
                $request->validate([
                    'lien_video' => 'required|url',
                ]);

                $filePath = $request->lien_video;
                $type = 'link';
            } elseif ($request->media_type === 'pdf') {
                $request->validate([
                    'fichier_pdf' => 'nullable|file|mimes:pdf|max:20480',
                ]);

                if ($request->hasFile('fichier_pdf')) {
                    $file = $request->file('fichier_pdf');
                    $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.pdf';

                    // Supprimer ancien PDF
                    if ($media->type === 'pdf' && Storage::disk('public')->exists($media->url_fichier)) {
                        Storage::disk('public')->delete($media->url_fichier);
                    }

                    // Stockage
                    $filePath = $file->storeAs('pdfs', $uniqueName, 'public');
                    $type = 'pdf';
                }

                // Traitement de l'image de couverture pour les PDFs
                if ($request->hasFile('image_couverture_pdf')) {
                    // Supprimer l'ancienne image de couverture
                    if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                        Storage::disk('public')->delete($media->thumbnail);
                    }

                    $thumbnailFile = $request->file('image_couverture_pdf');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }
            } elseif ($request->media_type === 'images') {
                Log::info('ProgrammeController@update: type images détecté');
                $request->validate([
                    'images' => 'nullable',
                    'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                    'image_couverture_images' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                    'existing_images_delete' => 'nullable|array',
                    'existing_images_delete.*' => 'string',
                ]);

                $type = 'images';

                // Conserver les images existantes
                $existingImages = [];
                if (!empty($media->url_fichier)) {
                    $decoded = json_decode($media->url_fichier, true);
                    $existingImages = is_array($decoded) ? $decoded : [];
                }
                // Supprimer celles cochées
                $toDelete = (array) $request->input('existing_images_delete', []);
                if (!empty($toDelete)) {
                    $existingImages = array_values(array_filter($existingImages, function ($path) use ($toDelete) {
                        return !in_array($path, $toDelete, true);
                    }));
                    // Supprimer physiquement
                    foreach ($toDelete as $delPath) {
                        if ($delPath && Storage::disk('public')->exists($delPath)) {
                            Storage::disk('public')->delete($delPath);
                        }
                    }
                }
                $newImages = [];

                if ($request->hasFile('images')) {
                    Log::info('ProgrammeController@update: nombre de nouveaux fichiers images', [
                        'count' => is_countable($request->file('images')) ? count($request->file('images')) : null
                    ]);
                    foreach ($request->file('images') as $imgFile) {
                        if ($imgFile && $imgFile->isValid()) {
                            Log::info('ProgrammeController@update: nouveau fichier image valide', [
                                'original_name' => $imgFile->getClientOriginalName(),
                                'size' => $imgFile->getSize(),
                                'mime' => $imgFile->getMimeType(),
                            ]);
                            $base = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $ext = $imgFile->getClientOriginalExtension();
                            $unique = Str::slug($base, '_') . '_' . now()->format('Ymd_Hisv') . '.' . $ext;
                            $path = $imgFile->storeAs('images/programmes', $unique, 'public');
                            $newImages[] = $path;
                            Log::info('ProgrammeController@update: image stockée', ['path' => $path]);
                        }
                    }
                }

                // Gestion de la couverture
                if ($request->hasFile('image_couverture_images')) {
                    Log::info('ProgrammeController@update: mise à jour image de couverture');
                    if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                        Storage::disk('public')->delete($media->thumbnail);
                        Log::info('ProgrammeController@update: ancienne couverture supprimée', ['thumbnail' => $media->thumbnail]);
                    }
                    $thumbnailFile = $request->file('image_couverture_images');
                    if ($thumbnailFile->isValid()) {
                        $thumbName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $thumbUniqueName = 'thumb_' . Str::slug($thumbName, '_') . '_' . now()->format('Ymd_Hisv') . '.' . $thumbnailFile->getClientOriginalExtension();
                        $thumbnailPath = $thumbnailFile->storeAs('thumbnails/images', $thumbUniqueName, 'public');
                        Log::info('ProgrammeController@update: nouvelle couverture stockée', ['thumbnail' => $thumbnailPath]);
                    }
                }

                // url_fichier stocke le JSON des chemins
                $filePath = json_encode(array_values(array_merge($existingImages, $newImages)));
            }

            // Mise à jour du média
            $updateData = [
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $type,
                'update_by' => auth()->id(),
            ];

            $media->update($updateData);
            Log::info('ProgrammeController@update: média mis à jour', [
                'media_id' => $media->id,
                'type' => $media->type,
                'has_thumbnail' => (bool) $media->thumbnail,
            ]);

            // Mise à jour du programme
            $programme->update([
                'nom' => $request->nom,
                'description' => $request->description,
                'update_by' => auth()->id(),
            ]);
            Log::info('ProgrammeController@update: programme mis à jour', ['programme_id' => $programme->id]);

            DB::commit();
            notify()->success('Succès', 'Programme mis à jour avec succès.');
            return redirect()->route('programmes.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProgrammeController@update: erreur', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Alert::error('Erreur', 'Impossible de mettre à jour le programme: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $programme = Programme::findOrFail($id);
        try {
            DB::beginTransaction();

            $programme->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Programme supprimé avec succès.');
            return redirect()->route('programmes.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProgrammeController@destroy: erreur', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Alert::error('Erreur', 'Impossible de supprimer le programme: ' . $e->getMessage());
            return back();
        }
    }

    public function publish($id)
    {
        try {
            $programme = Programme::findOrFail($id);
            $programme->media->update(['is_published' => true]);
            
            notify()->success('Succès', 'Programme publié avec succès.');
            return redirect()->route('programmes.index');
        } catch (\Exception $e) {
            Alert::error('Erreur', 'Impossible de publier le programme.');
            return back();
        }
    }

    public function unpublish($id)
    {
        try {
            $programme = Programme::findOrFail($id);
            $programme->media->update(['is_published' => false]);
            
            notify()->success('Succès', 'Programme dépublié avec succès.');
            return redirect()->route('programmes.index');
        } catch (\Exception $e) {
            Alert::error('Erreur', 'Impossible de dépublier le programme.');
            return back();
        }
    }
}