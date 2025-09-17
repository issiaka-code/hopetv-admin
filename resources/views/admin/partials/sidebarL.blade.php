@php
    $parametre = App\Models\Parametre::first();
@endphp
<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center justify-content-around">
                <img src="{{ $parametre && $parametre->logo ? asset('storage/' . $parametre->logo) : asset('assets/img/logo.png') }}"
                    alt="{{ config('app.name') }}" class="header-logo rounded" style="width: 50px; height: 50px;" />
                <span class="logo-name">
                    <small style="font-size: 12px;">{{ $parametre ? $parametre->nom_site : config('app.name') }}</small>
                </span>
            </a>
        </div>

        <ul class="sidebar-menu">
            <!-- Section Tableau de bord -->
            <li class="menu-header">Principal</li>

            <li class="{{ Route::is('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}" class="nav-link">
                    <i data-feather="monitor"></i><span>Tableau de bord</span>
                </a>
            </li>

            <!-- Section Gestion des Médias -->
            <li class="menu-header">Gestion des Médias</li>

            <li class="{{ Route::is('videos.*') ? 'active' : '' }}">
                <a href="{{ route('videos.index') }}" class="nav-link">
                    <i data-feather="film"></i><span>Vidéos</span>
                </a>
            </li>
            <li class="{{ Route::is('podcasts.*') ? 'active' : '' }}">
                <a href="{{ route('podcasts.index') }}" class="nav-link">
                    <i data-feather="mic"></i><span>Podcasts</span>
                </a>
            </li>
            <li class="{{ Route::is('temoignages.*') ? 'active' : '' }}">
                <a href="{{ route('temoignages.index') }}" class="nav-link">
                    <i data-feather="message-square"></i><span>Témoignages</span>
                </a>
            </li>
            <li class="{{ Route::is('emissions.*') ? 'active' : '' }}">
                <a href="{{ route('emissions.index') }}" class="nav-link">
                    <i data-feather="radio"></i><span>Nos émissions</span>
                </a>
            </li>

            <!-- Section Programmation -->
            <li class="menu-header">Programmation et Contenu</li>

            <li class="dropdown {{ Route::is('playlists.*') ? 'active' : '' }}">
                <a href="#" class="menu-toggle nav-link has-dropdown">
                    <i data-feather="list"></i><span>Playlists</span>
                </a>
                <ul class="dropdown-menu">
                    <li><a class="nav-link {{ Route::is('playlists.index') ? 'active' : '' }}" href="{{ route('playlists.index') }}">Toutes les playlists</a></li>
                    <li><a class="nav-link {{ Route::is('playlists.create') ? 'active' : '' }}"" href="{{ route('playlists.create') }}">Créer une playlist</a></li>
                </ul>
            </li>
            <li class="{{ Route::is('info_importantes.*') ? 'active' : '' }}">
                <a href="{{ route('info_importantes.index') }}" class="nav-link">
                    <i data-feather="star"></i><span>Directes</span>
                </a>
            </li>
            <li class="{{ Route::is('info-bulles.*') ? 'active' : '' }}">
                <a href="{{ route('info-bulles.index') }}" class="nav-link">
                    <i data-feather="info"></i><span>Info-bulles</span>
                </a>
            </li>

            <!-- Section Configuration -->
            <li class="menu-header">Configuration</li>

            <li class="{{ Route::is('parametres.*') ? 'active' : '' }}">
                <a href="{{ route('parametres.index') }}" class="nav-link">
                    <i data-feather="settings"></i><span>Paramètres</span>
                </a>
            </li>

            <li class="{{ Route::is('etablissements.*') ? 'active' : '' }}">
                <a href="{{ route('etablissements.index') }}" class="nav-link">
                    <i data-feather="home"></i><span>Établissements</span>
                </a>
            </li>

            <li class="{{ Route::is('liens-utiles.*') ? 'active' : '' }}">
                <a href="{{ route('liens-utiles.index') }}" class="nav-link">
                    <i data-feather="link"></i><span>Liens utiles</span>
                </a>
            </li>
        </ul>
    </aside>
</div>
