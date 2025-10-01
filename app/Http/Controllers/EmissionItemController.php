<?php

namespace App\Http\Controllers;


use App\Models\Emission;
use App\Models\EmissionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class EmissionItemController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Emission $emission)
    {
        $request->validate([
            'titre_video' => 'required|string|max:255',
            'description_video' => 'nullable|string',
            'type_video' => 'required|in:upload,link',
            'video_url' => 'nullable|required_if:type_video,link|url',
            'video_file' => 'nullable|required_if:type_video,upload|file|mimes:mp4,mov,ogg,qt|max:102400', // Max 100MB
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            DB::beginTransaction();

            Log::info('EmissionItemController@store: début ajout vidéo', [
                'emission_id' => $emission->id,
                'user_id' => auth()->id(),
                'payload' => [
                    'titre_video' => $request->input('titre_video'),
                    'type_video_input' => $request->input('type_video'),
                    'has_video_file' => $request->hasFile('video_file'),
                    'has_video_url' => !empty($request->input('video_url')),
                ],
            ]);

            $data = $request->only(['titre_video', 'description_video', 'type_video', 'video_url']);
            $data['id_Emission'] = $emission->id;
            $data['insert_by'] = auth()->id();
            $data['update_by'] = auth()->id();
            $data['is_active'] = true; // Active by default
            // Mapper le type 'upload' venant du formulaire vers la valeur DB 'video'
            $data['type_video'] = $request->type_video === 'upload' ? 'video' : 'link';

            Log::info('EmissionItemController@store: type mappé', [
                'type_video_mapped' => $data['type_video'],
            ]);

            if ($request->hasFile('video_file') && $request->type_video === 'upload') {
                $file = $request->file('video_file');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp4';
                // Stockage temporaire
                $tempPath = $file->storeAs('temp/emissions', "tmp_{$uniqueName}");
                // Compression avec FFmpeg
                FFMpeg::fromDisk('local')
                    ->open($tempPath)
                    ->export()
                    ->toDisk('public')
                    ->inFormat(new \FFMpeg\Format\Video\X264('aac', 'libx264'))
                    ->resize(1280, 720)
                    ->save('emissions/videos/' . $uniqueName);
                // Nettoyage du fichier temporaire
                Storage::disk('local')->delete($tempPath);
                // Stocker le nom du fichier dans video_url (pas de colonne video_file)
                $data['video_url'] = $uniqueName;

                Log::info('EmissionItemController@store: fichier vidéo traité', [
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => $uniqueName,
                    'public_path' => 'emissions/videos/' . $uniqueName,
                ]);
            }

            // Générer automatiquement une miniature pour certains liens connus (YouTube, Vimeo)
            if ($data['type_video'] === 'link' && empty($data['thumbnail']) && !empty($data['video_url'])) {
                $link = $data['video_url'];
                try {
                    // YouTube
                    if (strpos($link, 'youtube.com') !== false || strpos($link, 'youtu.be') !== false) {
                        $pattern = '/^.*((youtu.be\\/)|(v\\/)|(\\/u\\/\\w\\/)|(embed\\/)|(watch\\?))\\??v?=?([^#&?]*).*/';
                        if (preg_match($pattern, $link, $matches) && strlen($matches[7]) === 11) {
                            $ytId = $matches[7];
                            $data['thumbnail'] = 'https://img.youtube.com/vi/' . $ytId . '/hqdefault.jpg';
                        }
                    }
                    // Vimeo (via oEmbed)
                    elseif (strpos($link, 'vimeo.com') !== false) {
                        $oembedUrl = 'https://vimeo.com/api/oembed.json?url=' . urlencode($link);
                        $response = @file_get_contents($oembedUrl);
                        if ($response) {
                            $json = json_decode($response, true);
                            if (!empty($json['thumbnail_url'])) {
                                $data['thumbnail'] = $json['thumbnail_url'];
                            }
                        }
                    }
                } catch (\Throwable $t) {
                    Log::warning('EmissionItemController@store: génération miniature lien échouée', [
                        'error' => $t->getMessage(),
                    ]);
                }
            }

            // Miniature (optionnelle)
            if ($request->hasFile('thumbnail')) {
                $thumb = $request->file('thumbnail');
                $thumbName = pathinfo($thumb->getClientOriginalName(), PATHINFO_FILENAME) . '_' . now()->format('Ymd_His') . '.' . $thumb->getClientOriginalExtension();
                $thumb->storeAs('emissions/thumbnails', $thumbName, 'public');
                $data['thumbnail'] = $thumbName;
                Log::info('EmissionItemController@store: miniature stockée', [
                    'thumbnail' => $thumbName,
                ]);
            }

            $created = EmissionItem::create($data);

            Log::info('EmissionItemController@store: vidéo créée', [
                'item_id' => $created->id,
                'type_video' => $created->type_video,
                'video_url' => $created->video_url,
            ]);

            DB::commit();
            notify()->success('Succès', 'Vidéo ajoutée avec succès à l\'émission "' . $emission->nom . '".');
            return redirect()->route('emissions.show', $emission->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'ajout de la vidéo: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible d\'ajouter la vidéo: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Emission $emission, EmissionItem $item)
    {
        return response()->json($item);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Emission $emission, EmissionItem $item)
    {
        $request->validate([
            'titre_video' => 'required|string|max:255',
            'description_video' => 'nullable|string',
            'type_video' => 'required|in:upload,link',
            'video_url' => 'nullable|required_if:type_video,link|url',
            'video_file' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:102400',
        ]);

        try {
            DB::beginTransaction();

            $data = $request->only(['titre_video', 'description_video', 'type_video', 'video_url']);
            $data['update_by'] = auth()->id();
            // Mapper le type 'upload' vers 'video' pour la DB
            $data['type_video'] = $request->type_video === 'upload' ? 'video' : 'link';

            if ($request->hasFile('video_file') && $request->type_video === 'upload') {
                // Delete old file
                if ($item->video_url) {
                    Storage::disk('public')->delete('emissions/videos/' . $item->video_url);
                }
                $file = $request->file('video_file');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp4';
                // Stockage temporaire
                $tempPath = $file->storeAs('temp/emissions', "tmp_{$uniqueName}");
                // Compression avec FFmpeg
                FFMpeg::fromDisk('local')
                    ->open($tempPath)
                    ->export()
                    ->toDisk('public')
                    ->inFormat(new \FFMpeg\Format\Video\X264('aac', 'libx264'))
                    ->resize(1280, 720)
                    ->save('emissions/videos/' . $uniqueName);
                // Nettoyage du fichier temporaire
                Storage::disk('local')->delete($tempPath);
                // Stocker le nom du fichier dans video_url (pas de colonne video_file)
                $data['video_url'] = $uniqueName;
            } elseif ($request->type_video === 'link') {
                 // Delete old file if switching from upload to link
                if ($item->video_url) {
                    Storage::disk('public')->delete('emissions/videos/' . $item->video_url);
                }
                // Conserver/mettre à jour le lien dans video_url
                $data['video_url'] = $request->video_url;
            }

            // Ignorer la miniature: la colonne n'existe pas dans le schéma actuel

            $item->update($data);

            DB::commit();
            notify()->success('Succès', 'Vidéo mise à jour avec succès.');
            return redirect()->route('emissions.show', $emission->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de la vidéo: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de mettre à jour la vidéo: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Emission $emission, EmissionItem $item)
    {
        try {
            DB::beginTransaction();

            $item->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Vidéo supprimée avec succès.');
            return redirect()->route('emissions.show', $emission->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression de la vidéo: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de supprimer la vidéo.');
            return back();
        }
    }

    /**
     * Toggle the status of an emission item.
     */
    public function toggleStatus(Request $request, Emission $emission, EmissionItem $item)
    {
        try {
            $item->update([
                'is_active' => !$item->is_active,
                'update_by' => auth()->id(),
            ]);

            $status = $item->is_active ? 'activée' : 'désactivée';
            notify()->success('Succès', "Vidéo {$status} avec succès.");

            if ($request->ajax()) {
                return response()->json(['success' => true, 'new_status' => $item->is_active]);
            }
            return redirect()->route('emissions.show', $emission->id);
        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut de la vidéo: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de changer le statut.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return back();
        }
    }
}
