@extends('admin.master')

@section('title', 'Gestion des Émissions')

@push('styles')
    <style>
        .btn-group-toggle .btn {
            border-radius: 5px;
        }

        .btn-group-toggle .btn.active {
            background-color: #4e73df;
            color: white;
            border-color: #4e73df;
        }

        #audioFileSection,
        #videoFileSection,
        #videoLinkSection,
        #pdfFileSection {
            transition: opacity 0.3s ease;
        }

        .section-title {
            font-size: 1.5rem;
            color: #4e73df;
            margin-bottom: 0;
        }

        .emission-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            height: 100%;
        }

        .emission-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .emission-thumbnail-container {
            overflow: hidden;
            height: 180px;
            position: relative;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .emission-thumbnail {
            cursor: pointer;
            height: 100%;
            width: 100%;
        }

        .emission-thumbnail video,
        .emission-thumbnail img,
        .emission-thumbnail iframe {
            object-fit: cover;
            height: 100%;
            width: 100%;
            transition: transform 0.3s;
        }

        .audio-thumbnail,
        .pdf-thumbnail {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 4rem;
            color: #4e73df;
        }

        .pdf-thumbnail {
            color: #e74a3b;
        }

        .emission-card:hover .emission-thumbnail video,
        .emission-card:hover .emission-thumbnail img {
            transform: scale(1.05);
        }

        .thumbnail-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
        }

        .thumbnail-overlay i {
            font-size: 3rem;
            color: white;
        }

        .emission-thumbnail:hover .thumbnail-overlay {
            opacity: 1;
        }

        .emission-card .card-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .emission-card .card-text {
            height: 40px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .btn-group .btn {
            border-radius: 5px;
            margin-left: 2px;
            padding: 0.25rem 0.5rem;
        }

        .empty-state {
            padding: 3rem 1rem;
        }

        /* Styles pour la grille responsive */
        #emissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .emission-grid-item {
            width: 100%;
        }

        /* Personnalisation du file input */
        .custom-file-label::after {
            content: "Parcourir";
        }

        /* Media type badge */
        .media-type-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }

        /* Responsive improvements */
        @media (max-width: 1400px) {
            #emissions-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            #emissions-grid {
                grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
                gap: 1.25rem;
            }

            .emission-thumbnail-container {
                height: 160px;
            }
        }

        @media (max-width: 992px) {
            #emissions-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }

            .emission-thumbnail-container {
                height: 140px;
            }

            .section-title {
                font-size: 1.35rem;
            }

            .card-title {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            #emissions-grid {
                grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
                gap: 0.875rem;
            }

            .emission-thumbnail-container {
                height: 120px;
            }

            .section-title {
                font-size: 1.25rem;
            }

            .btn-group .btn {
                padding: 0.2rem 0.4rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            #emissions-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                max-width: 400px;
                margin: 0 auto;
            }

            .emission-thumbnail-container {
                height: 180px;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .section-title {
                font-size: 1.4rem;
            }

            .modal-dialog {
                margin: 0.5rem;
            }

            .btn-group {
                width: 100%;
                justify-content: center;
                margin-top: 0.5rem;
            }

            .btn-group .btn {
                margin: 0 2px;
                flex: 1;
                max-width: 45px;
            }
        }

        @media (max-width: 400px) {
            .emission-thumbnail-container {
                height: 150px;
            }

            .modal-dialog {
                margin: 0.25rem;
            }

            .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }
        }

        /* Mobile first improvements */
        .modal-header .close {
            padding: 0.5rem;
            margin: -0.5rem -0.5rem -0.5rem auto;
        }

        /* Touch device improvements */
        @media (hover: none) {
            .emission-card:hover {
                transform: none;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            }

            .thumbnail-overlay {
                opacity: 0.7;
            }

            .btn-group .btn {
                padding: 0.4rem 0.6rem;
            }
        }

        /* High density screens */
        @media (min-resolution: 2dppx) {
            .thumbnail-overlay i {
                font-size: 2.5rem;
            }
        }
    </style>
@endpush

