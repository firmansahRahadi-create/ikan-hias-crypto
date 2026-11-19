<?php
declare(strict_types=1);
require_once __DIR__ . '/koneksi.php';

$katalogIkan = [];
$validKeyPath = __DIR__ . '/keys/admin_private_key.pem';
$serverPrivKey = file_exists($validKeyPath) ? file_get_contents($validKeyPath) : '';

$res = $conn->query("SELECT * FROM produk_ikan ORDER BY id DESC");
if ($res && $serverPrivKey !== '') {
    while ($row = $res->fetch_assoc()) {
        $dec = decryptHybridAllFields($row, $serverPrivKey);
        if ($dec['status']) {
            $dec['id'] = $row['id'];
            $dec['cipher_preview'] = substr($row['enc_nama_ikan'], 0, 28) . '...';
            $katalogIkan[] = $dec;
        }
    }
}

function getFishSvgArtwork(string $kategori): string
{
    $kat = strtolower($kategori);
    if (str_contains($kat, 'predator') || str_contains($kat, 'arwana')) {
        return '<svg viewBox="0 0 240 110" class="fish-svg" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="arwanaGrad" x1="20" y1="20" x2="220" y2="90" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#F59E0B"/>
                    <stop offset="0.5" stop-color="#EF4444"/>
                    <stop offset="1" stop-color="#B91C1C"/>
                </linearGradient>
            </defs>
            <path d="M35 55C65 25 135 22 185 48L222 30L210 55L224 80L185 62C135 88 65 85 35 55Z" fill="url(#arwanaGrad)"/>
            <path d="M18 55L42 40V70L18 55Z" fill="#F59E0B"/>
            <path d="M85 33C110 15 145 18 165 36" stroke="#D97706" stroke-width="3" stroke-linecap="round"/>
            <path d="M90 77C115 94 148 90 168 73" stroke="#DC2626" stroke-width="3" stroke-linecap="round"/>
            <circle cx="176" cy="49" r="4" fill="#FEF08A"/>
            <circle cx="177" cy="49" r="2" fill="#0F172A"/>
        </svg>';
    }
    if (str_contains($kat, 'kontes') || str_contains($kat, 'cupang')) {
        return '<svg viewBox="0 0 240 110" class="fish-svg" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="bettaGrad" x1="30" y1="15" x2="200" y2="95" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#0284C7"/>
                    <stop offset="0.5" stop-color="#4F46E5"/>
                    <stop offset="1" stop-color="#9333EA"/>
                </linearGradient>
            </defs>
            <path d="M120 55C120 15 55 10 35 55C55 100 120 95 120 55Z" fill="url(#bettaGrad)" opacity="0.72"/>
            <path d="M95 55C120 35 165 35 192 55C165 75 120 75 95 55Z" fill="url(#bettaGrad)"/>
            <path d="M125 38C135 16 162 18 172 40" stroke="#0284C7" stroke-width="2.5"/>
            <circle cx="178" cy="52" r="3.5" fill="#FFFFFF"/>
            <circle cx="179" cy="52" r="1.8" fill="#0F172A"/>
        </svg>';
    }
    return '<svg viewBox="0 0 240 110" class="fish-svg" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="koiGrad" x1="30" y1="30" x2="210" y2="80" gradientUnits="userSpaceOnUse">
                <stop stop-color="#FFFFFF"/>
                <stop offset="1" stop-color="#E2E8F0"/>
            </linearGradient>
        </defs>
        <path d="M45 55C75 28 140 28 188 55C140 82 75 82 45 55Z" fill="url(#koiGrad)" stroke="#94A3B8" stroke-width="1.5"/>
        <path d="M22 36L52 55L22 74L32 55L22 36Z" fill="#F97316"/>
        <ellipse cx="115" cy="48" rx="22" ry="11" fill="#EF4444"/>
        <ellipse cx="155" cy="56" rx="15" ry="9" fill="#EF4444"/>
        <circle cx="175" cy="50" r="3.5" fill="#0F172A"/>
    </svg>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nusantara AquaVault — Galeri Ikan Hias & Kriptografi Hybrid</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-page: #f8fafc;
            --bg-white: #ffffff;
            --border: #e2e8f0;
            --border-accent: #7dd3fc;
            --primary: #0284c7;
            --cyan: #06b6d4;
            --emerald: #059669;
            --text-dark: #0f172a;
            --text-muted: #475569;
            --shadow-sm: 0 4px 16px rgba(15, 23, 42, 0.04);
            --shadow-lg: 0 20px 45px rgba(15, 23, 42, 0.08);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-dark);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Soft Ambient Light Orbs */
        .ambient-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(110px);
            z-index: -1;
            pointer-events: none;
        }
        .orb-1 { width: 520px; height: 520px; background: rgba(14, 165, 233, 0.12); top: -140px; left: -100px; }
        .orb-2 { width: 460px; height: 460px; background: rgba(20, 184, 166, 0.10); bottom: 5%; right: -120px; }

        /* Floating White Navbar */
        .navbar {
            position: sticky;
            top: 16px;
            z-index: 100;
            max-width: 1240px;
            margin: 16px auto 0;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-dark);
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 20px;
            letter-spacing: -0.3px;
        }
        .brand-logo {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0284c7, #0d9488);
            display: grid;
            place-items: center;
            color: #ffffff;
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.25);
        }
        .nav-menu { display: flex; align-items: center; gap: 28px; }
        .nav-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s;
        }
        .nav-link:hover { color: var(--primary); }
        .btn-vault {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: #ffffff;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 11px;
            font-size: 13.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.25);
            transition: all 0.25s;
        }
        .btn-vault:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(2, 132, 199, 0.35);
        }

        /* Hero Section */
        .hero {
            max-width: 1240px;
            margin: 0 auto;
            padding: 68px 20px 54px;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 48px;
            align-items: center;
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            color: #0369a1;
            font-size: 12.5px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .hero h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 48px;
            line-height: 1.14;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 20px;
            color: var(--text-dark);
        }
        .hero h1 span {
            background: linear-gradient(135deg, #0284c7, #0d9488);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p {
            color: var(--text-muted);
            font-size: 16px;
            max-width: 540px;
            margin-bottom: 32px;
        }
        .hero-cta { display: flex; gap: 14px; flex-wrap: wrap; }
        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 11px;
            border: 1px solid var(--border);
            background: var(--bg-white);
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
        }
        .btn-ghost:hover { border-color: var(--primary); color: var(--primary); }

        /* Right Vault Preview Card */
        .vault-widget {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 28px;
            box-shadow: var(--shadow-lg);
        }
        .vault-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 18px;
        }
        .pulse-dot {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            color: var(--emerald);
            background: #ecfdf5;
            padding: 4px 10px;
            border-radius: 999px;
        }
        .pulse-dot::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
        }
        .cipher-ticker {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11.5px;
            color: #0369a1;
            margin-bottom: 18px;
        }
        .metric-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .metric-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 16px;
        }
        .metric-item strong {
            display: block;
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            color: var(--text-dark);
        }
        .metric-item span { font-size: 12px; color: var(--text-muted); }

        /* Catalog Section */
        .catalog-wrap {
            max-width: 1240px;
            margin: 0 auto;
            padding: 36px 20px 90px;
        }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }
        .toolbar h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 30px;
            font-weight: 800;
            color: var(--text-dark);
        }
        .controls {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        .search-box { position: relative; }
        .search-box input {
            background: var(--bg-white);
            border: 1px solid var(--border);
            color: var(--text-dark);
            padding: 10px 14px 10px 38px;
            border-radius: 11px;
            font-size: 13.5px;
            width: 240px;
            outline: none;
            box-shadow: var(--shadow-sm);
        }
        .search-box input:focus { border-color: var(--primary); }
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            width: 16px;
            height: 16px;
        }

        .tabs { display: flex; gap: 8px; flex-wrap: wrap; }
        .tab-btn {
            background: var(--bg-white);
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab-btn.active, .tab-btn:hover {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            font-weight: 700;
        }

        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
        }
        .product-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .product-card:hover {
            transform: translateY(-6px);
            border-color: var(--border-accent);
            box-shadow: var(--shadow-lg);
        }
        .card-visual {
            height: 165px;
            background: linear-gradient(135deg, #e0f2fe 0%, #ccfbf1 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .fish-svg { width: 190px; height: 95px; filter: drop-shadow(0 8px 14px rgba(15, 23, 42, 0.14)); }
        .cat-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid #bae6fd;
            color: #0369a1;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 11px;
            border-radius: 999px;
        }
        .enc-badge {
            position: absolute;
            top: 14px;
            right: 14px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
            font-size: 10.5px;
            font-weight: 700;
            padding: 4px 9px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .card-content { padding: 22px; flex: 1; display: flex; flex-direction: column; }
        .origin-line {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: var(--emerald);
            font-weight: 700;
            margin-bottom: 6px;
        }
        .card-title {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        .card-desc {
            font-size: 13.5px;
            color: var(--text-muted);
            margin-bottom: 20px;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .card-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
        }
        .price-tag {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
        }
        .btn-detail {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            color: #0369a1;
            padding: 7px 14px;
            border-radius: 9px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-detail:hover { background: var(--primary); color: #ffffff; border-color: var(--primary); }

        /* Modal Detail */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(6px);
            display: none;
            place-items: center;
            z-index: 999;
            padding: 20px;
        }
        .modal-backdrop.open { display: grid; }
        .modal-box {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 22px;
            max-width: 520px;
            width: 100%;
            padding: 28px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.2);
        }

        footer {
            background: #ffffff;
            border-top: 1px solid var(--border);
            text-align: center;
            padding: 28px 20px;
            color: var(--text-muted);
            font-size: 13px;
        }

        @media (max-width: 920px) {
            .hero { grid-template-columns: 1fr; padding-top: 40px; }
            .hero h1 { font-size: 36px; }
            .nav-menu a.nav-link { display: none; }
        }
    </style>
</head>
<body>

    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>

    <header class="navbar">
        <a href="index.php" class="brand">
            <div class="brand-logo"><i data-lucide="waves" size="20"></i></div>
            <span>Nusantara<span style="color:var(--primary)">AquaVault</span></span>
        </a>
        <nav class="nav-menu">
            <a href="#katalog" class="nav-link">Katalog Koleksi</a>
            <a href="#arsitektur" class="nav-link">Arsitektur Keamanan</a>
            <a href="login.php" class="btn-vault">
                <i data-lucide="key-round" size="16"></i> Portal Admin (Key Auth)
            </a>
        </nav>
    </header>

    <section class="hero">
        <div>
            <div class="eyebrow">
                <i data-lucide="shield-check" size="15"></i>
                Sistem Informasi Ikan Hias Indonesia • PHP 8.4
            </div>
            <h1>Kurasi Ikan Hias Eksotis dengan <span>Proteksi Kriptografi Hybrid</span></h1>
            <p>
                Platform distribusi ikan hias nusantara—mulai dari Arwana Super Red Kapuas hingga Cupang Kontes Blitar. Seluruh data inventaris admin dienkripsi penuh menggunakan <strong>AES-256-CBC & RSA-2048</strong>.
            </p>
            <div class="hero-cta">
                <a href="#katalog" class="btn-vault">
                    <i data-lucide="compass" size="16"></i> Jelajahi Katalog
                </a>
                <a href="login.php" class="btn-ghost">
                    <i data-lucide="file-key-2" size="16"></i> Masuk via File Kunci (.pem)
                </a>
            </div>
        </div>

        <div class="vault-widget" id="arsitektur">
            <div class="vault-header">
                <span style="font-weight:700; font-size:14px; display:flex; align-items:center; gap:8px; color:#0f172a;">
                    <i data-lucide="cpu" size="17" color="#0284c7"></i> Cryptographic Engine
                </span>
                <span class="pulse-dot">OpenSSL Active</span>
            </div>

            <div class="cipher-ticker">
                <div style="color:#64748b; font-size:10.5px; margin-bottom:4px;">// SAMPLE HYBRID CIPHERTEXT IN MYSQL</div>
                AES-256: U2FsdGVkX19+8xK9mP2vL0qR7wT4zN1...<br>
                RSA-2048: mQENBF9x2kABCAC7pL9vQ3rT8wY1zX4...
            </div>

            <div class="metric-row">
                <div class="metric-item">
                    <strong>AES-256</strong>
                    <span>Enkripsi Simetris Kolom</span>
                </div>
                <div class="metric-item">
                    <strong>RSA-2048</strong>
                    <span>Auth File Kunci & Key Wrap</span>
                </div>
                <div class="metric-item">
                    <strong><?= count($katalogIkan) ?> Spesies</strong>
                    <span>Tersedia di Katalog</span>
                </div>
                <div class="metric-item">
                    <strong>Zero-Pass</strong>
                    <span>Autentikasi Tanpa Password</span>
                </div>
            </div>
        </div>
    </section>

    <main class="catalog-wrap" id="katalog">
        <div class="toolbar">
            <div>
                <h2>Koleksi Ikan Hias Nusantara</h2>
                <p style="color:var(--text-muted); font-size:14px;">Didekripsi langsung dari tabel terenkripsi <code>produk_ikan</code>.</p>
            </div>

            <div class="controls">
                <div class="search-box">
                    <i data-lucide="search"></i>
                    <input type="text" id="searchInput" placeholder="Cari nama atau asal ikan..." onkeyup="applyFilters()">
                </div>

                <div class="tabs">
                    <button class="tab-btn active" onclick="setCategory('all', this)">Semua</button>
                    <button class="tab-btn" onclick="setCategory('predator', this)">Predator</button>
                    <button class="tab-btn" onclick="setCategory('kontes', this)">Kontes</button>
                    <button class="tab-btn" onclick="setCategory('kolam', this)">Kolam</button>
                </div>
            </div>
        </div>

        <div class="grid-cards" id="cardsContainer">
            <?php foreach ($katalogIkan as $ikan): ?>
                <article class="product-card"
                         data-kategori="<?= htmlspecialchars(strtolower($ikan['kategori'])) ?>"
                         data-search="<?= htmlspecialchars(strtolower($ikan['nama'] . ' ' . $ikan['asal'])) ?>">
                    <div class="card-visual">
                        <span class="cat-badge"><?= htmlspecialchars($ikan['kategori']) ?></span>
                        <span class="enc-badge"><i data-lucide="lock" size="11"></i> AES+RSA</span>
                        <?= getFishSvgArtwork($ikan['kategori']) ?>
                    </div>
                    <div class="card-content">
                        <div class="origin-line">
                            <i data-lucide="map-pin" size="13"></i> <?= htmlspecialchars($ikan['asal']) ?>
                        </div>
                        <h3 class="card-title"><?= htmlspecialchars($ikan['nama']) ?></h3>
                        <p class="card-desc"><?= htmlspecialchars($ikan['deskripsi']) ?></p>

                        <div class="card-bottom">
                            <div>
                                <div class="price-tag">Rp <?= number_format((float)$ikan['harga'], 0, ',', '.') ?></div>
                                <span style="font-size:12px; color:var(--emerald); font-weight:700;">Stok: <?= htmlspecialchars($ikan['stok']) ?> ekor</span>
                            </div>
                            <button class="btn-detail" onclick='openDetailModal(<?= json_encode($ikan, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                Detail &rarr;
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Modal Detail Ikan -->
    <div class="modal-backdrop" id="detailModal" onclick="if(event.target===this) closeDetailModal()">
        <div class="modal-box">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                <span id="mKategori" style="font-size:12px; font-weight:700; color:#0369a1; background:#e0f2fe; padding:4px 12px; border-radius:999px;"></span>
                <button onclick="closeDetailModal()" style="background:none; border:none; color:#64748b; cursor:pointer; font-size:22px;">&times;</button>
            </div>
            <h3 id="mNama" style="font-family:'Outfit',sans-serif; font-size:24px; color:#0f172a; margin-bottom:6px;"></h3>
            <p id="mAsal" style="font-size:13px; color:#059669; font-weight:700; margin-bottom:14px;"></p>
            <p id="mDeskripsi" style="font-size:14px; color:#334155; background:#f8fafc; padding:14px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:16px;"></p>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-size:12px; color:var(--text-muted); display:block;">Harga Penawaran</span>
                    <strong id="mHarga" style="font-family:'Outfit',sans-serif; font-size:22px; color:#0284c7;"></strong>
                </div>
                <span id="mStok" style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; padding:6px 14px; border-radius:10px; font-weight:700; font-size:13px;"></span>
            </div>
        </div>
    </div>

    <footer>
        &copy; 2026 Nusantara AquaVault Indonesia — Tugas UTS Sistem Informasi Kriptografi Simetris & Asimetris (PHP 8.4).
    </footer>

    <script>
        lucide.createIcons();
        let activeCategory = 'all';

        function setCategory(cat, btn) {
            activeCategory = cat;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilters();
        }

        function applyFilters() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.product-card').forEach(card => {
                const kat = card.getAttribute('data-kategori');
                const searchText = card.getAttribute('data-search');
                const matchCat = activeCategory === 'all' || kat.includes(activeCategory);
                const matchSearch = searchText.includes(query);
                card.style.display = (matchCat && matchSearch) ? 'flex' : 'none';
            });
        }

        function openDetailModal(data) {
            document.getElementById('mKategori').textContent = data.kategori;
            document.getElementById('mNama').textContent = data.nama;
            document.getElementById('mAsal').textContent = 'Asal Budidaya: ' + data.asal;
            document.getElementById('mDeskripsi').textContent = data.deskripsi;
            document.getElementById('mHarga').textContent = 'Rp ' + Number(data.harga).toLocaleString('id-ID');
            document.getElementById('mStok').textContent = 'Tersedia: ' + data.stok + ' Ekor';
            document.getElementById('detailModal').classList.add('open');
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.remove('open');
        }
    </script>
</body>
</html>