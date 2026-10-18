<?php
require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';

header('Cache-Control: no-store');

if (empty($_SESSION['id_user'])) {
    header('Location: login_admin.php');
    exit;
}

$idUser = (int) $_SESSION['id_user'];

$cek = $koneksi->prepare(
    "SELECT role FROM `user` WHERE id_user = ? LIMIT 1"
);
$cek->bind_param('i', $idUser);
$cek->execute();

$akun = $cek->get_result()->fetch_assoc();

if (!$akun || $akun['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini hanya untuk admin.');
}

function teksVarian($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

function inputVarian($nama)
{
    return isset($_POST[$nama]) && is_string($_POST[$nama])
        ? trim($_POST[$nama])
        : '';
}

$idBarang = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idBarang || $idBarang < 1) {
    http_response_code(404);
    exit('Barang tidak ditemukan. <a href="admin_barang.php">Kembali</a>');
}

$ambilBarang = $koneksi->prepare(
    "SELECT b.id_barang, b.id_jenis, b.nama_barang, j.nama_jenis
     FROM barang b
     JOIN jenis_barang j ON j.id_jenis = b.id_jenis
     WHERE b.id_barang = ?"
);
$ambilBarang->bind_param('i', $idBarang);
$ambilBarang->execute();

$barang = $ambilBarang->get_result()->fetch_assoc();

if (!$barang) {
    http_response_code(404);
    exit('Barang tidak ditemukan. <a href="admin_barang.php">Kembali</a>');
}

$idJenis = (int) $barang['id_jenis'];

$ambilUkuran = $koneksi->prepare(
    "SELECT id_ukuran, nama_ukuran
     FROM ukuran_barang
     WHERE id_jenis = ?
     ORDER BY nama_ukuran"
);
$ambilUkuran->bind_param('i', $idJenis);
$ambilUkuran->execute();

$pilihanUkuran = $ambilUkuran->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($_SESSION['csrf_tambah_varian'])) {
    $_SESSION['csrf_tambah_varian'] = bin2hex(random_bytes(32));
}

