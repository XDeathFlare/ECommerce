<?php
require_once __DIR__ . '/sesi.php';
require __DIR__ . "/koneksi.php";

function aman($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, "UTF-8");
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(404);
    exit('Produk tidak ditemukan. <a href="index.php">Kembali ke koleksi</a>');
}

$stmt = $koneksi->prepare("
    SELECT b.*, j.nama_jenis, br.nama_brand
    FROM barang AS b
    JOIN jenis_barang AS j ON j.id_jenis = b.id_jenis
    JOIN brand AS br ON br.id_brand = b.id_brand
    WHERE b.id_barang = ? AND b.aktif = 1
");

$stmt->bind_param("i", $id);
$stmt->execute();
$barang = $stmt->get_result()->fetch_assoc();

if (!$barang) {
    http_response_code(404);
    exit('Produk tidak ditemukan. <a href="index.php">Kembali ke koleksi</a>');
}

$stmtVarian = $koneksi->prepare("
    SELECT v.warna, v.stok, u.nama_ukuran
    FROM varian_barang AS v
    JOIN ukuran_barang AS u ON u.id_ukuran = v.id_ukuran
    WHERE v.id_barang = ?
    ORDER BY u.nama_ukuran, v.warna
");

$stmtVarian->bind_param("i", $id);
$stmtVarian->execute();
$varian = $stmtVarian->get_result()->fetch_all(MYSQLI_ASSOC);

$stmtFoto = $koneksi->prepare("
    SELECT url_gambar
    FROM gambar_barang
    WHERE id_barang = ?
    ORDER BY urutan, id_gambar
");

$stmtFoto->bind_param("i", $id);
$stmtFoto->execute();
$fotoProduk = $stmtFoto->get_result()->fetch_all(MYSQLI_ASSOC);

$totalStok = array_sum(array_column($varian, "stok"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= aman($barang["nama_barang"]) ?> — CL Collection</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Parisienne&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="topbar">
        CL COLLECTION — YOUR STYLE, YOUR STORY
    </div>

    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="index.php">
                <img src="assets/images/logo.png" alt="Logo CL Collection">
                <div>
                    <span class="brand-name">CL COLLECTION</span>
                    <small>EXPRESS YOUR STYLE</small>
                </div>
            </a>

            <nav class="nav" aria-label="Navigasi utama">
                <a href="index.php">Beranda</a>
                <a href="katalog.php">Katalog</a>
                <a href="index.php#tentang">Tentang Kami</a>

                <?php require __DIR__ . '/menu_akun.php'; ?>
            </nav>
        </div>
    </header>

    <main class="container detail-page">
        <a class="back-link" href="katalog.php">
             &larr; Kembali ke katalog
        </a>

        <div class="detail-layout">
            <section class="detail-gallery" aria-label="Foto produk">
                <?php if ($fotoProduk): ?>
                    <?php foreach ($fotoProduk as $foto): ?>
                        <img
                            class="detail-photo"
                            src="<?= aman($foto["url_gambar"]) ?>"
                            alt="<?= aman($barang["nama_barang"]) ?>"
                        >
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="detail-placeholder">
                        <img
                            src="assets/images/logo.png"
                            alt="CL Collection"
                        >
                        <p>Foto produk belum tersedia</p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="detail-content">
                <span class="eyebrow">
                    <?= aman($barang["nama_jenis"]) ?>
                </span>

                <h1><?= aman($barang["nama_barang"]) ?></h1>

                <p class="detail-brand">
                    <?= aman($barang["nama_brand"]) ?>
                </p>

                <p class="detail-price">
                    Rp<?= number_format(
                        (float) $barang["harga_jual"],
                        0,
                        ",",
                        "."
                    ) ?>
                </p>

                <span class="stock-badge">
                    <?= $totalStok > 0
                        ? "Total stok: " . (int) $totalStok . " pcs"
                        : "Stok habis" ?>
                </span>

                <h2>Deskripsi Produk</h2>

                <div class="detail-description">
                    <?= nl2br(aman($barang["deskripsi"])) ?>
                </div>

                <h2>Pilihan Ukuran dan Warna</h2>

                <?php if ($varian): ?>
                    <div class="variant-table-wrap">
                        <table class="variant-table">
                            <thead>
                                <tr>
                                    <th scope="col">Ukuran</th>
                                    <th scope="col">Warna</th>
                                    <th scope="col">Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($varian as $pilihan): ?>
                                    <tr>
                                        <td><?= aman($pilihan["nama_ukuran"]) ?></td>
                                        <td><?= aman($pilihan["warna"]) ?></td>
                                        <td>
                                            <?= (int) $pilihan["stok"] > 0
                                                ? (int) $pilihan["stok"] . " pcs"
                                                : "Habis" ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Belum ada varian produk yang tersedia.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p><strong>CL COLLECTION</strong></p>
            <p>&copy; <?= date("Y") ?> CL Collection.</p>
        </div>
    </footer>

</body>
</html>