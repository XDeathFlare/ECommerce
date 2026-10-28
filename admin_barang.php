<?php
require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';

header('Cache-Control: no-store');

if (empty($_SESSION['id_user'])) {
    header('Location: login_admin.php');
    exit;
}

// Selalu periksa hak akses dari database.
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

function hBarang($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

function inputBarang($nama)
{
    return isset($_POST[$nama]) && is_string($_POST[$nama])
        ? trim($_POST[$nama])
        : '';
}

if (empty($_SESSION['csrf_barang'])) {
    $_SESSION['csrf_barang'] = bin2hex(random_bytes(32));
}

$pesan = '';
$sukses = $_SESSION['pesan_barang'] ?? '';
unset($_SESSION['pesan_barang']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id = $id && $id > 0 ? $id : 0;

// Simpan perubahan melalui POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPost = filter_var(
        inputBarang('id_barang'),
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $nama = inputBarang('nama_barang');
    $deskripsi = inputBarang('deskripsi');
    $jualTeks = inputBarang('harga_jual');
    $beliTeks = inputBarang('harga_beli');
    $beratTeks = inputBarang('berat_gram');

    $berat = $beratTeks === ''
        ? null
        : filter_var(
            $beratTeks,
            FILTER_VALIDATE_INT,
            ['options' => [
                'min_range' => 1,
                'max_range' => 2147483647
            ]]
        );
    $aktifTeks = inputBarang('aktif');
    $stokInput = $_POST['stok'] ?? [];

    $hargaValid =
        preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/D', $jualTeks) &&
        preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/D', $beliTeks);

    if (!hash_equals($_SESSION['csrf_barang'], inputBarang('csrf'))) {
        $pesan = 'Sesi formulir tidak valid. Muat ulang halaman.';
    } elseif (!$idPost) {
        $pesan = 'Barang tidak valid.';
    } elseif ($nama === '' || mb_strlen($nama) > 150) {
        $pesan = 'Nama barang wajib diisi, maksimal 150 karakter.';
    } elseif (strlen($deskripsi) > 60000) {
        $pesan = 'Deskripsi terlalu panjang.';
    } elseif (
        !$hargaValid ||
        (float) $jualTeks > 1000000000 ||
        (float) $beliTeks > 1000000000
    ) {
        $pesan = 'Harga harus berupa angka 0–1.000.000.000 tanpa pemisah ribuan.';
    } elseif ($beratTeks !== '' && $berat === false) {
    $pesan = 'Berat harus berupa bilangan bulat positif dalam gram, atau dikosongkan jika belum diketahui.';
    } elseif (!in_array($aktifTeks, ['0', '1'], true)) {
        $pesan = 'Status barang tidak valid.';
    } elseif (!is_array($stokInput)) {
        $pesan = 'Data stok tidak valid.';
    } else {
        $dalamTransaksi = false;

        try {
            $koneksi->begin_transaction();
            $dalamTransaksi = true;

            $cekBarang = $koneksi->prepare(
                "SELECT id_barang FROM barang
                 WHERE id_barang = ? FOR UPDATE"
            );
            $cekBarang->bind_param('i', $idPost);
            $cekBarang->execute();

            if (!$cekBarang->get_result()->fetch_assoc()) {
                throw new RuntimeException('Barang tidak ditemukan.');
            }

            $cekVarian = $koneksi->prepare(
                "SELECT id_varian FROM varian_barang
                 WHERE id_barang = ? ORDER BY id_varian FOR UPDATE"
            );
            $cekVarian->bind_param('i', $idPost);
            $cekVarian->execute();

            $daftarVarian = $cekVarian->get_result()->fetch_all(MYSQLI_ASSOC);
            $stokValid = [];

            if (count($stokInput) !== count($daftarVarian)) {
                throw new RuntimeException(
                    'Daftar varian berubah. Muat ulang halaman sebelum menyimpan.'
                );
            }

            foreach ($daftarVarian as $varian) {
                $idVarian = (int) $varian['id_varian'];
                $nilai = $stokInput[$idVarian] ?? null;

                if (
                    !is_string($nilai) ||
                    !preg_match('/^[0-9]{1,10}$/D', $nilai) ||
                    (float) $nilai > 2147483647
                ) {
                    throw new RuntimeException(
                        'Stok harus berupa bilangan bulat 0–2147483647.'
                    );
                }

                $stokValid[$idVarian] = (int) $nilai;
            }

            $jual = (float) $jualTeks;
            $beli = (float) $beliTeks;
            $aktif = (int) $aktifTeks;

            $ubah = $koneksi->prepare(
                "UPDATE barang
                SET nama_barang = ?, deskripsi = ?,
                    harga_jual = ?, harga_beli = ?,
                    aktif = ?, berat_gram = ?
                WHERE id_barang = ?"
            );

            $ubah->bind_param(
                'ssddiii',
                $nama,
                $deskripsi,
                $jual,
                $beli,
                $aktif,
                $berat,
                $idPost
            );

            $ubah->execute();

            $ubahStok = $koneksi->prepare(
                "UPDATE varian_barang SET stok = ?
                 WHERE id_varian = ? AND id_barang = ?"
            );

            foreach ($stokValid as $idVarian => $jumlah) {
                $ubahStok->bind_param('iii', $jumlah, $idVarian, $idPost);
                $ubahStok->execute();
            }

            $koneksi->commit();
            $dalamTransaksi = false;

            $_SESSION['pesan_barang'] = 'Perubahan barang dan stok berhasil disimpan.';
            header('Location: admin_barang.php?id=' . $idPost);
            exit;
        } catch (RuntimeException $e) {
            if ($dalamTransaksi) {
                $koneksi->rollback();
            }
            $pesan = $e->getMessage();
        } catch (mysqli_sql_exception $e) {
            if ($dalamTransaksi) {
                $koneksi->rollback();
            }
            error_log($e->getMessage());
            $pesan = 'Perubahan gagal disimpan. Silakan coba lagi.';
        }
    }

    if ($idPost) {
        $id = (int) $idPost;
    }
}

$barang = null;
$varianBarang = [];

if ($id > 0) {
    $ambil = $koneksi->prepare(
        "SELECT b.*, j.nama_jenis
         FROM barang b
         JOIN jenis_barang j ON j.id_jenis = b.id_jenis
         WHERE b.id_barang = ?"
    );
    $ambil->bind_param('i', $id);
    $ambil->execute();
    $barang = $ambil->get_result()->fetch_assoc();

    if (!$barang) {
        http_response_code(404);
        exit('Barang tidak ditemukan. <a href="admin_barang.php">Kembali</a>');
    }

    $ambilVarian = $koneksi->prepare(
        "SELECT v.id_varian, v.warna, v.stok, u.nama_ukuran
         FROM varian_barang v
         JOIN ukuran_barang u ON u.id_ukuran = v.id_ukuran
         WHERE v.id_barang = ?
         ORDER BY u.nama_ukuran, v.warna"
    );
    $ambilVarian->bind_param('i', $id);
    $ambilVarian->execute();
    $varianBarang = $ambilVarian->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Pertahankan isian ketika validasi gagal.
if ($pesan !== '' && $barang && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $barang['nama_barang'] = $nama;
    $barang['deskripsi'] = $deskripsi;
    $barang['harga_jual'] = $jualTeks;
    $barang['harga_beli'] = $beliTeks;
    $barang['berat_gram'] = $beratTeks;
    $barang['aktif'] = $aktifTeks;
}

$q = isset($_GET['q']) && is_string($_GET['q'])
    ? mb_substr(trim($_GET['q']), 0, 150)
    : '';

$pencarian = '%' . $q . '%';

$daftar = $koneksi->prepare(
    "SELECT b.id_barang, b.nama_barang, b.harga_jual, b.aktif,
            j.nama_jenis,
            COALESCE(s.total_stok, 0) AS total_stok
     FROM barang b
     JOIN jenis_barang j ON j.id_jenis = b.id_jenis
     LEFT JOIN (
         SELECT id_barang, SUM(stok) AS total_stok
         FROM varian_barang GROUP BY id_barang
     ) s ON s.id_barang = b.id_barang
     WHERE b.nama_barang LIKE ?
     ORDER BY b.id_barang DESC"
);
$daftar->bind_param('s', $pencarian);
$daftar->execute();
$semuaBarang = $daftar->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Barang | CL Collection</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body.kelola-barang {
            margin: 0;
            background: #fff7fa;
            color: #191519;
        }

        .kb-wadah {
            width: 90%;
            max-width: 1150px;
            margin: 30px auto;
        }

        .kb-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .kb-header h1 {
            margin: 8px 0;
            font-family: Georgia, serif;
            color: #a8315b;
        }

        .kb-panel {
            background: white;
            padding: 25px;
            border: 1px solid #efdee5;
            border-radius: 18px;
            margin-bottom: 24px;
        }

        .kb-panel h2 {
            margin-top: 0;
        }

        .kb-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .kb-form label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
        }

        .kb-form input,
        .kb-form select,
        .kb-form textarea {
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
            padding: 11px;
            border: 1px solid #e5d5dd;
            border-radius: 9px;
            font: inherit;
            font-size: 14px;
        }

        .kb-form input:focus,
        .kb-form select:focus,
        .kb-form textarea:focus {
            outline: 2px solid #c84d7a;
            outline-offset: 2px;
        }

        .kb-lebar {
            grid-column: 1 / -1;
        }

        .kb-tombol {
            display: inline-block;
            padding: 11px 18px;
            border: 0;
            border-radius: 10px;
            background: #191519;
            color: white;
            text-decoration: none;
            font: inherit;
            font-size: 13px;
            cursor: pointer;
        }

        .kb-tombol:hover {
            background: #a8315b;
        }

        .kb-tautan {
            color: #a8315b;
        }

        .kb-pesan {
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            background: #fff0f2;
            color: #8c203e;
        }

        .kb-sukses {
            background: #edf8f0;
            color: #25633b;
        }

        .kb-tabel-wrap {
            overflow-x: auto;
        }

        .kb-tabel {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        .kb-tabel th,
        .kb-tabel td {
            padding: 13px 10px;
            border-bottom: 1px solid #efdee5;
        }

        .kb-tabel th {
            background: #fff2f7;
        }

        .kb-tabel input {
            min-width: 100px;
            max-width: 140px;
        }

        .kb-cari {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .kb-cari input {
            flex: 1;
            min-width: 180px;
        }

        .kb-catatan {
            font-size: 13px;
            color: #71666d;
            line-height: 1.8;
        }

        @media (max-width: 650px) {
            .kb-grid {
                grid-template-columns: 1fr;
            }

            .kb-panel {
                padding: 18px;
            }
        }
    </style>
</head>

<body class="kelola-barang">
<main class="kb-wadah">
    <header class="kb-header">
        <div>
            <a class="kb-tautan" href="admin.php">← Dashboard Admin</a>
            <h1>Kelola Barang</h1>
            <a class="kb-tombol" href="admin_tambah_barang.php">
    + Tambah Barang Baru
</a>
        </div>

        <a class="kb-tombol" href="admin_barang.php">
            Semua Barang
        </a>
    </header>

    <?php if ($sukses !== ''): ?>
        <div class="kb-pesan kb-sukses" role="status">
            <?= hBarang($sukses) ?>
        </div>
    <?php endif; ?>

    <?php if ($pesan !== ''): ?>
        <div class="kb-pesan" role="alert">
            <?= hBarang($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($barang): ?>
        <section class="kb-panel">
            <h2>Edit Barang</h2>
            <p>
            <a
            class="kb-tombol"
            href="admin_tambah_varian.php?id=<?= (int) $barang['id_barang'] ?>"
    >
             + Tambah Warna / Ukuran
        </a>
        </p>

            <p class="kb-catatan">
                Kategori: <?= hBarang($barang['nama_jenis']) ?>.
                Harga diisi dalam rupiah penuh, misalnya 50000 untuk Rp50.000.
            </p>

            <form
                class="kb-form"
                method="post"
                action="admin_barang.php?id=<?= $id ?>"
            >
                <input type="hidden" name="csrf"
                       value="<?= hBarang($_SESSION['csrf_barang']) ?>">

                <input type="hidden" name="id_barang"
                       value="<?= $id ?>">

                <div class="kb-grid">
                    <div class="kb-lebar">
                        <label for="nama_barang">Nama Barang</label>
                        <input
                            id="nama_barang"
                            name="nama_barang"
                            maxlength="150"
                            value="<?= hBarang($barang['nama_barang']) ?>"
                            required
                        >
                    </div>

                    <div>
                        <label for="harga_jual">Harga Jual (Rp)</label>
                        <input
                            id="harga_jual"
                            name="harga_jual"
                            type="number"
                            min="0"
                            max="1000000000"
                            step="0.01"
                            value="<?= hBarang($barang['harga_jual']) ?>"
                            required
                        >
                    </div>

                    <div>
                        <label for="harga_beli">Harga Beli / Modal (Rp)</label>
                        <input
                            id="harga_beli"
                            name="harga_beli"
                            type="number"
                            min="0"
                            max="1000000000"
                            step="0.01"
                            value="<?= hBarang($barang['harga_beli']) ?>"
                            required
                        >
                    </div>

                    <div class="kb-lebar">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea
                            id="deskripsi"
                            name="deskripsi"
                            rows="6"
                        ><?= hBarang($barang['deskripsi']) ?></textarea>
                    </div>
                    <div>
                        <label for="berat_gram">
                            Berat pengiriman per barang (gram)
                        </label>

                        <input
                            id="berat_gram"
                            name="berat_gram"
                            type="number"
                            min="1"
                            max="2147483647"
                            step="1"
                            placeholder="Belum diketahui"
                            value="<?= hBarang($barang['berat_gram'] ?? '') ?>"
                            aria-describedby="catatan-berat"
                        >

                        <p class="kb-catatan" id="catatan-berat">
                            Isi berat satu barang termasuk kemasannya dalam gram.
                            Kosongkan jika belum ditimbang.
                        </p>
                    </div>
                    <div>
                        <label for="aktif">Status Barang</label>
                        <select id="aktif" name="aktif">
                            <option value="1"
                                <?= (string) $barang['aktif'] === '1' ? 'selected' : '' ?>>
                                Aktif — tampil di katalog
                            </option>
                            <option value="0"
                                <?= (string) $barang['aktif'] === '0' ? 'selected' : '' ?>>
                                Nonaktif — disembunyikan
                            </option>
                        </select>
                    </div>
                </div>

                <h3>Stok per Warna dan Ukuran</h3>

                <p class="kb-catatan">
                    Isi jumlah stok saat ini, bukan jumlah penambahan stok.
                </p>

                <div class="kb-tabel-wrap">
                    <table class="kb-tabel">
                        <thead>
                            <tr>
                                <th>Warna</th>
                                <th>Ukuran</th>
                                <th>Stok Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($varianBarang as $v): ?>
                            <?php
                            $nilaiStok = $v['stok'];

                            if (
                                $pesan !== '' &&
                                isset($stokInput) &&
                                is_array($stokInput) &&
                                isset($stokInput[$v['id_varian']]) &&
                                is_string($stokInput[$v['id_varian']])
                            ) {
                                $nilaiStok = $stokInput[$v['id_varian']];
                            }
                            ?>
                            <tr>
                                <td><?= hBarang($v['warna']) ?></td>
                                <td><?= hBarang($v['nama_ukuran']) ?></td>
                                <td>
                                    <input
                                        type="number"
                                        name="stok[<?= (int) $v['id_varian'] ?>]"
                                        min="0"
                                        max="2147483647"
                                        step="1"
                                        aria-label="<?= hBarang(
                                            'Stok ' . $v['warna'] . ' ' . $v['nama_ukuran']
                                        ) ?>"
                                        value="<?= hBarang($nilaiStok) ?>"
                                        required
                                    >
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$varianBarang): ?>
                            <tr>
                                <td colspan="3">Barang ini belum memiliki varian.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <p>
                    <button class="kb-tombol" type="submit">
                        Simpan Perubahan
                    </button>
                </p>
            </form>
        </section>
    <?php endif; ?>

    <section class="kb-panel">
        <h2>Daftar Semua Barang</h2>

        <form class="kb-form kb-cari" method="get" action="admin_barang.php">
            <input
                name="q"
                placeholder="Cari nama barang..."
                aria-label="Cari nama barang"
                maxlength="150"
                value="<?= hBarang($q) ?>"
            >
            <button class="kb-tombol" type="submit">Cari</button>
            <a class="kb-tautan" href="admin_barang.php">Reset</a>
        </form>

        <p class="kb-catatan">
            Ditemukan <?= $semuaBarang->num_rows ?> barang.
            Daftar ini mencakup barang aktif dan nonaktif.
        </p>

        <div class="kb-tabel-wrap">
            <table class="kb-tabel">
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($b = $semuaBarang->fetch_assoc()): ?>
                    <tr>
                        <td><?= hBarang($b['nama_barang']) ?></td>
                        <td><?= hBarang($b['nama_jenis']) ?></td>
                        <td>
                            Rp<?= number_format(
                                (float) $b['harga_jual'], 0, ',', '.'
                            ) ?>
                        </td>
                        <td><?= (int) $b['total_stok'] ?></td>
                        <td><?= $b['aktif'] ? 'Aktif' : 'Nonaktif' ?></td>
                        <td>
                            <a class="kb-tautan"
                               href="admin_barang.php?id=<?= (int) $b['id_barang'] ?>">
                                Edit &amp; Stok
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <?php if ($semuaBarang->num_rows === 0): ?>
                    <tr>
                        <td colspan="6">Barang tidak ditemukan.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>