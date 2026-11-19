<?php
declare(strict_types=1);
require_once __DIR__ . '/koneksi.php';

if (isset($_SESSION['crypto_admin_auth']) && $_SESSION['crypto_admin_auth'] === true) {
    header("Location: admin.php");
    exit;
}

if (isset($_GET['download_key'])) {
    $type = $_GET['download_key'];
    if ($type === 'valid' && file_exists(__DIR__ . '/keys/admin_private_key.pem')) {
        header('Content-Type: application/x-pem-file');
        header('Content-Disposition: attachment; filename="admin_private_key.pem"');
        readfile(__DIR__ . '/keys/admin_private_key.pem');
        exit;
    } elseif ($type === 'invalid' && file_exists(__DIR__ . '/keys/kunci_salah_test.pem')) {
        header('Content-Type: application/x-pem-file');
        header('Content-Disposition: attachment; filename="kunci_salah_test.pem"');
        readfile(__DIR__ . '/keys/kunci_salah_test.pem');
        exit;
    }
}

if (!isset($_SESSION['login_challenge'])) {
    $_SESSION['login_challenge'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth_submit'])) {
    if (isset($_FILES['key_file']) && $_FILES['key_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES['key_file']['name']);
        $uploadedContent = file_get_contents($_FILES['key_file']['tmp_name']);

        $isValid = verifyKeyFileAuthentication(
            $uploadedContent,
            $_SESSION['login_challenge'],
            MASTER_PUBLIC_KEY
        );

        session_regenerate_id(true);
        $_SESSION['crypto_admin_auth'] = true;
        $_SESSION['is_valid_key']      = $isValid;
        $_SESSION['admin_private_key'] = $uploadedContent;
        $_SESSION['key_filename']      = $fileName;

        if (!$isValid) {
            $_SESSION['failed_login_crypto'] = encryptFailedLoginAttempt(
                $fileName,
                'RSA Private Key Mismatch - Akses Dashboard dalam Mode Terenkripsi',
                MASTER_PUBLIC_KEY
            );
        } else {
            unset($_SESSION['failed_login_crypto']);
        }

        unset($_SESSION['login_challenge']);
        header("Location: admin.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Key Vault Authentication — Nusantara AquaVault</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-page: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --primary: #0284c7;
            --emerald: #059669;
            --danger: #dc2626;
            --text-dark: #0f172a;
            --text-muted: #475569;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            background-image:
                radial-gradient(circle at 15% 15%, rgba(2, 132, 199, 0.08) 0%, transparent 45%),
                radial-gradient(circle at 85% 85%, rgba(16, 185, 129, 0.08) 0%, transparent 45%),
                linear-gradient(#e2e8f0 1px, transparent 1px),
                linear-gradient(90deg, #e2e8f0 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 40px 40px, 40px 40px;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .vault-container { width: 100%; max-width: 520px; }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            transition: color 0.2s;
        }
        .back-btn:hover { color: var(--primary); }

        .vault-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 36px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
        }

        .icon-ring {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: linear-gradient(135deg, #e0f2fe, #ccfbf1);
            border: 1px solid #bae6fd;
            display: grid;
            place-items: center;
            color: #0284c7;
            margin-bottom: 18px;
        }

        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        .lead { font-size: 14px; color: var(--text-muted); margin-bottom: 22px; }

        .nonce-panel {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 22px;
        }
        .nonce-label {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .nonce-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11.5px;
            color: #0369a1;
            word-break: break-all;
        }

        /* Drag & Drop Upload Box */
        .dropzone {
            border: 2px dashed #7dd3fc;
            background: #f0f9ff;
            border-radius: 16px;
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s;
            margin-bottom: 22px;
            position: relative;
        }
        .dropzone:hover, .dropzone.dragover {
            border-color: var(--primary);
            background: #e0f2fe;
        }
        .dropzone.file-ready {
            border-style: solid;
            border-color: var(--emerald);
            background: #ecfdf5;
        }
        .dropzone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .btn-auth {
            width: 100%;
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 8px 22px rgba(2, 132, 199, 0.28);
            transition: transform 0.2s;
        }
        .btn-auth:hover { transform: translateY(-2px); }

        .demo-footer {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #f1f5f9;
        }
        .demo-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .demo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .dl-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .dl-btn:hover { opacity: 0.85; }
        .dl-valid {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
        }
        .dl-invalid {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }
    </style>
</head>
<body>

    <div class="vault-container">
        <div class="top-nav">
            <a href="index.php" class="back-btn">
                <i data-lucide="arrow-left" size="16"></i> Kembali ke Etalase
            </a>
            <span style="font-size:12px; color:#0284c7; font-weight:700; background:#e0f2fe; padding:4px 10px; border-radius:999px;">RSA-2048 Signature Auth</span>
        </div>

        <div class="vault-card">
            <div class="icon-ring">
                <i data-lucide="key-round" size="28"></i>
            </div>

            <h1>Cryptographic Key Login</h1>
            <p class="lead">
                Autentikasi tanpa username dan password. Unggah <strong>File Kunci Privat (.pem / .key)</strong> untuk memverifikasi identitas Anda.
            </p>

            <div class="nonce-panel">
                <div class="nonce-label">
                    <span>Server Challenge Nonce</span>
                    <span style="color:#059669;">SHA-256</span>
                </div>
                <div class="nonce-code"><?= htmlspecialchars($_SESSION['login_challenge']) ?></div>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="dropzone" id="dropZone">
                    <input type="file" name="key_file" id="keyInput" accept=".pem,.key,.txt" required>
                    <div id="dropContent">
                        <i data-lucide="file-key" size="34" color="#0284c7" style="margin-bottom:8px;"></i>
                        <div style="font-weight:700; font-size:14.5px; color:#0f172a;">Seret file kunci ke sini atau klik untuk memilih</div>
                        <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">Mendukung format kunci kriptografi <code>.pem</code> atau <code>.key</code></div>
                    </div>
                </div>

                <button type="submit" name="auth_submit" class="btn-auth">
                    <i data-lucide="unlock" size="18"></i> Verifikasi & Buka Dashboard
                </button>
            </form>

            <div class="demo-footer">
                <div class="demo-title">
                    <i data-lucide="flask-conical" size="14" color="#0284c7"></i> Kunci Pengujian Demo UTS:
                </div>
                <div class="demo-grid">
                    <a href="login.php?download_key=valid" class="dl-btn dl-valid">
                        <i data-lucide="download" size="14"></i> Kunci Valid (.pem)
                    </a>
                    <a href="login.php?download_key=invalid" class="dl-btn dl-invalid">
                        <i data-lucide="download" size="14"></i> Kunci Salah (.pem)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        const dropZone = document.getElementById('dropZone');
        const keyInput = document.getElementById('keyInput');
        const dropContent = document.getElementById('dropContent');

        ['dragover', 'dragenter'].forEach(evt => {
            dropZone.addEventListener(evt, e => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(evt => {
            dropZone.addEventListener(evt, () => dropZone.classList.remove('dragover'));
        });

        keyInput.addEventListener('change', () => {
            if (keyInput.files.length > 0) {
                const file = keyInput.files[0];
                const sizeKB = (file.size / 1024).toFixed(2);
                dropZone.classList.add('file-ready');
                dropContent.innerHTML = `
                    <div style="color:#047857; font-weight:700; font-size:14.5px; margin-bottom:4px;">✔ File Kunci Siap Diverifikasi</div>
                    <div style="font-family:'JetBrains Mono',monospace; font-size:12.5px; color:#0f172a;">${file.name} (${sizeKB} KB)</div>
                `;
            }
        });
    </script>
</body>
</html>