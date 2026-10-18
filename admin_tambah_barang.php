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

function amanTambah($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

function isiTambah($nama)
{
    return isset($_POST[$nama]) && is_string($_POST[$nama])
        ? trim($_POST[$nama])
        : '';
}

$brands = $koneksi->query(
    "SELECT id_brand, nama_brand FROM brand ORDER BY nama_brand"
)->fetch_all(MYSQLI_ASSOC);

$jenis = $koneksi->query(
    "SELECT id_jenis, nama_jenis FROM jenis_barang ORDER BY nama_jenis"
)->fetch_all(MYSQLI_ASSOC);

$model = $koneksi->query(
    "SELECT id_model, nama_model FROM model_barang ORDER BY nama_model"
)->fetch_all(MYSQLI_ASSOC);

$ukuran = $koneksi->query(
    "SELECT u.id_ukuran, u.id_jenis, u.nama_ukuran, j.nama_jenis
     FROM ukuran_barang u
     JOIN jenis_barang j ON j.id_jenis = u.id_jenis
     ORDER BY j.nama_jenis, u.nama_ukuran"
)->fetch_all(MYSQLI_ASSOC);

if (empty($_SESSION['csrf_tambah_barang'])) {
    $_SESSION['csrf_tambah_barang'] = bin2hex(random_bytes(32));
}

$data = [
    'nama_barang' => '',
    'id_brand' => '',
    'id_jenis' => '',
    'id_model' => '',
    'deskripsi' => '',
    'harga_jual' => '',
    'harga_beli' => '',
    'aktif' => '1'
];

$baris = [['warna' => '', 'id_ukuran' => '', 'stok' => '5']];
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $kolom => $nilai) {
        $data[$kolom] = isiTambah($kolom);
    }

    $warnaInput = $_POST['warna'] ?? [];
    $ukuranInput = $_POST['id_ukuran'] ?? [];
    $stokInput = $_POST['stok'] ?? [];

    $strukturValid =
        is_array($warnaInput) &&
        is_array($ukuranInput) &&
        is_array($stokInput) &&
        count($warnaInput) >= 1 &&
        count($warnaInput) <= 100 &&
        count($warnaInput) === count($ukuranInput) &&
        count($warnaInput) === count($stokInput);

    $baris = [];

    if ($strukturValid) {
        foreach ($warnaInput as $k => $warna) {
            if (
                !is_string($warna) ||
                !isset($ukuranInput[$k], $stokInput[$k]) ||
                !is_string($ukuranInput[$k]) ||
                !is_string($stokInput[$k])
            ) {
                $strukturValid = false;
                break;
            }

            $baris[] = [
                'warna' => trim($warna),
                'id_ukuran' => trim($ukuranInput[$k]),
                'stok' => trim($stokInput[$k])
            ];
        }
    }

    if (!$baris) {
        $baris = [['warna' => '', 'id_ukuran' => '', 'stok' => '5']];
    }

    $brandId = filter_var(
        $data['id_brand'], FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $jenisId = filter_var(
        $data['id_jenis'], FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $modelId = filter_var(
        $data['id_model'], FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $hargaValid = true;

    foreach (['harga_jual', 'harga_beli'] as $kolom) {
        if (
            !preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/D', $data[$kolom]) ||
            (float) $data[$kolom] > 1000000000
        ) {
            $hargaValid = false;
        }
    }

    if (!hash_equals(
        $_SESSION['csrf_tambah_barang'],
        isiTambah('csrf')
    )) {
        $pesan = 'Sesi formulir tidak valid. Muat ulang halaman.';
    } elseif (
        $data['nama_barang'] === '' ||
        mb_strlen($data['nama_barang']) > 150
    ) {
        $pesan = 'Nama barang wajib diisi, maksimal 150 karakter.';
    } elseif (
        !$brandId || !$jenisId || !$modelId ||
        !in_array($brandId, array_column($brands, 'id_brand')) ||
        !in_array($jenisId, array_column($jenis, 'id_jenis')) ||
        !in_array($modelId, array_column($model, 'id_model'))
    ) {
        $pesan = 'Pilih brand, kategori, dan model yang tersedia.';
    } elseif (!$hargaValid) {
        $pesan = 'Isi harga 0–1.000.000.000 tanpa titik pemisah ribuan.';
    } elseif (strlen($data['deskripsi']) > 60000) {
        $pesan = 'Deskripsi terlalu panjang.';
    } elseif (!in_array($data['aktif'], ['0', '1'], true)) {
        $pesan = 'Status barang tidak valid.';
    } elseif (!$strukturValid) {
        $pesan = 'Isi 1–100 varian dengan lengkap.';
    } else {
        $kombinasi = [];

        foreach ($baris as $v) {
            $ukuranSesuai = false;

            foreach ($ukuran as $u) {
                if (
                    (string) $u['id_ukuran'] === $v['id_ukuran'] &&
                    (int) $u['id_jenis'] === $jenisId
                ) {
                    $ukuranSesuai = true;
                    break;
                }
            }

            if ($v['warna'] === '' || mb_strlen($v['warna']) > 50) {
                $pesan = 'Warna wajib diisi, maksimal 50 karakter.';
                break;
            }

            if (!$ukuranSesuai) {
                $pesan = 'Ukuran harus sesuai kategori barang yang dipilih.';
                break;
            }

            if (
                !preg_match('/^[0-9]{1,10}$/D', $v['stok']) ||
                (float) $v['stok'] > 2147483647
            ) {
                $pesan = 'Stok harus bilangan bulat 0–2147483647.';
                break;
            }

            $kunci = mb_strtolower($v['warna']) . '|' . $v['id_ukuran'];

            if (isset($kombinasi[$kunci])) {
                $pesan = 'Kombinasi warna dan ukuran tidak boleh berulang.';
                break;
            }

            $kombinasi[$kunci] = true;
        }

        if ($pesan === '') {
            try {
                $koneksi->begin_transaction();

                $nama = $data['nama_barang'];
                $deskripsi = $data['deskripsi'];
                $jual = (float) $data['harga_jual'];
                $beli = (float) $data['harga_beli'];
                $aktif = (int) $data['aktif'];

                $simpan = $koneksi->prepare(
                    "INSERT INTO barang
                     (id_brand, id_jenis, id_model, nama_barang,
                      deskripsi, harga_jual, harga_beli, aktif)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $simpan->bind_param(
                    'iiissddi',
                    $brandId, $jenisId, $modelId,
                    $nama, $deskripsi, $jual, $beli, $aktif
                );
                $simpan->execute();

                $idBarang = (int) $koneksi->insert_id;

                $simpanVarian = $koneksi->prepare(
                    "INSERT INTO varian_barang
                     (id_barang, id_ukuran, warna, sku, stok)
                     VALUES (?, ?, ?, ?, ?)"
                );

                foreach ($baris as $nomor => $v) {
                    $ukuranId = (int) $v['id_ukuran'];
                    $warna = $v['warna'];
                    $stok = (int) $v['stok'];
                    $sku = 'CL-' . $idBarang . '-' .
                        ($nomor + 1) . '-' . bin2hex(random_bytes(4));

                    $simpanVarian->bind_param(
                        'iissi',
                        $idBarang, $ukuranId, $warna, $sku, $stok
                    );
                    $simpanVarian->execute();
                }

                $koneksi->commit();

                // Ganti token untuk mencegah pengiriman ulang formulir lama.
                $_SESSION['csrf_tambah_barang'] = bin2hex(random_bytes(32));
                $_SESSION['pesan_barang'] =
                    'Barang baru beserta variannya berhasil ditambahkan.';

                header('Location: admin_barang.php?id=' . $idBarang);
                exit;
            } catch (mysqli_sql_exception $e) {
                $koneksi->rollback();
                error_log($e->getMessage());

                $pesan = (int) $e->getCode() === 1062
                    ? 'Ada varian yang sama. Periksa warna dan ukuran.'
                    : 'Barang gagal disimpan. Periksa data dan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Barang | CL Collection</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body.tambah-barang {
            margin: 0;
            background: #fff7fa;
            color: #191519;
        }

        .tb-wadah {
            width: 90%;
            max-width: 950px;
            margin: 35px auto;
        }

        .tb-wadah h1 {
            font-family: Georgia, serif;
            color: #a8315b;
        }

        .tb-panel {
            padding: 28px;
            border: 1px solid #efdee5;
            border-radius: 20px;
            background: white;
        }

        .tb-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .tb-lebar {
            grid-column: 1 / -1;
        }

        .tb-panel label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .tb-panel input,
        .tb-panel select,
        .tb-panel textarea {
            box-sizing: border-box;
            width: 100%;
            min-width: 0;
            padding: 12px;
            border: 1px solid #e5d5dd;
            border-radius: 9px;
            font: inherit;
            font-size: 14px;
        }

        .tb-panel input:focus,
        .tb-panel select:focus,
        .tb-panel textarea:focus {
            outline: 2px solid #c84d7a;
            outline-offset: 2px;
        }

        .tb-panel button {
            padding: 12px 18px;
            border: 0;
            border-radius: 10px;
            background: #191519;
            color: white;
            font: inherit;
            font-size: 13px;
            cursor: pointer;
        }

        .tb-panel button:hover {
            background: #a8315b;
        }

        .tb-varian {
            display: grid;
            grid-template-columns: 1fr 1.5fr .7fr auto;
            gap: 12px;
            align-items: end;
            padding: 18px 0;
            border-bottom: 1px solid #efdee5;
        }

        .tb-catatan {
            color: #71666d;
            font-size: 13px;
            line-height: 1.8;
        }

        .tb-pesan {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #fff0f2;
            color: #8c203e;
        }

        .tb-aksi {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            margin-top: 24px;
        }

        .tb-wadah a {
            color: #a8315b;
        }

        @media (max-width: 700px) {
            .tb-grid {
                grid-template-columns: 1fr;
            }

            .tb-varian {
                grid-template-columns: 1fr;
            }

            .tb-panel {
                padding: 20px;
            }
        }
    </style>
</head>

<body class="tambah-barang">
<main class="tb-wadah">
    <a href="admin_barang.php">← Kembali ke Kelola Barang</a>
    <h1>Tambah Barang Baru</h1>

    <?php if ($pesan !== ''): ?>
        <div class="tb-pesan" role="alert">
            <?= amanTambah($pesan) ?>
        </div>
    <?php endif; ?>

    <form class="tb-panel" action="admin_tambah_barang.php" method="post">
        <input type="hidden" name="csrf"
               value="<?= amanTambah($_SESSION['csrf_tambah_barang']) ?>">

        <div class="tb-grid">
            <div class="tb-lebar">
                <label for="nama_barang">Nama Barang</label>
                <input
                    id="nama_barang"
                    name="nama_barang"
                    maxlength="150"
                    value="<?= amanTambah($data['nama_barang']) ?>"
                    required
                >
            </div>

            <?php
            $pilihan = [
                ['id_brand', 'Brand', $brands, 'nama_brand'],
                ['id_jenis', 'Kategori', $jenis, 'nama_jenis'],
                ['id_model', 'Model', $model, 'nama_model']
            ];
            ?>

            <?php foreach ($pilihan as [$kolom, $label, $opsi, $namaKolom]): ?>
                <div>
                    <label for="<?= $kolom ?>"><?= $label ?></label>
                    <select id="<?= $kolom ?>" name="<?= $kolom ?>" required>
                        <option value="">Pilih <?= $label ?></option>

                        <?php foreach ($opsi as $o): ?>
                            <option
                                value="<?= (int) $o[$kolom] ?>"
                                <?= (string) $data[$kolom] === (string) $o[$kolom]
                                    ? 'selected' : '' ?>
                            >
                                <?= amanTambah($o[$namaKolom]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>

            <div>
                <label for="aktif">Status Barang</label>
                <select id="aktif" name="aktif">
                    <option value="1"
                        <?= $data['aktif'] === '1' ? 'selected' : '' ?>>
                        Aktif — tampil di katalog
                    </option>
                    <option value="0"
                        <?= $data['aktif'] === '0' ? 'selected' : '' ?>>
                        Nonaktif — disembunyikan
                    </option>
                </select>
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
                    placeholder="Contoh: 50000"
                    value="<?= amanTambah($data['harga_jual']) ?>"
                    required
                >
            </div>

            <div>
                <label for="harga_beli">Modal / Harga Beli (Rp)</label>
                <input
                    id="harga_beli"
                    name="harga_beli"
                    type="number"
                    min="0"
                    max="1000000000"
                    step="0.01"
                    placeholder="Contoh: 35000"
                    value="<?= amanTambah($data['harga_beli']) ?>"
                    required
                >
            </div>

            <div class="tb-lebar">
                <label for="deskripsi">Deskripsi</label>
                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    rows="5"
                ><?= amanTambah($data['deskripsi']) ?></textarea>
            </div>
        </div>

        <p class="tb-catatan">
            Isi harga dalam rupiah penuh tanpa pemisah ribuan.
            Untuk margin 30%, modal = harga jual × 0,70.
        </p>

        <h2>Varian Barang</h2>
        <p class="tb-catatan">
            Satu baris untuk satu kombinasi warna dan ukuran.
            Pilih ukuran yang kategorinya sesuai dengan barang.
            SKU dibuat otomatis.
        </p>

        <div id="daftar-varian">
            <?php foreach ($baris as $v): ?>
                <div class="tb-varian">
                    <div>
                        <label>
                            Warna
                            <input name="warna[]" maxlength="50"
                                   value="<?= amanTambah($v['warna']) ?>"
                                   placeholder="Contoh: Hitam" required>
                        </label>
                    </div>

                    <div>
                        <label>
                            Ukuran
                            <select name="id_ukuran[]" required>
                                <option value="">Pilih ukuran</option>

                                <?php foreach ($ukuran as $u): ?>
                                    <option
                                        value="<?= (int) $u['id_ukuran'] ?>"
                                        <?= (string) $v['id_ukuran'] === (string) $u['id_ukuran']
                                            ? 'selected' : '' ?>
                                    >
                                        <?= amanTambah(
                                            $u['nama_jenis'] . ' — ' . $u['nama_ukuran']
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <div>
                        <label>
                            Stok
                            <input name="stok[]" type="number"
                                   min="0" max="2147483647" step="1"
                                   value="<?= amanTambah($v['stok']) ?>"
                                   required>
                        </label>
                    </div>

                    <button type="button" class="hapus-varian">
                        Hapus
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="tb-aksi">
            <button type="button" id="tambah-varian">+ Tambah Varian</button>
        </div>

        <div class="tb-aksi">
            <button type="submit">Simpan Barang Baru</button>
            <a href="admin_barang.php">Batal</a>
        </div>
    </form>
</main>

<script>
    const daftarVarian = document.getElementById('daftar-varian');

    document.getElementById('tambah-varian').addEventListener('click', () => {
        if (daftarVarian.children.length >= 100) {
            alert('Maksimal 100 varian per barang.');
            return;
        }

        const baris = daftarVarian.firstElementChild.cloneNode(true);

        baris.querySelector('[name="warna[]"]').value = '';
        baris.querySelector('[name="id_ukuran[]"]').value = '';
        baris.querySelector('[name="stok[]"]').value = '5';

        daftarVarian.appendChild(baris);
        baris.querySelector('[name="warna[]"]').focus();
    });

    daftarVarian.addEventListener('click', (event) => {
        if (!event.target.matches('.hapus-varian')) {
            return;
        }

        if (daftarVarian.children.length === 1) {
            alert('Barang harus mempunyai minimal satu varian.');
            return;
        }

        event.target.closest('.tb-varian').remove();
    });
</script>
</body>
</html>