@php
    $motionFlash = null;

    foreach (['success', 'error', 'warning', 'info'] as $type) {
        if (session()->has($type) && filled(session($type))) {
            $motionFlash = [
                'type' => $type,
                'message' => session($type),
            ];
            break;
        }
    }
@endphp

<style>
    @keyframes motion-page-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes motion-sidebar-in {
        from { opacity: 0; transform: translateX(-18px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @keyframes motion-nav-in {
        from { opacity: 0; transform: translateX(-8px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @keyframes motion-dropdown-in {
        from { opacity: 0; transform: translateY(-6px) scale(.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes motion-toast {
        0% { opacity: 0; transform: translateX(calc(100% + 2rem)); }
        12% { opacity: 1; transform: translateX(0); }
        78% { opacity: 1; transform: translateX(0); }
        100% { opacity: 0; transform: translateX(calc(100% + 2rem)); }
    }

    @keyframes motion-toast-close {
        to { opacity: 0; transform: translateX(calc(100% + 2rem)); }
    }

    @keyframes motion-login-in {
        from { opacity: 0; transform: translateY(18px) scale(.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes motion-field-in {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .motion-page {
        animation: motion-page-in .42s ease-out both;
    }

    .motion-sidebar {
        animation: motion-sidebar-in .45s ease-out both;
    }

    .motion-sidebar-nav a {
        animation: motion-nav-in .35s ease-out both;
        animation-delay: var(--nav-delay, 0ms);
        transition: transform .2s ease, background-color .2s ease, color .2s ease;
    }

    .motion-sidebar-nav a:hover,
    .motion-sidebar-nav a:focus-visible {
        transform: translateX(4px);
    }

    .motion-main {
        animation: motion-page-in .42s ease-out .08s both;
    }

    .motion-topbar {
        animation: motion-page-in .35s ease-out both;
    }

    details[open] > div {
        animation: motion-dropdown-in .2s ease-out both;
        transform-origin: top right;
    }

    .motion-toast {
        --toast-accent: #2563eb;
        --toast-surface: #eff6ff;
        position: fixed;
        top: 1.25rem;
        right: 1.25rem;
        z-index: 1000;
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        width: min(24rem, calc(100vw - 2rem));
        padding: .9rem 1rem;
        color: #1f2937;
        background: #fff;
        border: 1px solid #dbe3ef;
        border-top: 3px solid var(--toast-accent);
        border-radius: .75rem;
        box-shadow: 0 14px 35px rgba(15, 23, 42, .18);
        animation: motion-toast 5.5s cubic-bezier(.22, .8, .25, 1) forwards;
    }

    .motion-toast--success {
        --toast-accent: #059669;
        --toast-surface: #ecfdf5;
    }

    .motion-toast--error {
        --toast-accent: #dc2626;
        --toast-surface: #fef2f2;
    }

    .motion-toast--warning {
        --toast-accent: #d97706;
        --toast-surface: #fffbeb;
    }

    .motion-toast--info {
        --toast-accent: #2563eb;
        --toast-surface: #eff6ff;
    }

    .motion-toast.is-closing {
        animation: motion-toast-close .32s ease-in forwards;
    }

    .motion-toast__icon {
        display: grid;
        flex: 0 0 2rem;
        width: 2rem;
        height: 2rem;
        place-items: center;
        color: var(--toast-accent);
        background: var(--toast-surface);
        border-radius: .5rem;
        font-weight: 800;
    }

    .motion-toast__content {
        min-width: 0;
        flex: 1;
    }

    .motion-toast__title {
        margin: 0;
        color: #111827;
        font-size: .875rem;
        font-weight: 700;
    }

    .motion-toast__message {
        margin: .2rem 0 0;
        color: #4b5563;
        font-size: .8125rem;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .motion-toast__close {
        min-width: 2rem;
        min-height: 2rem;
        padding: 0;
        color: #6b7280;
        background: transparent;
        border: 0;
        border-radius: .375rem;
        cursor: pointer;
        font-size: 1.25rem;
        line-height: 1;
    }

    .motion-toast__close:hover,
    .motion-toast__close:focus-visible {
        color: #111827;
        background: #f3f4f6;
    }

    .motion-toast__close:focus-visible,
    .motion-sidebar-nav a:focus-visible,
    .motion-login-form input:focus-visible,
    .motion-login-form button:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .35);
        outline-offset: 2px;
    }

    .motion-login-card {
        animation: motion-login-in .5s cubic-bezier(.22, .8, .25, 1) both;
    }

    .motion-login-form .motion-field {
        animation: motion-field-in .35s ease-out both;
    }

    .motion-login-form .motion-field:nth-child(2) {
        animation-delay: .08s;
    }

    .motion-submit {
        transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease;
    }

    .motion-submit:hover,
    .motion-submit:focus-visible {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(37, 99, 235, .22);
    }

    .motion-submit.is-submitting {
        cursor: wait;
        opacity: .8;
    }

    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
            scroll-behavior: auto !important;
            transition-duration: .01ms !important;
        }

        .motion-toast {
            animation: none !important;
            opacity: 1 !important;
            transform: none !important;
        }
    }

    @media (max-width: 640px) {
        .motion-toast {
            top: .75rem;
            right: .75rem;
            width: calc(100vw - 1.5rem);
        }
    }
</style>

@if($motionFlash)
    @php
        $motionTitle = match ($motionFlash['type']) {
            'success' => 'Berhasil',
            'error' => 'Gagal',
            'warning' => 'Perhatian',
            default => 'Informasi',
        };
    @endphp

    <div
        id="app-toast"
        class="motion-toast motion-toast--{{ $motionFlash['type'] }}"
        role="{{ $motionFlash['type'] === 'error' ? 'alert' : 'status' }}"
        aria-live="polite"
        data-message="{{ $motionFlash['message'] }}"
    >
        <span class="motion-toast__icon" aria-hidden="true">
            @if($motionFlash['type'] === 'success')
                ✓
            @elseif($motionFlash['type'] === 'error')
                !
            @elseif($motionFlash['type'] === 'warning')
                !
            @else
                i
            @endif
        </span>

        <div class="motion-toast__content">
            <p class="motion-toast__title">{{ $motionTitle }}</p>
            <p class="motion-toast__message">{{ $motionFlash['message'] }}</p>
        </div>

        <button type="button" class="motion-toast__close" aria-label="Tutup notifikasi">×</button>
    </div>
@endif

<script>
    (() => {
        const initMotion = () => {
            document.querySelectorAll('.motion-sidebar-nav a').forEach((link, index) => {
                link.style.setProperty('--nav-delay', `${index * 35}ms`);
            });

            document.querySelectorAll('.motion-login-form').forEach((form) => {
                form.addEventListener('submit', () => {
                    form.querySelector('.motion-submit')?.classList.add('is-submitting');
                });
            });

            const toast = document.getElementById('app-toast');

            if (!toast) {
                return;
            }

            const message = toast.dataset.message?.replace(/\s+/g, ' ').trim();
            const closeButton = toast.querySelector('.motion-toast__close');

            if (message) {
                const inlineAlert = [...document.querySelectorAll('div')]
                    .filter((element) => {
                        const classes = element.className;
                        const text = element.textContent.replace(/\s+/g, ' ').trim();

                        return typeof classes === 'string'
                            && /bg-(emerald|green|red|amber|blue)-(50|100)/.test(classes)
                            && text.includes(message);
                    })
                    .sort((first, second) => first.textContent.length - second.textContent.length)[0];

                inlineAlert?.remove();
            }

            closeButton?.addEventListener('click', () => {
                toast.classList.add('is-closing');
                toast.addEventListener('animationend', () => toast.remove(), { once: true });
            });

            toast.addEventListener('animationend', (event) => {
                if (event.animationName === 'motion-toast') {
                    toast.remove();
                }
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMotion, { once: true });
        } else {
            initMotion();
        }
    })();
</script>
