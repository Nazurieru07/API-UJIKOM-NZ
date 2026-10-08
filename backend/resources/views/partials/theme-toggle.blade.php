{{--
    Tombol toggle tema + logika klik. Taruh di dalam <body>,
    biasanya di navbar. Memakai @once supaya tidak dobel kalau
    di-include lebih dari sekali di satu halaman.
--}}

<button id="themeToggle"
        type="button"
        title="Ganti mode tampilan"
        aria-label="Ganti mode tampilan"
        class="theme-toggle-btn relative w-9 h-9 flex items-center justify-center
               rounded-lg border border-gray-300 bg-white text-gray-600
               hover:text-amber-500 transition overflow-hidden select-none">
    <svg class="sun-icon w-5 h-5 absolute" fill="none" viewBox="0 0 24 24"
         stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="5"/>
        <path stroke-linecap="round" d="M12 2v2m0 16v2M2 12h2m16 0h2
            M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M19.07 4.93l-1.41 1.41
            M6.34 17.66l-1.41 1.41"/>
    </svg>
    <svg class="moon-icon w-5 h-5 absolute" fill="none" viewBox="0 0 24 24"
         stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>
    </svg>
</button>

@once
<script>
    (function () {
        const btn = document.getElementById('themeToggle');
        if (!btn) return;

        const SAVE_URL = @json(auth()->check() ? route('tema.simpan') : null);
        const CSRF = @json(csrf_token());

        btn.addEventListener('click', function (e) {
            const html = document.documentElement;
            const isDark = html.dataset.tema !== 'dark';
            const tema = isDark ? 'dark' : 'light';

            // Pasang class transisi SEBELUM warna berubah, lepas
            // setelah selesai supaya page-load tidak jadi fade.
            html.classList.add('theme-anim');
            setTimeout(() => html.classList.remove('theme-anim'), 500);

            html.dataset.tema = tema;
            html.classList.toggle('dark', isDark);
            localStorage.setItem('tema', tema);

            // Ripple dari titik klik.
            const rect = btn.getBoundingClientRect();
            const ripple = document.createElement('span');
            ripple.className = 'theme-ripple';
            ripple.style.left = (e.clientX - rect.left) + 'px';
            ripple.style.top = (e.clientY - rect.top) + 'px';
            btn.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);

            // Preferensi disimpan di DB kalau user sudah login;
            // localStorage pegang kalau belum (mis. di halaman login).
            if (SAVE_URL) {
                fetch(SAVE_URL, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ tema: tema }),
                }).catch(function (err) {
                    console.warn('Tema: gagal simpan ke server', err);
                });
            }
        });
    })();
</script>
@endonce
