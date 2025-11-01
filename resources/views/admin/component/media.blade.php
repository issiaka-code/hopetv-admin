 <div class="temoignage-grid-item">
                        <div class="card temoignage-card">
                            <div class="temoignage-thumbnail-container">
                                <div class="temoignage-thumbnail position-relative" data-temoignage-id="{{ $id }}" >
                                    <!-- Afficher l'image de couverture ou icône par défaut -->
                                    @if ($temoignageData->has_thumbnail)

                                    <img src="{{ $thumbnail_url }}" alt="{{ $nom }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                    <div class="default-thumbnail d-flex align-items-center justify-content-center" style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                        @if ($media_type === 'audio')
                                        <i class="fas fa-music text-white" style="font-size: 3rem;"></i>
                                        @elseif($media_type === 'video_link')
                                        <iframe src="{{ $thumbnail_url }}" width="100%" height="100%" frameborder="0"></iframe>
                                        @elseif($media_type === 'video_file')
                                        <i class="fas fa-video text-white" style="font-size: 3rem;"></i>
                                        @elseif($media_type === 'pdf')
                                        <i class="fas fa-file-pdf text-white" style="font-size: 3rem;"></i>
                                        @elseif($media_type === 'images')
                                        <i class="fas fa-images text-white" style="font-size: 3rem;"></i>
                                        @endif
                                    </div>
                                    @endif

                                    <div class="thumbnail-overlay">
                                        <i class="fas fa-play-circle"></i>
                                    </div>

                                    <span class="badge badge-primary media-type-badge">
                                        {{ ucfirst(
                                                    $media_type === 'audio'
                                                        ? 'Audio'
                                                        : ($media_type === 'pdf'
                                                            ? 'PDF'
                                                            : ($media_type === 'video_link'
                                                                ? 'Lien vidéo'
                                                                : ($media_type === 'video_file'
                                                                    ? 'Fichier vidéo'
                                                                    : ($media_type === 'images' ? 'Images' : $media_type)))),
                                                )}}
                                    </span>

                                    <!-- Badge statut publication (uniquement pour les vidéos) -->
                                    @if (in_array($media_type, ['video_link', 'video_file']))
                                    <span class="badge {{ $is_published ? 'badge-success' : 'badge-secondary' }}" style="position: absolute; top: 10px; left: 10px; z-index: 10;">
                                        {{ $is_published ? 'Publié' : 'Non publié' }}
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <div class="card-body">
                                <h5 class="card-title" title="{{ $nom }}">{{ Str::limit($nom, 25) }}</h5>
                                <p class="card-text text-muted small" title="{{ $description }}">
                                    {{ Str::limit($description, 30) }}</p>

                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <small class="text-muted mb-1">{{ $created_at->format('d/m/Y') }}</small>

                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-info view-temoignage-btn rounded" data-temoignage-id="{{ $id }}" title="Voir le témoignage" data-temoignage-url="{{ $thumbnail_url }}" data-media-url="{{ $media_url }}" data-temoignage-name="{{ $nom }}" data-title="{{ $nom }}" data-description="{{ $description }}" data-media-type="{{ $media_type }}" data-images='@json($temoignageData->images ?? [])'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary edit-temoignage-btn mx-1 rounded" title="Modifier le témoignage" data-temoignage-id="{{ $id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form action="{{ route('temoignages.destroy', $id) }}" method="POST" class="d-inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded" title="Supprimer le témoignage" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce témoignage ?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>

                                        <!-- Switch Publication (uniquement pour les vidéos) -->
                                        @if (in_array($media_type, ['video_link', 'video_file']))
                                        <button class="btn btn-sm btn-outline-{{ $is_published ? 'success' : 'secondary' }} toggle-publish-btn mx-1 rounded" title="{{ $is_published ? 'Dépublier' : 'Publier' }} la vidéo" data-temoignage-id="{{ $id }}" data-status="{{ $is_published ? 1 : 0 }}">
                                            <i class="fas fa-{{ $is_published ? 'toggle-on' : 'toggle-off' }}"></i>
                                            <span class="p-1">{{ $is_published ? 'Publié' : 'Non publié' }}</span>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>