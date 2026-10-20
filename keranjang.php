<?php
require_once __DIR__ . '/sesi.php';
require __DIR__ . '/koneksi.php';

if (empty($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

header('Cache-Control: no-store');

function aman($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

function rupiah($nilai)
{
    return 'Rp' . number_format((float) $nilai, 0, ',', '.');
}

$idUser = (int) $_SESSION['id_user'];
if (empty($_SESSION['csrf_keranjang'])) {
    $_SESSION['csrf_keranjang'] = bin2hex(random_bytes(32));
}

$pesanUbah = $_SESSION['pesan_ubah_keranjang'] ?? '';
unset($_SESSION['pesan_ubah_keranjang']);
$stmtUser = $koneksi->prepare(
    'SELECT role FROM user WHERE id_user = ?'
);
$stmtUser->bind_param('i', $idUser);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();

if (!$user || $user['role'] !== 'customer') {
    http_response_code(403);
    exit('Keranjang belanja hanya tersedia untuk akun customer.');
}

$stmt = $koneksi->prepare("
    SELECT
        k.id_keranjang,
        k.jumlah,
        b.id_barang,
        b.nama_barang,
        b.harga_jual,
        b.aktif,
        v.warna,
        v.stok,
        u.nama_ukuran
    FROM keranjang_belanja AS k
    JOIN varian_barang AS v ON v.id_varian = k.id_varian
    JOIN barang AS b ON b.id_barang = v.id_barang
    JOIN ukuran_barang AS u ON u.id_ukuran = v.id_ukuran
    WHERE k.id_user = ?
    ORDER BY k.id_keranjang DESC
");
$stmt->bind_param('i', $idUser);
$stmt->execute();
$isiKeranjang = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = 0;
$totalJumlah = 0;

foreach ($isiKeranjang as $item) {
    $total += (float) $item['harga_jual'] * (int) $item['jumlah'];
    $totalJumlah += (int) $item['jumlah'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja — CL Collection</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="style.css">

    <style>
        .halaman-keranjang {
            padding-top: 40px;
            padding-bottom: 60px;
            min-height: 55vh;
        }

        .judul-keranjang {
            margin-bottom: 8px;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(36px, 5vw, 54px);
        }

        .pengantar-keranjang {
            margin-bottom: 28px;
            color: #70646a;
        }

        .daftar-keranjang {
            display: grid;
            gap: 18px;
        }

        .kartu-keranjang {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding: 24px;
            border: 1px solid #efd2dd;
            border-radius: 16px;
            background: #fff;
        }

        .kartu-keranjang h2 {
            margin: 0 0 10px;
            font-size: 19px;
        }

        .kartu-keranjang p {
            margin: 6px 0;
        }

        .info-varian {
            color: #70646a;
        }

        .subtotal-item {
            color: #a8315b;
            font-weight: 600;
            white-space: nowrap;
        }

        .peringatan-stok {
            color: #a12738;
            font-size: 14px;
        }

        .ringkasan-keranjang {
            margin-top: 26px;
            padding: 26px;
            background: #fff3f8;
            border: 1px solid #efd2dd;
            border-radius: 16px;
        }

        .ringkasan-keranjang h2 {
            margin-top: 0;
        }

        .total-keranjang {
            font-size: 26px;
            font-weight: 600;
            color: #a8315b;
        }

        .catatan-keranjang {
            color: #70646a;
            font-size: 14px;
        }

        .keranjang-kosong {
            padding: 40px 24px;
            text-align: center;
            background: #fff3f8;
            border-radius: 16px;
        }

        .lanjut-belanja {
            display: inline-block;
            margin-top: 18px;
            padding: 12px 22px;
            border-radius: 8px;
            background: #a8315b;
            color: #fff;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .kartu-keranjang {
                flex-direction: column;
            }
        }
        .aksi-keranjang {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-top: 16px;
}

.aksi-keranjang form {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.aksi-keranjang input[type="number"] {
    width: 80px;
    padding: 10px;
    border: 1px solid #dcb9c7;
    border-radius: 8px;
    font: inherit;
}

.aksi-keranjang button {
    padding: 10px 14px;
    border: 1px solid #a8315b;
    border-radius: 8px;
    background: #a8315b;
    color: white;
    font: inherit;
    cursor: pointer;
}

.aksi-keranjang .tombol-hapus {
    background: white;
    color: #a8315b;
}

.notifikasi-keranjang {
    margin-bottom: 20px;
    padding: 16px;
    border-left: 4px solid #a8315b;
    border-radius: 8px;
    background: #fff3f8;
    color: #68223c;
}
    </style>
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
                <a href="keranjang.php" aria-current="page">Keranjang</a>
                <?php require __DIR__ . '/menu_akun.php'; ?>
            </nav>
        </div>
    </header>

    <main class="container halaman-keranjang">
        <h1 class="judul-keranjang">Keranjang Belanjamu</h1>
        <p class="pengantar-keranjang">
            Pilihan favoritmu, tersimpan di sini.
        </p>
        <?php if ($pesanUbah !== ''): ?>
        <p role="status" class="notifikasi-keranjang">
        <?= aman($pesanUbah) ?>
        </p>
        <?php endif; ?>
        <?php if (!$isiKeranjang): ?>
            <section class="keranjang-kosong">
                <h2>Keranjangmu masih kosong</h2>
                <p>Temukan pakaian favoritmu di katalog kami.</p>
                <a class="lanjut-belanja" href="katalog.php">
                    Jelajahi Katalog
                </a>
            </section>
        <?php else: ?>
            <div class="daftar-keranjang">
                <?php foreach ($isiKeranjang as $item): ?>
                    <?php
                    $subtotal = (float) $item['harga_jual']
                        * (int) $item['jumlah'];
                    ?>

                    <article class="kartu-keranjang">
                        <div>
                            <h2>
                                <?php if ((int) $item['aktif'] === 1): ?>
                                    <a href="detail_produk.php?id=<?= (int) $item['id_barang'] ?>">
                                        <?= aman($item['nama_barang']) ?>
                                    </a>
                                <?php else: ?>
                                    <?= aman($item['nama_barang']) ?>
                                <?php endif; ?>
                            </h2>

                            <p class="info-varian">
                                <?= aman($item['nama_ukuran']) ?>
                                · <?= aman($item['warna']) ?>
                            </p>

                            <p>
                                Harga satuan:
                                <?= rupiah($item['harga_jual']) ?>
                            </p>

                                <div class="aksi-keranjang">
                                <?php if (
                                    (int) $item['aktif'] === 1
                                    && (int) $item['stok'] > 0
                                ): ?>
                                    <form action="ubah_keranjang.php" method="post">
                                        <input
                                            type="hidden"
                                            name="csrf_keranjang"
                                            value="<?= aman($_SESSION['csrf_keranjang']) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="id_keranjang"
                                            value="<?= (int) $item['id_keranjang'] ?>"
                                        >

                                        <label for="jumlah-<?= (int) $item['id_keranjang'] ?>">
                                            Jumlah
                                        </label>

                                        <input
                                            type="number"
                                            id="jumlah-<?= (int) $item['id_keranjang'] ?>"
                                            name="jumlah"
                                            value="<?= (int) $item['jumlah'] ?>"
                                            min="1"
                                            max="<?= (int) $item['stok'] ?>"
                                            step="1"
                                            required
                                        >

                                        <button type="submit" name="aksi" value="ubah">
                                            Simpan Jumlah
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <p>Jumlah: <?= (int) $item['jumlah'] ?> pcs</p>
                                <?php endif; ?>

                                <form
                                    action="ubah_keranjang.php"
                                    method="post"
                                    onsubmit="return confirm('Hapus barang ini dari keranjang?');"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_keranjang"
                                        value="<?= aman($_SESSION['csrf_keranjang']) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id_keranjang"
                                        value="<?= (int) $item['id_keranjang'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="aksi"
                                        value="hapus"
                                        class="tombol-hapus"
                                    >
                                        Hapus
                                    </button>
                                </form>
                            </div>

                            <?php if ((int) $item['aktif'] !== 1): ?>
                                <p class="peringatan-stok">
                                    Produk ini sudah tidak tersedia.
                                </p>
                            <?php elseif ((int) $item['stok'] < 1): ?>
                                <p class="peringatan-stok">
                                    Stok varian ini sedang habis.
                                </p>
                            <?php elseif ((int) $item['jumlah'] > (int) $item['stok']): ?>
                                <p class="peringatan-stok">
                                    Stok saat ini hanya
                                    <?= (int) $item['stok'] ?> pcs.
                                    Jumlah perlu disesuaikan sebelum checkout.
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="subtotal-item">
                            <?= rupiah($subtotal) ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <section class="ringkasan-keranjang">
                <h2>Ringkasan Belanja</h2>
                <p>Total jumlah: <?= $totalJumlah ?> pcs</p>
                <p>Subtotal seluruh isi keranjang:</p>
                <p class="total-keranjang"><?= rupiah($total) ?></p>

                <p class="catatan-keranjang">
                    Belum termasuk ongkir dan diskon.
                    Ketersediaan barang akan diperiksa kembali saat checkout.
                    Barang di keranjang belum dipesan dan belum mengurangi stok.
                </p>

                <a class="lanjut-belanja" href="katalog.php">
                    Lanjut Belanja
                </a>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p><strong>CL COLLECTION</strong></p>
            <p>&copy; <?= date('Y') ?> CL Collection.</p>
        </div>
    </footer>
</body>
</html>