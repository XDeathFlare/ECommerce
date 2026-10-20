<?php
require_once __DIR__ . '/sesi.php';
require __DIR__ . "/koneksi.php";

function aman($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, "UTF-8");
}

$pencarian = isset($_GET["q"]) && is_string($_GET["q"])
    ? trim($_GET["q"])
    : "";

$kategori = filter_input(INPUT_GET, "kategori", FILTER_VALIDATE_INT);
$kategori = $kategori !== false && $kategori !== null && $kategori > 0
    ? $kategori
    : 0;

$pilihanUrutan = [
    "terbaru" => "b.id_barang DESC",
    "termurah" => "b.harga_jual ASC, b.id_barang DESC",
    "termahal" => "b.harga_jual DESC, b.id_barang DESC",
    "nama" => "b.nama_barang ASC, b.id_barang ASC"
];

$urutan = isset($_GET["urutan"]) && is_string($_GET["urutan"])
    ? $_GET["urutan"]
    : "terbaru";

if (!isset($pilihanUrutan[$urutan])) {
    $urutan = "terbaru";
}

/* ORDER BY hanya berasal dari daftar pilihan di atas. */
$orderBy = $pilihanUrutan[$urutan];

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
    ORDER BY $orderBy
");

$kataKunci = "%" . $pencarian . "%";
$stmt->bind_param("iis", $kategori, $kategori, $kataKunci);
$stmt->execute();
$produk = $stmt->get_result();

if (empty($_SESSION['csrf_favorit'])) {
    $_SESSION['csrf_favorit'] = bin2hex(random_bytes(32));
}

$customerLogin = !empty($_SESSION['id_user'])
    && ($_SESSION['role'] ?? '') === 'customer';

$daftarFavorit = [];

if ($customerLogin) {
    $idUserFavorit = (int) $_SESSION['id_user'];

    $stmtFavorit = $koneksi->prepare(
        "SELECT id_barang FROM favorit WHERE id_user = ?"
    );
    $stmtFavorit->bind_param('i', $idUserFavorit);
    $stmtFavorit->execute();

    $hasilFavorit = $stmtFavorit->get_result();

    while ($itemFavorit = $hasilFavorit->fetch_assoc()) {
        $daftarFavorit[(int) $itemFavorit['id_barang']] = true;
    }
}

