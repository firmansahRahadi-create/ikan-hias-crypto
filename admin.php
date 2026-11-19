<?php
declare(strict_types=1);
require_once __DIR__ . '/koneksi.php';

if (!isset($_SESSION['crypto_admin_auth']) || $_SESSION['crypto_admin_auth'] !== true || empty($_SESSION['admin_private_key'])) {
    header("Location: login.php");
    exit;
}

$isValidKey      = (bool)($_SESSION['is_valid_key'] ?? false);
$adminPrivateKey = (string)$_SESSION['admin_private_key'];
$keyFilename     = (string)($_SESSION['key_filename'] ?? 'unknown.pem');
$failedCryptoLog = $_SESSION['failed_login_crypto'] ?? null;

// 1. Tambah Produk Ikan Hias (Hanya aktif jika kunci valid)
if ($isValidKey && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_ikan'])) {
    $inputData = [
        'nama'      => trim($_POST['nama']),
        'kategori'  => trim($_POST['kategori']),
        'harga'     => trim($_POST['harga']),
        'stok'      => trim($_POST['stok']),
        'asal'      => trim($_POST['asal']),
        'deskripsi' => trim($_POST['deskripsi']),
    ];

    $encrypted = encryptHybridAllFields($inputData, MASTER_PUBLIC_KEY);

    $stmt = $conn->prepare("INSERT INTO produk_ikan (enc_nama_ikan, enc_kategori, enc_harga, enc_stok, enc_asal_daerah, enc_deskripsi, iv, encrypted_aes_key) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "ssssssss",
        $encrypted['enc_nama_ikan'],
        $encrypted['enc_kategori'],
        $encrypted['enc_harga'],
        $encrypted['enc_stok'],
        $encrypted['enc_asal_daerah'],
        $encrypted['enc_deskripsi'],
        $encrypted['iv'],
        $encrypted['encrypted_aes_key']
    );
    $stmt->execute();
    $_SESSION['newly_encrypted_id'] = $stmt->insert_id;
    $stmt->close();

    header("Location: admin.php");
    exit;
}

// 2. Hapus Produk (Hanya aktif jika kunci valid)
if ($isValidKey && isset($_GET['hapus'])) {
    $idHapus = (int)$_GET['hapus'];
    $stmtDel = $conn->prepare("DELETE FROM produk_ikan WHERE id = ?");
    $stmtDel->bind_param("i", $idHapus);
    $stmtDel->execute();
    $stmtDel->close();

    header("Location: admin.php");
    exit;
}

// 3. Data Baru Ditambahkan untuk Sorotan Enkripsi & Dekripsi
$highlightCrypto = null;
if ($isValidKey && isset($_SESSION['newly_encrypted_id'])) {
    $newId = (int)$_SESSION['newly_encrypted_id'];
    unset($_SESSION['newly_encrypted_id']);

    $stmtNew = $conn->prepare("SELECT * FROM produk_ikan WHERE id = ? LIMIT 1");
    $stmtNew->bind_param("i", $newId);
    $stmtNew->execute();
    $resNew = $stmtNew->get_result();
    if ($rowNew = $resNew->fetch_assoc()) {
        $highlightCrypto = [
            'encrypted' => $rowNew,
            'decrypted' => decryptHybridAllFields($rowNew, $adminPrivateKey)
        ];
    }
    $stmtNew->close();
}

// 4. Ambil Semua Data Produk & Hitung KPI
$allRows = [];
$totalStok = 0;
$totalNilaiAset = 0.0;

