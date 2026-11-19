<?php
declare(strict_types=1);

/**
 * Menghasilkan pasangan kunci RSA-2048 yang kompatibel dengan Laragon (Windows) & PHP 8.4
 */
function generateRsaKeyPair(): array
{
    $config = [
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];

    $phpBinDir = dirname(PHP_BINARY);
    $possibleConfigs = [
        $phpBinDir . '/extras/ssl/openssl.cnf',
        dirname($phpBinDir, 2) . '/apache/conf/openssl.cnf',
        'C:/laragon/bin/php/php-8.4.0-Win32-vs17-x64/extras/ssl/openssl.cnf'
    ];

    foreach ($possibleConfigs as $cfg) {
        if (file_exists($cfg)) {
            $config["config"] = realpath($cfg);
            break;
        }
    }

    $res = openssl_pkey_new($config);
    if ($res === false) {
        die("Error OpenSSL: Gagal membangkitkan kunci RSA-2048. Pastikan extension=openssl aktif di php.ini Laragon.");
    }

    openssl_pkey_export($res, $privKey, null, $config);
    $pubKeyDetails = openssl_pkey_get_details($res);
    $pubKey = $pubKeyDetails["key"];

    return [$privKey, $pubKey];
}

/**
 * ENKRIPSI HYBRID (Simetris AES-256-CBC + Asimetris RSA-2048)
 * Mengenkripsi SELURUH konten produk ikan hias di halaman admin.
 */
function encryptHybridAllFields(array $data, string $publicKeyPem): array
{
    // 1. Pembangkitan Kunci Simetris AES-256 (32 byte) & IV (16 byte)
    $aesKey = random_bytes(32);
    $iv     = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    // 2. Enkripsi Simetris (AES-256-CBC) untuk SEMUA kolom konten
    $encNama      = openssl_encrypt((string)$data['nama'], 'aes-256-cbc', $aesKey, 0, $iv);
    $encKategori  = openssl_encrypt((string)$data['kategori'], 'aes-256-cbc', $aesKey, 0, $iv);
    $encHarga     = openssl_encrypt((string)$data['harga'], 'aes-256-cbc', $aesKey, 0, $iv);
    $encStok      = openssl_encrypt((string)$data['stok'], 'aes-256-cbc', $aesKey, 0, $iv);
    $encAsal      = openssl_encrypt((string)$data['asal'], 'aes-256-cbc', $aesKey, 0, $iv);
    $encDeskripsi = openssl_encrypt((string)$data['deskripsi'], 'aes-256-cbc', $aesKey, 0, $iv);

    // 3. Enkripsi Asimetris (RSA-2048 Public Key) untuk mengamankan AES Key
    $encryptedAesKey = '';
    openssl_public_encrypt($aesKey, $encryptedAesKey, $publicKeyPem);

    return [
        'enc_nama_ikan'     => $encNama,
        'enc_kategori'      => $encKategori,
        'enc_harga'         => $encHarga,
        'enc_stok'          => $encStok,
        'enc_asal_daerah'   => $encAsal,
        'enc_deskripsi'     => $encDeskripsi,
        'iv'                => base64_encode($iv),
        'encrypted_aes_key' => base64_encode($encryptedAesKey)
    ];
}

/**
 * DEKRIPSI HYBRID (Asimetris RSA-2048 Private Key -> Simetris AES-256-CBC)
 */
function decryptHybridAllFields(array $row, string $privateKeyPem): array
{
    $iv              = base64_decode($row['iv']);
    $encryptedAesKey = base64_decode($row['encrypted_aes_key']);

    $privateKey = openssl_pkey_get_private($privateKeyPem);
    if ($privateKey === false) {
        return ['status' => false, 'error' => 'Private Key tidak valid'];
    }

    // Dekripsi Asimetris untuk membuka AES Key
    $decryptedAesKey = '';
    $ok = openssl_private_decrypt($encryptedAesKey, $decryptedAesKey, $privateKey);

    if (!$ok) {
        return ['status' => false, 'error' => 'Kunci RSA tidak cocok untuk membuka AES Key'];
    }

    // Dekripsi Simetris untuk mengembalikan semua konten asli
    return [
        'status'    => true,
        'nama'      => openssl_decrypt($row['enc_nama_ikan'], 'aes-256-cbc', $decryptedAesKey, 0, $iv) ?: '',
        'kategori'  => openssl_decrypt($row['enc_kategori'], 'aes-256-cbc', $decryptedAesKey, 0, $iv) ?: '',
        'harga'     => openssl_decrypt($row['enc_harga'], 'aes-256-cbc', $decryptedAesKey, 0, $iv) ?: '0',
        'stok'      => openssl_decrypt($row['enc_stok'], 'aes-256-cbc', $decryptedAesKey, 0, $iv) ?: '0',
        'asal'      => openssl_decrypt($row['enc_asal_daerah'], 'aes-256-cbc', $decryptedAesKey, 0, $iv) ?: '',
        'deskripsi' => openssl_decrypt($row['enc_deskripsi'], 'aes-256-cbc', $decryptedAesKey, 0, $iv) ?: '',
    ];
}

/**
 * VERIFIKASI LOGIN BERBASIS FILE KUNCI KRIPTOGRAFI (RSA Digital Signature Challenge)
 */
function verifyKeyFileAuthentication(string $uploadedPemContent, string $challengeNonce, string $masterPublicKeyPem): bool
{
    $privateKey = @openssl_pkey_get_private($uploadedPemContent);
    if ($privateKey === false) {
        return false;
    }

    // Tandatangani challenge server dengan Private Key yang dilampirkan pengguna
    $signature = '';
    $signed = @openssl_sign($challengeNonce, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    if (!$signed) {
        return false;
    }

    // Verifikasi tanda tangan menggunakan Public Key resmi milik Admin di Database
    $verified = @openssl_verify($challengeNonce, $signature, $masterPublicKeyPem, OPENSSL_ALGO_SHA256);
    return $verified === 1;
}

/**
 * ENKRIPSI PERCOBAAN LOGIN SALAH
 * Menghasilkan ciphertext Hybrid (AES-256 + RSA-2048) untuk ditampilkan saat login gagal
 */
function encryptFailedLoginAttempt(string $fileName, string $reason, string $publicKeyPem): array
{
    $payloadLog = json_encode([
        'event'     => 'FAILED_KEY_AUTH',
        'file_name' => $fileName,
        'reason'    => $reason,
        'ip_client' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

    $aesKey = random_bytes(32);
    $iv     = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    $cipherLog = openssl_encrypt($payloadLog, 'aes-256-cbc', $aesKey, 0, $iv);
    $encKey    = '';
    openssl_public_encrypt($aesKey, $encKey, $publicKeyPem);

    return [
        'aes_ciphertext' => $cipherLog,
        'iv_base64'      => base64_encode($iv),
        'rsa_enc_key'    => base64_encode($encKey),
        'sha256_digest'  => hash('sha256', $cipherLog)
    ];
}