{{--
    CSS dark mode + animasi toggle. Di-include oleh partials/theme.
    Tailwind CDN dipakai tanpa build step, dan 26 file view memakai
    utility class mentah. Daripada sentuh 26 file, utility netral
    di-override di sini dengan spesifisitas lebih tinggi
    (html[data-tema=...] = 0,2,0 vs utility = 0,1,0).
--}}
<style>
    html[data-tema="light"] { color-scheme: light; }
    html[data-tema="dark"]  { color-scheme: dark; }

    /* ---- Tombol toggle: state warna ikut tema ---- */
    html[data-tema="dark"] .theme-toggle-btn {
        border-color: #475569;
        background-color: #1e293b;
        color: #cbd5e1;
    }
    html[data-tema="dark"] .theme-toggle-btn:hover { color: #fbbf24; }

    /* ---- Swap ikon sun/moon: rotasi + scale ---- */
    .sun-icon, .moon-icon {
        transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.35s ease;
    }
    html[data-tema="light"] .sun-icon  { transform: rotate(0deg)  scale(1); opacity: 1; }
    html[data-tema="light"] .moon-icon { transform: rotate(90deg) scale(0); opacity: 0; }
    html[data-tema="dark"] .sun-icon   { transform: rotate(-90deg) scale(0); opacity: 0; }
    html[data-tema="dark"] .moon-icon  { transform: rotate(0deg)  scale(1); opacity: 1; }

    /* ---- Ripple dari titik klik ---- */
    .theme-ripple {
        position: absolute;
        width: 6px; height: 6px;
        border-radius: 9999px;
        transform: translate(-50%, -50%) scale(0);
        background: currentColor;
        opacity: 0.55;
        pointer-events: none;
        animation: theme-ripple-expand 0.6s ease-out forwards;
    }
    @keyframes theme-ripple-expand {
        to { transform: translate(-50%, -50%) scale(12); opacity: 0; }
    }

    /* ---- Transisi global saat ganti tema ---- */
    html.theme-anim,
    html.theme-anim *,
    html.theme-anim *::before,
    html.theme-anim *::after {
        transition:
            background-color 0.45s ease,
            border-color 0.45s ease,
            color 0.45s ease,
            fill 0.45s ease,
            stroke 0.45s ease,
            box-shadow 0.45s ease !important;
    }
    html[data-tema="dark"] body { background-color: #0f172a; color: #e2e8f0; }

    html[data-tema="dark"] .bg-gray-100 { background-color: #0f172a !important; }
    html[data-tema="dark"] .bg-gray-50  { background-color: #1e293b !important; }
    html[data-tema="dark"] .bg-gray-200 { background-color: #334155 !important; }
    html[data-tema="dark"] .bg-gray-300 { background-color: #475569 !important; }
    html[data-tema="dark"] .bg-white    { background-color: #1e293b !important; }
    html[data-tema="dark"] .bg-gray-900 { background-color: #020617 !important; }

    html[data-tema="dark"] .text-gray-900 { color: #f1f5f9 !important; }
    html[data-tema="dark"] .text-gray-800 { color: #e2e8f0 !important; }
    html[data-tema="dark"] .text-gray-700 { color: #cbd5e1 !important; }
    html[data-tema="dark"] .text-gray-600 { color: #94a3b8 !important; }
    html[data-tema="dark"] .text-gray-500 { color: #94a3b8 !important; }
    html[data-tema="dark"] .text-gray-400 { color: #64748b !important; }

    html[data-tema="dark"] .border-gray-300 { border-color: #475569 !important; }
    html[data-tema="dark"] .border-gray-200 { border-color: #334155 !important; }
    html[data-tema="dark"] .border-gray-100 { border-color: #1e293b !important; }
    html[data-tema="dark"] .divide-gray-200 > * + * { border-color: #334155 !important; }

    html[data-tema="dark"] .hover\:bg-gray-50:hover  { background-color: #334155 !important; }
    html[data-tema="dark"] .hover\:bg-gray-100:hover { background-color: #334155 !important; }
    html[data-tema="dark"] .hover\:bg-gray-200:hover { background-color: #475569 !important; }
    html[data-tema="dark"] .hover\:text-gray-900:hover { color: #f1f5f9 !important; }
    html[data-tema="dark"] .hover\:text-gray-800:hover { color: #e2e8f0 !important; }
    html[data-tema="dark"] .hover\:text-gray-700:hover { color: #cbd5e1 !important; }
    html[data-tema="dark"] .hover\:bg-white:hover { background-color: #334155 !important; }

    /* Tint 50/100: tetap khas warnanya tapi redup supaya tidak silau */
    html[data-tema="dark"] .bg-blue-50    { background-color: rgba(59,130,246,0.15)  !important; }
    html[data-tema="dark"] .bg-red-50     { background-color: rgba(239,68,68,0.15)   !important; }
    html[data-tema="dark"] .bg-emerald-50 { background-color: rgba(16,185,129,0.15)  !important; }
    html[data-tema="dark"] .bg-yellow-50  { background-color: rgba(234,179,8,0.15)   !important; }
    html[data-tema="dark"] .bg-green-50   { background-color: rgba(34,197,94,0.15)   !important; }
    html[data-tema="dark"] .bg-orange-50  { background-color: rgba(249,115,22,0.15)  !important; }
    html[data-tema="dark"] .bg-amber-50   { background-color: rgba(245,158,11,0.15)  !important; }
    html[data-tema="dark"] .bg-purple-50  { background-color: rgba(168,85,247,0.15)  !important; }
    html[data-tema="dark"] .bg-pink-50    { background-color: rgba(236,72,153,0.15)  !important; }

    html[data-tema="dark"] .bg-blue-100   { background-color: rgba(59,130,246,0.25)  !important; }
    html[data-tema="dark"] .bg-red-100    { background-color: rgba(239,68,68,0.25)   !important; }
    html[data-tema="dark"] .bg-emerald-100{ background-color: rgba(16,185,129,0.25)  !important; }
    html[data-tema="dark"] .bg-green-100  { background-color: rgba(34,197,94,0.25)   !important; }
    html[data-tema="dark"] .bg-yellow-100 { background-color: rgba(234,179,8,0.25)   !important; }
    html[data-tema="dark"] .bg-amber-100  { background-color: rgba(245,158,11,0.25)  !important; }
    html[data-tema="dark"] .bg-pink-100   { background-color: rgba(236,72,153,0.25)  !important; }
    html[data-tema="dark"] .bg-purple-100 { background-color: rgba(168,85,247,0.25)  !important; }

    /* Form control butuh background eksplisit */
    html[data-tema="dark"] input,
    html[data-tema="dark"] select,
    html[data-tema="dark"] textarea {
        background-color: #0f172a;
        border-color: #475569;
        color: #e2e8f0;
    }
    html[data-tema="dark"] input::placeholder,
    html[data-tema="dark"] textarea::placeholder { color: #64748b; }
    html[data-tema="dark"] option { background-color: #1e293b; color: #e2e8f0; }

    /* Shadow lebih dalam di gelap */
    html[data-tema="dark"] .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0,0,0,0.4) !important; }
    html[data-tema="dark"] .shadow-md { box-shadow: 0 4px 6px -1px rgba(0,0,0,0.5) !important; }
    html[data-tema="dark"] .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.5) !important; }

    /*
     | Alpha utility (bg-white/90 dll).
     | Override biasa hanya menangani shade penuh; yang pakai slash
     | opacity lolos dan tetap putih/abu di dark mode. Inilah sisa
     | warna terang yang masih terlihat di katalog & riwayat.
    */
    html[data-tema="dark"] .bg-white\/20   { background-color: rgba(148,163,184,0.20) !important; }
    html[data-tema="dark"] .bg-white\/70   { background-color: rgba(15,23,42,0.70)  !important; }
    html[data-tema="dark"] .bg-white\/80   { background-color: rgba(15,23,42,0.80)  !important; }
    html[data-tema="dark"] .bg-white\/90   { background-color: rgba(30,41,59,0.92)  !important; }
    html[data-tema="dark"] .bg-white\/95   { background-color: rgba(15,23,42,0.95)  !important; }
    html[data-tema="dark"] .bg-gray-50\/70 { background-color: rgba(15,23,42,0.70)  !important; }
    html[data-tema="dark"] .bg-black\/30   { background-color: rgba(0,0,0,0.30)     !important; }
    html[data-tema="dark"] .bg-black\/50   { background-color: rgba(0,0,0,0.50)     !important; }
    html[data-tema="dark"] .border-white\/30 { border-color: rgba(148,163,184,0.35) !important; }

    /*
     | Tombol disabled (mis. "Lanjutkan Pengajuan" sebelum unit
     | dipilih). Shade penuh sudah di-override, tapi variant
     | disabled punya selector sendiri, jadi harus terpisah.
    */
    html[data-tema="dark"] .disabled\:bg-gray-200:disabled   { background-color: #334155 !important; }
    html[data-tema="dark"] .disabled\:bg-gray-300:disabled   { background-color: #475569 !important; }
    html[data-tema="dark"] .disabled\:bg-gray-100:disabled   { background-color: #1e293b !important; }
    html[data-tema="dark"] .disabled\:text-gray-400:disabled { color: #64748b !important; }
    html[data-tema="dark"] .disabled\:text-gray-500:disabled { color: #64748b !important; }
    html[data-tema="dark"] .disabled\:border-gray-300:disabled { border-color: #475569 !important; }
</style>

{{--
    Highlight unit yang DIPILIH di katalog peminjam.

    Sebelumnya memakai has-[:checked]:bg-blue-50 (sintaks Tailwind v4)
    yang di-compile CDN v3 tapi selector-nya cocok untuk semua label
    walau checkbox tidak di-checked -- jadi semua unit kelihatan
    terpilih di dark mode. Diganti pseudo-class CSS biasa.
--}}
<style>
    .checked-unit:has(input:checked) {
        border-color: rgb(96, 165, 250);  /* blue-400 */
        background-color: rgb(239, 246, 255); /* blue-50 */
    }
    html[data-tema="dark"] .checked-unit:has(input:checked) {
        border-color: rgb(96, 165, 250);  /* blue-400 */
        background-color: rgba(59, 130, 246, 0.15);
    }
</style>
