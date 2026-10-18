<?php
require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';

// Pengunjung yang belum login diarahkan ke login admin.
if (empty($_SESSION['id_user'])) {
    header('Location: login_admin.php');
    exit;
}

// Periksa role terbaru langsung dari database.
$idUser = (int) $_SESSION['id_user'];

$cek = $koneksi->prepare(
    "SELECT nama_user, role FROM `user` WHERE id_user = ? LIMIT 1"
);
$cek->bind_param('i', $idUser);
$cek->execute();

$admin = $cek->get_result()->fetch_assoc();

if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini hanya untuk admin.');
}

function teksAdmin($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

header('Cache-Control: no-store');

$jumlahBarang = (int) $koneksi->query(
    "SELECT COUNT(*) FROM barang WHERE aktif = 1"
)->fetch_row()[0];

$jumlahCustomer = (int) $koneksi->query(
    "SELECT COUNT(*) FROM `user` WHERE role = 'customer'"
)->fetch_row()[0];

$jumlahPesanan = (int) $koneksi->query(
    "SELECT COUNT(*) FROM transaksi"
)->fetch_row()[0];

$totalStok = (int) $koneksi->query(
    "SELECT COALESCE(SUM(v.stok), 0)
     FROM varian_barang v
     JOIN barang b ON b.id_barang = v.id_barang
     WHERE b.aktif = 1"
)->fetch_row()[0];

$barangTerbaru = $koneksi->query(
    "SELECT b.id_barang, b.nama_barang, b.harga_jual,
            j.nama_jenis,
            COALESCE(s.total_stok, 0) AS total_stok
     FROM barang b
     JOIN jenis_barang j ON j.id_jenis = b.id_jenis
     LEFT JOIN (
         SELECT id_barang, SUM(stok) AS total_stok
         FROM varian_barang
         GROUP BY id_barang
     ) s ON s.id_barang = b.id_barang
     WHERE b.aktif = 1
     ORDER BY b.id_barang DESC
     LIMIT 8"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | CL Collection</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .halaman-dashboard {
            margin: 0;
            background: #fff7fa;
            color: #191519;
        }

        .header-dashboard {
            background: #fff;
            border-bottom: 1px solid #efdee5;
        }

        .wadah-dashboard {
            width: 90%;
            max-width: 1180px;
            margin: 0 auto;
        }

        .baris-dashboard {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            padding: 18px 0;
        }

        .brand-dashboard {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #191519;
            text-decoration: none;
        }

        .brand-dashboard img {
            width: 65px;
            height: 65px;
            object-fit: contain;
        }

        .brand-dashboard strong {
            display: block;
            font-size: 18px;
            letter-spacing: 1px;
        }

        .brand-dashboard small {
            color: #a8315b;
            font-size: 12px;
        }

        .aksi-dashboard {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .aksi-dashboard a {
            color: #a8315b;
            font-size: 13px;
        }

        .aksi-dashboard form {
            margin: 0;
        }

        .aksi-dashboard button {
            padding: 10px 18px;
            border: 0;
            border-radius: 20px;
            background: #191519;
            color: #fff;
            font: inherit;
            font-size: 13px;
            cursor: pointer;
        }

        .isi-dashboard {
            padding: 35px 0 55px;
        }

        .sambutan-dashboard {
            padding: 30px;
            border: 1px solid #efdee5;
            border-radius: 20px;
            background: linear-gradient(120deg, #fce8ef, #fff);
        }

        .sambutan-dashboard small {
            color: #a8315b;
            letter-spacing: 2px;
            font-size: 11px;
        }

        .sambutan-dashboard h1 {
            margin: 12px 0;
            font-family: Georgia, serif;
            font-size: clamp(26px, 4vw, 38px);
            overflow-wrap: anywhere;
        }

        .sambutan-dashboard p {
            margin: 0;
            font-size: 14px;
            line-height: 1.8;
            color: #71666d;
        }

        .statistik-dashboard {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin: 25px 0;
        }

        .statistik-dashboard article {
            padding: 24px;
            border: 1px solid #efdee5;
            border-radius: 16px;
            background: #fff;
        }

        .statistik-dashboard span {
            display: block;
            font-size: 13px;
            color: #71666d;
        }

        .statistik-dashboard strong {
            display: block;
            margin-top: 12px;
            font-size: 34px;
            color: #a8315b;
        }

        .panel-dashboard {
            padding: 26px;
            border: 1px solid #efdee5;
            border-radius: 18px;
            background: #fff;
        }

        .panel-dashboard h2 {
            margin: 0 0 8px;
            font-family: Georgia, serif;
            font-size: 26px;
        }

        .panel-dashboard > p {
            font-size: 13px;
            color: #71666d;
            line-height: 1.8;
        }

        .tabel-dashboard-wrap {
            overflow-x: auto;
        }

        .tabel-dashboard {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            text-align: left;
            font-size: 13px;
        }

        .tabel-dashboard th,
        .tabel-dashboard td {
            padding: 15px 12px;
            border-bottom: 1px solid #f0e5ea;
        }

        .tabel-dashboard th {
            background: #fff2f7;
            color: #8d3555;
        }

        .tabel-dashboard a {
            color: #a8315b;
        }

        @media (max-width: 800px) {
            .statistik-dashboard {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 450px) {
            .statistik-dashboard {
                grid-template-columns: 1fr;
            }

            .sambutan-dashboard,
            .panel-dashboard {
                padding: 20px;
            }
        }
    </style>
</head>

<body class="halaman-dashboard">
    <header class="header-dashboard">
        <div class="wadah-dashboard baris-dashboard">
            <a class="brand-dashboard" href="admin.php">
                <img src="assets/images/logo.png" alt="CL Collection">
                <div>
                    <strong>CL COLLECTION</strong>
                    <small>Dashboard Admin</small>
                </div>
            </a>

            <div class="aksi-dashboard">
                <a href="admin_barang.php">Kelola Barang</a>
                <a href="index.php">Lihat Toko ↗</a>

                <form action="logout.php" method="post">
                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= teksAdmin($_SESSION['csrf_logout']) ?>"
                    >
                    <button type="submit">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="wadah-dashboard isi-dashboard">
        <section class="sambutan-dashboard">
            <small>RUANG KELOLA TOKO</small>
            <h1>Halo, <?= teksAdmin($admin['nama_user']) ?>!</h1>
            <p>
                Selamat datang di dashboard CL Collection.
                Berikut ringkasan data toko saat ini.
            </p>
        </section>

        <section class="statistik-dashboard" aria-label="Ringkasan toko">
            <article>
                <span>Produk Aktif</span>
                <strong><?= $jumlahBarang ?></strong>
            </article>

            <article>
                <span>Total Stok Produk Aktif</span>
                <strong><?= $totalStok ?></strong>
            </article>

            <article>
                <span>Akun Customer</span>
                <strong><?= $jumlahCustomer ?></strong>
            </article>

            <article>
                <span>Total Pesanan</span>
                <strong><?= $jumlahPesanan ?></strong>
            </article>
        </section>

        <section class="panel-dashboard">
            <h2>Produk Terbaru</h2>
            <p>Menampilkan maksimal delapan produk aktif terbaru.</p>

            <div class="tabel-dashboard-wrap">
                <table class="tabel-dashboard">
                    <thead>
                        <tr>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga Jual</th>
                            <th>Stok</th>
                            <th>Detail</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($barangTerbaru->num_rows === 0): ?>
                            <tr>
                                <td colspan="5">Belum ada produk aktif.</td>
                            </tr>
                        <?php endif; ?>

                        <?php while ($barang = $barangTerbaru->fetch_assoc()): ?>
                            <tr>
                                <td><?= teksAdmin($barang['nama_barang']) ?></td>
                                <td><?= teksAdmin($barang['nama_jenis']) ?></td>
                                <td>
                                    Rp <?= number_format(
                                        (float) $barang['harga_jual'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>
                                <td><?= (int) $barang['total_stok'] ?></td>
                                <td>
                                    <a href="detail_produk.php?id=<?= (int) $barang['id_barang'] ?>">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>