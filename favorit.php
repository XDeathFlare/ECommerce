<?php
require_once __DIR__ . '/sesi.php';
require __DIR__ . '/koneksi.php';

$idUser = (int) ($_SESSION['id_user'] ?? 0);

if ($idUser < 1) {
    header('Location: login.php');
    exit;
}

$stmtAkun = $koneksi->prepare(
    "SELECT role FROM `user` WHERE id_user = ?"
);
$stmtAkun->bind_param('i', $idUser);
$stmtAkun->execute();

$akun = $stmtAkun->get_result()->fetch_assoc();

if (!$akun || $akun['role'] !== 'customer') {
    http_response_code(403);
    exit('Halaman favorit hanya tersedia untuk customer.');
}

header('Cache-Control: no-store');

function aman($nilai)
{
    return htmlspecialchars(
        (string) $nilai,
        ENT_QUOTES,
        'UTF-8'
    );
}

if (empty($_SESSION['csrf_favorit'])) {
    $_SESSION['csrf_favorit'] = bin2hex(random_bytes(32));
}

$pesanFavorit = $_SESSION['pesan_favorit'] ?? '';
unset($_SESSION['pesan_favorit']);

$stmt = $koneksi->prepare("
    SELECT
        b.id_barang,
        b.nama_barang,
        b.harga_jual,
        b.aktif,
        j.nama_jenis,
        (
            SELECT g.url_gambar
            FROM gambar_barang AS g
            WHERE g.id_barang = b.id_barang
            ORDER BY g.urutan, g.id_gambar
            LIMIT 1
        ) AS foto
    FROM favorit AS f
    JOIN barang AS b ON b.id_barang = f.id_barang
    JOIN jenis_barang AS j ON j.id_jenis = b.id_jenis
    WHERE f.id_user = ?
    ORDER BY f.id_favorit DESC
");

$stmt->bind_param('i', $idUser);
$stmt->execute();

$produk = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Favorit Saya — CL Collection</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Parisienne&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="topbar">
        CL COLLECTION — YOUR STYLE, YOUR STORY
    </div>

    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="index.php">
                <img
                    src="assets/images/logo.png"
                    alt="Logo CL Collection"
                >

                <div>
                    <span class="brand-name">
                        CL COLLECTION
                    </span>
                    <small>EXPRESS YOUR STYLE</small>
                </div>
            </a>

            <nav class="nav" aria-label="Navigasi utama">
                <a href="index.php">Beranda</a>
                <a href="katalog.php">Katalog</a>
                <a href="favorit.php" aria-current="page">
                    Favorit
                </a>
                <a href="keranjang.php">Keranjang</a>

                <?php require __DIR__ . '/menu_akun.php'; ?>
            </nav>
        </div>
    </header>

    <section class="catalog-banner">
        <div class="container">
            <span class="eyebrow">Your lovely picks</span>
            <h1>Favorit Saya</h1>
            <p>
                Simpan pilihan kesukaanmu, temukan lagi
                kapan pun kamu mau.
            </p>
        </div>
    </section>

    <main class="catalog-section" id="hasil">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">
                        Pilihan kesukaanmu
                    </span>
                    <h2>Koleksi Favorit</h2>
                </div>

                <p>
                    <?= (int) $produk->num_rows ?>
                    produk tersimpan
                </p>
            </div>

            <?php if ($pesanFavorit !== ''): ?>
                <p class="favorit-notifikasi" role="status">
                    <?= aman($pesanFavorit) ?>
                </p>
            <?php endif; ?>

            <?php if ($produk->num_rows === 0): ?>
                <div class="empty-state">
                    <h3>Belum ada produk favorit</h3>

                    <p>
                        Klik hati di katalog untuk menyimpan
                        produk kesukaanmu.
                    </p>

                    <a class="button" href="katalog.php">
                        Jelajahi Katalog
                    </a>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php while ($barang = $produk->fetch_assoc()): ?>
                        <?php
                        $foto = trim(
                            (string) ($barang['foto'] ?? '')
                        );

                        $adaFoto = $foto !== '';
                        $aktif = (int) $barang['aktif'] === 1;

                        $alamatDetail = 'detail_produk.php?id='
                            . (int) $barang['id_barang'];
                        ?>

                        <article class="product-card">
                            <form
                                class="favorit-form"
                                action="proses_favorit.php"
                                method="post"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_favorit"
                                    value="<?= aman(
                                        $_SESSION['csrf_favorit']
                                    ) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id_barang"
                                    value="<?= (int) $barang['id_barang'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="aksi"
                                    value="hapus"
                                >

                                <input
                                    type="hidden"
                                    name="asal"
                                    value="favorit"
                                >

                                <button
                                    class="favorit-tombol tersimpan"
                                    type="submit"
                                    aria-label="<?= aman(
                                        'Hapus dari favorit: '
                                        . $barang['nama_barang']
                                    ) ?>"
                                    title="Hapus dari favorit"
                                >
                                    <span aria-hidden="true">
                                        &#9829;
                                    </span>
                                </button>
                            </form>

                            <div
                                class="product-image <?= !$adaFoto
                                    ? 'placeholder' : '' ?>"
                            >
                                <img
                                    src="<?= aman(
                                        $adaFoto
                                            ? $foto
                                            : 'assets/images/logo.png'
                                    ) ?>"
                                    alt="<?= aman(
                                        $adaFoto
                                            ? $barang['nama_barang']
                                            : 'Foto produk belum tersedia'
                                    ) ?>"
                                    loading="lazy"
                                >
                            </div>

                            <div class="product-info">
                                <span class="product-category">
                                    <?= aman($barang['nama_jenis']) ?>
                                </span>

                                <h3>
                                    <?= aman($barang['nama_barang']) ?>
                                </h3>

                                <p class="product-price">
                                    Rp<?= number_format(
                                        (float) $barang['harga_jual'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>
                                </p>

                                <?php if ($aktif): ?>
                                    <a
                                        class="detail-link"
                                        href="<?= aman($alamatDetail) ?>"
                                    >
                                        Lihat Detail &rarr;
                                    </a>
                                <?php else: ?>
                                    <p class="product-stock">
                                        Produk sudah tidak tersedia.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p><strong>CL COLLECTION</strong></p>
            <p>
                &copy; <?= date('Y') ?> CL Collection.
            </p>
        </div>
    </footer>
</body>
</html>