$resAll = $conn->query("SELECT * FROM produk_ikan ORDER BY id DESC");
if ($resAll) {
    while ($r = $resAll->fetch_assoc()) {
        $dec = $isValidKey
            ? decryptHybridAllFields($r, $adminPrivateKey)
            : ['status' => false];

        if ($isValidKey && $dec['status']) {
            $stokInt = (int)$dec['stok'];
            $hargaFloat = (float)$dec['harga'];
            $totalStok += $stokInt;
            $totalNilaiAset += ($stokInt * $hargaFloat);
        }

        $allRows[] = [
            'encrypted' => $r,
            'decrypted' => $dec
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isValidKey ? 'Admin Vault — Terdekripsi' : 'Admin Vault — Terkunci (Kunci Salah)' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-page: #f8fafc;
            --sidebar-bg: #ffffff;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --primary: #0284c7;
            --emerald: #059669;
            --danger: #dc2626;
            --text-dark: #0f172a;
            --text-muted: #475569;
            --shadow-sm: 0 2px 10px rgba(15, 23, 42, 0.04);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: <?= $isValidKey ? 'var(--bg-page)' : 'linear-gradient(180deg, #fef2f2 0%, #f8fafc 320px)' ?>;
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* Left White Sidebar */
        .sidebar {
            width: 265px;
            background: var(--sidebar-bg);
            border-right: 1px solid <?= $isValidKey ? 'var(--border)' : '#fecaca' ?>;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .side-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 18px;
            color: var(--text-dark);
            padding: 0 8px 22px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 20px;
        }
        .side-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: <?= $isValidKey ? 'linear-gradient(135deg, #0284c7, #0d9488)' : 'linear-gradient(135deg, #dc2626, #991b1b)' ?>;
            display: grid;
            place-items: center;
            color: #ffffff;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 14px;
            border-radius: 11px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 6px;
            transition: all 0.2s;
        }
        .nav-item:hover, .nav-item.active {
            background: <?= $isValidKey ? '#f0f9ff' : '#fef2f2' ?>;
            color: <?= $isValidKey ? '#0284c7' : '#dc2626' ?>;
        }

        .key-box-side {
            background: <?= $isValidKey ? '#ecfdf5' : '#fef2f2' ?>;
            border: 1px solid <?= $isValidKey ? '#a7f3d0' : '#fecaca' ?>;
            border-radius: 14px;
            padding: 14px;
        }
        .btn-logout-side {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 12px;
            padding: 10px;
            border-radius: 10px;
            background: var(--danger);
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        /* Main Workspace */
        .main-content {
            flex: 1;
            padding: 28px 34px;
            max-width: calc(100vw - 265px);
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 26px;
        }
        .page-head h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--text-dark);
        }

        /* Bento KPI Cards */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 26px;
        }
        .bento-card {
            background: var(--card-bg);
            border: 1px solid <?= $isValidKey ? 'var(--border)' : '#fecaca' ?>;
            border-radius: 18px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }
        .bento-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-muted);
            font-size: 12.5px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .bento-val {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
        }
        .bento-val.locked {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13.5px;
            color: #dc2626;
        }

        /* Panel Containers */
        .panel {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }
        .panel-title {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 9px;
        }

        /* Real-Time Crypto Proof Banner */
        .proof-panel {
            border: 1.5px solid #10b981;
            background: linear-gradient(180deg, #ecfdf5 0%, #ffffff 100%);
        }
        .proof-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .sub-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
        }

        /* Form Styling */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .field { display: flex; flex-direction: column; gap: 6px; }
        .field.span-2 { grid-column: span 2; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-size: 12.5px; font-weight: 600; color: var(--text-muted); }
        .field input, .field select, .field textarea {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: var(--text-dark);
            padding: 11px 14px;
            border-radius: 11px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, background 0.2s;
        }
        .field input:focus, .field select:focus, .field textarea:focus {
            border-color: var(--primary);
            background: #ffffff;
        }
        .btn-primary {
            background: linear-gradient(135deg, #059669, #047857);
            color: #ffffff;
            border: none;
            padding: 12px 22px;
            border-radius: 11px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.24);
        }

        /* Cipher Badges & Blocks */
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .tag-aes { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .tag-rsa { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .tag-dec { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
        .tag-err { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        .code-pill {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: #be185d;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 7px 10px;
            border-radius: 8px;
            margin-bottom: 7px;
            word-break: break-all;
        }
        .code-pill.red {
            color: #b91c1c;
            background: #fff5f5;
            border-color: #fecaca;
        }

        /* Table */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 15px 12px;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
            vertical-align: top;
        }
        th {
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            background: #f8fafc;
        }
        .btn-del {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 7px 11px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-del:hover { background: #fee2e2; }

        @media (max-width: 1050px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: static; }
            .main-content { max-width: 100%; padding: 20px; }
            .bento-grid { grid-template-columns: repeat(2, 1fr); }
            .proof-columns, .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigasi Putih -->
    <aside class="sidebar">
        <div>
            <div class="side-brand">
                <div class="side-icon"><i data-lucide="shield" size="18"></i></div>
                <span>Aqua<span style="color:<?= $isValidKey ? '#0284c7' : '#dc2626' ?>">Vault</span></span>
            </div>

            <a href="#kpi" class="nav-item active"><i data-lucide="layout-dashboard" size="17"></i> Ringkasan Vault</a>
            <?php if ($isValidKey): ?>
                <a href="#formTambah" class="nav-item"><i data-lucide="plus-circle" size="17"></i> Input Ikan Hias</a>
            <?php endif; ?>
            <a href="#tabelData" class="nav-item"><i data-lucide="database" size="17"></i> Database Terenkripsi</a>
            <a href="index.php" target="_blank" class="nav-item"><i data-lucide="external-link" size="17"></i> Etalase Publik</a>
        </div>

        <div class="key-box-side">
            <div style="font-size:11px; font-weight:700; color:<?= $isValidKey ? '#047857' : '#b91c1c' ?>; display:flex; align-items:center; gap:6px;">
                <i data-lucide="<?= $isValidKey ? 'check-circle-2' : 'alert-triangle' ?>" size="14"></i>
                <?= $isValidKey ? 'KUNCI VALID TERVERIFIKASI' : 'KUNCI SALAH (TERKUNCI)' ?>
            </div>
            <div style="font-family:'JetBrains Mono',monospace; font-size:11px; color:var(--text-muted); margin-top:5px; word-break:break-all;">
                <?= htmlspecialchars($keyFilename) ?>
            </div>
            <a href="logout.php" class="btn-logout-side">
                <i data-lucide="log-out" size="15"></i> <?= $isValidKey ? 'Kunci & Keluar' : 'Ganti File Kunci' ?>
            </a>
        </div>
    </aside>

    <!-- Area Utama -->
    <main class="main-content" id="kpi">
        <div class="page-head">
            <div>
                <h1><?= $isValidKey ? 'Dashboard Inventaris Ikan Hias' : 'Dashboard Mode Terkunci (Kunci Salah)' ?></h1>
                <p style="font-size:13.5px; color:var(--text-muted);">
                    <?= $isValidKey
                        ? 'Seluruh kolom di MySQL terenkripsi AES-256-CBC + RSA-2048 dan didekripsi secara real-time.'
                        : 'Autentikasi file kunci gagal. Sistem menolak dekripsi dan menampilkan seluruh data dalam bentuk ciphertext.' ?>
                </p>
            </div>
        </div>

        <!-- Bento KPI Cards -->
        <div class="bento-grid">
            <div class="bento-card">
                <div class="bento-top"><span>Total Spesies</span><i data-lucide="fish" size="16" color="#0284c7"></i></div>
                <div class="bento-val"><?= count($allRows) ?> Jenis</div>
            </div>
            <div class="bento-card">
                <div class="bento-top"><span>Total Stok Ikan</span><i data-lucide="package" size="16" color="#059669"></i></div>
                <?php if ($isValidKey): ?>
                    <div class="bento-val"><?= number_format($totalStok) ?> Ekor</div>
                <?php else: ?>
                    <div class="bento-val locked">🔒 AES_ENCRYPTED</div>
                <?php endif; ?>
            </div>
            <div class="bento-card">
                <div class="bento-top"><span>Estimasi Nilai Aset</span><i data-lucide="wallet" size="16" color="#d97706"></i></div>
                <?php if ($isValidKey): ?>
                    <div class="bento-val" style="color:#0284c7;">Rp <?= number_format($totalNilaiAset, 0, ',', '.') ?></div>
                <?php else: ?>
                    <div class="bento-val locked">🔒 RSA_LOCKED</div>
                <?php endif; ?>
            </div>
            <div class="bento-card">
                <div class="bento-top"><span>Skema Kriptografi</span><i data-lucide="lock" size="16" color="#7c3aed"></i></div>
                <div class="bento-val" style="font-size:18px;">AES-256 + RSA-2048</div>
            </div>
        </div>

        <?php if (!$isValidKey): ?>
            <!-- MODE KUNCI SALAH -->
            <div class="panel" style="border:1.5px solid #ef4444; background:linear-gradient(180deg, #fef2f2 0%, #ffffff 100%);">
                <div class="panel-header">
                    <div class="panel-title" style="color:#b91c1c;">
                        <i data-lucide="shield-alert" size="20"></i> Autentikasi Kunci Salah — Hasil Enkripsi Sesi Ditolak
                    </div>
                </div>
                <p style="font-size:13.5px; color:#7f1d1d; margin-bottom:14px;">
                    File <strong><?= htmlspecialchars($keyFilename) ?></strong> tidak cocok dengan <em>RSA-2048 Public Key</em> server. Sistem otomatis mengenkripsi log akses ini menggunakan <strong>AES-256-CBC + RSA-2048</strong>:
                </p>
                <?php if (is_array($failedCryptoLog)): ?>
                    <span class="tag tag-err">CIPHERTEXT LOG LOGIN SALAH (AES-256-CBC + RSA-2048)</span>
                    <div class="code-pill red"><strong>AES-256 Ciphertext:</strong> <?= htmlspecialchars($failedCryptoLog['aes_ciphertext']) ?></div>
                    <div class="code-pill red"><strong>RSA-2048 Encrypted Key:</strong> <?= htmlspecialchars(substr($failedCryptoLog['rsa_enc_key'], 0, 140)) ?>...</div>
                    <div class="code-pill red"><strong>IV (Base64):</strong> <?= htmlspecialchars($failedCryptoLog['iv_base64']) ?> | <strong>SHA-256:</strong> <?= htmlspecialchars($failedCryptoLog['sha256_digest']) ?></div>
                <?php endif; ?>
            </div>

            <div class="panel" id="tabelData">
                <div class="panel-header">
                    <div class="panel-title" style="color:#b91c1c;">
                        <i data-lucide="lock" size="18"></i> Seluruh Konten Dashboard dalam Bentuk Enkripsi (Gagal Dekripsi)
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="30%">Nama & Kategori (AES-256 Ciphertext)</th>
                                <th width="25%">Harga, Stok & Asal (AES-256 Ciphertext)</th>
                                <th width="22%">Deskripsi (AES-256 Ciphertext)</th>
                                <th width="18%">Kunci Sesi (RSA-2048)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allRows as $item): $enc = $item['encrypted']; ?>
                                <tr>
                                    <td>#<?= $enc['id'] ?></td>
                                    <td>
                                        <span class="tag tag-err">TERENKRIPSI</span>
                                        <div class="code-pill red"><strong>Nama:</strong> <?= htmlspecialchars($enc['enc_nama_ikan']) ?></div>
                                        <div class="code-pill red"><strong>Kategori:</strong> <?= htmlspecialchars($enc['enc_kategori']) ?></div>
                                    </td>
                                    <td>
                                        <div class="code-pill red"><strong>Harga:</strong> <?= htmlspecialchars($enc['enc_harga']) ?></div>
                                        <div class="code-pill red"><strong>Stok:</strong> <?= htmlspecialchars($enc['enc_stok']) ?></div>
                                        <div class="code-pill red"><strong>Asal:</strong> <?= htmlspecialchars($enc['enc_asal_daerah']) ?></div>
                                    </td>
                                    <td>
                                        <div class="code-pill red"><?= htmlspecialchars(substr($enc['enc_deskripsi'], 0, 85)) ?>...</div>
                                    </td>
                                    <td>
                                        <span class="tag tag-rsa">RSA-2048 LOCKED</span>
                                        <div class="code-pill" style="color:#0369a1;"><?= htmlspecialchars(substr($enc['encrypted_aes_key'], 0, 48)) ?>...</div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <!-- MODE KUNCI VALID -->
            <?php if ($highlightCrypto !== null): ?>
                <div class="panel proof-panel">
                    <div class="panel-header">
                        <div class="panel-title" style="color:#047857;">
                            <i data-lucide="sparkles" size="20"></i> Data Berhasil Dienkripsi & Didekripsi Secara Real-Time!
                        </div>
                    </div>
                    <div class="proof-columns">
                        <div class="sub-box">
                            <span class="tag tag-aes">1. Hasil Enkripsi di MySQL (AES-256-CBC + RSA-2048)</span>
                            <div class="code-pill"><strong>enc_nama_ikan:</strong> <?= htmlspecialchars($highlightCrypto['encrypted']['enc_nama_ikan']) ?></div>
                            <div class="code-pill"><strong>enc_kategori:</strong> <?= htmlspecialchars($highlightCrypto['encrypted']['enc_kategori']) ?></div>
                            <div class="code-pill"><strong>enc_harga:</strong> <?= htmlspecialchars($highlightCrypto['encrypted']['enc_harga']) ?></div>
                            <div class="code-pill"><strong>enc_stok:</strong> <?= htmlspecialchars($highlightCrypto['encrypted']['enc_stok']) ?></div>
                            <div class="code-pill"><strong>enc_asal_daerah:</strong> <?= htmlspecialchars($highlightCrypto['encrypted']['enc_asal_daerah']) ?></div>
                            <div class="code-pill" style="color:#0369a1;"><strong>encrypted_aes_key (RSA):</strong> <?= htmlspecialchars(substr($highlightCrypto['encrypted']['encrypted_aes_key'], 0, 90)) ?>...</div>
                        </div>

                        <div class="sub-box">
                            <span class="tag tag-dec">2. Hasil Dekripsi Otomatis (RSA Private Key &rarr; AES &rarr; Plaintext)</span>
                            <p style="margin-bottom:8px;"><strong>Nama Ikan:</strong> <span style="color:#0284c7; font-size:17px; font-weight:700;"><?= htmlspecialchars($highlightCrypto['decrypted']['nama']) ?></span></p>
                            <p style="margin-bottom:8px;"><strong>Kategori:</strong> <?= htmlspecialchars($highlightCrypto['decrypted']['kategori']) ?></p>
                            <p style="margin-bottom:8px;"><strong>Harga Jual:</strong> Rp <?= number_format((float)$highlightCrypto['decrypted']['harga'], 0, ',', '.') ?></p>
                            <p style="margin-bottom:8px;"><strong>Stok:</strong> <?= htmlspecialchars($highlightCrypto['decrypted']['stok']) ?> Ekor</p>
                            <p style="margin-bottom:8px;"><strong>Asal Daerah:</strong> <?= htmlspecialchars($highlightCrypto['decrypted']['asal']) ?></p>
                            <p style="font-size:13px; color:var(--text-muted);"><strong>Deskripsi:</strong> <?= htmlspecialchars($highlightCrypto['decrypted']['deskripsi']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="panel" id="formTambah">
                <div class="panel-header">
                    <div class="panel-title">
                        <i data-lucide="plus-square" size="19" color="#059669"></i> Tambah Data Ikan Hias (Enkripsi Hybrid Otomatis)
                    </div>
                </div>
                <form action="" method="POST">
                    <div class="form-grid">
                        <div class="field">
                            <label>Nama Ikan Hias</label>
                            <input type="text" name="nama" placeholder="Contoh: Channa Barca Ori India" required>
                        </div>
                        <div class="field">
                            <label>Kategori Koleksi</label>
                            <select name="kategori" required>
                                <option value="Predator Eksklusif">Predator Eksklusif (Arwana / Channa)</option>
                                <option value="Ikan Kontes">Ikan Kontes (Cupang / Louhan / Discus)</option>
                                <option value="Ikan Kolam">Ikan Kolam (Koi / Koki)</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Harga Jual (Rp)</label>
                            <input type="number" name="harga" placeholder="Contoh: 2500000" required>
                        </div>
                        <div class="field">
                            <label>Stok (Ekor)</label>
                            <input type="number" name="stok" placeholder="Contoh: 5" required>
                        </div>
                        <div class="field span-2">
                            <label>Asal Breeder / Daerah Indonesia</label>
                            <input type="text" name="asal" placeholder="Contoh: Pontianak, Kalimantan Barat" required>
                        </div>
                        <div class="field full">
                            <label>Deskripsi & Catatan Rahasia Breeder</label>
                            <textarea name="deskripsi" rows="2" placeholder="Masukkan ukuran, sertifikat, atau catatan internal..." required></textarea>
                        </div>
                    </div>
                    <button type="submit" name="tambah_ikan" class="btn-primary">
                        <i data-lucide="lock" size="16"></i> Enkripsi Hybrid & Simpan Data
                    </button>
                </form>
            </div>

            <div class="panel" id="tabelData">
                <div class="panel-header">
                    <div class="panel-title">
                        <i data-lucide="database" size="19" color="#0284c7"></i> Inventaris Terenkripsi (MySQL Ciphertext vs Admin Plaintext)
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="45%">Data Tersimpan di MySQL (100% Terenkripsi AES-256 + RSA-2048)</th>
                                <th width="42%">Hasil Dekripsi Halaman Admin (Plaintext)</th>
                                <th width="8%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allRows as $item): $enc = $item['encrypted']; $dec = $item['decrypted']; ?>
                                <tr>
                                    <td>#<?= $enc['id'] ?></td>
                                    <td>
                                        <span class="tag tag-aes">AES-256-CBC CIPHERTEXT</span>
                                        <div class="code-pill">Nama: <?= htmlspecialchars(substr($enc['enc_nama_ikan'], 0, 38)) ?>...</div>
                                        <div class="code-pill">Kategori: <?= htmlspecialchars(substr($enc['enc_kategori'], 0, 34)) ?>... | Harga: <?= htmlspecialchars(substr($enc['enc_harga'], 0, 22)) ?>...</div>
                                        <div class="code-pill">Stok: <?= htmlspecialchars(substr($enc['enc_stok'], 0, 22)) ?>... | Asal: <?= htmlspecialchars(substr($enc['enc_asal_daerah'], 0, 28)) ?>...</div>
                                        <span class="tag tag-rsa">RSA-2048 WRAPPED KEY</span>
                                        <div class="code-pill" style="color:#0369a1;"><?= htmlspecialchars(substr($enc['encrypted_aes_key'], 0, 52)) ?>...</div>
                                    </td>
                                    <td>
                                        <span class="tag tag-dec">✔ DECRYPTED</span>
                                        <div style="font-family:'Outfit',sans-serif; font-size:17px; font-weight:700; color:#0f172a; margin-bottom:4px;">
                                            <?= htmlspecialchars($dec['nama']) ?>
                                        </div>
                                        <div style="font-size:12.5px; color:#0284c7; font-weight:600;">
                                            <?= htmlspecialchars($dec['kategori']) ?> • <?= htmlspecialchars($dec['asal']) ?>
                                        </div>
                                        <div style="font-size:14.5px; font-weight:800; color:#059669; margin:6px 0;">
                                            Rp <?= number_format((float)$dec['harga'], 0, ',', '.') ?> <span style="font-weight:500; color:var(--text-muted); font-size:12.5px;">(Stok: <?= htmlspecialchars($dec['stok']) ?> ekor)</span>
                                        </div>
                                        <p style="font-size:12.5px; color:var(--text-muted);"><?= htmlspecialchars($dec['deskripsi']) ?></p>
                                    </td>
                                    <td>
                                        <a href="admin.php?hapus=<?= $enc['id'] ?>" class="btn-del" onclick="return confirm('Hapus data terenkripsi ini?')">
                                            <i data-lucide="trash-2" size="13"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>