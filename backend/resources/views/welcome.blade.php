<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tempest KariMono</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:        #05070f;
            --bg-2:      #080c18;
            --panel:     rgba(255, 255, 255, 0.035);
            --panel-2:   rgba(255, 255, 255, 0.06);
            --line:      rgba(120, 200, 255, 0.14);
            --line-2:    rgba(120, 200, 255, 0.28);
            --ink:       #eef4ff;
            --ink-2:     #a8b8d4;
            --ink-3:     #6b7b99;
            --cyan:      #4fd1ff;
            --cyan-2:    #2f9fe0;
            --blue:      #7aa8ff;
            --violet:    #a78bfa;
            --glow:      0 0 40px rgba(79, 209, 255, 0.25);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; overflow-x: hidden; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--ink);
            overflow-x: hidden;
            min-height: 100vh;
        }

        ::selection { background: rgba(79, 209, 255, 0.3); color: #fff; }

        /* ============ Background animasi ============ */
        .bg-fx {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .bg-fx::before {
            content: '';
            position: absolute;
            inset: -30%;
            background:
                radial-gradient(38% 45% at 50% 42%, rgba(47, 159, 224, 0.20), transparent 70%),
                radial-gradient(30% 38% at 12% 75%, rgba(122, 168, 255, 0.13), transparent 70%),
                radial-gradient(34% 40% at 88% 22%, rgba(167, 139, 250, 0.12), transparent 70%);
            filter: blur(20px);
            animation: aurora 18s ease-in-out infinite alternate;
        }
        @keyframes aurora {
            0%   { transform: translate3d(0, 0, 0) scale(1); }
            50%  { transform: translate3d(-3%, 2%, 0) scale(1.08); }
            100% { transform: translate3d(3%, -2%, 0) scale(1.02); }
        }

        .bg-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(120, 200, 255, 0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(120, 200, 255, 0.045) 1px, transparent 1px);
            background-size: 64px 64px;
            mask-image: radial-gradient(70% 60% at 50% 40%, #000 30%, transparent 85%);
            -webkit-mask-image: radial-gradient(70% 60% at 50% 40%, #000 30%, transparent 85%);
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(2px);
            opacity: 0.5;
            animation: float-orb linear infinite;
        }
        @keyframes float-orb {
            from { transform: translateY(0) translateX(0); opacity: 0; }
            10%  { opacity: 0.6; }
            90%  { opacity: 0.6; }
            to   { transform: translateY(-110vh) translateX(40px); opacity: 0; }
        }

        /* ============ Navbar ============ */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.1rem clamp(1.1rem, 4vw, 3.2rem);
            background: linear-gradient(180deg, rgba(5, 7, 15, 0.85), rgba(5, 7, 15, 0.55) 60%, transparent);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.12rem;
            letter-spacing: 0.01em;
            color: var(--ink);
            text-decoration: none;
        }
        .brand-mark {
            width: 36px; height: 36px;
            display: grid; place-items: center;
            border-radius: 11px;
            background: linear-gradient(140deg, rgba(79, 209, 255, 0.22), rgba(122, 168, 255, 0.14));
            border: 1px solid var(--line-2);
            box-shadow: inset 0 0 14px rgba(79, 209, 255, 0.18);
            position: relative;
            overflow: hidden;
        }
        .brand-mark::after {
            content: '';
            position: absolute;
            width: 130%; height: 40%;
            top: -40%; left: -15%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35), transparent);
            animation: shine 3.4s ease-in-out infinite;
        }
        @keyframes shine {
            0%, 100% { transform: translateY(0) rotate(18deg); opacity: 0; }
            45%, 60% { transform: translateY(46px) rotate(18deg); opacity: 1; }
        }
        .brand-mark svg { width: 20px; height: 20px; position: relative; z-index: 1; }

        .nav-actions { display: flex; align-items: center; gap: 0.6rem; }

        .btn {
            font-family: inherit;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.62rem 1.35rem;
            border-radius: 999px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.18s ease, box-shadow 0.25s ease, border-color 0.25s ease, background 0.25s ease;
            white-space: nowrap;
        }
        .btn-primary {
            color: #04121d;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            box-shadow: 0 6px 22px rgba(79, 209, 255, 0.32);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(79, 209, 255, 0.5);
        }
        .btn-ghost {
            color: var(--ink);
            background: var(--panel);
            border-color: var(--line-2);
            backdrop-filter: blur(6px);
        }
        .btn-ghost:hover {
            transform: translateY(-2px);
            border-color: var(--cyan);
            box-shadow: var(--glow);
            color: #fff;
        }

        /* ============ Hero ============ */
        .hero {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(280px, 1fr) minmax(320px, 1.15fr) minmax(280px, 1fr);
            gap: clamp(1.2rem, 3vw, 2.6rem);
            align-items: center;
            padding: 6.5rem clamp(1.1rem, 4vw, 3.2rem) 3rem;
            max-width: 1560px;
            margin: 0 auto;
        }

        /* --- Panel samping --- */
        .side-panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 1.6rem 1.45rem;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(26px);
        }
        .side-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(79, 209, 255, 0.55), transparent);
        }
        .side-panel.in { animation: panel-in 0.85s cubic-bezier(0.22, 1, 0.36, 1) forwards; }
        .side-panel.right.in { animation-delay: 0.15s; }
        @keyframes panel-in {
            to { opacity: 1; transform: translateY(0); }
        }

        .panel-label {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--cyan);
            background: rgba(79, 209, 255, 0.09);
            border: 1px solid rgba(79, 209, 255, 0.22);
            padding: 0.32rem 0.7rem;
            border-radius: 999px;
            margin-bottom: 1rem;
        }
        .panel-label.violet { color: var(--violet); background: rgba(167, 139, 250, 0.09); border-color: rgba(167, 139, 250, 0.24); }

        .side-panel h3 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.22rem;
            font-weight: 700;
            margin-bottom: 0.6rem;
            line-height: 1.3;
        }
        .side-panel p {
            font-size: 0.9rem;
            line-height: 1.68;
            color: var(--ink-2);
        }

        .panel-list { list-style: none; margin-top: 1.1rem; display: grid; gap: 0.78rem; }
        .panel-list li {
            display: flex;
            gap: 0.7rem;
            align-items: flex-start;
            font-size: 0.875rem;
            color: var(--ink-2);
            line-height: 1.5;
        }
        .tick {
            flex: none;
            width: 20px; height: 20px;
            margin-top: 1px;
            border-radius: 7px;
            display: grid; place-items: center;
            background: rgba(79, 209, 255, 0.12);
            border: 1px solid rgba(79, 209, 255, 0.26);
        }
        .tick.v { background: rgba(167, 139, 250, 0.12); border-color: rgba(167, 139, 250, 0.28); }
        .tick svg { width: 11px; height: 11px; }
        .panel-list b { color: var(--ink); font-weight: 700; }

        /* --- Karakter tengah --- */
        .stage {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 560px;
        }
        .stage-glow {
            position: absolute;
            width: 118%;
            aspect-ratio: 1;
            top: 50%; left: 50%;
            transform: translate(-50%, -48%);
            background: radial-gradient(circle, rgba(79, 209, 255, 0.20) 0%, rgba(122, 168, 255, 0.09) 42%, transparent 68%);
            filter: blur(8px);
            animation: pulse-glow 5.5s ease-in-out infinite;
            pointer-events: none;
        }
        @keyframes pulse-glow {
            0%, 100% { transform: translate(-50%, -48%) scale(1);   opacity: 0.75; }
            50%      { transform: translate(-50%, -48%) scale(1.09); opacity: 1; }
        }
        .ring {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            border: 1px solid var(--line);
            pointer-events: none;
        }
        .ring.r1 { width: 86%;  aspect-ratio: 1; animation: spin-slow 26s linear infinite; }
        .ring.r2 { width: 64%;  aspect-ratio: 1; animation: spin-slow 18s linear infinite reverse; border-color: rgba(167, 139, 250, 0.16); }
        @keyframes spin-slow { to { transform: translate(-50%, -50%) rotate(360deg); } }
        .ring::after {
            content: '';
            position: absolute;
            top: -3px; left: 50%;
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--cyan);
            box-shadow: 0 0 12px var(--cyan);
            transform: translateX(-50%);
        }
        .ring.r2::after { background: var(--violet); box-shadow: 0 0 12px var(--violet); }

        .hero-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(2.1rem, 4.4vw, 3.6rem);
            font-weight: 700;
            line-height: 1.06;
            letter-spacing: -0.02em;
            text-align: center;
            margin-bottom: 0.35rem;
            position: relative;
            z-index: 2;
            opacity: 0;
            animation: title-in 1s cubic-bezier(0.22, 1, 0.36, 1) 0.1s forwards;
        }
        @keyframes title-in {
            from { opacity: 0; transform: translateY(24px) scale(0.97); filter: blur(6px); }
            to   { opacity: 1; transform: none; filter: blur(0); }
        }
        .hero-title .grad {
            background: linear-gradient(100deg, #ffffff 12%, var(--cyan) 48%, var(--blue) 78%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }
        .hero-sub {
            text-align: center;
            font-size: clamp(0.86rem, 1.1vw, 0.98rem);
            color: var(--ink-2);
            max-width: 400px;
            margin-bottom: 1.1rem;
            position: relative;
            z-index: 2;
            opacity: 0;
            animation: title-in 1s cubic-bezier(0.22, 1, 0.36, 1) 0.28s forwards;
        }

        .rimuru-wrap {
                    position: relative;
                    z-index: 2;
                    width: 100%;
                    max-width: 320px;
                    margin: 0 auto;
                    display: flex;
                    justify-content: center;
                }
                .rimuru {
                    width: 100%;
                    height: auto;
                    object-fit: contain;
                    filter: drop-shadow(0 18px 44px rgba(47, 159, 224, 0.38));
                    animation: float-char 6s ease-in-out infinite;
                    opacity: 0;
                    transition: opacity 0.6s ease;
                    /* Gradient fade ke bawah — opacity berkurang di bagian
                       yang terpotong oleh batas hero */
                    -webkit-mask-image: linear-gradient(180deg, #000 0%, #000 62%, rgba(0,0,0,0.55) 82%, rgba(0,0,0,0.05) 100%);
                    mask-image: linear-gradient(180deg, #000 0%, #000 62%, rgba(0,0,0,0.55) 82%, rgba(0,0,0,0.05) 100%);
                }
                .rimuru.loaded { opacity: 1; }
        @keyframes float-char {
            0%, 100% { transform: translateY(0) scale(1); }
            50%      { transform: translateY(-16px) scale(1.018); }
        }

        .cta-row {
            display: flex;
            gap: 0.7rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 0.4rem;
            position: relative;
            z-index: 2;
            opacity: 0;
            animation: title-in 1s cubic-bezier(0.22, 1, 0.36, 1) 0.42s forwards;
        }
        .scroll-hint {
            margin-top: 1.3rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.7rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--ink-3);
            position: relative;
            z-index: 2;
        }
        .mouse {
            width: 22px; height: 36px;
            border: 1.5px solid var(--line-2);
            border-radius: 12px;
            position: relative;
        }
        .mouse::after {
            content: '';
            position: absolute;
            top: 7px; left: 50%;
            width: 3px; height: 7px;
            border-radius: 2px;
            background: var(--cyan);
            transform: translateX(-50%);
            animation: scroll-dot 1.9s ease-in-out infinite;
        }
        @keyframes scroll-dot {
            0%   { opacity: 0; transform: translate(-50%, -4px); }
            35%  { opacity: 1; }
            100% { opacity: 0; transform: translate(-50%, 12px); }
        }

        /* ============ Section penjelasan ============ */
        .detail {
            position: relative;
            z-index: 1;
            padding: clamp(3.5rem, 8vw, 6.5rem) clamp(1.1rem, 4vw, 3.2rem) 4.5rem;
            max-width: 1240px;
            margin: 0 auto;
        }
        .detail-head { text-align: center; margin-bottom: 3rem; }
        .detail-head .panel-label { margin-bottom: 0.9rem; }
        .detail-head h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(1.7rem, 3.4vw, 2.5rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.6rem;
        }
        .detail-head p { color: var(--ink-2); max-width: 560px; margin: 0 auto; font-size: 0.95rem; line-height: 1.66; }

        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1.1rem;
        }
        .step {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 1.5rem 1.3rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .step:hover {
            transform: translateY(-6px);
            border-color: var(--line-2);
            box-shadow: 0 14px 40px rgba(47, 159, 224, 0.16);
        }
        .step::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(60% 55% at 50% 0%, rgba(79, 209, 255, 0.10), transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .step:hover::before { opacity: 1; }
        .step-num {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--cyan);
            letter-spacing: 0.14em;
            margin-bottom: 0.7rem;
        }
        .step h4 {
            font-size: 1.04rem;
            font-weight: 700;
            margin-bottom: 0.45rem;
        }
        .step p { font-size: 0.865rem; color: var(--ink-2); line-height: 1.62; }

        .reveal { opacity: 0; transform: translateY(28px); }
        .reveal.in { animation: panel-in 0.8s cubic-bezier(0.22, 1, 0.36, 1) forwards; }

        .detail-cta {
            margin-top: 3rem;
            text-align: center;
            display: flex;
            gap: 0.8rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ============ Footer ============ */
        .footer {
            position: relative;
            z-index: 1;
            border-top: 1px solid var(--line);
            padding: 1.6rem clamp(1.1rem, 4vw, 3.2rem);
            text-align: center;
            font-size: 0.8rem;
            color: var(--ink-3);
        }
        .footer b { color: var(--ink-2); font-weight: 700; }

        /* ============ Responsive ============ */
        @media (max-width: 1080px) {
            .hero {
                grid-template-columns: 1fr;
                grid-template-areas: 'title' 'char' 'left' 'right';
                padding-top: 6rem;
                text-align: center;
            }
            .stage { grid-area: char; min-height: 420px; order: -1; }
            .side-panel.left  { grid-area: left; }
            .side-panel.right { grid-area: right; }
            .rimuru-wrap { max-width: 300px; margin: 0 auto; }
            .stage-glow { width: 130%; }
        }
        @media (max-width: 560px) {
            .navbar { padding: 0.85rem 1rem; }
            .brand { font-size: 1rem; }
            .btn { padding: 0.55rem 1rem; font-size: 0.84rem; }
            .side-panel { padding: 1.25rem 1.1rem; border-radius: 18px; }
            .rimuru-wrap { max-width: 250px; }
            .stage { min-height: 340px; }
            .steps { grid-template-columns: 1fr; }
            .hero-title { font-size: 2rem; }
        }
    </style>
</head>
<body>

    {{-- Background FX --}}
    <div class="bg-fx">
        <div class="bg-grid"></div>
    </div>

    {{-- Navbar --}}
    <nav class="navbar">
        <a class="brand" href="/">
            <span class="brand-mark">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2.5 21 7v10l-9 4.5L3 17V7l9-4.5Z" stroke="#4fd1ff" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M12 7.5 16.5 10v5L12 17.5 7.5 15v-5L12 7.5Z" fill="#4fd1ff" opacity="0.85"/>
                </svg>
            </span>
            Tempest KariMono
        </a>
        <div class="nav-actions">
            @guest
                <a class="btn btn-primary" href="{{ route('login') }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l-5-5 5-5M15 12H5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Login
                </a>
            @else
                @if(auth()->user()->role === 'admin')
                    <a class="btn btn-primary" href="{{ route('admin.dashboard') }}">Dashboard Admin</a>
                @elseif(auth()->user()->role === 'petugas')
                    <a class="btn btn-primary" href="{{ route('petugas.peminjaman.index') }}">Dashboard Petugas</a>
                @else
                    <a class="btn btn-primary" href="{{ route('peminjam.katalog') }}">Lihat Katalog</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button class="btn btn-ghost" type="submit">Keluar</button>
                </form>
            @endguest
        </div>
    </nav>

    {{-- Hero --}}
    <section class="hero">

        {{-- Panel kiri: penjelasan web --}}
        <aside class="side-panel left">
            <span class="panel-label">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                Tentang Web
            </span>
            <h3>Sistem Peminjaman Alat yang Rapi &amp; Cepat</h3>
            <p>
                <b>Tempest KariMono</b> mengelola peminjaman alat dari permintaan, persetujuan,
                sampai pengembalian — semua dalam satu tempat. Tidak ada lagi catatan manual
                yang mudah hilang.
            </p>
            <ul class="panel-list">
                <li>
                    <span class="tick"><svg viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="#4fd1ff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span><b>Permintaan online</b> — ajukan pinjaman alat langsung dari katalog.</span>
                </li>
                <li>
                    <span class="tick"><svg viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="#4fd1ff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span><b>Persetujuan bertingkat</b> — petugas &amp; admin verifikasi tiap pengajuan.</span>
                </li>
                <li>
                    <span class="tick"><svg viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="#4fd1ff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span><b>Riwayat lengkap</b> — lacak semua aktivitas peminjamanmu kapan saja.</span>
                </li>
            </ul>
        </aside>

        {{-- Karakter tengah --}}
        <div class="stage">
            <div class="stage-glow"></div>
            <div class="ring r1"></div>
            <div class="ring r2"></div>

            <h1 class="hero-title">Tempest <span class="grad">KariMono</span></h1>
            <p class="hero-sub">
                Platform peminjaman alat modern — cepat, transparan, dan mudah
                digunakan untuk siapa saja.
            </p>

            <div class="rimuru-wrap">
                <img class="rimuru" id="rimuru" src="{{ asset('images/rimuru/rimuru_new.webp') }}"
                     alt="Rimuru Tempest" onerror="this.onerror=null;this.src='{{ asset('images/rimuru/rimuru.png') }}'">
            </div>

            <div class="cta-row">
                @guest
                    <a class="btn btn-primary" href="{{ route('login') }}">Login Sekarang</a>
                @else
                    @if(auth()->user()->role === 'admin')
                        <a class="btn btn-primary" href="{{ route('admin.dashboard') }}">Buka Dashboard</a>
                    @elseif(auth()->user()->role === 'petugas')
                        <a class="btn btn-primary" href="{{ route('petugas.peminjaman.index') }}">Buka Dashboard</a>
                    @else
                        <a class="btn btn-primary" href="{{ route('peminjam.katalog') }}">Mulai Meminjam</a>
                    @endif
                @endguest
                <a class="btn btn-ghost" href="#detail">Pelajari lebih lanjut</a>
            </div>

            <div class="scroll-hint">
                <div class="mouse"></div>
                <span>Scroll</span>
            </div>
        </div>

        {{-- Panel kanan: kelebihan --}}
        <aside class="side-panel right">
            <span class="panel-label violet">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.3 5.9 20.6l1.4-6.8L2.2 9.1l6.9-.8L12 2z"/></svg>
                Kelebihan
            </span>
            <h3>Kenapa Memilih Tempest KariMono?</h3>
            <ul class="panel-list">
                <li>
                    <span class="tick v"><svg viewBox="0 0 24 24" fill="none"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z" stroke="#a78bfa" stroke-width="2" stroke-linejoin="round"/></svg></span>
                    <span><b>Real-time</b> — status alat &amp; pengajuan selalu mutakhir.</span>
                </li>
                <li>
                    <span class="tick v"><svg viewBox="0 0 24 24" fill="none"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z" stroke="#a78bfa" stroke-width="2" stroke-linejoin="round"/></svg></span>
                    <span><b>3 peran terpisah</b> — admin, petugas, dan peminjam punya ruang kerjanya sendiri.</span>
                </li>
                <li>
                    <span class="tick v"><svg viewBox="0 0 24 24" fill="none"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z" stroke="#a78bfa" stroke-width="2" stroke-linejoin="round"/></svg></span>
                    <span><b>Laporan &amp; ekspor</b> — data peminjaman bisa diunduh PDF &amp; Excel.</span>
                </li>
                <li>
                    <span class="tick v"><svg viewBox="0 0 24 24" fill="none"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z" stroke="#a78bfa" stroke-width="2" stroke-linejoin="round"/></svg></span>
                    <span><b>Notifikasi</b> — ingatkan jatuh tempo &amp; status pengajuan.</span>
                </li>
            </ul>
        </aside>
    </section>

    {{-- Section penjelasan lanjutan --}}
    <section class="detail" id="detail">
        <div class="detail-head reveal">
            <span class="panel-label">Cara Kerja</span>
            <h2>Empat Langkah, Selesai</h2>
            <p>Dari masuk akun sampai alat kembali, semuanya berjalan tertib dan bisa dilacak di setiap tahapnya.</p>
        </div>

        <div class="steps">
            <div class="step reveal">
                <div class="step-num">LANGKAH 01</div>
                <h4>Login ke Akun</h4>
                <p>Masuk dengan akunmu. Admin, petugas, dan peminjam langsung diarahkan ke dashboard masing-masing.</p>
            </div>
            <div class="step reveal">
                <div class="step-num">LANGKAH 02</div>
                <h4>Pilih Alat di Katalog</h4>
                <p>Jelajahi katalog, cek ketersediaan unit, lalu ajukan peminjaman untuk alat yang dibutuhkan.</p>
            </div>
            <div class="step reveal">
                <div class="step-num">LANGKAH 03</div>
                <h4>Petugas Menyetujui</h4>
                <p>Pengajuan diverifikasi petugas. Kamu mendapat notifikasi begitu statusnya berubah.</p>
            </div>
            <div class="step reveal">
                <div class="step-num">LANGKAH 04</div>
                <h4>Kembalikan &amp; Selesai</h4>
                <p>Ajukan pengembalian, petugas konfirmasi, dan riwayat peminjamanmu tersimpan rapi.</p>
            </div>
        </div>

        <div class="detail-cta reveal">
            @guest
                <a class="btn btn-primary" href="{{ route('login') }}">Mulai Sekarang</a>
                <a class="btn btn-ghost" href="#detail">Baca Panduan</a>
            @else
                @if(auth()->user()->role === 'admin')
                    <a class="btn btn-primary" href="{{ route('admin.dashboard') }}">Buka Dashboard Admin</a>
                @elseif(auth()->user()->role === 'petugas')
                    <a class="btn btn-primary" href="{{ route('petugas.peminjaman.index') }}">Buka Dashboard Petugas</a>
                @else
                    <a class="btn btn-primary" href="{{ route('peminjam.katalog') }}">Lihat Katalog Alat</a>
                @endif
            @endguest
        </div>
    </section>

    <footer class="footer">
        <b>Tempest KariMono</b> — Sistem Peminjaman Alat &middot; Dibuat untuk UJIKOM
    </footer>

    <script>
        // Karakter fade-in saat gambar siap
        (function () {
            var img = document.getElementById('rimuru');
            if (img && img.complete && img.naturalWidth > 0) {
                img.classList.add('loaded');
            } else if (img) {
                img.addEventListener('load', function () { img.classList.add('loaded'); });
            }

            // Orb partikel background
            var fx = document.querySelector('.bg-fx');
            var colors = ['#4fd1ff', '#7aa8ff', '#a78bfa'];
                        for (var i = 0; i < 18; i++) {
                            var o = document.createElement('span');
                            o.className = 'orb';
                            var s = Math.random() * 3 + 1.4;
                            o.style.width = s + 'px';
                            o.style.height = s + 'px';
                            // Jaga margin agar tidak memicu horizontal scroll
                            o.style.left = (Math.random() * 96 + 2) + 'vw';
                            o.style.bottom = (-Math.random() * 30) + 'vh';
                o.style.background = colors[i % colors.length];
                o.style.boxShadow = '0 0 8px ' + colors[i % colors.length];
                o.style.animationDuration = (Math.random() * 14 + 12) + 's';
                o.style.animationDelay = (Math.random() * 14) + 's';
                fx.appendChild(o);
            }

            // Reveal on scroll
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) {
                        var el = e.target;
                        var idx = Array.prototype.indexOf.call(el.parentNode.children, el);
                        el.style.animationDelay = (idx * 0.09) + 's';
                        el.classList.add('in');
                        io.unobserve(el);
                    }
                });
            }, { threshold: 0.14, rootMargin: '0px 0px -40px 0px' });

            document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });

            // Panel hero masuk setelah load
            requestAnimationFrame(function () {
                document.querySelectorAll('.side-panel').forEach(function (el) { el.classList.add('in'); });
            });
        })();
    </script>
</body>
</html>