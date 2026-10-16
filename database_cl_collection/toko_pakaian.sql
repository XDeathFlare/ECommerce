-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2026 at 09:55 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `toko_pakaian`
--

-- --------------------------------------------------------

--
-- Table structure for table `bank`
--

CREATE TABLE `bank` (
  `id_bank` int(10) UNSIGNED NOT NULL,
  `nama_bank` varchar(100) NOT NULL,
  `no_rekening` varchar(50) NOT NULL,
  `nama_pemilik` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id_barang` int(10) UNSIGNED NOT NULL,
  `id_brand` int(10) UNSIGNED NOT NULL,
  `id_jenis` int(10) UNSIGNED NOT NULL,
  `id_model` int(10) UNSIGNED NOT NULL,
  `nama_barang` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga_jual` float NOT NULL,
  `harga_beli` float NOT NULL DEFAULT 0,
  `aktif` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id_barang`, `id_brand`, `id_jenis`, `id_model`, `nama_barang`, `deskripsi`, `harga_jual`, `harga_beli`, `aktif`) VALUES
(1, 1, 1, 1, 'Kaos Basic', 'Contoh produk baju', 75000, 45000, 0),
(2, 1, 2, 1, 'Celana Chino', 'Contoh produk celana', 150000, 100000, 0),
(3, 1, 3, 3, 'Rok A-Line', 'Contoh produk rok', 120000, 80000, 0),
(4, 1, 4, 1, 'Setelan Santai', 'Atasan dan bawahan dijual sebagai satu produk', 200000, 140000, 0),
(5, 2, 1, 4, 'Tanktop Kamisol Motif Bunga', 'Tanktop kamisol wanita berbahan rajut dengan motif bunga-bunga dan gaya Korean. Cocok dipadukan dengan rok, jeans, atau outer untuk tampilan kasual yang manis.\nBahan: Rajut. Motif: Bunga Bunga. Gaya: Korean. Ukuran: Allsize.', 60000, 42000, 1),
(6, 2, 1, 5, 'Tanktop polos Import', 'Tanktop wanita model polos berbahan rajut dengan desain simpel dan mudah dipadukan dengan berbagai outfit sehari-hari.\nBahan: Rajut. Motif: Polos. Gaya: Basic. Ukuran: Allsize.', 40000, 28000, 1),
(7, 2, 1, 5, 'Crop Top Polos', 'Crop top polos berbahan rajut dengan model simpel dan kasual. Cocok dipadukan dengan celana high waist, rok, atau outer.\nBahan: Rajut. Motif: Polos. Gaya: Basic. Ukuran: Allsize.', 45000, 31500, 1),
(8, 2, 1, 5, 'Tanktop Wanita Import Korean Style', 'Tanktop wanita import bergaya Korean dengan bahan nilon dan desain polos. Pilihan simpel untuk padu padan outfit kasual maupun gaya yang lebih modis.\nBahan: Nilon. Motif: Polos. Gaya: Basic,Korean,Seksi. Ukuran: Allsize.', 50000, 35000, 1),
(9, 2, 1, 4, 'Tamktop Rajut', 'Tanktop kamisol wanita berbahan rajut dengan desain polos. Modelnya simpel dan mudah dipadukan dengan berbagai bawahan favorit.\nBahan: Rajut. Motif: Polos. Gaya: Seksi. Ukuran: Allsize.', 40000, 28000, 1),
(10, 2, 1, 5, 'Tanktop Tali Spageti', 'Tanktop wanita dengan tali spageti berbahan spandek dan motif polos. Cocok untuk padu padan outfit kasual atau sebagai inner.\nBahan: Spandek. Motif: Polos. Gaya: Seksi. Ukuran: Allsize.', 35000, 24500, 1),
(11, 2, 1, 5, 'Tanktop Silang', 'Tanktop wanita model silang berbahan rajut dengan desain polos. Memberikan tampilan simpel dan modis untuk berbagai padu padan outfit.\nBahan: Rajut. Motif: Polos. Gaya: Seksi. Ukuran: Allsize.', 50000, 35000, 1),
(12, 2, 1, 4, 'Tanktop Tali Kecil Import', 'Tanktop kamisol wanita dengan tali kecil berbahan nilon spandek dan desain polos. Praktis dipadukan dengan kemeja, cardigan, atau outer.\nBahan: Nilon Spandek. Motif: Polos. Gaya: Basic. Ukuran: Allsize.', 60000, 42000, 1),
(13, 2, 1, 4, 'Tanktop Milky Import', 'Tanktop kamisol wanita berbahan nilon dengan motif polos dan tampilan basic. Cocok digunakan untuk outfit sehari-hari maupun sebagai inner.\nBahan: Nilon. Motif: Polos. Gaya: Basic. Ukuran: Allsize.', 70000, 49000, 1),
(14, 2, 1, 6, 'Tweed Blezer Iort', 'Blazer tweed import dengan motif kotak-kotak dan gaya Korean. Cocok dipadukan dengan atasan polos serta celana atau rok untuk tampilan rapi dan modis.\nBahan: Wol. Motif: Kotak Kotak. Gaya: Korean. Ukuran: Allsize.', 285000, 199500, 1),
(15, 2, 1, 6, 'Cardigan Rajut Import', 'Cardigan wanita berbahan rajut dengan motif houndstooth dan gaya basic street style. Cocok sebagai outer untuk melengkapi outfit sehari-hari.\nBahan: Rajut. Motif: Houndstooht. Gaya: Basic,Street Style. Ukuran: Allsize.', 215000, 150500, 1),
(16, 2, 1, 7, 'Dress BodyCon', 'Dress model bodycon berbahan rajut dengan motif polos. Desainnya mengikuti siluet tubuh dan cocok dipadukan dengan aksesori sederhana.\nBahan: Rajut. Motif: Polos. Gaya: Korean. Ukuran: Allsize.', 92000, 64400, 1),
(17, 2, 1, 8, 'Sabrina Polka', 'Atasan model Sabrina berbahan sifon dengan motif polkadot. Memberikan tampilan feminin dan cocok dipadukan dengan rok maupun celana.\nBahan: Sifon. Motif: Polkadot. Gaya: Korean. Ukuran: Allsize.', 100000, 70000, 1),
(18, 2, 1, 9, 'Blouse V Crop', 'Blouse crop model V berbahan rajut dengan motif polos. Desain simpel yang mudah dipadukan dengan bawahan kasual maupun formal santai.\nBahan: Rajut. Motif: Polos. Gaya: Korean. Ukuran: Allsize.', 40000, 28000, 1),
(19, 2, 1, 9, 'Bludru Panjang', 'Blouse panjang berbahan velvet dengan tampilan lembut dan elegan. Cocok dipadukan dengan celana panjang atau rok untuk berbagai acara.\nBahan: Velvet. Motif: Bludru. Gaya: Korean. Ukuran: Allsize.', 65000, 45500, 1),
(20, 2, 1, 9, 'Sabrina Flower', 'Blouse model Sabrina dengan motif bunga-bunga. Desain feminin yang cocok dipadukan dengan bawahan polos agar tampilan tetap seimbang.\nBahan: Sifon. Motif: Bunga Bunga. Gaya: Korean. Ukuran: Allsize.', 95000, 66500, 1),
(21, 2, 1, 10, 'Kemeja Putih Polos', 'Kemeja wanita berbahan chiffon dengan desain polos. Cocok untuk tampilan simpel dan rapi, serta mudah dipadukan dengan berbagai bawahan.\nBahan: Chiffon. Motif: Polos. Gaya: Korean. Ukuran: Allsize.', 35000, 24500, 1),
(22, 2, 1, 5, 'Croptop Kancing', 'Croptop kancing berbahan spandek dengan desain kancing Model simpel yang cocok untuk padu padan outfit kasual sehari-hari.\nBahan: Spandek. Motif: Kancing. Gaya: Seksi. Ukuran: Allsize (Fit to M).', 45000, 31500, 1),
(23, 2, 1, 5, 'Tanktop Zebra', 'Tanktop wanita motif zebra dengan tampilan modis. Cocok dipadukan dengan bawahan polos untuk membuat motifnya menjadi fokus outfit.\nBahan: Spandek. Motif: Zebra. Gaya: Seksi. Ukuran: Allsize.', 45000, 31500, 1),
(24, 2, 1, 9, 'Sabrina Motif Tali Rantai', 'Blouse model Sabrina dengan detail tali dan aksen rantai. Cocok untuk tampilan feminin yang lebih menarik saat dipadukan dengan bawahan simpel.\nBahan: Rayon. Motif: Garis. Gaya: Basic. Ukuran: Allsize.', 40000, 28000, 1),
(25, 2, 3, 11, 'Rok Motif', 'Rok panjang bermotif yg nyaman, adem cocok dipakai sehari- hari\nBahan: Wolfis. Motif: Bunga dan Daun. Gaya: Basic. Ukuran: Allsize.', 100000, 70000, 1),
(26, 2, 1, 9, 'Sabrina Tali Sepatu', 'Blouse model Sabrina dengan detail tali sepatu yang memberi aksen unik. Cocok dipadukan dengan bawahan polos untuk tampilan kasual feminin.\nBahan: Sifon. Motif: Polos. Gaya: Seksi. Ukuran: Allsize.', 35000, 24500, 1),
(27, 2, 4, 12, 'Kemeja Monalisa &Baggy Pants Satu Set', 'Setelan wanita yang terdiri dari kemeja Monalisa dan baggy pants. Praktis untuk mendapatkan tampilan serasi tanpa perlu memilih atasan dan bawahan secara terpisah.\nBahan: Rayon. Motif: Daun. Gaya: Basic. Ukuran: Allsize.', 85000, 59500, 1),
(28, 2, 1, 9, 'Sabrina Rantai', 'Blouse model Sabrina dengan aksen rantai sebagai detail pemanis. Cocok untuk padu padan kasual maupun gaya yang lebih modis.\nBahan: Sifon. Motif: Polos. Gaya: Basic. Ukuran: Allsize.', 50000, 35000, 1),
(29, 2, 1, 7, 'Dress Sabrina', 'Dress wanita model Sabrina dengan desain feminin. Cocok dipadukan dengan aksesori sederhana untuk tampilan yang manis.\nBahan: Rayon Polyester. Motif: Polos. Gaya: Basic. Ukuran: Allsize.', 65000, 45500, 1),
(30, 2, 1, 9, 'Sabrina Crop', 'Blouse model Sabrina crop dengan potongan pendek dan tampilan kasual. Cocok dipadukan dengan bawahan high waist.\nBahan: Rayon Polyester. Motif: Polkadot. Gaya: Korean. Ukuran: Allsize.', 45000, 31500, 1),
(31, 2, 1, 9, 'Blouse Yukensi Monolog', 'Blouse Yukensi Monolog dengan desain simpel dan modern. Mudah dipadukan dengan celana panjang, jeans, atau rok.\nBahan: Rayon. Motif: Polos. Gaya: Korean. Ukuran: Allsize.', 40000, 28000, 1),
(32, 2, 1, 5, 'Tanktop Bulu Import', 'Tanktop wanita berbulu dengan tampilan unik dan modis. Cocok dipadukan dengan bawahan polos untuk menciptakan padu padan yang seimbang.\nBahan: Bulu. Motif: Bulu. Gaya: Korean. Ukuran: Allsize.', 80000, 56000, 1),
(33, 2, 1, 9, 'Sabrina Karet', 'Blouse model Sabrina dengan detail karet yang membantu memberi bentuk pada bagian tertentu. Cocok untuk tampilan feminin dan kasual.\nBahan: Rayon Polyester. Motif: Polos. Gaya: Basic. Ukuran: Allsize.', 45000, 31500, 1),
(34, 2, 1, 9, 'Sabrina Tali Punggung', 'Blouse model Sabrina dengan detail tali di bagian punggung. Memberikan aksen menarik dan cocok dipadukan dengan bawahan simpel.\nBahan: Rayon Polyester. Motif: Polos. Gaya: Basic. Ukuran: Allsize.', 45000, 31500, 1),
(35, 2, 1, 9, 'Sabrina Kalong', 'Blouse model Sabrina dengan potongan kalong yang memberi kesan longgar dan nyaman. Cocok untuk padu padan outfit kasual.\nBahan: Rayon Polyester. Motif: Polos. Gaya: Basic. Ukuran: Allsize.', 35000, 24500, 1),
(36, 2, 1, 5, 'Tanktop Rajut Glitter', 'Tanktop berglitter dengan tampilan unik dan modis. Cocok dipadukan dengan bawahan polos atau high waist sebagai outfit statement.\nBahan: Rajut. Motif: Glitter. Gaya: Korean. Ukuran: Allsize.', 50000, 35000, 1),
(37, 2, 1, 13, 'CL Homedress', 'Daster wanita leopard tidak menerawang, tidak luntur cocok dipakai di dalam atau di luar rumah\nBahan: Premium Katun Rayon. Motif: Leopard. Gaya: Basic. Ukuran: Allsize (S- Fit to XL).', 75000, 52500, 1),
(38, 2, 1, 14, 'Kaftan Brukat', 'Kaftan dengan model brukat berwarna putih tidak bikin gatal, cocok untuk acara santai hingga formal\nBahan: Brukat. Motif: Brukat. Gaya: Mewah. Ukuran: Allsize.', 250000, 175000, 1),
(39, 2, 1, 14, 'Kaftan Polos', 'Kaftan dengan model polos banyak pilihan warna cocok untuk acara santai hingga formal\nBahan: Satin Silk. Motif: Polos. Gaya: Mewah. Ukuran: Allsize.', 200000, 140000, 1),
(40, 2, 1, 14, 'Kaftan Motif', 'Kaftan dengan model bermotif  ada 2  pilihan warna cocok dipakai untuk acara santai hingga formal\nBahan: Satin silk. Motif: Bermotif. Gaya: Mewah. Ukuran: Allsize.', 250000, 175000, 1),
(41, 2, 1, 15, 'Tunik Strip', 'Tunik Salur berpotongan loose fit berbahan Rayon adem, halus, dan jatuh\nBahan: Rayon. Motif: Bermotif garis-garis. Gaya: Basic. Ukuran: Allsize (Fit to L).', 89000, 62300, 1),
(42, 2, 1, 16, 'Gamis Mutiara', 'Gamis mutiara anggun memberi kesan elegan dan ramping serta nyaman dipakai\nBahan: Katun Toyobo. Motif: Manik-manik. Gaya: Basic. Ukuran: Allsize (Fit to L).', 150000, 105000, 1),
(43, 2, 2, 17, 'Kulot Salur', 'Celana kulot salur bahan scuba nyaman, adem, cocok untuk sehari-hari\nBahan: Scuba. Motif: Salur. Gaya: Basic. Ukuran: Allsize (Fit to L).', 100000, 70000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `brand`
--

CREATE TABLE `brand` (
  `id_brand` int(10) UNSIGNED NOT NULL,
  `nama_brand` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brand`
--

INSERT INTO `brand` (`id_brand`, `nama_brand`, `logo`) VALUES
(1, 'Brand Kelompok', NULL),
(2, 'CL Collection', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id_detail` int(10) UNSIGNED NOT NULL,
  `id_transaksi` int(10) UNSIGNED NOT NULL,
  `id_varian` int(10) UNSIGNED NOT NULL,
  `nama_barang_saat_beli` varchar(150) NOT NULL,
  `varian_saat_beli` varchar(100) NOT NULL,
  `harga_jual` float NOT NULL,
  `qty` int(11) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `gambar_barang`
--

CREATE TABLE `gambar_barang` (
  `id_gambar` int(10) UNSIGNED NOT NULL,
  `id_barang` int(10) UNSIGNED NOT NULL,
  `url_gambar` varchar(255) NOT NULL,
  `urutan` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jenis_barang`
--

CREATE TABLE `jenis_barang` (
  `id_jenis` int(10) UNSIGNED NOT NULL,
  `nama_jenis` varchar(50) NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jenis_barang`
--

INSERT INTO `jenis_barang` (`id_jenis`, `nama_jenis`, `keterangan`) VALUES
(1, 'Baju', 'Atasan'),
(2, 'Celana', 'Bawahan celana'),
(3, 'Rok', 'Bawahan rok'),
(4, 'Setelan', 'Satu paket, stok tersendiri');

-- --------------------------------------------------------

--
-- Table structure for table `keranjang_belanja`
--

CREATE TABLE `keranjang_belanja` (
  `id_keranjang` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `id_varian` int(10) UNSIGNED NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1
) ;

-- --------------------------------------------------------

--
-- Table structure for table `model_barang`
--

CREATE TABLE `model_barang` (
  `id_model` int(10) UNSIGNED NOT NULL,
  `nama_model` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_barang`
--

INSERT INTO `model_barang` (`id_model`, `nama_model`, `keterangan`) VALUES
(1, 'Regular Fit', 'Potongan standar'),
(2, 'Oversize', 'Potongan longgar'),
(3, 'A-Line', 'Potongan melebar ke bawah'),
(4, 'Kamisol/Tanktop', NULL),
(5, 'Tanktop', NULL),
(6, 'Cardigan', NULL),
(7, 'Dress', NULL),
(8, 'Sabrina', NULL),
(9, 'Blouse', NULL),
(10, 'Kemeja', NULL),
(11, 'Rok Panjang', NULL),
(12, 'Kemeja dan Baggy Pants', NULL),
(13, 'Daster', NULL),
(14, 'Kaftan', NULL),
(15, 'Tunik', NULL),
(16, 'Gamis', NULL),
(17, 'Kulot', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(10) UNSIGNED NOT NULL,
  `id_transaksi` int(10) UNSIGNED NOT NULL,
  `id_bank` int(10) UNSIGNED NOT NULL,
  `jumlah_bayar` float NOT NULL,
  `tanggal_bayar` datetime DEFAULT NULL,
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `status_pembayaran` varchar(30) NOT NULL DEFAULT 'menunggu' CHECK (`status_pembayaran` in ('menunggu','menunggu_verifikasi','diterima','ditolak'))
) ;

-- --------------------------------------------------------

--
-- Table structure for table `pengiriman`
--

CREATE TABLE `pengiriman` (
  `id_pengiriman` int(10) UNSIGNED NOT NULL,
  `id_transaksi` int(10) UNSIGNED NOT NULL,
  `nama_penerima` varchar(100) NOT NULL,
  `no_telp` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `kode_pos` varchar(10) NOT NULL,
  `kurir` varchar(50) NOT NULL,
  `layanan` varchar(50) NOT NULL,
  `no_resi` varchar(100) DEFAULT NULL,
  `status_pengiriman` varchar(30) NOT NULL DEFAULT 'belum_dikirim' CHECK (`status_pengiriman` in ('belum_dikirim','dikirim','diterima')),
  `tgl_kirim` datetime DEFAULT NULL,
  `tgl_diterima` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `no_invoice` varchar(50) NOT NULL,
  `tgl_transaksi` datetime NOT NULL DEFAULT current_timestamp(),
  `status_pesanan` varchar(30) NOT NULL DEFAULT 'menunggu_pembayaran' CHECK (`status_pesanan` in ('menunggu_pembayaran','diproses','dikirim','selesai','dibatalkan')),
  `subtotal` float NOT NULL,
  `diskon` float NOT NULL DEFAULT 0,
  `ongkir` float NOT NULL DEFAULT 0,
  `total_bayar` float NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `ukuran_barang`
--

CREATE TABLE `ukuran_barang` (
  `id_ukuran` int(10) UNSIGNED NOT NULL,
  `id_jenis` int(10) UNSIGNED NOT NULL,
  `nama_ukuran` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ukuran_barang`
--

INSERT INTO `ukuran_barang` (`id_ukuran`, `id_jenis`, `nama_ukuran`) VALUES
(18, 1, 'All Size'),
(22, 1, 'All Size Fit to L'),
(19, 1, 'All Size Fit to M'),
(21, 1, 'All Size S-XL'),
(3, 1, 'L'),
(2, 1, 'M'),
(1, 1, 'S'),
(4, 1, 'XL'),
(5, 2, '28'),
(6, 2, '30'),
(7, 2, '32'),
(8, 2, '34'),
(23, 2, 'All Size Fit to L'),
(13, 3, 'All Size'),
(11, 3, 'L'),
(10, 3, 'M'),
(9, 3, 'S'),
(12, 3, 'XL'),
(20, 4, 'All Size'),
(16, 4, 'L'),
(15, 4, 'M'),
(14, 4, 'S'),
(17, 4, 'XL');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(10) UNSIGNED NOT NULL,
  `nama_user` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'customer' CHECK (`role` in ('admin','customer')),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `varian_barang`
--

CREATE TABLE `varian_barang` (
  `id_varian` int(10) UNSIGNED NOT NULL,
  `id_barang` int(10) UNSIGNED NOT NULL,
  `id_ukuran` int(10) UNSIGNED NOT NULL,
  `warna` varchar(50) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0
) ;

--
-- Dumping data for table `varian_barang`
--

INSERT INTO `varian_barang` (`id_varian`, `id_barang`, `id_ukuran`, `warna`, `sku`, `stok`) VALUES
(1, 1, 2, 'Hitam', 'KBS-M-HTM', 10),
(2, 1, 3, 'Putih', 'KBS-L-PTH', 8),
(3, 2, 6, 'Krem', 'CCH-30-KRM', 7),
(4, 2, 7, 'Hitam', 'CCH-32-HTM', 5),
(5, 3, 10, 'Hitam', 'RAL-M-HTM', 6),
(6, 3, 11, 'Cokelat', 'RAL-L-CKL', 4),
(7, 4, 15, 'Abu-abu', 'STS-M-ABU', 5),
(8, 4, 16, 'Hitam', 'STS-L-HTM', 3),
(9, 5, 18, 'Hitam', 'CL-001-01', 5),
(10, 5, 18, 'Putih', 'CL-001-02', 5),
(11, 6, 18, 'Ungu', 'CL-002-01', 5),
(12, 6, 18, 'Putih', 'CL-002-02', 5),
(13, 6, 18, 'Hijau', 'CL-002-03', 5),
(14, 6, 18, 'Coklat', 'CL-002-04', 5),
(15, 6, 18, 'Mustard', 'CL-002-05', 5),
(16, 6, 18, 'Hitam', 'CL-002-06', 5),
(17, 7, 18, 'Pink', 'CL-003-01', 5),
(18, 7, 18, 'Hijau', 'CL-003-02', 5),
(19, 7, 18, 'Ungu', 'CL-003-03', 5),
(20, 7, 18, 'Hitam', 'CL-003-04', 5),
(21, 7, 18, 'Mustard', 'CL-003-05', 5),
(22, 7, 18, 'Putih', 'CL-003-06', 5),
(23, 8, 18, 'Hitam', 'CL-004-01', 5),
(24, 8, 18, 'Putih', 'CL-004-02', 5),
(25, 8, 18, 'Coklat', 'CL-004-03', 5),
(26, 8, 18, 'Hijau', 'CL-004-04', 5),
(27, 8, 18, 'Army', 'CL-004-05', 5),
(28, 9, 18, 'Coklat', 'CL-005-01', 5),
(29, 9, 18, 'Kuning', 'CL-005-02', 5),
(30, 9, 18, 'Putih', 'CL-005-03', 5),
(31, 9, 18, 'Hitam', 'CL-005-04', 5),
(32, 9, 18, 'Biru', 'CL-005-05', 5),
(33, 10, 18, 'Hitam', 'CL-006-01', 5),
(34, 10, 18, 'Putih', 'CL-006-02', 5),
(35, 11, 18, 'Maroon', 'CL-007-01', 5),
(36, 11, 18, 'Putih', 'CL-007-02', 5),
(37, 11, 18, 'Kuning', 'CL-007-03', 5),
(38, 12, 18, 'Khaki', 'CL-008-01', 5),
(39, 12, 18, 'Abu', 'CL-008-02', 5),
(40, 12, 18, 'Putih', 'CL-008-03', 5),
(41, 12, 18, 'Hitam', 'CL-008-04', 5),
(42, 13, 18, 'Milky', 'CL-009-01', 5),
(43, 13, 18, 'Hitam', 'CL-009-02', 5),
(44, 13, 18, 'Putih', 'CL-009-03', 5),
(45, 13, 18, 'Cream', 'CL-009-04', 5),
(46, 13, 18, 'Coklat', 'CL-009-05', 5),
(47, 14, 18, 'Pink', 'CL-010-01', 5),
(48, 14, 18, 'Biru', 'CL-010-02', 5),
(49, 14, 18, 'Putih', 'CL-010-03', 5),
(50, 15, 18, 'Hitam', 'CL-011-01', 5),
(51, 16, 18, 'Hitam', 'CL-012-01', 5),
(52, 17, 18, 'Hijau Mint', 'CL-013-01', 5),
(53, 17, 18, 'Ungu', 'CL-013-02', 5),
(54, 17, 18, 'Putih', 'CL-013-03', 5),
(55, 17, 18, 'Hitam', 'CL-013-04', 5),
(56, 18, 18, 'Navy', 'CL-014-01', 5),
(57, 18, 18, 'Ungu', 'CL-014-02', 5),
(58, 18, 18, 'Mustard', 'CL-014-03', 5),
(59, 18, 18, 'Hujau', 'CL-014-04', 5),
(60, 18, 18, 'Pink', 'CL-014-05', 5),
(61, 19, 18, 'Maroon', 'CL-015-01', 5),
(62, 19, 18, 'Emerald', 'CL-015-02', 5),
(63, 19, 18, 'Pink', 'CL-015-03', 5),
(64, 19, 18, 'Navy', 'CL-015-04', 5),
(65, 19, 18, 'Hitam', 'CL-015-05', 5),
(66, 20, 18, 'Hitam', 'CL-016-01', 5),
(67, 20, 18, 'Putih', 'CL-016-02', 5),
(68, 20, 18, 'Pink', 'CL-016-03', 5),
(69, 20, 18, 'Hijau', 'CL-016-04', 5),
(70, 20, 18, 'Cream', 'CL-016-05', 5),
(71, 21, 18, 'Putih', 'CL-017-01', 5),
(72, 22, 19, 'Hitam', 'CL-018-01', 5),
(73, 22, 19, 'Abu', 'CL-018-02', 5),
(74, 22, 19, 'Putih', 'CL-018-03', 5),
(75, 23, 18, 'Hitam', 'CL-019-01', 5),
(76, 24, 18, 'Cream', 'CL-020-01', 5),
(77, 25, 13, 'Pink', 'CL-021-01', 5),
(78, 25, 13, 'Hitam', 'CL-021-02', 5),
(79, 25, 13, 'Cream', 'CL-021-03', 5),
(80, 26, 18, 'Hitam', 'CL-022-01', 5),
(81, 27, 20, 'Cream', 'CL-023-01', 5),
(82, 28, 18, 'Hitam', 'CL-024-01', 5),
(83, 29, 18, 'Mustard', 'CL-025-01', 5),
(84, 30, 18, 'Hitam', 'CL-026-01', 5),
(85, 31, 18, 'Navy', 'CL-027-01', 5),
(86, 32, 18, 'Biru', 'CL-028-01', 5),
(87, 32, 18, 'Pink', 'CL-028-02', 5),
(88, 32, 18, 'Putih', 'CL-028-03', 5),
(89, 32, 18, 'Hitam', 'CL-028-04', 5),
(90, 33, 18, 'Army', 'CL-029-01', 5),
(91, 34, 18, 'Putih', 'CL-030-01', 5),
(92, 35, 18, 'Army', 'CL-031-01', 5),
(93, 36, 18, 'Abu Muda', 'CL-032-01', 5),
(94, 36, 18, 'Putih', 'CL-032-02', 5),
(95, 36, 18, 'Cream', 'CL-032-03', 5),
(96, 36, 18, 'Abu Tua', 'CL-032-04', 5),
(97, 36, 18, 'Dusty Pink', 'CL-032-05', 5),
(98, 37, 21, 'Leopard', 'CL-033-01', 5),
(99, 38, 18, 'Putih', 'CL-034-01', 5),
(100, 39, 18, 'Hitam', 'CL-035-01', 5),
(101, 39, 18, 'Pink', 'CL-035-02', 5),
(102, 39, 18, 'Caramel', 'CL-035-03', 5),
(103, 39, 18, 'Navi', 'CL-035-04', 5),
(104, 39, 18, 'Abu-abu', 'CL-035-05', 5),
(105, 40, 18, 'Biru', 'CL-036-01', 5),
(106, 40, 18, 'Pink', 'CL-036-02', 5),
(107, 41, 22, 'Navi', 'CL-037-01', 5),
(108, 41, 22, 'Putih', 'CL-037-02', 5),
(109, 42, 22, 'Hijau mint', 'CL-038-01', 5),
(110, 43, 23, 'Peach', 'CL-039-01', 5),
(111, 43, 23, 'putih', 'CL-039-02', 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bank`
--
ALTER TABLE `bank`
  ADD PRIMARY KEY (`id_bank`),
  ADD UNIQUE KEY `uq_bank_rekening` (`nama_bank`,`no_rekening`);

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`),
  ADD KEY `fk_barang_brand` (`id_brand`),
  ADD KEY `fk_barang_jenis_barang` (`id_jenis`),
  ADD KEY `fk_barang_model_barang` (`id_model`);

--
-- Indexes for table `brand`
--
ALTER TABLE `brand`
  ADD PRIMARY KEY (`id_brand`);

--
-- Indexes for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `fk_detail_transaksi` (`id_transaksi`),
  ADD KEY `fk_detail_varian_barang` (`id_varian`);

--
-- Indexes for table `gambar_barang`
--
ALTER TABLE `gambar_barang`
  ADD PRIMARY KEY (`id_gambar`),
  ADD KEY `fk_gambar_barang` (`id_barang`);

--
-- Indexes for table `jenis_barang`
--
ALTER TABLE `jenis_barang`
  ADD PRIMARY KEY (`id_jenis`),
  ADD UNIQUE KEY `nama_jenis` (`nama_jenis`);

--
-- Indexes for table `keranjang_belanja`
--
ALTER TABLE `keranjang_belanja`
  ADD PRIMARY KEY (`id_keranjang`),
  ADD UNIQUE KEY `uq_keranjang_user_varian` (`id_user`,`id_varian`),
  ADD KEY `fk_keranjang_varian_barang` (`id_varian`);

--
-- Indexes for table `model_barang`
--
ALTER TABLE `model_barang`
  ADD PRIMARY KEY (`id_model`),
  ADD UNIQUE KEY `nama_model` (`nama_model`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `fk_pembayaran_transaksi` (`id_transaksi`),
  ADD KEY `fk_pembayaran_bank` (`id_bank`);

--
-- Indexes for table `pengiriman`
--
ALTER TABLE `pengiriman`
  ADD PRIMARY KEY (`id_pengiriman`),
  ADD UNIQUE KEY `id_transaksi` (`id_transaksi`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD UNIQUE KEY `no_invoice` (`no_invoice`),
  ADD KEY `fk_transaksi_user` (`id_user`);

--
-- Indexes for table `ukuran_barang`
--
ALTER TABLE `ukuran_barang`
  ADD PRIMARY KEY (`id_ukuran`),
  ADD UNIQUE KEY `uq_ukuran_jenis` (`id_jenis`,`nama_ukuran`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `varian_barang`
--
ALTER TABLE `varian_barang`
  ADD PRIMARY KEY (`id_varian`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `uq_varian` (`id_barang`,`id_ukuran`,`warna`),
  ADD KEY `fk_varian_ukuran_barang` (`id_ukuran`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bank`
--
ALTER TABLE `bank`
  MODIFY `id_bank` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id_barang` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `brand`
--
ALTER TABLE `brand`
  MODIFY `id_brand` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id_detail` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gambar_barang`
--
ALTER TABLE `gambar_barang`
  MODIFY `id_gambar` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jenis_barang`
--
ALTER TABLE `jenis_barang`
  MODIFY `id_jenis` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `keranjang_belanja`
--
ALTER TABLE `keranjang_belanja`
  MODIFY `id_keranjang` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `model_barang`
--
ALTER TABLE `model_barang`
  MODIFY `id_model` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengiriman`
--
ALTER TABLE `pengiriman`
  MODIFY `id_pengiriman` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ukuran_barang`
--
ALTER TABLE `ukuran_barang`
  MODIFY `id_ukuran` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `varian_barang`
--
ALTER TABLE `varian_barang`
  MODIFY `id_varian` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `fk_barang_brand` FOREIGN KEY (`id_brand`) REFERENCES `brand` (`id_brand`),
  ADD CONSTRAINT `fk_barang_jenis_barang` FOREIGN KEY (`id_jenis`) REFERENCES `jenis_barang` (`id_jenis`),
  ADD CONSTRAINT `fk_barang_model_barang` FOREIGN KEY (`id_model`) REFERENCES `model_barang` (`id_model`);

--
-- Constraints for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `fk_detail_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`),
  ADD CONSTRAINT `fk_detail_varian_barang` FOREIGN KEY (`id_varian`) REFERENCES `varian_barang` (`id_varian`);

--
-- Constraints for table `gambar_barang`
--
ALTER TABLE `gambar_barang`
  ADD CONSTRAINT `fk_gambar_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON DELETE CASCADE;

--
-- Constraints for table `keranjang_belanja`
--
ALTER TABLE `keranjang_belanja`
  ADD CONSTRAINT `fk_keranjang_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_keranjang_varian_barang` FOREIGN KEY (`id_varian`) REFERENCES `varian_barang` (`id_varian`);

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `fk_pembayaran_bank` FOREIGN KEY (`id_bank`) REFERENCES `bank` (`id_bank`),
  ADD CONSTRAINT `fk_pembayaran_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`);

--
-- Constraints for table `pengiriman`
--
ALTER TABLE `pengiriman`
  ADD CONSTRAINT `fk_pengiriman_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`);

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`);

--
-- Constraints for table `ukuran_barang`
--
ALTER TABLE `ukuran_barang`
  ADD CONSTRAINT `fk_ukuran_jenis_barang` FOREIGN KEY (`id_jenis`) REFERENCES `jenis_barang` (`id_jenis`);

--
-- Constraints for table `varian_barang`
--
ALTER TABLE `varian_barang`
  ADD CONSTRAINT `fk_varian_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `fk_varian_ukuran_barang` FOREIGN KEY (`id_ukuran`) REFERENCES `ukuran_barang` (`id_ukuran`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
