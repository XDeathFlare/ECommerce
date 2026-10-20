<?php
require_once __DIR__ . '/sesi.php';
require __DIR__ . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: keranjang.php');
    exit;
}

if (empty($_SESSION['id_user'])) {
    header('Location: login.php', true, 303);
    exit;
}

function kembali($pesan)
{
    $_SESSION['pesan_ubah_keranjang'] = $pesan;
    header('Location: keranjang.php', true, 303);
    exit;
}

$idUser = (int) $_SESSION['id_user'];

$stmt = $koneksi->prepare(
    'SELECT role FROM user WHERE id_user = ?'
);
$stmt->bind_param('i', $idUser);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user['role'] !== 'customer') {
    http_response_code(403);
    exit('Keranjang hanya tersedia untuk akun customer.');
}

$token = $_POST['csrf_keranjang'] ?? '';

if (
    !is_string($token)
    || empty($_SESSION['csrf_keranjang'])
    || !hash_equals($_SESSION['csrf_keranjang'], $token)
) {
    kembali('Sesi formulir tidak valid. Muat ulang halaman.');
}

$idKeranjang = filter_input(
    INPUT_POST,
    'id_keranjang',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$aksi = $_POST['aksi'] ?? '';

if (
    !$idKeranjang
    || !is_string($aksi)
    || !in_array($aksi, ['ubah', 'hapus'], true)
) {
    kembali('Permintaan tidak valid.');
}

// Penghapusan hanya boleh mengenai keranjang akun sendiri.
if ($aksi === 'hapus') {
    try {
        $stmt = $koneksi->prepare("
            DELETE FROM keranjang_belanja
            WHERE id_keranjang = ? AND id_user = ?
        ");
        $stmt->bind_param('ii', $idKeranjang, $idUser);
        $stmt->execute();

        $pesan = $stmt->affected_rows > 0
            ? 'Barang berhasil dihapus dari keranjang.'
            : 'Barang tidak ditemukan di keranjangmu.';
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        $pesan = 'Barang belum berhasil dihapus. Coba lagi.';
    }

    kembali($pesan);
}

$jumlah = filter_input(
    INPUT_POST,
    'jumlah',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 2147483647]]
);

if (!$jumlah) {
    kembali('Jumlah harus berupa bilangan bulat minimal 1.');
}

// Cari varian terlebih dahulu agar urutan penguncian
// sama dengan proses tambah keranjang.
$stmt = $koneksi->prepare("
    SELECT id_varian
    FROM keranjang_belanja
    WHERE id_keranjang = ? AND id_user = ?
");
$stmt->bind_param('ii', $idKeranjang, $idUser);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    kembali('Barang tidak ditemukan di keranjangmu.');
}

$idVarian = (int) $item['id_varian'];

$koneksi->begin_transaction();

try {
    $stmt = $koneksi->prepare("
        SELECT v.stok, b.aktif
        FROM varian_barang AS v
        JOIN barang AS b ON b.id_barang = v.id_barang
        WHERE v.id_varian = ?
        FOR UPDATE
    ");
    $stmt->bind_param('i', $idVarian);
    $stmt->execute();
    $varian = $stmt->get_result()->fetch_assoc();

    if (!$varian || (int) $varian['aktif'] !== 1) {
        throw new RuntimeException(
            'Produk sudah tidak tersedia. Kamu bisa menghapusnya dari keranjang.'
        );
    }

    $stmt = $koneksi->prepare("
        SELECT id_keranjang
        FROM keranjang_belanja
        WHERE id_keranjang = ?
          AND id_user = ?
          AND id_varian = ?
        FOR UPDATE
    ");
    $stmt->bind_param('iii', $idKeranjang, $idUser, $idVarian);
    $stmt->execute();

    if (!$stmt->get_result()->fetch_assoc()) {
        throw new RuntimeException(
            'Barang tidak ditemukan di keranjangmu.'
        );
    }

    $stok = (int) $varian['stok'];

    if ($jumlah > $stok) {
        throw new RuntimeException(
            'Jumlah belum diubah. Stok tersedia hanya ' . $stok . ' pcs.'
        );
    }

    $stmt = $koneksi->prepare("
        UPDATE keranjang_belanja
        SET jumlah = ?
        WHERE id_keranjang = ? AND id_user = ?
    ");
    $stmt->bind_param('iii', $jumlah, $idKeranjang, $idUser);
    $stmt->execute();

    $koneksi->commit();
    $pesan = 'Jumlah barang berhasil diperbarui.';
} catch (mysqli_sql_exception $e) {
    $koneksi->rollback();
    error_log($e->getMessage());
    $pesan = 'Jumlah belum berhasil diubah. Coba lagi.';
} catch (RuntimeException $e) {
    $koneksi->rollback();
    $pesan = $e->getMessage();
}

kembali($pesan);