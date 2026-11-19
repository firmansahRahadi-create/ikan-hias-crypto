-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 19 Nov 2026 pada 02.31
-- Versi server: 11.8.6-MariaDB-log
-- Versi PHP: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `db_ikan_hias_crypto`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `auth_keys`
--

CREATE TABLE `auth_keys` (
  `id` int(11) NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `public_key` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `auth_keys`
--

INSERT INTO `auth_keys` (`id`, `key_name`, `public_key`, `created_at`) VALUES
(1, 'Master Admin RSA-2048 Key', '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5lNDRYrU+zdUJJr+8Ud0\nr22nUZqmxVaMpPnu054Et4Lt5c/ZQXOo0T54EV98UcCSI5orayR2MTRyvJMN7Tpy\nL8DNKPyJFa4o90So+59zxRL767iwKnNTrOXUteoxpZsqwntTj7LW+JHg0AFOIavR\nEkGm9r0MVvpiFBhsNl8mbQpm4o0HbzPSDmsnR3xSIH72M8vhnCh1NIR36qDPkkqz\nzaALHKv8l9kkRv1sdSFGzmMePkKgVYhFA+TmcH3nlHbv9i2FaNoRaypDNl+yLzT7\nhziHWH58gQTPYAxxeWLZ39K4F1k5VD+r9DQ6CDYcnx14eDE6o4uzU382Oh5F8LqU\nxQIDAQAB\n-----END PUBLIC KEY-----\n', '2026-11-19 01:45:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk_ikan`
--

CREATE TABLE `produk_ikan` (
  `id` int(11) NOT NULL,
  `enc_nama_ikan` text NOT NULL,
  `enc_kategori` text NOT NULL,
  `enc_harga` text NOT NULL,
  `enc_stok` text NOT NULL,
  `enc_asal_daerah` text NOT NULL,
  `enc_deskripsi` longtext NOT NULL,
  `iv` varchar(255) NOT NULL,
  `encrypted_aes_key` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `produk_ikan`
--

INSERT INTO `produk_ikan` (`id`, `enc_nama_ikan`, `enc_kategori`, `enc_harga`, `enc_stok`, `enc_asal_daerah`, `enc_deskripsi`, `iv`, `encrypted_aes_key`, `created_at`) VALUES
(1, 'Uplm9OvR/5X7ysbmG+EKpIe5TPInIJeOdMpP/RhLA1c=', 'Y4VM2qj9dBkMOafpUcQtagcI43MkditTsPNT+fRm5Hg=', 'g2+J3m6ATgaFeBQHXGt5pg==', '8z7DnhkYpYaDBuWgNODL4w==', '42BWVdqTAnYJd97GvfAIK/Em+6o52DLn9/Ua/sEsDwY=', 'ad8bAAacCBAhDLduvwFRe+3QW5xijwUzBGvDLACklwPKVnC+jIRduB9XNzCnlmAtKA4wK13weshgJSXXmJqrW8hM5HEZijLwgB81X7JeyYOsjaHSWsiP51py3rHALH9b/WrwzL3cvn+2hZc+nRg9IjCtJ91pWC9ekLR0fNEsd9I=', 'uurPE8LG1Ksog+HgSnXGrA==', 'kpzBT4A933GU3AzO7aw29xZYSMgofevhlZ85NNwsbJ4QaORY64jns30Y2VZK7gbjjaOrYbefawirl9Dc100g9FhH4VvS/2pgVkq04LiErLw5/iHF3Zee9C8Q65XRi688eDsd7oOQe3jRegdBGOVw97ctCzu49vBiCWPTj6Pn0DpZIhgkD4EBAMNsNlnT/9NLSOstTEBU6T/P0HKvfWEAQL6FqfPeCLoyVYmWCAeAGvSKpZEbelnbHydD7g/7I45eq8uJ4H4u4usTfDwUJPx221HO2l/anian9bnP/IXJWp1QlWYU+6srnP4pvXDxXiIbGrXvPus2fwOVe/vjUDecWQ==', '2026-11-19 01:45:14'),
(2, 'EGeCFaAAv0g9G6LIdCyr0WPtNaQ2ymWPLCpkFlZdPJ8=', 'KpjcDhBgF/gwJNBjE7bfOg==', 'jekPv9e3VGpvvwFFDHEupA==', 'zhxypI2GqvHBhO0kcFYcdw==', '2fiWrNiOcoWy6+/d3N/41k5tzywvLPJIxiJTn19QjuY=', 'wQemmWydp+AmqznfU4ZFBYDRvc3J377Gr4mSdVidYnFrJqVRNROMLdUk2raQXJavaU/zww3MDB3lGIkuoYQEqrby9EWbrp94HkjEBrO2UBPc9Kibb1x+fmBZ1e//mbCzi0aOqKbVoYDwL+eRsdNE2g==', 'i7GsXeSnYv/yOSYBe5RIWA==', 'HSJAurCc18xt/Kqd80zX+VBuSHvaiOtkeZ49eMLXYBDIczT96z7V+mZvGSPaPs1XUunDTACfEGOoz1Sl7f4TcrPSkZUH72JCCyTbN6DeqR1tTUGSBjYeGNDE5JO3DPkG37h0+Wl+ESJFDSUiOrxfpHpJaT8sBwK3dDarxcwAWvgNZjpDtST/pcXLxCa5RsAVz32AXbEF+vVsVaiUX4zMJzhQOdQmI7+LqqQPcQfI9wN/1jrie4YoryP35DaOksWM+5PZQsU9uE96E62/YjgZSA4zKZvHDYRLkhpTleuXwreUknb9ZgvmjcImBXsns2evkq8CMybXAdin0wpyP68DEQ==', '2026-11-19 01:45:14'),
(3, 'UYsVgRIxr60wgtyJHO8BlEVWjbP8Qe2FX2tRrQfG5Y0=', 'xhgGnLpEwVijTDnzK6Qa/w==', 'LhiFle5Z94UxGF5LGXtwXA==', 'j2HBsxSxPVay4P5bhFfOjg==', 'nBDPcf5On8Yfn1UW17oE2nPt8huS6ZNYDJvDpUypLF8=', 'bHia+dupO0GpdYW9Rh8e/YJwHY6w9whL36kfqltyqc61cWV+2pd3O8dsY8Du0eCshiASu0n9MtIrylOsp5oA9eoVR4fwlS7n+AjZI+Vch1l+XugijvagtoYSpMAytZcRMAmHEg1sXAqVnVgJ2zgOzg==', 'CfBysfJub+WyLvbQuAnSUQ==', 'ysroHV0NcVOB2/B7RN4I7wCVrhZB7VQeEB43Tb/Q/Yi/fgAXUjZXBvWd8NjSCXH+koNXbl2XaiWcbUgIQDYlnXkYbYQB7iLpoio+yW/fmFYxOeXx/e5INTORI0a3UjsieHQhM2UhXZHM0mOUlEFFiEzci6Kk1K3jVpckhY/76nEJICC3FC0w5LNCIbrIRDCCSrpVDJ4kj02oLaArNAyqg93grt+W67h24lHTZO6Q3/1UepTLLm8AyRju68Pp5qY2O/NFMlsCIACHpdWGDrj/FujSKBLVQ7C5FzwQ8ujVBCgvp6Bhst/d7y4a5Uj4dt2aCfBlcflnyDDqTjOErRafJA==', '2026-11-19 01:45:14');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `auth_keys`
--
ALTER TABLE `auth_keys`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `produk_ikan`
--
ALTER TABLE `produk_ikan`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `auth_keys`
--
ALTER TABLE `auth_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `produk_ikan`
--
ALTER TABLE `produk_ikan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
