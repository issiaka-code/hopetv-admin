@extends('admin.master')

@section('title', 'Gestion des Liens Utiles')

@push('styles')
    <style>
        .section-title {
            font-size: 1.5rem;
            color: #4e73df;
            margin-bottom: 0;
        }

        .lien-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            height: 100%;
        }

        .lien-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .lien-icon-container {
            height: 120px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #4e73df;
        }

        .lien-card .card-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lien-url {
            height: 40px;
            overflow: hidden;
            margin-bottom: 1rem;
            color: #6c757d;
            font-size: 0.9rem;
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
        #liens-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .lien-grid-item {
            width: 100%;
        }

        /* Responsive improvements */
        @media (max-width: 1400px) {
            #liens-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            #liens-grid {
                grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
                gap: 1.25rem;
            }

            .lien-icon-container {
                height: 110px;
            }
        }

        @media (max-width: 992px) {
            #liens-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }

            .lien-icon-container {
                height: 100px;
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 1.35rem;
            }

            .card-title {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            #liens-grid {
                grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
                gap: 0.875rem;
            }

            .lien-icon-container {
                height: 90px;
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
            #liens-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                max-width: 400px;
                margin: 0 auto;
            }

            .lien-icon-container {
                height: 100px;
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
            .lien-icon-container {
                height: 80px;
            }

            .modal-dialog {
                margin: 0.25rem;
            }

            .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }
        }

        /* Touch device improvements */
        @media (hover: none) {
            .lien-card:hover {
                transform: none;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            }
        }
    </style>
@endpush

@section('content')
    <section class="section" style="margin-top: -25px;">
        <div class="section-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                        {{ session('success') }}
                    </div>
                </div>
            @elseif (session('error'))
                <div class="alert alert-danger alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                        {{ session('error') }}
                    </div>
                </div>
            @endif

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
                        <h2 class="section-title">Liens Utiles disponibles</h2>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addLienModal">
                            <i class="fas fa-plus"></i> Ajouter un lien
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <form method="GET" action="{{ route('liens-utiles.index') }}" class="w-100">
                    <div class="row g-2 d-flex flex-row justify-content-end align-items-center">
                        <!-- Champ recherche -->
                        <div class="col-3">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Rechercher un lien...">
                        </div>

                        <!-- Bouton recherche -->
                        <div class="col-2">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="fas fa-search py-2"></i> Rechercher
                            </button>
                        </div>
                         <div class="col-2">
                            <a href="{{ route('liens-utiles.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-sync py-2"></i> Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Grille de liens -->
            <div class="row">
                <div class="col-12">
                    <div id="liens-grid">
                        @forelse($liens as $lien)
                            <div class="lien-grid-item">
                                <div class="card lien-card">
                                    <div class="lien-icon-container">
                                        <i class="fas fa-link"></i>
                                    </div>

                                    <div class="card-body">
                                        <h5 class="card-title" title="{{ $lien->nom }}">{{ Str::limit($lien->nom, 25) }}</h5>
                                        <p class="lien-url text-truncate" title="{{ $lien->lien }}">
                                            {{ Str::limit($lien->lien, 30) }}</p>

                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <small class="text-muted mb-1">{{ $lien->created_at->format('d/m/Y') }}</small>

                                            <div class="btn-group">
                                                <a href="{{ $lien->lien }}" target="_blank" class="btn btn-sm btn-outline-info rounded">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-primary edit-lien-btn mx-1 rounded"
                                                    data-lien-id="{{ $lien->id }}"
                                                    data-lien-nom="{{ $lien->nom }}"
                                                    data-lien-url="{{ $lien->lien }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('liens-utiles.destroy', $lien->id) }}" method="POST"
                                                    class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce lien ?')">
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
                            <i class="fas fa-link fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Aucun lien disponible</h4>
                            <p class="text-muted">Commencez par ajouter votre premier lien utile.</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination si nécessaire -->
            @if ($liens->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $liens->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </section>

    <!-- Modal d'ajout -->
    <div class="modal fade" id="addLienModal" tabindex="-1" role="dialog" aria-labelledby="addLienModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLienModalLabel">Ajouter un lien utile</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addLienForm" action="{{ route('liens-utiles.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="addLienNom">Nom du lien *</label>
                            <input type="text" class="form-control" id="addLienNom" name="nom" required>
                        </div>
                        <div class="form-group">
                            <label for="addLienUrl">URL *</label>
                            <input type="url" class="form-control" id="addLienUrl" name="lien" placeholder="https://example.com" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary bg-secondary" data-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"> <i class="fas fa-plus-circle"></i> Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal d'édition -->
    <div class="modal fade" id="editLienModal" tabindex="-1" role="dialog" aria-labelledby="editLienModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLienModalLabel">Modifier le lien</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editLienForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="editLienNom">Nom du lien *</label>
                            <input type="text" class="form-control" id="editLienNom" name="nom" required>
                        </div>
                        <div class="form-group">
                            <label for="editLienUrl">URL *</label>
                            <input type="url" class="form-control" id="editLienUrl" name="lien" placeholder="https://example.com" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary bg-secondary" data-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"> <i class="fas fa-save"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Gestion de l'édition des liens
            $(document).on('click', '.edit-lien-btn', function() {
                const lienId = $(this).data('lien-id');
                const lienNom = $(this).data('lien-nom');
                const lienUrl = $(this).data('lien-url');
                
                $('#editLienNom').val(lienNom);
                $('#editLienUrl').val(lienUrl);
                $('#editLienForm').attr('action', "{{ route('liens-utiles.update', ':id') }}".replace(':id', lienId));
                
                $('#editLienModal').modal('show');
            });

            // Réinitialiser le formulaire d'ajout quand le modal est fermé
            $('#addLienModal').on('hidden.bs.modal', function() {
                $('#addLienForm')[0].reset();
            });
        });
    </script>
@endpush