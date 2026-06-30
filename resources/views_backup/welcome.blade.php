<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ATUR — Align. Track. Update. Result.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --black: #0a0a0a;
            --white: #ffffff;
            --gray-50: #f9f9f9;
            --gray-100: #f0f0f0;
            --gray-200: #e5e5e5;
            --gray-300: #d4d4d4;
            --gray-400: #a3a3a3;
            --gray-500: #737373;
            --gray-700: #404040;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--white);
            color: var(--black);
            overflow-x: hidden;
        }

        /* ===== NAVBAR ===== */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 1.1rem 3rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }

        .nav-logo {
            display: flex;
            align-items: baseline;
            gap: 0.4rem;
            text-decoration: none;
        }

        .nav-logo-word {
            font-family: 'Instrument Serif', serif;
            font-size: 1.5rem;
            color: var(--black);
            letter-spacing: 0.08em;
        }

        .nav-logo-tagline {
            font-size: 0.65rem;
            font-weight: 500;
            color: var(--gray-500);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-700);
            text-decoration: none;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--black);
        }

        .nav-cta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-ghost {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-700)
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .btn-ghost:hover {
            background: rgba(0, 0, 0, 0.05)
        }

        .btn-primary {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--black);
            background: var(--white);
            text-decoration: none;
            padding: 0.55rem 1.25rem;
            border-radius: 6px;
            transition: opacity 0.2s, transform 0.2s;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* ===== HERO ===== */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 9rem 2rem 5rem;
            position: relative;
            overflow: hidden;

            /* BACKGROUND PUTIH POLOS */
            background: var(#ffffff);
        }

        /* Overlay gelap agar teks tetap terbaca */
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom,
                    rgba(255, 255, 255, 0.95) 0%,
                    rgba(255, 255, 255, 0.85) 40%,
                    rgba(96, 165, 250, 0.80) 100%);
                z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 820px;
            margin: 0 auto;
        }

        /* Akronim A.T.U.R */
        .acronym-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            opacity: 0;
            animation: fadeUp 0.6s ease 0.1s forwards;
        }

        .acronym-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.2rem;
        }

        .acronym-letter {
            font-family: 'Instrument Serif', serif;
            font-size: 1.1rem;
            color: var(--black);
            width: 2.2rem;
            height: 2.2rem;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(4px);
        }

        .acronym-word {
            font-size: 0.6rem;
            font-weight: 500;
            color: var(--gray-700);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }

        .acronym-dot {
            font-size: 1rem;
            color: var(--gray-400);
            margin-bottom: 1.2rem;
            align-self: flex-start;
            margin-top: 0.5rem;
        }

        .hero-title {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(2.8rem, 6.5vw, 5rem);
            line-height: 1.08;
            letter-spacing: -0.03em;
            color: var(--black);
            margin-bottom: 1.5rem;
            opacity: 0;
            animation: fadeUp 0.7s ease 0.3s forwards;
            text-shadow: none;
        }

        .hero-title em {
            font-style: italic;
            color: var(--gray-500);
        }

        .hero-desc {
            font-size: 1rem;
            font-weight: 400;
            color: var(--gray-700);
            line-height: 1.75;
            max-width: 560px;
            margin: 0 auto 2.5rem;
            opacity: 0;
            animation: fadeUp 0.7s ease 0.45s forwards;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            opacity: 0;
            animation: fadeUp 0.7s ease 0.6s forwards;
        }

        .btn-hero-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--black);
            background: var(--white);
            text-decoration: none;
            padding: 0.8rem 1.75rem;
            border-radius: 8px;
            transition: opacity 0.2s, transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
        }

        .btn-hero-primary:hover {
            opacity: 0.92;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }

        .btn-hero-primary svg {
            transition: transform 0.2s;
        }

        .btn-hero-primary:hover svg {
            transform: translateX(3px);
        }

        .btn-hero-outline {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--black);
            text-decoration: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(4px);
            transition: border-color 0.2s, transform 0.2s;
            background: transparent;
        }

        .btn-hero-outline:hover {
            background: rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.5);
            transform: translateY(-1px);
        }

        /* ===== APP MOCKUP ===== */
        .hero-visual {
            position: relative;
            z-index: 2;
            margin-top: 4.5rem;
            max-width: 920px;
            width: 100%;
            opacity: 0;
            animation: fadeUp 0.9s ease 0.75s forwards;
        }

        .mockup-frame {
            background: var(--white);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            overflow: hidden;
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.1),
                0 24px 60px rgba(0, 0, 0, 0.4),
                0 48px 100px rgba(0, 0, 0, 0.2);
        }

        .mockup-topbar {
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-100);
            padding: 0.7rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .dot.r {
            background: #ff5f57;
        }

        .dot.y {
            background: #febc2e;
        }

        .dot.g {
            background: #28c840;
        }

        .mockup-url {
            margin-left: 0.75rem;
            background: var(--gray-200);
            border-radius: 4px;
            padding: 0.2rem 0.75rem;
            font-size: 0.72rem;
            color: var(--gray-500);
            font-family: monospace;
        }

        .mockup-body {
            display: grid;
            grid-template-columns: 190px 1fr;
            min-height: 300px;
        }

        .mockup-sidebar {
            background: #d6cfbf;
            border-right: 1px solid rgba(0, 0, 0, 0.08);
            padding: 1.25rem 0.9rem;
        }

        .mockup-brand {
            font-family: 'Instrument Serif', serif;
            font-size: 1.1rem;
            letter-spacing: 0.06em;
            color: var(--black);
            margin-bottom: 1.5rem;
            padding-left: 0.25rem;
        }

        .m-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.72rem;
            color: #5a5248;
            padding: 0.4rem 0.6rem;
            border-radius: 6px;
            margin-bottom: 0.2rem;
        }

        .m-nav.active {
            background: rgba(255, 255, 255, 0.55);
            font-weight: 600;
            color: var(--black);
        }

        .m-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.5;
            flex-shrink: 0;
        }

        .m-nav.active .m-dot {
            opacity: 1;
        }

        .mockup-main {
            padding: 1.25rem 1.5rem;
            background: #f8f8f8;
        }

        .m-section-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--black);
            margin-bottom: 0.85rem;
        }

        .m-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.6rem;
            margin-bottom: 0.85rem;
        }

        .m-card {
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: 8px;
            padding: 0.65rem 0.75rem;
        }

        .m-card-label {
            font-size: 0.6rem;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.2rem;
        }

        .m-card-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--black);
        }

        .m-progress {
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.6rem;
        }

        .m-progress-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.65rem;
            color: var(--gray-500);
            margin-bottom: 0.5rem;
        }

        .m-bar-bg {
            height: 5px;
            background: var(--gray-100);
            border-radius: 999px;
            overflow: hidden;
        }

        .m-bar-fill {
            height: 100%;
            background: var(--black);
            border-radius: 999px;
        }

        .m-tasks {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
        }

        .m-task {
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: 8px;
            padding: 0.65rem 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .m-task-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .m-task-text {
            font-size: 0.65rem;
            color: var(--gray-700);
            font-weight: 500;
        }

        .m-task-badge {
            margin-left: auto;
            font-size: 0.55rem;
            padding: 0.15rem 0.4rem;
            border-radius: 999px;
            font-weight: 600;
        }

        /* ===== STATS ===== */
        .hero-stats {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 3rem;
            margin-top: 3.5rem;
            padding-top: 2.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            opacity: 0;
            animation: fadeUp 0.7s ease 1s forwards;
            flex-wrap: wrap;
            justify-content: center;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-family: 'Instrument Serif', serif;
            font-size: 2rem;
            color: var(--white);
            letter-spacing: -0.02em;
        }

        .stat-label {
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.55);
            margin-top: 0.2rem;
            font-weight: 500;
        }

        .stat-divider {
            width: 1px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(22px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            nav {
                padding: 1rem 1.25rem;
            }

            .nav-links {
                display: none;
            }

            .mockup-sidebar {
                display: none;
            }

            .mockup-body {
                grid-template-columns: 1fr;
            }

            .m-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-divider {
                display: none;
            }

            .acronym-dot {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav>
        <a href="#" class="nav-logo">
            <span class="nav-logo-word">ATUR</span>
            <span class="nav-logo-tagline">Align · Track · Update · Result</span>
        </a>
        <ul class="nav-links">
            <li><a href="#">Fitur</a></li>
            <li><a href="#">Cara Kerja</a></li>
            <li><a href="#">Tentang</a></li>
        </ul>
        <div class="nav-cta">
            <a href="/login" class="btn-ghost">Masuk</a>
            <a href="/register" class="btn-primary">Mulai Gratis</a>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-content">

            <!-- Akronim A.T.U.R -->
            <div class="acronym-row">
                <div class="acronym-item">
                    <div class="acronym-letter">A</div>
                    <div class="acronym-word">Align</div>
                </div>
                <div class="acronym-dot">·</div>
                <div class="acronym-item">
                    <div class="acronym-letter">T</div>
                    <div class="acronym-word">Track</div>
                </div>
                <div class="acronym-dot">·</div>
                <div class="acronym-item">
                    <div class="acronym-letter">U</div>
                    <div class="acronym-word">Update</div>
                </div>
                <div class="acronym-dot">·</div>
                <div class="acronym-item">
                    <div class="acronym-letter">R</div>
                    <div class="acronym-word">Result</div>
                </div>
            </div>

            <h1 class="hero-title">
                Kerja tidak lagi sekadar sibuk—<br>
                <em>tetapi terarah dan tertata.</em>
            </h1>

            <p class="hero-desc">
                ATUR adalah platform manajemen proyek dan tugas yang membantu tim menyusun pekerjaan, mengelola
                prioritas, dan memantau progres secara terpusat dan terstruktur.
            </p>

            <div class="hero-actions">
                <a href="/register" class="btn-hero-primary">
                    Mulai Gratis
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </a>
                <a href="/login" class="btn-hero-outline">
                    Sudah punya akun? Masuk
                </a>
            </div>
        </div>

        <!-- APP MOCKUP -->
        <div class="hero-visual">
            <div class="mockup-frame">
                <div class="mockup-topbar">
                    <div class="dot r"></div>
                    <div class="dot y"></div>
                    <div class="dot g"></div>
                    <div class="mockup-url">atur.app/dashboard</div>
                </div>
                <div class="mockup-body">
                    <div class="mockup-sidebar">
                        <div class="mockup-brand">ATUR</div>
                        <div class="m-nav active">
                            <div class="m-dot"></div> Dashboard
                        </div>
                        <div class="m-nav">
                            <div class="m-dot"></div> Workspaces
                        </div>
                        <div class="m-nav">
                            <div class="m-dot"></div> Projects
                        </div>
                        <div class="m-nav">
                            <div class="m-dot"></div> My Tasks
                        </div>
                        <div style="margin-top:1rem; padding-top:1rem; border-top: 1px solid rgba(0,0,0,0.1);">
                            <div class="m-nav">
                                <div class="m-dot"></div> Pengaturan
                            </div>
                        </div>
                    </div>
                    <div class="mockup-main">
                        <div class="m-section-title">Dashboard</div>
                        <div class="m-cards">
                            <div class="m-card">
                                <div class="m-card-label">Workspaces</div>
                                <div class="m-card-value">4</div>
                            </div>
                            <div class="m-card">
                                <div class="m-card-label">Projects</div>
                                <div class="m-card-value">12</div>
                            </div>
                            <div class="m-card">
                                <div class="m-card-label">Tasks</div>
                                <div class="m-card-value">48</div>
                            </div>
                            <div class="m-card">
                                <div class="m-card-label">Selesai</div>
                                <div class="m-card-value">31</div>
                            </div>
                        </div>
                        <div class="m-progress">
                            <div class="m-progress-header">
                                <span>Overall Progress</span>
                                <span style="font-weight:600; color:#0a0a0a;">65%</span>
                            </div>
                            <div class="m-bar-bg">
                                <div class="m-bar-fill" style="width:65%"></div>
                            </div>
                        </div>
                        <div class="m-tasks">
                            <div class="m-task">
                                <div class="m-task-dot" style="background:#22c55e"></div>
                                <div class="m-task-text">Design UI Dashboard</div>
                                <div class="m-task-badge" style="background:#dcfce7;color:#15803d;">Done</div>
                            </div>
                            <div class="m-task">
                                <div class="m-task-dot" style="background:#3b82f6"></div>
                                <div class="m-task-text">API Integration</div>
                                <div class="m-task-badge" style="background:#dbeafe;color:#1d4ed8;">Active</div>
                            </div>
                            <div class="m-task">
                                <div class="m-task-dot" style="background:#f59e0b"></div>
                                <div class="m-task-text">User Testing</div>
                                <div class="m-task-badge" style="background:#fef3c7;color:#92400e;">Review</div>
                            </div>
                            <div class="m-task">
                                <div class="m-task-dot" style="background:#e5e5e5"></div>
                                <div class="m-task-text">Dokumentasi</div>
                                <div class="m-task-badge" style="background:#f0f0f0;color:#737373;">To Do</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>

</body>

</html>
