@extends('admin.master')

@section('title', 'Gestion des Établissements')

@push('styles')
    <style>
        .section-title {
            font-size: 1.5rem;
            color: #4e73df;
            margin-bottom: 0;
        }

        #etabs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .etab-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .05);
            border: none;
            overflow: hidden;
        }

        .etab-thumbnail-container {
            height: 180px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .etab-thumbnail-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .badge-pos {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }

        .badge-status {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
        }

        @media (max-width: 1200px) {
            #etabs-grid {
                grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            }

            .etab-thumbnail-container {
                height: 160px;
            }
        }

        @media (max-width: 768px) {
            #etabs-grid {
                grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
                gap: .875rem;
            }

            .etab-thumbnail-container {
                height: 120px;
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
                        <button class="close" data-dismiss="alert"><span>&times;</span></button>
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
                        <h2 class="section-title">Établissements</h2>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addEtabModal">
                            <i class="fas fa-plus"></i> Ajouter un établissement
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <form method="GET" action="{{ route('etablissements.index') }}" class="w-100">
                    <div class="row g-2 d-flex flex-row justify-content-end align-items-center">
                        <div class="col-3">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Rechercher un établissement...">
                        </div>
                        <div class="col-2">
                            <select name="type" class="form-control">
                                <option value="">Tous</option>
                                <option value="siege" {{ request('type') === 'siege' ? 'selected' : '' }}>Siège</option>
                                <option value="annexe" {{ request('type') === 'annexe' ? 'selected' : '' }}>Annexe</option>
                            </select>
                        </div>
                        <div class="col-2">
                            <select name="status" class="form-control">
                                <option value="">Tous</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Actif</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactif</option>
                            </select>
                        </div>
                        <div class="col-2">
                            <button class="btn btn-primary w-100" type="submit"><i class="fas fa-filter py-2"></i>
                                Filtrer</button>
                        </div>
                        <div class="col-2">
                            <a href="{{ route('etablissements.index') }}" class="btn btn-outline-secondary w-100"><i
                                    class="fas fa-sync py-2"></i> Réinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="row">
                <div class="col-12">
                    <div id="etabs-grid">
                        @forelse($etablissements as $etab)
                            <div class="etab-grid-item">
                                <div class="card etab-card">
                                    <div class="etab-thumbnail-container">
                                        @if ($etab->image_path)
                                            <img src="{{ asset('storage/' . $etab->image_path) }}"
                                                alt="{{ $etab->nom }}">
                                        @else
                                            <div class="w-100 h-100 d-flex align-items-center justify-content-center"
                                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                <i class="fas fa-building text-white" style="font-size: 3rem;"></i>
                                            </div>
                                        @endif
                                        <span
                                            class="badge badge-primary badge-pos">{{ $etab->type === 'siege' ? 'Siège' : 'Annexe' }}</span>
                                        <span
                                            class="badge {{ $etab->is_active ? 'badge-success' : 'badge-danger' }} badge-status">{{ $etab->is_active ? 'Actif' : 'Inactif' }}</span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title mb-1" title="{{ $etab->nom }}">
                                            {{ Str::limit($etab->nom, 28) }}</h5>
                                        <div class="small text-muted">
                                            @if ($etab->telephone)
                                                <div><i class="fas fa-phone"></i> {{ $etab->telephone }}</div>
                                            @endif
                                            @if ($etab->email)
                                                <div><i class="fas fa-envelope"></i> {{ $etab->email }}</div>
                                            @endif
                                            @if ($etab->adresse)
                                                <div><i class="fas fa-map-marker-alt"></i> {{ Str::limit($etab->adresse, 50) }}</div>
                                                @if ($etab->google_maps_url)
                                                    <div class="small">
                                                        <a href="{{ $etab->google_maps_url }}" target="_blank" class="text-primary">
                                                            <i class="fas fa-external-link-alt"></i> Voir sur Google Maps
                                                        </a>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-info view-etab-btn rounded"
                                                    data-image="{{ $etab->image_path ? asset('storage/' . $etab->image_path) : '' }}"
                                                    data-nom="{{ $etab->nom }}" data-type="{{ $etab->type }}"
                                                    data-telephone="{{ $etab->telephone }}"
                                                    data-email="{{ $etab->email }}"
                                                    data-adresse="{{ $etab->adresse }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary edit-etab-btn mx-1 rounded"
                                                    data-etab-id="{{ $etab->id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('etablissements.destroy', $etab->id) }}"
                                                    method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded"
                                                        onclick="return confirm('Supprimer cet établissement ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                <button
                                                    class="btn btn-sm btn-outline-{{ $etab->is_active ? 'success' : 'danger' }} toggle-status-btn mx-1 rounded"
                                                    data-etab-id="{{ $etab->id }}"
                                                    data-status="{{ $etab->is_active ? 1 : 0 }}">
                                                    <i
                                                        class="fas fa-{{ $etab->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                                    <span
                                                        class="p-1">{{ $etab->is_active ? 'Actif' : 'Inactif' }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                    </div>
                    <div class="col-12 text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-city fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Aucun établissement</h4>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            @if ($etablissements->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $etablissements->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </section>

    @include('admin.medias.etablissements.modals.add')
    @include('admin.medias.etablissements.modals.edit')
    @include('admin.medias.etablissements.modals.view')
@endsection

@push('scripts')
    <script>
        $(function() {
            // Reset Add Modal
            $('#addEtabModal').on('hidden.bs.modal', function() {
                $('#addEtabForm')[0].reset();
                $('#addImage').next('.custom-file-label').html('Choisir une image');
                $('#addTypeSiege').prop('checked', true);
            });

            // File input labels
            $('#addImage, #editImage').on('change', function() {
                const fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass('selected').html(fileName || 'Choisir une image');
            });

            // Toggle status
            $(document).on('click', '.toggle-status-btn', function() {
                const id = $(this).data('etab-id');
                const current = $(this).data('status');
                const next = current ? 0 : 1;
                $.post("{{ url('etablissements') }}/" + id + "/toggle_status", {
                    _token: '{{ csrf_token() }}',
                    is_active: next
                }, function() {
                    window.location.reload();
                }).fail(function() {
                    alert('Erreur lors du changement de statut');
                });
            });

            // Edit populate
            $(document).on('click', '.edit-etab-btn', function() {
                const id = $(this).data('etab-id');
                $.get("{{ route('etablissements.edit', ':id') }}".replace(':id', id), function(data) {
                    $('#editEtabForm').attr('action', "{{ route('etablissements.update', ':id') }}".replace(':id', id));
                    $('#editNom').val(data.nom);
                    $('#editTelephone').val(data.telephone);
                    $('#editEmail').val(data.email);
                    $('#editAdresse').val(data.adresse);
                    if (data.type === 'siege') {
                        $('#editTypeSiege').prop('checked', true);
                    } else {
                        $('#editTypeAnnexe').prop('checked', true);
                    }
                    $('#editIsActive').prop('checked', !!data.is_active);
                    if (data.image_path) {
                        $('#editCurrentImageName').text(data.image_path.split('/').pop());
                        $('#editCurrentImagePreview').attr('src', '/storage/' + data.image_path).show();
                        $('#editCurrentImage').show();
                    } else {
                        $('#editCurrentImage').hide();
                    }
                    $('#editEtabModal').modal('show');
                }).fail(function() {
                    alert('Erreur de chargement');
                });
            });

            // View modal
            $(document).on('click', '.view-etab-btn', function() {
                const image = $(this).data('image');
                const nom = $(this).data('nom');
                const type = $(this).data('type');
                const tel = $(this).data('telephone') || '-';
                const email = $(this).data('email') || '-';
                const adresse = $(this).data('adresse') || '-';
                
                if (image) {
                    $('#viewEtabImage').attr('src', image).show();
                } else {
                    $('#viewEtabImage').hide();
                }
                $('#viewEtabNom').text(nom);
                $('#viewEtabType').text(type === 'siege' ? 'Siège' : 'Annexe');
                $('#viewEtabTel').text(tel);
                $('#viewEtabEmail').text(email);
                $('#viewEtabAdresse').text(adresse);
                $('#viewEtabModal').modal('show');
            });
        });
    </script>
@endpush
