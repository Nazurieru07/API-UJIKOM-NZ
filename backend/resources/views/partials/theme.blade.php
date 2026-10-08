{{--
    |--------------------------------------------------------------------------
    | Tema: inisialisasi (HEAD)
    |--------------------------------------------------------------------------
    | Hanya boleh di-include di <head>. Menjalankan script anti-FOUC
    | sebelum halaman dirender, lalu memuat CSS + logika toggle.
    |
    | Sumber tema (urutan): DB user > localStorage > 'light'.
    |--}}

@php
    $tema = auth()->check() ? (auth()->user()->tema ?? 'light') : 'light';
    if (!in_array($tema, ['light', 'dark'], true)) {
        $tema = 'light';
    }
@endphp

<script>
    window.__initTema = function () {
        const TEMA_KEY = 'tema';
        let tema = localStorage.getItem(TEMA_KEY) || '{{ $tema }}';
        if (tema !== 'light' && tema !== 'dark') tema = 'light';

        const html = document.documentElement;
        html.dataset.tema = tema;
        html.classList.toggle('dark', tema === 'dark');
    };
    window.__initTema();
</script>

@include('partials.theme-css')
