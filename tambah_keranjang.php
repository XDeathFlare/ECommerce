<?php
require_once __DIR__ . '/sesi.php';
require __DIR__ . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: katalog.php');
    exit;
}

if (empty($_SESSION['id_user'])) {
    header('Location: login.php', true, 303);
    exit;
}

$idUser = (int) $_SESSION['id_user'];

$idBarang = filter_input(
    INPUT_POST,
    'id_barang',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$idVarian = filter_input(
    INPUT_POST,
    'id_varian',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$jumlah = filter_input(
    INPUT_POST,
    'jumlah',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 2147483647]]
);

$tujuan = $idBarang
    ? 'detail_produk.php?id=' . $idBarang . '#form-keranjang'
    : 'katalog.php';

function kembaliKeProduk($pesan, $tujuan)
{
    $_SESSION['pesan_keranjang'] = $pesan;
    header('Location: ' . $tujuan, true, 303);
    exit;
}

$token = $_POST['csrf_keranjang'] ?? '';

if (
    !is_string($token)
    || empty($_SESSION['csrf_keranjang'])
    || !hash_equals($_SESSION['csrf_keranjang'], $token)
) {
    kembaliKeProduk(
        'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.',
        $tujuan
    );
}

if (!$idBarang || !$idVarian || !$jumlah) {
    kembaliKeProduk(
        'Pilih varian dan masukkan jumlah yang benar.',
        $tujuan
    );
}

// Periksa akun langsung dari database.
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

$koneksi->begin_transaction();

try {
    // Kunci varian agar penambahan bersamaan tidak melewati batas stok.
    $stmt = $koneksi->prepare("
        SELECT v.stok
        FROM varian_barang AS v
        JOIN barang AS b ON b.id_barang = v.id_barang
        WHERE v.id_varian = ?
          AND v.id_barang = ?
          AND b.aktif = 1
        FOR UPDATE
    ");
    $stmt->bind_param('ii', $idVarian, $idBarang);
    $stmt->execute();
    $varian = $stmt->get_result()->fetch_assoc();

    if (!$varian) {
        throw new RuntimeException(
            'Varian produk tidak tersedia.'
        );
    }

    $stmt = $koneksi->prepare("
        SELECT id_keranjang, jumlah
        FROM keranjang_belanja
        WHERE id_user = ? AND id_varian = ?
        FOR UPDATE
    ");
    $stmt->bind_param('ii', $idUser, $idVarian);
    $stmt->execute();
    $keranjang = $stmt->get_result()->fetch_assoc();

    $jumlahLama = $keranjang ? (int) $keranjang['jumlah'] : 0;
    $jumlahBaru = $jumlahLama + $jumlah;
    $stok = (int) $varian['stok'];

    if ($jumlahBaru > $stok) {
        throw new RuntimeException(
            'Stok tersedia ' . $stok . ' pcs. '
            . 'Di keranjangmu sudah ada ' . $jumlahLama . ' pcs.'
        );
    }

    if ($keranjang) {
        $stmt = $koneksi->prepare("
            UPDATE keranjang_belanja
            SET jumlah = ?
            WHERE id_keranjang = ? AND id_user = ?
        ");
        $idKeranjang = (int) $keranjang['id_keranjang'];
        $stmt->bind_param(
            'iii',
            $jumlahBaru,
            $idKeranjang,
            $idUser
        );
    } else {
        $stmt = $koneksi->prepare("
            INSERT INTO keranjang_belanja
                (id_user, id_varian, jumlah)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('iii', $idUser, $idVarian, $jumlah);
    }

    $stmt->execute();
    $koneksi->commit();

    $pesan = 'Barang berhasil ditambahkan ke keranjang.';
} catch (mysqli_sql_exception $e) {
    $koneksi->rollback();
    error_log($e->getMessage());
    $pesan = 'Barang belum berhasil ditambahkan. Coba lagi.';
} catch (RuntimeException $e) {
    $koneksi->rollback();
    $pesan = $e->getMessage();
}

kembaliKeProduk($pesan, $tujuan);