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

            </div>
            @include('admin.partials.footer')
        </div>
    </div>
    @include('sweetalert::alert')

    @include('admin.partials.script')
    @stack('scripts')
</body>

</html>
