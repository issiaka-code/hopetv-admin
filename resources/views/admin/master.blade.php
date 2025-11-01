<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('admin.partials.head')

<body>
    {{-- <div class="loader"></div> --}}
    <div id="app">

        <div class="main-wrapper main-wrapper-1">

            @include('admin.partials.header')

            @include('admin.partials.sidebarL')

            <div class="main-content">

                @yield('content')

                @include('notify::components.notify')

            </div>

            @include('admin.partials.footer')

        </div>

    </div>


    @include('admin.partials.script')

    @include('admin.modale.add')
    @include('admin.modale.edit')
    @include('admin.modale.view')

    @stack('scripts')
    <script src="{{ asset('store-modale.js') }}"></script>
    <script src="{{ asset('media-viewer.js') }}"></script>
    <script src="{{ asset('edit-modale.js') }}"></script>

</body>

</html>
