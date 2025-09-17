<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Temoignage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class TemoignageController extends Controller
{
    public function index(Request $request)
    {
        $query = Temoignage::with('media')->where('is_deleted', false)->latest();

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

        $temoignages = $query->paginate(12);

        // Préparer chaque variable pour la vue et JS
        $temoignagesData = collect($temoignages->items())->map(function ($temoignage) {
            $isAudio = $temoignage->media && $temoignage->media->type === 'audio';
            $isVideoLink = $temoignage->media && $temoignage->media->type === 'link';
            $isVideoFile = $temoignage->media && $temoignage->media->type === 'video';
            $isPdf = $temoignage->media && $temoignage->media->type === 'pdf';
            $isImage = $temoignage->media && $temoignage->media->type === 'images';

            $thumbnailUrl = null;

            if ($isVideoLink) {
                $rawUrl = $temoignage->media->url_fichier;

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
                if ($temoignage->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $temoignage->media->thumbnail);
                } else {
                    $thumbnailUrl = asset('storage/' . $temoignage->media->url_fichier);
                }
            } elseif ($isAudio || $isPdf) {
                // Pour les audios et PDFs, utiliser l'image de couverture si disponible
                if ($temoignage->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $temoignage->media->thumbnail);
                } else {
                    $thumbnailUrl = null; // Pas d'image, on utilisera l'icône par défaut
                }
            } elseif ($isImage) {
                // Pour les images, utiliser la première image ou l'image de couverture
                if ($temoignage->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $temoignage->media->thumbnail);
                } elseif ($temoignage->media->images) {
                    $images = json_decode($temoignage->media->images, true);
                    if (is_array($images) && count($images) > 0) {
                        $thumbnailUrl = asset('storage/' . $images[0]);
                    }
                } else {
                    $thumbnailUrl = null;
                }
            }
            return (object)[
                'id' => $temoignage->id,
                'nom' => $temoignage->nom,
                'description' => $temoignage->description,
                'created_at' => $temoignage->created_at,
                'media_type' => $isAudio ? 'audio' : ($isVideoLink ? 'video_link' : ($isVideoFile ? 'video_file' : ($isPdf ? 'pdf' : 'images'))),
                'thumbnail_url' => $thumbnailUrl,
                'video_url' => $isVideoFile ? asset('storage/' . $temoignage->media->url_fichier) : $thumbnailUrl,
                'media_url' => $temoignage->media ? asset('storage/' . $temoignage->media->url_fichier) : null,
                'images' => $isImage && $temoignage->media->images ? json_decode($temoignage->media->images, true) : null,
                'has_thumbnail' => $temoignage->media && $temoignage->media->thumbnail ? true : false,
                'is_published' => $temoignage->media->is_published ?? true,
            ];
        });
        // Envoyer chaque témoignage comme variable séparée
        return view('admin.medias.temoignages.index', [
            'temoignages' => $temoignages,
            'temoignagesData' => $temoignagesData,
        ]);
    }

    public function store(Request $request)
    {
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
                Log::info('Début du traitement des images', ['files' => $request->hasFile('images') ? 'Fichiers présents' : 'Aucun fichier', 'count' => $request->hasFile('images') ? count($request->file('images')) : 0]);
                
                $request->validate([
                    'images' => 'required|array|min:1',
                    'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                    'image_couverture_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                ]);

                $imagesPaths = [];
                $coverImagePath = null;

                // Upload des images multiples
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $index => $imageFile) {
                        if ($imageFile->isValid()) {
                            $filename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $uniqueName = $filename . '_' . now()->format('Ymd_His') . '_' . $index . '.' . $imageFile->getClientOriginalExtension();
                            $imagePath = $imageFile->storeAs('images/temoignages', $uniqueName, 'public');
                            if ($imagePath) {
                                $imagesPaths[] = $imagePath;
                            } else {
                                Log::error("Échec du stockage de l'image: " . $imageFile->getClientOriginalName());
                            }
                        }
                    }
                }

                // Vérifier qu'au moins une image a été téléchargée
                Log::info('Résumé du traitement des images', [
                    'total_traites' => $request->hasFile('images') ? count($request->file('images')) : 0,
                    'reussis' => count($imagesPaths),
                    'chemins' => $imagesPaths
                ]);
                
                if (empty($imagesPaths)) {
                    $errorMsg = 'Aucune image valide n\'a pu être téléchargée. ';
                    $errorMsg .= $request->hasFile('images') ? count($request->file('images')) . ' fichiers reçus mais aucun valide.' : 'Aucun fichier reçu.';
                    Log::error($errorMsg);
                    throw new \Exception($errorMsg);
                }

                // Upload de l'image de couverture si fournie
                if ($request->hasFile('image_couverture_image')) {
                    $coverFile = $request->file('image_couverture_image');
                    Log::debug('Traitement de l\'image de couverture', [
                        'original_name' => $coverFile->getClientOriginalName(),
                        'size' => $coverFile->getSize(),
                        'is_valid' => $coverFile->isValid()
                    ]);
                    
                    if ($coverFile->isValid()) {
                        $coverFilename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $coverUniqueName = 'cover_' . $coverFilename . '_' . now()->format('Ymd_His') . '.' . $coverFile->getClientOriginalExtension();
                        
                        Log::debug('Tentative de stockage de l\'image de couverture', [
                            'filename' => $coverFilename,
                            'unique_name' => $coverUniqueName
                        ]);
                        
                        $coverImagePath = $coverFile->storeAs('images/temoignages/covers', $coverUniqueName, 'public');

                        if (!$coverImagePath) {
                            $errorMsg = "Échec du stockage de l'image de couverture";
                            Log::error($errorMsg);
                            // Utiliser la première image comme couverture en cas d'échec
                            $coverImagePath = $imagesPaths[0];
                            Log::info('Utilisation de la première image comme couverture', ['path' => $coverImagePath]);
                        } else {
                            Log::info('Image de couverture enregistrée avec succès', ['path' => $coverImagePath]);
                        }
                    } else {
                        $errorMsg = 'Fichier d\'image de couverture invalide';
                        Log::error($errorMsg, ['error' => $coverFile->getError()]);
                        $coverImagePath = $imagesPaths[0];
                        Log::info('Utilisation de la première image comme couverture (fichier invalide)', ['path' => $coverImagePath]);
                    }
                } else {
                    // Utiliser la première image comme couverture si aucune couverture n'est fournie
                    $coverImagePath = $imagesPaths[0];
                    Log::info('Aucune image de couverture fournie, utilisation de la première image', ['path' => $coverImagePath]);
                }

                // Pour les images multiples, on utilise la première image comme url_fichier
                $filePath = $imagesPaths[0];
            }

            // Déterminer le type pour la base de données
            $type = $request->media_type === 'audio' ? 'audio' : ($request->media_type === 'video_file' ? 'video' : ($request->media_type === 'video_link' ? 'link' : ($request->media_type === 'pdf' ? 'pdf' : 'images')));

            // Traitement de l'image de couverture
            $thumbnailPath = null;

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
            } elseif ($request->media_type === 'images') {
                // Pour les images, utiliser l'image de couverture comme thumbnail
                $thumbnailPath = $coverImagePath;
            }

            // Créer l'enregistrement média
            $mediaData = [
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $type,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ];

            // Ajouter les images pour le type image
            if ($request->media_type === 'images') {
                $mediaData['images'] = json_encode($imagesPaths);
                Log::debug('Images encodées en JSON', ['count' => count($imagesPaths)]);

                // S'assurer que le thumbnail et l'url_fichier sont définis
                if (empty($thumbnailPath)) {
                    $mediaData['thumbnail'] = $imagesPaths[0];
                    Log::debug('Définition de la miniature', ['thumbnail' => $imagesPaths[0]]);
                }
                
                // Utiliser la première image comme url_fichier
                $mediaData['url_fichier'] = $imagesPaths[0];
                Log::debug('Définition de l\'URL du fichier', ['url_fichier' => $imagesPaths[0]]);
            }

            $media = Media::create($mediaData);

            // Créer le témoignage
            Temoignage::create([
                'id_media' => $media->id,
                'nom' => $request->nom,
                'description' => $request->description,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Témoignage ajouté avec succès.');
            return redirect()->route('temoignages.index');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de créer le témoignage: ' . $e->getMessage());
            return back()->withInput();
        }
    }


    public function edit(Temoignage $temoignage)
    {
        $temoignage->load('media');
        return response()->json([
            'nom' => $temoignage->nom,
            'description' => $temoignage->description,
            'media' => $temoignage->media
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:audio,video_file,video_link,pdf,images',
            'image_couverture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Récupérer le témoignage existant
            $temoignage = Temoignage::findOrFail($id);
            $media = $temoignage->media;

            if (!$media) {
                throw new \Exception('Média introuvable pour ce témoignage');
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
                $request->validate([
                    'images' => 'nullable|array',
                    'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                    'image_couverture_image' => 'nullable|image|mimes:jpeg,png,jjpg,gif,webp|max:10240',
                ]);

                $imagesPaths = [];
                $coverImagePath = $thumbnailPath; // Garder l'ancienne par défaut

                // Si de nouvelles images sont uploadées
                if ($request->hasFile('images')) {
                    // Supprimer les anciennes images si elles existent
                    if ($media->type === 'images' && $media->images) {
                        $oldImages = json_decode($media->images, true);
                        if (is_array($oldImages)) {
                            foreach ($oldImages as $oldImage) {
                                if (Storage::disk('public')->exists($oldImage)) {
                                    Storage::disk('public')->delete($oldImage);
                                }
                            }
                        }
                    }

                    // Upload des nouvelles images
                    foreach ($request->file('images') as $index => $imageFile) {
                        if ($imageFile->isValid()) {
                            $filename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $uniqueName = $filename . '_' . now()->format('Ymd_His') . '_' . $index . '.' . $imageFile->getClientOriginalExtension();
                            $imagePath = $imageFile->storeAs('images/temoignages', $uniqueName, 'public');
                            if ($imagePath) {
                                $imagesPaths[] = $imagePath;
                            }
                        }
                    }
                } else {
                    // Garder les anciennes images si aucune nouvelle n'est uploadée
                    $imagesPaths = $media->images ? json_decode($media->images, true) : [];
                }

                // Upload de la nouvelle image de couverture si fournie
                if ($request->hasFile('image_couverture_image')) {
                    // Supprimer l'ancienne image de couverture
                    if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                        Storage::disk('public')->delete($media->thumbnail);
                    }

                    $coverFile = $request->file('image_couverture_image');
                    $coverFilename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $coverUniqueName = $coverFilename . '_cover_' . now()->format('Ymd_His') . '.' . $coverFile->getClientOriginalExtension();
                    $coverImagePath = $coverFile->storeAs('images/temoignages/covers', $coverUniqueName, 'public');

                    if (!$coverImagePath) {
                        // Utiliser la première image comme couverture en cas d'échec
                        $coverImagePath = !empty($imagesPaths) ? $imagesPaths[0] : $thumbnailPath;
                    }
                } elseif (empty($imagesPaths) === false && empty($thumbnailPath)) {
                    // Si pas de couverture spécifique et qu'il y a des images, utiliser la première
                    $coverImagePath = $imagesPaths[0];
                }

                $filePath = null; // Pas de filePath pour les images multiples
                $thumbnailPath = $coverImagePath;
                $type = 'images';
            }

            // Mise à jour du média
            $updateData = [
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $type,
                'update_by' => auth()->id(),
            ];

            // Ajouter les images pour le type image
            if ($request->media_type === 'images') {
                $updateData['images'] = json_encode($imagesPaths);

                // S'assurer que le thumbnail est défini
                if (empty($thumbnailPath) && !empty($imagesPaths)) {
                    $updateData['thumbnail'] = $imagesPaths[0];
                }
            }

            $media->update($updateData);

            // Mise à jour du témoignage
            $temoignage->update([
                'nom' => $request->nom,
                'description' => $request->description,
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Témoignage mis à jour avec succès.');
            return redirect()->route('temoignages.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de mettre à jour le témoignage: ' . $e->getMessage());
            return back()->withInput();
        }
    }



    public function destroy($id)
    {
        $temoignage = Temoignage::findOrFail($id);
        try {
            DB::beginTransaction();

            $temoignage->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            // Marquer également le média comme supprimé
            if ($temoignage->media) {
                $temoignage->media->update([
                    'is_deleted' => true,
                    'update_by' => auth()->id(),
                ]);
            }

            DB::commit();
            notify()->success('Succès', 'Témoignage supprimé avec succès.');
            return redirect()->route('temoignages.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function publish($id)
    {
        $temoignage = Temoignage::findOrFail($id);

        // Vérifier que c'est une vidéo
        if (!$temoignage->media || !in_array($temoignage->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }

        try {
            $temoignage->media->update([
                'is_published' => true,
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Témoignage vidéo publié avec succès.');
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la publication: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de publier le témoignage.');
            return redirect()->back();
        }
    }

    public function unpublish($id)
    {
        $temoignage = Temoignage::findOrFail($id);

        // Vérifier que c'est une vidéo
        if (!$temoignage->media || !in_array($temoignage->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }

        try {
            $temoignage->media->update([
                'is_published' => false,
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Témoignage vidéo dépublié avec succès.');
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la dépublication: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de dépublier le témoignage.');
            return redirect()->back();
        }
    }
}
