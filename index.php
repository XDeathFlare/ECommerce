<?php
require_once __DIR__ . '/sesi.php';
require __DIR__ . "/koneksi.php";

function aman($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, "UTF-8");
}

$pencarian = trim((string) ($_GET["q"] ?? ""));
$kategori = max(0, (int) ($_GET["kategori"] ?? 0));

$daftarKategori = $koneksi->query("
    SELECT id_jenis, nama_jenis
    FROM jenis_barang
    ORDER BY nama_jenis
")->fetch_all(MYSQLI_ASSOC);

$stmt = $koneksi->prepare("
    SELECT
        b.id_barang,
        b.nama_barang,
        b.harga_jual,
        j.nama_jenis,
        COALESCE(s.total_stok, 0) AS total_stok,
        (
            SELECT g.url_gambar
            FROM gambar_barang AS g
            WHERE g.id_barang = b.id_barang
            ORDER BY g.urutan, g.id_gambar
            LIMIT 1
        ) AS foto
    FROM barang AS b
    JOIN jenis_barang AS j ON j.id_jenis = b.id_jenis
    LEFT JOIN (
        SELECT id_barang, SUM(stok) AS total_stok
        FROM varian_barang
        GROUP BY id_barang
    ) AS s ON s.id_barang = b.id_barang
    WHERE b.aktif = 1
      AND (? = 0 OR b.id_jenis = ?)
      AND b.nama_barang LIKE ?
    ORDER BY b.id_barang DESC
    LIMIT 8
");

$kataKunci = "%" . $pencarian . "%";
$stmt->bind_param("iis", $kategori, $kategori, $kataKunci);
$stmt->execute();
$produk = $stmt->get_result();

$jumlahProduk = (int) $koneksi->query("
    SELECT COUNT(*) AS jumlah
    FROM barang
    WHERE aktif = 1
")->fetch_assoc()["jumlah"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CL Collection — Temukan Gayamu</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Parisienne&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Oooh+Baby&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    </head>
    <body id="beranda">

    <div class="topbar">
        CL COLLECTION — YOUR STYLE, YOUR STORY
    </div>

    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="index.php" aria-label="Beranda CL Collection">
                <img src="assets/images/logo.png" alt="Logo CL Collection">

                <div>
                    <span class="brand-name">CL COLLECTION</span>
                    <small>EXPRESS YOUR STYLE</small>
                </div>
            </a>

            <nav class="nav" aria-label="Navigasi utama">
                <a href="index.php">Beranda</a>
                <a href="katalog.php">Katalog</a>
                <a href="#tentang">Tentang Kami</a>

                <?php require __DIR__ . '/menu_akun.php'; ?>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container hero-inner">
            <div>
                <span class="eyebrow">Koleksi pilihan untukmu</span>

                <h1>
                    Gayamu.<br>
                    Ceritamu.<br>
                    <em>CL Collection.</em>
                </h1>

                <p>
                    Temukan pilihan baju, celana, rok, dan setelan
                    untuk melengkapi penampilanmu.
                    Pilih warna dan model yang paling cocok dengan gayamu.
                </p>

                <a class="button" href="katalog.php">
                    Jelajahi Koleksi &rarr;
                </a>
            </div>

            <div class="hero-logo">
                <img src="assets/images/logo.png" alt="CL Collection">
            </div>
        </div>
    </section>

    <main>
        <section class="catalog-section" id="koleksi">
            <div class="container">

                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Find your favorites</span>
                        <h2>Koleksi Kami</h2>
                        <p>
                            <?= $jumlahProduk ?> produk untuk melengkapi gayamu.
                        </p>
                    </div>

                    <p>
                        Menampilkan <?= (int) $produk->num_rows ?> produk
                    </p>
                </div>

                <form class="filter-form" action="index.php#koleksi" method="get">
                    <input
                        type="search"
                        name="q"
                        placeholder="Cari tanktop, blouse, rok..."
                        aria-label="Cari nama produk"
                        value="<?= aman($pencarian) ?>"
                    >

                    <select name="kategori" aria-label="Pilih kategori">
                        <option value="0">Semua Kategori</option>

                        <?php foreach ($daftarKategori as $jenis): ?>
                            <option
                                value="<?= (int) $jenis["id_jenis"] ?>"
                                <?= $kategori === (int) $jenis["id_jenis"]
                                    ? "selected" : "" ?>
                            >
                                <?= aman($jenis["nama_jenis"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button class="button" type="submit">
                        Cari Produk
                    </button>

                    <?php if ($pencarian !== "" || $kategori > 0): ?>
                        <a class="button" href="index.php#koleksi">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <div class="product-grid">
                    <?php if ($produk->num_rows === 0): ?>
                        <div class="empty-state">
                            <h3>Produk belum ditemukan</h3>
                            <p>Coba kata kunci atau kategori lainnya.</p>
                        </div>
                    <?php endif; ?>

                    <?php while ($barang = $produk->fetch_assoc()): ?>
                        <?php
                        $foto = trim((string) ($barang["foto"] ?? ""));
                        $adaFoto = $foto !== "";
                        ?>

                        <article class="product-card">
                            <div class="product-image <?= !$adaFoto
                                ? "placeholder" : "" ?>">
                                <img
                                    src="<?= aman($adaFoto
                                        ? $foto
                                        : "assets/images/logo.png") ?>"
                                    alt="<?= aman($adaFoto
                                        ? $barang["nama_barang"]
                                        : "Foto produk belum tersedia") ?>"
                                    loading="lazy"
                                >
                            </div>

                            <div class="product-info">
                                <span class="product-category">
                                    <?= aman($barang["nama_jenis"]) ?>
                                </span>

                                <h3><?= aman($barang["nama_barang"]) ?></h3>
                                <a
    class="detail-link"
    href="detail_produk.php?id=<?= (int) $barang["id_barang"] ?>"
>
    Lihat Detail &rarr;
</a>

                                <p class="product-price">
                                    Rp<?= number_format(
                                        (float) $barang["harga_jual"],
                                        0,
                                        ",",
                                        "."
                                    ) ?>
                                </p>

                                <p class="product-stock">
                                    <?php if ((int) $barang["total_stok"] > 0): ?>
                                        Stok tersedia:
                                        <?= (int) $barang["total_stok"] ?> pcs
                                    <?php else: ?>
                                        Stok habis
                                    <?php endif; ?>
                                </p>

                                <?php if (!$adaFoto): ?>
                                    <p class="product-stock">
                                        Foto produk belum tersedia
                                    </p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>

        <section class="about-section" id="tentang">
            <div class="container">
                <span class="eyebrow">Tentang kami</span>
                <h2>Kenali CL Collection</h2>
                <p>
                    CL Collection menghadirkan pilihan pakaian wanita
                    dengan beragam model dan warna. Dari atasan untuk
                    padu padan sehari-hari hingga setelan yang serasi,
                    temukan koleksi yang sesuai dengan seleramu.
                </p>
                <div class="alamat-toko">
                <h3>Kunjungi Toko Kami</h3>
                <p>
                <strong>CL Collection</strong><br>
                    Jl. Jendral Sudirman, TangCity Mall,<br>
                    Lantai UG, Blok B 76, Cikokol, Tangerang.<br>
                    RT.001/RW.005, Babakan, Kec. Tangerang,<br>
                    Tangerang, Banten 15117.
                </p>

                <a
                class="tautan-peta"
                href="https://www.google.com/maps/search/?api=1&amp;query=CL+Collection+TangCity+Mall+Lantai+UG+Blok+B+76+Tangerang"
                target="_blank"
                rel="noopener noreferrer"
    >
                Cari di Google Maps ↗
            </a>
            </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p><strong>CL COLLECTION</strong></p>
            <p>&copy; <?= date("Y") ?> CL Collection.</p>
        </div>
    </footer>

</body>
</html>