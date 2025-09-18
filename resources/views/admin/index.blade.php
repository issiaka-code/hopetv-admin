@extends('admin.master')

@section('title', 'Dashboard')

@section('content')
    @php
        // Données pour les graphiques
        $mediaTypesData = [
            'audio' => $totalAudios,
            'vidéo_fichier' => $totalVideosFiles,
            'vidéo_lien' => $totalVideoLinks,
            'pdf' => $totalPdfs,
            'images' => $totalImages
        ];
        
        $statusData = [
            'publiés' => $totalVideosFiles + $totalVideoLinks - $videosNonPubliees,
            'non_publiés' => $videosNonPubliees
        ];
    @endphp
    
    <section class="section">
        <!-- Header avec titre et boutons rapides -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0 text-gray-800">Tableau de Bord</h1>
                </div>
            </div>
        </div>

        <!-- Cartes de statistiques -->
        <div class="row">
            <!-- Total Vidéos -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: #4e73df;">
                                    Total Vidéos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalVideos) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-video fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Témoignages -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Témoignages</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalTemoignages) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-comments fa-2x text-gray-300"></i>
                            </div>
                        </div>
                        <div class="mt-2 text-muted small">
                            Images: {{ $totalImages }} • PDF: {{ $totalPdfs }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Podcasts -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Podcasts</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalPodcasts) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-podcast fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Émissions -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Émissions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalEmissions) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-broadcast-tower fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques et visualisations -->
        <div class="row">
            <!-- Répartition des types de médias -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-dark">Répartition des types de médias</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="mediaTypesChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Statut des vidéos -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-dark">Statut des vidéos</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="videoStatusChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu récent et actions -->
        <div class="row">
            <!-- Derniers témoignages -->
            <div class="col-xl-12 col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-dark">Derniers témoignages</h6>
                        <a href="{{ route('temoignages.index') }}" class="btn btn-sm btn-primary">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @forelse($temoignagesRecents as $t)
                                @php
                                    $thumb = null;
                                    if ($t->media) {
                                        if ($t->media->thumbnail) {
                                            $thumb = asset('storage/' . $t->media->thumbnail);
                                        } elseif ($t->media->type === 'images' && $t->media->images) {
                                            $images = json_decode($t->media->images, true);
                                            if (is_array($images) && count($images) > 0) {
                                                $thumb = asset('storage/' . $images[0]);
                                            }
                                        } elseif ($t->media->url_fichier && !in_array($t->media->type, ['link'])) {
                                            $thumb = asset('storage/' . $t->media->url_fichier);
                                        }
                                    }
                                    
                                    $defaultIcon = 'assets/img/icons/';
                                    if ($t->media) {
                                        switch ($t->media->type) {
                                            case 'audio': $defaultIcon .= 'audio-icon.png'; break;
                                            case 'video': $defaultIcon .= 'video-icon.png'; break;
                                            case 'link': $defaultIcon .= 'link-icon.png'; break;
                                            case 'pdf': $defaultIcon .= 'pdf-icon.png'; break;
                                            case 'images': $defaultIcon .= 'images-icon.png'; break;
                                            default: $defaultIcon = 'assets/img/news/img01.jpg';
                                        }
                                    }
                                @endphp
                                <a href="{{ route('temoignages.edit', $t->id) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $thumb ?: asset($defaultIcon) }}" 
                                             class="rounded mr-3" 
                                             style="width: 50px; height: 50px; object-fit: cover;" 
                                             alt="cover"
                                             onerror="this.src='{{ asset('assets/img/news/img01.jpg') }}'">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ Str::limit($t->nom, 30) }}</h6>
                                            <small class="text-muted">
                                                {{ optional($t->created_at)->format('d/m/Y H:i') }} • 
                                                <span class="text-capitalize">
                                                    @if($t->media){{ $t->media->type }}@else Type inconnu @endif
                                                </span>
                                            </small>
                                        </div>
                                        <div class="ml-auto">
                                            <i class="fas fa-chevron-right text-muted"></i>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-comments fa-2x mb-2"></i>
                                    <p>Aucun témoignage récent</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Graphique des types de médias
        const mediaTypesCtx = document.getElementById('mediaTypesChart').getContext('2d');
        const mediaTypesChart = new Chart(mediaTypesCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_keys($mediaTypesData)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($mediaTypesData)) !!},
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
                    ],
                    hoverBackgroundColor: [
                        '#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617'
                    ],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '70%'
            }
        });

        // Graphique du statut des vidéos
        const videoStatusCtx = document.getElementById('videoStatusChart').getContext('2d');
        const videoStatusChart = new Chart(videoStatusCtx, {
            type: 'pie',
            data: {
                labels: {!! json_encode(array_keys($statusData)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($statusData)) !!},
                    backgroundColor: ['#1cc88a', '#f6c23e'],
                    hoverBackgroundColor: ['#17a673', '#dda20a'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>
@endpush