$warna = '';
$ukuranTeks = '';
$stokTeks = '5';
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $warna = inputVarian('warna');
    $ukuranTeks = inputVarian('id_ukuran');
    $stokTeks = inputVarian('stok');

    $ukuranId = filter_var(
        $ukuranTeks,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $ukuranValid = false;

    foreach ($pilihanUkuran as $u) {
        if ((int) $u['id_ukuran'] === $ukuranId) {
            $ukuranValid = true;
            break;
        }
    }

    if (!hash_equals(
        $_SESSION['csrf_tambah_varian'],
        inputVarian('csrf')
    )) {
        $pesan = 'Sesi formulir tidak valid. Muat ulang halaman.';
    } elseif ($warna === '' || mb_strlen($warna) > 50) {
        $pesan = 'Isi warna barang, maksimal 50 karakter.';
    } elseif (!$ukuranValid) {
        $pesan = 'Pilih ukuran yang tersedia untuk kategori barang ini.';
    } elseif (
        !preg_match('/^[0-9]{1,10}$/D', $stokTeks) ||
        (float) $stokTeks > 2147483647
    ) {
        $pesan = 'Stok harus bilangan bulat 0–2147483647.';
    } else {
        $dalamTransaksi = false;

        try {
            $koneksi->begin_transaction();
            $dalamTransaksi = true;

            // Kunci barang selama penambahan varian.
            $kunci = $koneksi->prepare(
                "SELECT id_jenis FROM barang
                 WHERE id_barang = ? FOR UPDATE"
            );
            $kunci->bind_param('i', $idBarang);
            $kunci->execute();

            $barangSaatIni = $kunci->get_result()->fetch_assoc();

            if (
                !$barangSaatIni ||
                (int) $barangSaatIni['id_jenis'] !== $idJenis
            ) {
                throw new RuntimeException(
                    'Data barang berubah. Muat ulang halaman.'
                );
            }

            $cekDuplikat = $koneksi->prepare(
                "SELECT id_varian FROM varian_barang
                 WHERE id_barang = ? AND id_ukuran = ? AND warna = ?
                 LIMIT 1"
            );
            $cekDuplikat->bind_param(
                'iis',
                $idBarang,
                $ukuranId,
                $warna
            );
            $cekDuplikat->execute();

            if ($cekDuplikat->get_result()->fetch_assoc()) {
                throw new RuntimeException(
                    'Warna dan ukuran ini sudah tersedia.
                     Untuk mengubah stoknya, gunakan halaman Edit & Stok.'
                );
            }

            $stok = (int) $stokTeks;
            $sku = 'CL-' . $idBarang . '-' . bin2hex(random_bytes(8));

            $simpan = $koneksi->prepare(
                "INSERT INTO varian_barang
                 (id_barang, id_ukuran, warna, sku, stok)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $simpan->bind_param(
                'iissi',
                $idBarang,
                $ukuranId,
                $warna,
                $sku,
                $stok
            );
            $simpan->execute();

            $koneksi->commit();
            $dalamTransaksi = false;

            $_SESSION['csrf_tambah_varian'] = bin2hex(random_bytes(32));
            $_SESSION['pesan_barang'] = 'Varian baru berhasil ditambahkan.';

            header('Location: admin_barang.php?id=' . $idBarang);
            exit;
        } catch (mysqli_sql_exception $e) {
            if ($dalamTransaksi) {
                $koneksi->rollback();
            }

            error_log($e->getMessage());

            $pesan = (int) $e->getCode() === 1062
                ? 'Varian sudah tersedia. Periksa warna dan ukuran.'
                : 'Varian gagal disimpan. Silakan coba lagi.';
        } catch (RuntimeException $e) {
            if ($dalamTransaksi) {
                $koneksi->rollback();
            }

            $pesan = $e->getMessage();
        }
    }
}

$ambilVarian = $koneksi->prepare(
    "SELECT v.warna, v.stok, u.nama_ukuran
     FROM varian_barang v
     JOIN ukuran_barang u ON u.id_ukuran = v.id_ukuran
     WHERE v.id_barang = ?
     ORDER BY u.nama_ukuran, v.warna"
);
$ambilVarian->bind_param('i', $idBarang);
$ambilVarian->execute();

$varianLama = $ambilVarian->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Varian | CL Collection</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body.halaman-varian {
            margin: 0;
            background: #fff7fa;
            color: #191519;
        }

        .varian-wadah {
            width: 90%;
            max-width: 800px;
            margin: 35px auto;
        }

        .varian-wadah a {
            color: #a8315b;
        }

        .varian-wadah h1 {
            font-family: Georgia, serif;
            color: #a8315b;
        }

        .varian-panel {
            padding: 26px;
            margin-bottom: 24px;
            background: white;
            border: 1px solid #efdee5;
            border-radius: 18px;
        }

        .varian-panel label {
            display: block;
            margin: 18px 0 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .varian-panel input,
        .varian-panel select {
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #e5d5dd;
            border-radius: 10px;
            background: white;
            font: inherit;
            font-size: 14px;
        }

        .varian-panel input:focus,
        .varian-panel select:focus {
            outline: 2px solid #c84d7a;
            outline-offset: 2px;
        }

        .varian-panel button {
            margin-top: 24px;
            padding: 13px 20px;
            border: 0;
            border-radius: 10px;
            background: #191519;
            color: white;
            font: inherit;
            cursor: pointer;
        }

        .varian-panel button:hover {
            background: #a8315b;
        }

        .varian-panel button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .varian-catatan {
            font-size: 13px;
            color: #71666d;
            line-height: 1.8;
        }

        .varian-pesan {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #fff0f2;
            color: #8c203e;
        }

        .varian-tabel-wrap {
            overflow-x: auto;
        }

        .varian-tabel {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        .varian-tabel th,
        .varian-tabel td {
            padding: 13px;
            border-bottom: 1px solid #efdee5;
        }

        .varian-tabel th {
            background: #fff2f7;
        }
    </style>
</head>

<body class="halaman-varian">
<main class="varian-wadah">
    <a href="admin_barang.php?id=<?= $idBarang ?>">
        ← Kembali ke Edit Barang
    </a>

    <h1>Tambah Varian</h1>

    <?php if ($pesan !== ''): ?>
        <div class="varian-pesan" role="alert">
            <?= teksVarian($pesan) ?>
        </div>
    <?php endif; ?>

    <section class="varian-panel">
        <h2><?= teksVarian($barang['nama_barang']) ?></h2>

        <p class="varian-catatan">
            Kategori: <?= teksVarian($barang['nama_jenis']) ?>.
            Tambahkan satu kombinasi warna dan ukuran baru.
            SKU dibuat otomatis.
        </p>

        <form
            method="post"
            action="admin_tambah_varian.php?id=<?= $idBarang ?>"
        >
            <input
                type="hidden"
                name="csrf"
                value="<?= teksVarian($_SESSION['csrf_tambah_varian']) ?>"
            >

            <label for="warna">Warna</label>
            <input
                id="warna"
                name="warna"
                maxlength="50"
                value="<?= teksVarian($warna) ?>"
                placeholder="Isi warna barang yang tersedia"
                required
            >

            <label for="id_ukuran">Ukuran</label>
            <select id="id_ukuran" name="id_ukuran" required>
                <option value="">Pilih ukuran</option>

                <?php foreach ($pilihanUkuran as $u): ?>
                    <option
                        value="<?= (int) $u['id_ukuran'] ?>"
                        <?= (string) $u['id_ukuran'] === $ukuranTeks
                            ? 'selected' : '' ?>
                    >
                        <?= teksVarian($u['nama_ukuran']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (!$pilihanUkuran): ?>
                <p class="varian-catatan">
                    Belum ada ukuran untuk kategori ini.
                    Tambahkan data ukuran terlebih dahulu.
                </p>
            <?php endif; ?>

            <label for="stok">Stok Awal Varian</label>
            <input
                id="stok"
                name="stok"
                type="number"
                min="0"
                max="2147483647"
                step="1"
                value="<?= teksVarian($stokTeks) ?>"
                required
            >

            <button type="submit" <?= !$pilihanUkuran ? 'disabled' : '' ?>>
                Simpan Varian Baru
            </button>
        </form>
    </section>

    <section class="varian-panel">
        <h2>Varian yang Sudah Ada</h2>

        <div class="varian-tabel-wrap">
            <table class="varian-tabel">
                <thead>
                    <tr>
                        <th>Warna</th>
                        <th>Ukuran</th>
                        <th>Stok</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($v = $varianLama->fetch_assoc()): ?>
                        <tr>
                            <td><?= teksVarian($v['warna']) ?></td>
                            <td><?= teksVarian($v['nama_ukuran']) ?></td>
                            <td><?= (int) $v['stok'] ?></td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if ($varianLama->num_rows === 0): ?>
                        <tr>
                            <td colspan="3">Belum ada varian.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>