@section('content')
    <section class="section" style="margin-top: -25px;">
        <div class="section-body">
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center section-header">
                        <h2 class="section-title">Émissions disponibles</h2>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addEmissionModal">
                            <i class="fas fa-plus"></i> Ajouter une émission
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <form method="GET" action="{{ route('emissions.index') }}" class="w-100">
                    <div class="row g-2 d-flex flex-row justify-content-end align-items-center">
                        <!-- Champ recherche -->
                        <div class="col-4">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Rechercher une émission...">
                        </div>

                        <!-- Filtre par statut -->
                        <div class="col-2">
                            <select name="status" class="form-control">
                                <option value="">Tous les statuts</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actives
                                </option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactives
                                </option>
                            </select>
                        </div>

                        <!-- Bouton recherche -->
                        <div class="col-2">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="fas fa-filter py-2"></i> Filtrer
                            </button>
                        </div>
                        <div class="col-md-2 my-1">
                            <a href="{{ route('emissions.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-sync py-2"></i> Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Grille d'émissions -->
            <div class="row">
                <div class="col-12">
                    <div id="emissions-grid">
                        @forelse($emissions as $emission)
                            <div class="emission-grid-item">
                                <div class="card emission-card">
                                    <div class="emission-thumbnail-container">
                                        <div class="emission-thumbnail position-relative"
                                            data-emission-id="{{ $emission->id }}"
                                            data-emission-name="{{ $emission->nom }}">

                                            <div class="default-thumbnail d-flex align-items-center justify-content-center"
                                                style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                <i class="fas fa-broadcast-tower text-white" style="font-size: 3rem;"></i>
                                            </div>

                                            <div class="thumbnail-overlay">
                                                <i class="fas fa-eye"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <h5 class="card-title" title="{{ $emission->nom }}">
                                            {{ Str::limit($emission->nom, 25) }}</h5>
                                        <p class="card-text text-muted small" title="{{ $emission->description }}">
                                            {{ Str::limit($emission->description, 30) }}</p>

                                        <!-- Statistiques -->
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-video"></i> {{ $emission->videos_count }} vidéos
                                                @if ($emission->videos_count > 0)
                                                    | <i class="fas fa-clock"></i> {{ $emission->total_duration }}
                                                @endif
                                            </small>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <small
                                                class="text-muted mb-1">{{ $emission->created_at->format('d/m/Y') }}</small>

                                            <div class="btn-group">
                                                <a href="{{ route('emissions.show', $emission->id) }}"
                                                    class="btn btn-sm btn-outline-info rounded" title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button
                                                    class="btn btn-sm btn-outline-primary edit-emission-btn mx-1 rounded"
                                                    data-emission-id="{{ $emission->id }}" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <form action="{{ route('emissions.destroy', $emission->id) }}"
                                                    method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette émission ?')"
                                                        title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                    </div>
                    <div class="col-12 text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-broadcast-tower fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Aucune émission disponible</h4>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination si nécessaire -->
            @if ($emissions->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $emissions->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </section>

    <!-- Modals -->
    @include('admin.medias.emissions.modals.add')
    @include('admin.medias.emissions.modals.edit')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // ===== TOGGLE STATUT (Émissions) =====
            $(document).on('click', '.toggle-status-emission-btn', function() {
                const $btn = $(this);
                const id = $btn.data('emission-id');
                const url = "{{ url('emissions') }}/" + id + "/toggle-status";

                $.post(url, {
                        _token: '{{ csrf_token() }}'
                    })
                    .done(function() {
                        window.location.reload();
                    })
                    .fail(function() {
                        alert("Erreur lors du changement de statut de l'émission");
                    });
            });

            // ===== GESTION DU FORMULAIRE D'ÉDITION =====
            $(document).on('click', '.edit-emission-btn', function() {
                const emissionId = $(this).data('emission-id');
                $.ajax({
                    url: "{{ route('emissions.edit', ':id') }}".replace(':id', emissionId),
                    method: 'GET',
                    success: function(data) {
                        $('#editEmissionNom').val(data.nom);
                        $('#editEmissionDescription').val(data.description);
                        $('#editEmissionEtat').prop('checked', data.etat);
                        $('#editEmissionForm').attr('action',
                            "{{ route('emissions.update', ':id') }}".replace(':id',
                                emissionId));
                        $('#editEmissionModal').modal('show');
                    },
                    error: function() {
                        alert('Erreur lors du chargement des données de l\'émission');
                    }
                });
            });

            // ===== VISUALISATION DES ÉMISSIONS =====
            $(document).on('click', '.emission-thumbnail', function() {
                const emissionId = $(this).data('emission-id');
                window.location.href = "{{ route('emissions.show', ':id') }}".replace(':id', emissionId);
            });
        });
    </script>
@endpush
