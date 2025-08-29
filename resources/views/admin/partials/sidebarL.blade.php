<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('img/logo.png') }}" class="header-logo" />
                <span class="logo-name">{{ config('app.name') }}</span>
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

            <!-- Section Programmation -->
            <li class="menu-header">Programmation et Contenu</li>

            <li class="{{ Route::is('playlists.*') ? 'active' : '' }}">
                <a href="{{ route('playlists.index') }}" class="nav-link">
                    <i data-feather="list"></i><span>Playlists</span>
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

            <li class="{{ Route::is('liens-utiles.*') ? 'active' : '' }}">
                <a href="{{ route('liens-utiles.index') }}" class="nav-link">
                    <i data-feather="link"></i><span>Liens utiles</span>
                </a>
            </li>

            <li class="{{ Route::is('users.*') ? 'active' : '' }}">
                <a href="{{ route('users.index') }}" class="nav-link">
                    <i data-feather="users"></i><span>Utilisateurs</span>
                </a>
            </li>
        </ul>
    </aside>
</div>