$pesanFavorit = $_SESSION['pesan_favorit'] ?? '';
unset($_SESSION['pesan_favorit']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk — CL Collection</title>
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
                <img
                    src="assets/images/logo.png"
                    alt="Logo CL Collection"
                >

                <div>
                    <span class="brand-name">CL COLLECTION</span>
                    <small>EXPRESS YOUR STYLE</small>
                </div>
            </a>

            <nav class="nav" aria-label="Navigasi utama">
                <a href="index.php">Beranda</a>
                <a href="katalog.php" aria-current="page">Katalog</a>
                <a href="index.php#tentang">Tentang Kami</a>

                <?php if (
                !empty($_SESSION['id_user'])
                && ($_SESSION['role'] ?? '') === 'customer'
            ): ?>
                <a href="favorit.php">Favorit</a>
                <a href="keranjang.php">Keranjang</a>
                <?php endif; ?>
                <?php require __DIR__ . '/menu_akun.php'; ?>
            </nav>
        </div>
    </header>

    <section class="catalog-banner">
        <div class="container">
            <span class="eyebrow">Explore your style</span>
            <h1>Temukan Favoritmu</h1>
            <p>
                Jelajahi pilihan pakaian, cari model kesukaanmu,
                dan temukan gaya yang paling cocok untukmu.
            </p>
        </div>
    </section>

    <main class="catalog-section" id="hasil">
        <div class="container">

            <div class="section-heading">
                <div>
                    <span class="eyebrow">Katalog CL Collection</span>
                    <h2>Semua Koleksi</h2>
                </div>

                <p role="status">
                    <?= (int) $produk->num_rows ?> produk ditemukan
                </p>
            </div>

            <form
                class="filter-form"
                action="katalog.php#hasil"
                method="get"
            >
                <input
                    type="search"
                    name="q"
                    placeholder="Cari nama produk..."
                    aria-label="Cari nama produk"
                    value="<?= aman($pencarian) ?>"
                >

                <select name="kategori" aria-label="Kategori produk">
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

                <select name="urutan" aria-label="Urutkan produk">
                    <option
                        value="terbaru"
                        <?= $urutan === "terbaru" ? "selected" : "" ?>
                    >
                        Terbaru ditambahkan
                    </option>

                    <option
                        value="termurah"
                        <?= $urutan === "termurah" ? "selected" : "" ?>
                    >
                        Harga Terendah
                    </option>

                    <option
                        value="termahal"
                        <?= $urutan === "termahal" ? "selected" : "" ?>
                    >
                        Harga Tertinggi
                    </option>

                    <option
                        value="nama"
                        <?= $urutan === "nama" ? "selected" : "" ?>
                    >
                        Nama A–Z
                    </option>
                </select>

                <button class="button" type="submit">
                    Terapkan
                </button>

                <?php if (
                    $pencarian !== "" ||
                    $kategori > 0 ||
                    $urutan !== "terbaru"
                ): ?>
                    <a class="filter-reset" href="katalog.php#hasil">
                        Reset
                    </a>
                <?php endif; ?>
            </form>
            
            <?php if ($pesanFavorit !== ''): ?>
                <p class="favorit-notifikasi" role="status">
                    <?= aman($pesanFavorit) ?>
                </p>
            <?php endif; ?>
            <div class="product-grid">
                <?php if ($produk->num_rows === 0): ?>
                    <div class="empty-state">
                        <h3>Belum ada produk yang cocok</h3>
                        <p>
                            Coba kata kunci lain atau pilih
                            semua kategori.
                        </p>
                        <a class="button" href="katalog.php#hasil">
                            Lihat Semua Produk
                        </a>
                    </div>
                <?php endif; ?>

                <?php while ($barang = $produk->fetch_assoc()): ?>
                    <?php
                    $foto = trim((string) ($barang["foto"] ?? ""));
                    $adaFoto = $foto !== "";
                    $alamatDetail = "detail_produk.php?id="
                        . (int) $barang["id_barang"];
                    ?>

                    <article class="product-card">
                        <?php
                        $sudahFavorit = isset(
                            $daftarFavorit[(int) $barang['id_barang']]
                        );
                        ?>

                        <?php if ($customerLogin): ?>
                            <form
                                class="favorit-form"
                                action="proses_favorit.php"
                                method="post"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_favorit"
                                    value="<?= aman($_SESSION['csrf_favorit']) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id_barang"
                                    value="<?= (int) $barang['id_barang'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="aksi"
                                    value="<?= $sudahFavorit ? 'hapus' : 'tambah' ?>"
                                >

                                <button
                                    type="submit"
                                    class="favorit-tombol <?= $sudahFavorit ? 'tersimpan' : '' ?>"
                                    aria-label="<?= aman(
                                        ($sudahFavorit
                                            ? 'Hapus dari favorit: '
                                            : 'Tambahkan ke favorit: ')
                                        . $barang['nama_barang']
                                    ) ?>"
                                    aria-pressed="<?= $sudahFavorit ? 'true' : 'false' ?>"
                                    title="<?= $sudahFavorit
                                        ? 'Hapus dari favorit'
                                        : 'Tambahkan ke favorit' ?>"
                                >
                                    <span aria-hidden="true">
                                        <?= $sudahFavorit ? '&#9829;' : '&#9825;' ?>
                                    </span>
                                </button>
                            </form>
                        <?php elseif (empty($_SESSION['id_user'])): ?>
                            <a
                                class="favorit-tombol favorit-login"
                                href="login.php"
                                aria-label="Masuk untuk menyimpan produk ke favorit"
                                title="Masuk untuk menyimpan favorit"
                            >
                                <span aria-hidden="true">&#9825;</span>
                            </a>
                        <?php endif; ?>
                        <a
                            class="product-image <?= !$adaFoto
                                ? "placeholder" : "" ?>"
                            href="<?= aman($alamatDetail) ?>"
                            aria-label="Lihat detail <?= aman(
                                $barang["nama_barang"]
                            ) ?>"
                        >
                            <img
                                src="<?= aman(
                                    $adaFoto
                                        ? $foto
                                        : "assets/images/logo.png"
                                ) ?>"
                                alt="<?= aman(
                                    $adaFoto
                                        ? $barang["nama_barang"]
                                        : "Foto produk belum tersedia"
                                ) ?>"
                                loading="lazy"
                            >
                        </a>

                        <div class="product-info">
                            <span class="product-category">
                                <?= aman($barang["nama_jenis"]) ?>
                            </span>

                            <h3>
                                <a href="<?= aman($alamatDetail) ?>">
                                    <?= aman($barang["nama_barang"]) ?>
                                </a>
                            </h3>

                            <p class="product-price">
                                Rp<?= number_format(
                                    (float) $barang["harga_jual"],
                                    0,
                                    ",",
                                    "."
                                ) ?>
                            </p>

                            <p class="product-stock">
                                <?= (int) $barang["total_stok"] > 0
                                    ? "Stok: "
                                        . (int) $barang["total_stok"]
                                        . " pcs"
                                    : "Stok habis" ?>
                            </p>

                            <?php if (!$adaFoto): ?>
                                <p class="product-stock">
                                    Foto produk belum tersedia
                                </p>
                            <?php endif; ?>

                            <a
                                class="detail-link"
                                href="<?= aman($alamatDetail) ?>"
                            >
                                Lihat Detail &rarr;
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
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
