<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Enseignement;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class EnseignementController extends Controller
{
    public function index(Request $request)
    {
        $query = Enseignement::with('media')->where('is_deleted', false)->latest();

        if ($request->filled('search')) {
            $query->where('nom', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%");
        }

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

        $enseignements = $query->paginate(12);

        $enseignementsData = collect($enseignements->items())->map(function ($enseignement) {
            $isAudio = $enseignement->media && $enseignement->media->type === 'audio';
            $isVideoLink = $enseignement->media && $enseignement->media->type === 'link';
            $isVideoFile = $enseignement->media && $enseignement->media->type === 'video';
            $isPdf = $enseignement->media && $enseignement->media->type === 'pdf';
            $isImages = $enseignement->media && $enseignement->media->type === 'images';

            $thumbnailUrl = null;

            if ($isVideoLink) {
                $rawUrl = $enseignement->media->url_fichier;

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
                if ($enseignement->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $enseignement->media->thumbnail);
                } else {
                    $thumbnailUrl = asset('storage/' . $enseignement->media->url_fichier);
                }
            } elseif ($isAudio || $isPdf) {
                if ($enseignement->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $enseignement->media->thumbnail);
                } else {
                    $thumbnailUrl = null;
                }
            } elseif ($isImages) {
                if ($enseignement->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $enseignement->media->thumbnail);
                } else {
                    $imagesArr = [];
                    if (!empty($enseignement->media->url_fichier)) {
                        $decoded = json_decode($enseignement->media->url_fichier, true);
                        $imagesArr = is_array($decoded) ? $decoded : [];
                    }
                    $first = count($imagesArr) > 0 ? $imagesArr[0] : null;
                    $thumbnailUrl = $first ? asset('storage/' . $first) : null;
                }
            }
            return (object) [
                'id' => $enseignement->id,
                'nom' => $enseignement->nom,
                'description' => $enseignement->description,
                'created_at' => $enseignement->created_at,
                'media_type' => $isAudio ? 'audio' : ($isVideoLink ? 'video_link' : ($isVideoFile ? 'video_file' : ($isPdf ? 'pdf' : ($isImages ? 'images' : null)))),
                'thumbnail_url' => $thumbnailUrl,
                'video_url' => $isVideoFile ? asset('storage/' . $enseignement->media->url_fichier) : $thumbnailUrl,
                'media_url' => $enseignement->media && !$isImages ? asset('storage/' . $enseignement->media->url_fichier) : null,
                'has_thumbnail' => $enseignement->media && $enseignement->media->thumbnail ? true : ($isImages && !empty(json_decode($enseignement->media->url_fichier ?? '[]', true))),
                'is_published' => $enseignement->media->is_published ?? true,
                'images' => $isImages ? array_map(function ($p) { return asset('storage/' . $p); }, (array) (json_decode($enseignement->media->url_fichier ?? '[]', true) ?: [])) : [],
            ];
        });

        return view('admin.medias.enseignements.index', [
            'enseignements' => $enseignements,
            'enseignementsData' => $enseignementsData,
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
                $filePath = $file->storeAs('audios', $uniqueName, 'public');
            } elseif ($request->media_type === 'video_file') {
                $request->validate([
                    'fichier_video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
                    'image_couverture_video' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                $file = $request->file('fichier_video');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp4';
                $tempPath = $file->storeAs('temp/videos', "tmp_{$uniqueName}");
                FFMpeg::fromDisk('local')
                    ->open($tempPath)
                    ->export()
                    ->toDisk('public')
                    ->inFormat(new \FFMpeg\Format\Video\X264('aac', 'libx264'))
                    ->resize(1280, 720)
                    ->save('videos/' . $uniqueName);
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
                $filePath = $file->storeAs('pdfs', $uniqueName, 'public');
            } elseif ($request->media_type === 'images') {
                $request->validate([
                    'images' => 'required|array|min:1',
                    'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                    'image_couverture_images' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                ]);

                $storedImages = [];
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $imgFile) {
                        if ($imgFile && $imgFile->isValid()) {
                            $base = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $ext = $imgFile->getClientOriginalExtension();
                            $unique = Str::slug($base, '_') . '_' . now()->format('Ymd_Hisv') . '.' . $ext;
                            $path = $imgFile->storeAs('images/enseignements', $unique, 'public');
                            $storedImages[] = $path;
                        }
                    }
                }
                if (empty($storedImages)) {
                    throw new \Exception('Aucune image valide n\'a été fournie');
                }
                $filePath = json_encode($storedImages);
                $thumbnailPath = null;
                if ($request->hasFile('image_couverture_images')) {
                    $thumbnailFile = $request->file('image_couverture_images');
                    if ($thumbnailFile->isValid()) {
                        $thumbName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $thumbUniqueName = 'thumb_' . Str::slug($thumbName, '_') . '_' . now()->format('Ymd_Hisv') . '.' . $thumbnailFile->getClientOriginalExtension();
                        $thumbnailPath = $thumbnailFile->storeAs('thumbnails/images', $thumbUniqueName, 'public');
                    }
                }
            }

            $type = $request->media_type === 'audio' ? 'audio' : ($request->media_type === 'video_file' ? 'video' : ($request->media_type === 'video_link' ? 'link' : ($request->media_type === 'pdf' ? 'pdf' : 'images')));

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

            $mediaData = [
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $type,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ];

            $media = Media::create($mediaData);

            $enseignement = Enseignement::create([
                'id_media' => $media->id,
                'nom' => $request->nom,
                'description' => $request->description,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Enseignement ajouté avec succès.');
            return redirect()->route('enseignements.index');
        } catch (\Exception $e) {
            Log::error('EnseignementController@store erreur', [
                'message' => $e->getMessage(),
            ]);
            Alert::error('Erreur', 'Impossible de créer l\'enseignement: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function edit(Enseignement $enseignement)
    {
        $enseignement->load('media');
        return response()->json([
            'nom' => $enseignement->nom,
            'description' => $enseignement->description,
            'media' => $enseignement->media,
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

            $enseignement = Enseignement::findOrFail($id);
            $media = $enseignement->media;
            if (!$media) {
                throw new \Exception('Média introuvable pour cet enseignement');
            }

            $filePath = $media->url_fichier;
            $thumbnailPath = $media->thumbnail;
            $type = $media->type;

            if ($request->media_type === 'audio') {
                $request->validate([
                    'fichier_audio' => 'nullable|file|mimes:mp3,wav,aac,ogg,flac|max:512000',
                ]);
                if ($request->hasFile('fichier_audio')) {
                    $file = $request->file('fichier_audio');
                    $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp3';
                    if ($media->type === 'audio' && Storage::disk('public')->exists($media->url_fichier)) {
                        Storage::disk('public')->delete($media->url_fichier);
                    }
                    $filePath = $file->storeAs('audios', $uniqueName, 'public');
                    $type = 'audio';
                }
                if ($request->hasFile('image_couverture_audio')) {
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
                    if ($media->type === 'video' && Storage::disk('public')->exists($media->url_fichier)) {
                        Storage::disk('public')->delete($media->url_fichier);
                    }
                    $tempPath = $file->storeAs('temp/videos', "tmp_{$uniqueName}");
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
                if ($request->hasFile('image_couverture_video')) {
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
                    if ($media->type === 'pdf' && Storage::disk('public')->exists($media->url_fichier)) {
                        Storage::disk('public')->delete($media->url_fichier);
                    }
                    $filePath = $file->storeAs('pdfs', $uniqueName, 'public');
                    $type = 'pdf';
                }
                if ($request->hasFile('image_couverture_pdf')) {
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
                    'images' => 'nullable',
                    'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                    'image_couverture_images' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                    'existing_images_delete' => 'nullable|array',
                    'existing_images_delete.*' => 'string',
                ]);
                $type = 'images';
                $existingImages = [];
                if (!empty($media->url_fichier)) {
                    $decoded = json_decode($media->url_fichier, true);
                    $existingImages = is_array($decoded) ? $decoded : [];
                }
                $newImages = [];
                // Remove checked ones
                $toDelete = (array) $request->input('existing_images_delete', []);
                if (!empty($toDelete)) {
                    $existingImages = array_values(array_filter($existingImages, function ($path) use ($toDelete) {
                        return !in_array($path, $toDelete, true);
                    }));
                    foreach ($toDelete as $delPath) {
                        if ($delPath && Storage::disk('public')->exists($delPath)) {
                            Storage::disk('public')->delete($delPath);
                        }
                    }
                }
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $imgFile) {
                        if ($imgFile && $imgFile->isValid()) {
                            $base = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $ext = $imgFile->getClientOriginalExtension();
                            $unique = Str::slug($base, '_') . '_' . now()->format('Ymd_Hisv') . '.' . $ext;
                            $path = $imgFile->storeAs('images/enseignements', $unique, 'public');
                            $newImages[] = $path;
                        }
                    }
                }
                if ($request->hasFile('image_couverture_images')) {
                    if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                        Storage::disk('public')->delete($media->thumbnail);
                    }
                    $thumbnailFile = $request->file('image_couverture_images');
                    if ($thumbnailFile->isValid()) {
                        $thumbName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $thumbUniqueName = 'thumb_' . Str::slug($thumbName, '_') . '_' . now()->format('Ymd_Hisv') . '.' . $thumbnailFile->getClientOriginalExtension();
                        $thumbnailPath = $thumbnailFile->storeAs('thumbnails/images', $thumbUniqueName, 'public');
                    }
                }
                $filePath = json_encode(array_values(array_merge($existingImages, $newImages)));
            }

            $updateData = [
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $type,
                'update_by' => auth()->id(),
            ];

            $media->update($updateData);

            $enseignement->update([
                'nom' => $request->nom,
                'description' => $request->description,
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Enseignement mis à jour avec succès.');
            return redirect()->route('enseignements.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EnseignementController@update erreur', [
                'message' => $e->getMessage(),
            ]);
            Alert::error('Erreur', 'Impossible de mettre à jour l\'enseignement: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $enseignement = Enseignement::findOrFail($id);
        try {
            DB::beginTransaction();
            $enseignement->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);
            if ($enseignement->media) {
                $enseignement->media->update([
                    'is_deleted' => true,
                    'update_by' => auth()->id(),
                ]);
            }
            DB::commit();
            notify()->success('Succès', 'Enseignement supprimé avec succès.');
            return redirect()->route('enseignements.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function publish(Request $request, $id)
    {
        $enseignement = Enseignement::findOrFail($id);
        if (!$enseignement->media || !in_array($enseignement->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }
        try {
            $enseignement->media->update([
                'is_published' => true,
                'update_by' => auth()->id(),
            ]);
            notify()->success('Succès', 'Vidéo d\'enseignement publiée avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur publication enseignement: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de publier.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }

    public function unpublish(Request $request, $id)
    {
        $enseignement = Enseignement::findOrFail($id);
        if (!$enseignement->media || !in_array($enseignement->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }
        try {
            $enseignement->media->update([
                'is_published' => false,
                'update_by' => auth()->id(),
            ]);
            notify()->success('Succès', 'Vidéo d\'enseignement dépubliée avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur dépublication enseignement: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de dépublier.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }
}


