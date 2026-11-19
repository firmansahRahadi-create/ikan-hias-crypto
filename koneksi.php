<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_ikan_hias_crypto";

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Koneksi MySQL gagal: " . $conn->connect_error);
}

// 1. Buat Database Otomatis
$conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$conn->select_db($db);

// 2. Tabel Penyimpanan Public Key untuk Autentikasi File Kunci
$conn->query("CREATE TABLE IF NOT EXISTS auth_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL,
    public_key TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 3. Tabel Produk Ikan Hias (SELURUH KONTEN DIENKRIPSI HYBRID)
$conn->query("CREATE TABLE IF NOT EXISTS produk_ikan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enc_nama_ikan TEXT NOT NULL,
    enc_kategori TEXT NOT NULL,
    enc_harga TEXT NOT NULL,
    enc_stok TEXT NOT NULL,
    enc_asal_daerah TEXT NOT NULL,
    enc_deskripsi LONGTEXT NOT NULL,
    iv VARCHAR(255) NOT NULL,
    encrypted_aes_key TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

require_once __DIR__ . '/crypto_helper.php';

// 4. Inisialisasi Otomatis Pasangan Kunci RSA-2048 & File Kunci (.pem) jika belum ada
$keyDir = __DIR__ . '/keys';
if (!is_dir($keyDir)) {
    mkdir($keyDir, 0755, true);
    file_put_contents($keyDir . '/.htaccess', "Deny from all\n");
}

$validKeyPath   = $keyDir . '/admin_private_key.pem';
$invalidKeyPath = $keyDir . '/kunci_salah_test.pem';

$checkKey = $conn->query("SELECT id, public_key FROM auth_keys LIMIT 1");
if ($checkKey && $checkKey->num_rows === 0) {
    // Generate Kunci Valid Admin
    [$privKey, $pubKey] = generateRsaKeyPair();
    file_put_contents($validKeyPath, $privKey);

    $keyName = 'Master Admin RSA-2048 Key';
    $stmt = $conn->prepare("INSERT INTO auth_keys (key_name, public_key) VALUES (?, ?)");
    $stmt->bind_param("ss", $keyName, $pubKey);
    $stmt->execute();
    $stmt->close();

    // Generate Kunci Palsu/Salah untuk Pengujian Login Gagal
    [$wrongPrivKey, ] = generateRsaKeyPair();
    file_put_contents($invalidKeyPath, $wrongPrivKey);
}

// Ambil Public Key Master dari Database
$resPub = $conn->query("SELECT public_key FROM auth_keys LIMIT 1");
$rowPub = $resPub ? $resPub->fetch_assoc() : null;
define('MASTER_PUBLIC_KEY', $rowPub['public_key'] ?? '');

// 5. Seeding Otomatis 3 Data Ikan Hias Indonesia Terenkripsi jika Tabel Masih Kosong
$checkProduk = $conn->query("SELECT id FROM produk_ikan LIMIT 1");
if ($checkProduk && $checkProduk->num_rows === 0 && MASTER_PUBLIC_KEY !== '') {
    $sampleData = [
        [
            'nama'      => 'Arwana Super Red Kapuas Hulu',
            'kategori'  => 'Predator Eksklusif',
            'harga'     => '6500000',
            'stok'      => '8',
            'asal'      => 'Kapuas Hulu, Kalimantan Barat',
            'deskripsi' => 'Grade A+ bersertifikat dan tertanam microchip resmi. Warna merah pekat alami, dayung panjang, pakan udang beku & kelabang.'
        ],
        [
            'nama'      => 'Cupang Halfmoon Rosetail Avatar',
            'kategori'  => 'Ikan Kontes',
            'harga'     => '350000',
            'stok'      => '25',
            'asal'      => 'Blitar, Jawa Timur',
            'deskripsi' => 'Sirip simetris 180 derajat dengan sisik mutiara biru elektrik. Siap kontes maupun indukan unggulan.'
        ],
        [
            'nama'      => 'Koi Kohaku Doitsu HQ',
            'kategori'  => 'Ikan Kolam',
            'harga'     => '1850000',
            'stok'      => '14',
            'asal'      => 'Sukabumi, Jawa Barat',
            'deskripsi' => 'Pola merah (Hi) tajam di atas dasar putih salju (Shiroji), ukuran 38 cm, lolos karantina bebas parasit.'
        ]
    ];

    foreach ($sampleData as $item) {
        $enc = encryptHybridAllFields($item, MASTER_PUBLIC_KEY);
        $stmtSeed = $conn->prepare("INSERT INTO produk_ikan (enc_nama_ikan, enc_kategori, enc_harga, enc_stok, enc_asal_daerah, enc_deskripsi, iv, encrypted_aes_key) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtSeed->bind_param(
            "ssssssss",
            $enc['enc_nama_ikan'],
            $enc['enc_kategori'],
            $enc['enc_harga'],
            $enc['enc_stok'],
            $enc['enc_asal_daerah'],
            $enc['enc_deskripsi'],
            $enc['iv'],
            $enc['encrypted_aes_key']
        );
        $stmtSeed->execute();
        $stmtSeed->close();
    }
}