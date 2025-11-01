<?php

namespace App\Services;

use App\Models\Media;
use App\Jobs\ProcessVideoJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Gère l’insertion d’un média selon le type.
     * Retourne l’instance Media créée ou null en cas d’erreur.
     */
    public function createMedia(Request $request)
    {
        $type = $request->media_type ?? null;
        if (!$type) {
            return [
                'success' => false,
                'errors' => 'Le type de média est obligatoire.',
            ];
        }

        $validator = Validator::make(
            $request->all(),
            [
                'nom' => 'required|string|max:255',
                'description' => 'required|string',
            ],
            [
                'nom.required' => 'Le nom est obligatoire.',
                'nom.string' => 'Le nom doit être une chaîne de caractères.',
                'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
                'description.required' => 'La description est obligatoire.',
                'description.string' => 'La description doit être une chaîne de caractères.',
            ]
        );

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors(),
            ];
        }


        Log::info("MediaService@createMedia: début", ['media_type' => $type]);

        $filePath = null;
        $thumbnailPath = null;
        $status = 'ready';
        $uniqueName = null;
        $tempPath = null;

        try {
            switch ($type) {

                case 'audio':
                    $validator = Validator::make(
                        $request->all(),
                        [
                            'fichier_audio' => 'required|file|mimes:mp3,wav,aac,ogg,flac|max:512000',
                            'image_couverture_audio' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                        ],
                        [
                            'fichier_audio.required' => 'Le fichier audio est obligatoire.',
                            'fichier_audio.file' => 'Le fichier audio doit être un fichier valide.',
                            'fichier_audio.mimes' => 'Le fichier audio doit être au format : mp3, wav, aac, ogg ou flac.',
                            'fichier_audio.max' => 'Le fichier audio ne doit pas dépasser 500 Mo.',
                            'image_couverture_audio.required' => "L'image de couverture est obligatoire.",
                            'image_couverture_audio.image' => "L'image de couverture doit être une image.",
                            'image_couverture_audio.mimes' => "L'image de couverture doit être au format : jpeg, png, jpg ou gif.",
                            'image_couverture_audio.max' => "L'image de couverture ne doit pas dépasser 2 Mo.",
                        ]
                    );

                    if ($validator->fails()) {
                        return [
                            'success' => false,
                            'errors' => $validator->errors(),
                        ];
                    }

                    $file = $request->file('fichier_audio');
                    $uniqueName = $this->uniqueName($file, 'mp3');
                    $filePath = $file->storeAs('audios', $uniqueName, 'public');
                    $thumbnailPath = $this->storeThumbnail($request->file('image_couverture_audio'), 'audios', false);
                    $mediaType = 'audio';
                    break;

                case 'video_file':
                    $validator = Validator::make(
                        $request->all(),
                        [
                            'fichier_video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
                            'image_couverture_video' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                        ],
                        [
                            'fichier_video.required' => 'Le fichier vidéo est obligatoire.',
                            'fichier_video.file' => 'Le fichier vidéo doit être valide.',
                            'fichier_video.mimes' => 'Le fichier vidéo doit être au format : mp4, avi, mov, wmv, flv, mkv ou webm.',
                            'fichier_video.max' => 'La taille maximale de la vidéo est de 1 Go.',
                            'image_couverture_video.required' => "L'image de couverture est obligatoire.",
                            'image_couverture_video.image' => "L'image de couverture doit être une image.",
                            'image_couverture_video.mimes' => "L'image de couverture doit être au format : jpeg, png, jpg ou gif.",
                            'image_couverture_video.max' => "L'image de couverture ne doit pas dépasser 2 Mo.",
                        ]
                    );

                    if ($validator->fails()) {
                        return [
                            'success' => false,
                            'errors' => $validator->errors(),
                        ];
                    }

                    $file = $request->file('fichier_video');
                    $uniqueName = $this->uniqueName($file, 'mp4');
                    $tempPath = $file->storeAs('temp/videos', "tmp_{$uniqueName}");
                    $status = 'processing';

                    $thumbnailPath = $this->storeThumbnail($request->file('image_couverture_video'), 'videos', false);
                    $mediaType = 'video';
                    break;

                case 'video_link':
                    $validator = Validator::make(
                        $request->all(),
                        [
                            'lien_video' => 'required|url',
                            'image_couverture_link' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                        ],
                        [
                            'lien_video.required' => 'Le lien de la vidéo est obligatoire.',
                            'lien_video.url' => 'Le lien de la vidéo doit être une URL valide.',
                            'image_couverture_link.required' => "L'image de couverture est obligatoire.",
                            'image_couverture_link.image' => "L'image de couverture doit être une image.",
                            'image_couverture_link.mimes' => "L'image de couverture doit être au format : jpeg, png, jpg ou gif.",
                            'image_couverture_link.max' => "L'image de couverture ne doit pas dépasser 2 Mo.",
                        ]
                    );

                    if ($validator->fails()) {
                        return [
                            'success' => false,
                            'errors' => $validator->errors(),
                        ];
                    }

                    $filePath = $request->lien_video;
                    $thumbnailPath = $this->storeThumbnail($request->file('image_couverture_link'), 'link', false);
                    $mediaType = 'link';
                    break;

                case 'pdf':
                    $validator = Validator::make(
                        $request->all(),
                        [
                            'fichier_pdf' => 'required|file|mimes:pdf|max:20480',
                            'image_couverture_pdf' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                        ],
                        [
                            'fichier_pdf.required' => 'Le fichier PDF est obligatoire.',
                            'fichier_pdf.file' => 'Le fichier PDF doit être valide.',
                            'fichier_pdf.mimes' => 'Le fichier doit être au format PDF.',
                            'fichier_pdf.max' => 'La taille du fichier PDF ne doit pas dépasser 20 Mo.',
                            'image_couverture_pdf.required' => "L'image de couverture est obligatoire.",
                            'image_couverture_pdf.image' => "L'image de couverture doit être une image.",
                            'image_couverture_pdf.mimes' => "L'image de couverture doit être au format : jpeg, png, jpg, gif ou webp.",
                            'image_couverture_pdf.max' => "L'image de couverture ne doit pas dépasser 2 Mo.",
                        ]
                    );

                    if ($validator->fails()) {
                        return [
                            'success' => false,
                            'errors' => $validator->errors(),
                        ];
                    }

                    $file = $request->file('fichier_pdf');
                    $uniqueName = $this->uniqueName($file, 'pdf');
                    $filePath = $file->storeAs('pdfs', $uniqueName, 'public');
                    $thumbnailPath = $this->storeThumbnail($request->file('image_couverture_pdf'), 'pdfs', false);
                    $mediaType = 'pdf';
                    break;

                case 'images':
                    $validator = Validator::make(
                        $request->all(),
                        [
                            'images' => 'required|array|min:1',
                            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                            'image_couverture_images' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                        ],
                        [
                            'images.required' => 'Veuillez sélectionner au moins une image.',
                            'images.array' => 'Les images doivent être envoyées sous forme de tableau.',
                            'images.min' => 'Vous devez sélectionner au moins une image.',
                            'images.*.image' => 'Chaque fichier doit être une image.',
                            'images.*.mimes' => 'Les images doivent être au format : jpeg, png, jpg, gif ou webp.',
                            'images.*.max' => 'Chaque image ne doit pas dépasser 4 Mo.',
                            'image_couverture_images.image' => "L'image de couverture doit être une image.",
                            'image_couverture_images.mimes' => "L'image de couverture doit être au format : jpeg, png, jpg, gif ou webp.",
                            'image_couverture_images.max' => "L'image de couverture ne doit pas dépasser 4 Mo.",
                        ]
                    );

                    if ($validator->fails()) {
                        return [
                            'success' => false,
                            'errors' => $validator->errors(),
                        ];
                    }

                    $storedImages = [];
                    foreach ($request->file('images') as $imgFile) {
                        $storedImages[] = $imgFile->storeAs(
                            'images/medias',
                            $this->uniqueName($imgFile),
                            'public'
                        );
                    }

                    $filePath = json_encode($storedImages);
                    $thumbnailPath = $this->storeThumbnail($request->file('image_couverture_images'), 'images', false);
                    $mediaType = 'images';
                    break;

                default:
                    notify()->error('Des erreurs sont survenues.', 'Type de média non pris en charge.');
                    return null;
            }

            // Création du média
            $media = Media::create([
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $mediaType,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
                'status' => $status,
            ]);

            // Si c’est une vidéo locale, on lance le job d’encodage
            if ($type === 'video_file') {
                ProcessVideoJob::dispatch($tempPath, $uniqueName, [
                    'media_id' => $media->id,
                    'insert_by' => auth()->id(),
                    'update_by' => auth()->id(),
                ], $thumbnailPath);
            }

            Log::info("MediaService@createMedia: média créé", ['media_id' => $media->id]);
            notify()->success('Succès', 'Le média a été enregistré avec succès.');
            return $media;
        } catch (\Exception $e) {
            Log::error("MediaService@createMedia: erreur", ['message' => $e->getMessage()]);
            notify()->error('Des erreurs sont survenues.', 'Une erreur est survenue : ' . $e->getMessage());
            return [
                'success' => false,
                'errors' => 'Une erreur est survenue : ' . $e->getMessage(),
            ];
        }
    }

    private function uniqueName($file, $extension = null)
    {
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext = $extension ?? $file->getClientOriginalExtension();
        return Str::slug($filename, '_') . '_' . now()->format('Ymd_Hisv') . '.' . $ext;
    }

    private function storeThumbnail($file, $folder, $required = false)
    {
        if (!$file || !$file->isValid()) {
            if ($required) {
                throw new \Exception("Image de couverture manquante pour $folder");
            }
            return [
                'success' => false,
                'errors' => "l'image de couverture est obligatoire.",
            ];
        }

        $uniqueName = 'thumb_' . $this->uniqueName($file);
        return $file->storeAs("thumbnails/$folder", $uniqueName, 'public');
    }

    public function updateMedia(Request $request, $media)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'nom' => 'required|string|max:255',
                'description' => 'required|string',
            ],
            [
                'nom.required' => 'Le nom est obligatoire.',
                'nom.string' => 'Le nom doit être une chaîne de caractères.',
                'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
                'description.required' => 'La description est obligatoire.',
                'description.string' => 'La description doit être une chaîne de caractères.',
            ]
        );

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors(),
            ];
        }

        $filePath = $media->url_fichier;
        $thumbnailPath = $media->thumbnail;
        $type = $media->type;
        $status = $media->status ?? 'ready';
        $uniqueName = null;
        $tempPath = null;

        try {
            switch ($request->media_type) {

                case 'audio':
                    $validator = Validator::make($request->all(), [
                        'fichier_audio' => 'nullable|file|mimes:mp3,wav,aac,ogg,flac|max:512000',
                        'image_couverture_audio' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                    ], [
                        'fichier_audio.mimes' => 'Le fichier audio doit être mp3, wav, aac, ogg ou flac.',
                        'fichier_audio.max' => 'Le fichier audio ne doit pas dépasser 500 Mo.',
                        'image_couverture_audio.image' => 'L’image de couverture doit être une image valide.',
                        'image_couverture_audio.max' => 'L’image de couverture ne doit pas dépasser 2 Mo.',
                    ]);

                    if ($validator->fails()) {
                        return ['success' => false, 'errors' => $validator->errors()];
                    }

                    if ($request->hasFile('fichier_audio')) {
                        if ($media->type === 'audio' && Storage::disk('public')->exists($media->url_fichier)) {
                            Storage::disk('public')->delete($media->url_fichier);
                        }
                        $file = $request->file('fichier_audio');
                        $uniqueName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . now()->format('Ymd_His') . '.mp3';
                        $filePath = $file->storeAs('audios', $uniqueName, 'public');
                    }

                    if ($request->hasFile('image_couverture_audio')) {
                        if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                            Storage::disk('public')->delete($media->thumbnail);
                        }
                        $file = $request->file('image_couverture_audio');
                        $thumbnailPath = $file->storeAs('thumbnails/audios', 'thumb_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension(), 'public');
                    }

                    $type = 'audio';
                    break;

                // -------------------- Vidéo locale --------------------
                case 'video_file':
                    $validator = Validator::make($request->all(), [
                        'fichier_video' => 'nullable|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
                        'image_couverture_video' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                    ], [
                        'fichier_video.mimes' => 'Le fichier vidéo doit être mp4, avi, mov, wmv, flv, mkv ou webm.',
                        'fichier_video.max' => 'La vidéo ne doit pas dépasser 1 Go.',
                        'image_couverture_video.image' => 'L’image de couverture doit être une image valide.',
                        'image_couverture_video.max' => 'L’image de couverture ne doit pas dépasser 2 Mo.',
                    ]);
                    if ($validator->fails()) {
                        return ['success' => false, 'errors' => $validator->errors()];
                    }

                    if ($request->hasFile('fichier_video')) {
                        if ($media->type === 'video' && Storage::disk('public')->exists($media->url_fichier)) {
                            Storage::disk('public')->delete($media->url_fichier);
                        }
                        $file = $request->file('fichier_video');
                        $uniqueName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . now()->format('Ymd_His') . '.mp4';
                        $tempPath = $file->storeAs('temp/videos', "tmp_{$uniqueName}");
                        $filePath = $media->url_fichier; // pas encore stocké final
                        $status = 'processing';
                    }

                    if ($request->hasFile('image_couverture_video')) {
                        if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                            Storage::disk('public')->delete($media->thumbnail);
                        }
                        $file = $request->file('image_couverture_video');
                        $thumbnailPath = $file->storeAs('thumbnails/videos', 'thumb_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension(), 'public');
                    }

                    $type = 'video';
                    break;

                // -------------------- Vidéo lien --------------------
                case 'video_link':
                    $validator = Validator::make($request->all(), [
                        'lien_video' => 'required|url',
                        'image_couverture_link' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                    ], [
                        'lien_video.required' => 'Le lien de la vidéo est obligatoire.',
                        'lien_video.url' => 'Le lien de la vidéo doit être une URL valide.',
                        'image_couverture_link.image' => 'L’image de couverture doit être une image valide.',
                        'image_couverture_link.max' => 'L’image de couverture ne doit pas dépasser 2 Mo.',
                    ]);
                    if ($validator->fails()) {
                        return ['success' => false, 'errors' => $validator->errors()];
                    }

                    $filePath = $request->lien_video;
                    $type = 'link';
                    if ($request->hasFile('image_couverture_link')) {
                        if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                            Storage::disk('public')->delete($media->thumbnail);
                        }
                        $file = $request->file('image_couverture_link');
                        $thumbnailPath = $file->storeAs('thumbnails/link', 'thumb_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension(), 'public');
                    }
                    break;

                // -------------------- PDF --------------------
                case 'pdf':
                    $validator = Validator::make($request->all(), [
                        'fichier_pdf' => 'nullable|file|mimes:pdf|max:20480',
                        'image_couverture_pdf' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                    ], [
                        'fichier_pdf.mimes' => 'Le fichier doit être au format PDF.',
                        'fichier_pdf.max' => 'La taille du fichier PDF ne doit pas dépasser 20 Mo.',
                        'image_couverture_pdf.image' => 'L’image de couverture doit être une image valide.',
                        'image_couverture_pdf.max' => 'L’image de couverture ne doit pas dépasser 2 Mo.',
                    ]);
                    if ($validator->fails()) {
                        return ['success' => false, 'errors' => $validator->errors()];
                    }

                    if ($request->hasFile('fichier_pdf')) {
                        if ($media->type === 'pdf' && Storage::disk('public')->exists($media->url_fichier)) {
                            Storage::disk('public')->delete($media->url_fichier);
                        }
                        $file = $request->file('fichier_pdf');
                        $uniqueName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . now()->format('Ymd_His') . '.pdf';
                        $filePath = $file->storeAs('pdfs', $uniqueName, 'public');
                    }

                    if ($request->hasFile('image_couverture_pdf')) {
                        if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                            Storage::disk('public')->delete($media->thumbnail);
                        }
                        $file = $request->file('image_couverture_pdf');
                        $thumbnailPath = $file->storeAs('thumbnails/pdfs', 'thumb_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension(), 'public');
                    }

                    $type = 'pdf';
                    break;

                // -------------------- Images --------------------
                case 'images':
                    $validator = Validator::make($request->all(), [
                        'images' => 'nullable|array|min:0',
                        'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                        'image_couverture_images' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
                        'existing_images_delete' => 'nullable|array',
                    ], [
                        'images.*.image' => 'Chaque fichier doit être une image valide.',
                        'images.*.mimes' => 'Les images doivent être jpeg, png, jpg, gif ou webp.',
                        'images.*.max' => 'Chaque image ne doit pas dépasser 4 Mo.',
                        'image_couverture_images.image' => 'L’image de couverture doit être une image valide.',
                        'image_couverture_images.max' => 'L’image de couverture ne doit pas dépasser 4 Mo.',
                    ]);
                    if ($validator->fails()) {
                        return ['success' => false, 'errors' => $validator->errors()];
                    }

                    $existingImages = json_decode($media->url_fichier ?? '[]', true) ?: [];
                    $deleteList = $request->input('existing_images_delete', []);
                    foreach ($deleteList as $img) {
                        if (Storage::disk('public')->exists($img)) {
                            Storage::disk('public')->delete($img);
                        }
                    }
                    $existingImages = array_diff($existingImages, $deleteList);

                    $newImages = [];
                    if ($request->hasFile('images')) {
                        foreach ($request->file('images') as $imgFile) {
                            if ($imgFile->isValid()) {
                                $base = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
                                $ext = $imgFile->getClientOriginalExtension();
                                $unique = Str::slug($base, '_') . '_' . now()->format('Ymd_Hisv') . '.' . $ext;
                                $path = $imgFile->storeAs('images/temoignages', $unique, 'public');
                                $newImages[] = $path;
                            }
                        }
                    }

                    if ($request->hasFile('image_couverture_images')) {
                        if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                            Storage::disk('public')->delete($media->thumbnail);
                        }
                        $file = $request->file('image_couverture_images');
                        $thumbnailPath = $file->storeAs('thumbnails/images', 'thumb_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_') . '_' . now()->format('Ymd_Hisv') . '.' . $file->getClientOriginalExtension(), 'public');
                    }

                    $filePath = json_encode(array_merge($existingImages, $newImages));
                    $type = 'images';
                    break;

                default:
                    return ['success' => false, 'errors' => collect(['Type de média non pris en charge'])];
            }

            // Update du média
            $media->update([
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $type,
                'status' => $status,
                'update_by' => auth()->id(),
            ]);



            // Job vidéo
            if ($type === 'video' && $tempPath) {
                ProcessVideoJob::dispatch(
                    $tempPath,
                    $uniqueName,
                    ['media_id' => $media->id, 'update_by' => auth()->id()],
                    $thumbnailPath
                );
            }

            return ['success' => true, 'media' => $media];
        } catch (\Exception $e) {
            Log::error('MediaService@updateMedia', ['message' => $e->getMessage()]);
            return ['success' => false, 'errors' => collect([$e->getMessage()])];
        }
    }
}
