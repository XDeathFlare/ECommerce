<?php
require_once __DIR__ . '/sesi.php';
require __DIR__ . '/koneksi.php';

function kembaliFavorit($pesan)
{
    $_SESSION['pesan_favorit'] = $pesan;

    $tujuan = ($_POST['asal'] ?? '') === 'favorit'
        ? 'favorit.php#hasil'
        : 'katalog.php#hasil';

    header('Location: ' . $tujuan, true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: katalog.php', true, 303);
    exit;
}

$idUser = (int) ($_SESSION['id_user'] ?? 0);

if ($idUser < 1) {
    header('Location: login.php', true, 303);
    exit;
}

/* Periksa token keamanan formulir. */
$token = $_POST['csrf_favorit'] ?? '';

if (
    !is_string($token)
    || empty($_SESSION['csrf_favorit'])
    || !hash_equals($_SESSION['csrf_favorit'], $token)
) {
    kembaliFavorit('Sesi formulir kedaluwarsa. Silakan coba lagi.');
}

$idBarang = filter_input(
    INPUT_POST,
    'id_barang',
    FILTER_VALIDATE_INT
);

$aksi = $_POST['aksi'] ?? '';

if (
    !$idBarang
    || $idBarang < 1
    || !is_string($aksi)
    || !in_array($aksi, ['tambah', 'hapus'], true)
) {
    kembaliFavorit('Permintaan favorit tidak valid.');
}

try {
    /* Pastikan akun masih terdaftar sebagai customer. */
    $stmt = $koneksi->prepare(
        "SELECT role FROM `user` WHERE id_user = ?"
    );
    $stmt->bind_param('i', $idUser);
    $stmt->execute();

    $akun = $stmt->get_result()->fetch_assoc();

    if (!$akun || $akun['role'] !== 'customer') {
        http_response_code(403);
        exit('Fitur favorit hanya tersedia untuk customer.');
    }

    if ($aksi === 'hapus') {
        /* Hanya hapus favorit milik akun yang sedang masuk. */
        $stmt = $koneksi->prepare(
            "DELETE FROM favorit
             WHERE id_user = ? AND id_barang = ?"
        );
        $stmt->bind_param('ii', $idUser, $idBarang);
        $stmt->execute();

        kembaliFavorit('Produk sudah dihapus dari favorit.');
    }

    /* Produk yang ditambahkan harus masih aktif. */
    $stmt = $koneksi->prepare(
        "SELECT id_barang FROM barang
         WHERE id_barang = ? AND aktif = 1"
    );
    $stmt->bind_param('i', $idBarang);
    $stmt->execute();

    if (!$stmt->get_result()->fetch_assoc()) {
        kembaliFavorit('Produk sudah tidak tersedia.');
    }

    /* Jika sudah tersimpan, tidak membuat favorit ganda. */
    $stmt = $koneksi->prepare(
        "INSERT INTO favorit (id_user, id_barang)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE id_barang = VALUES(id_barang)"
    );
    $stmt->bind_param('ii', $idUser, $idBarang);
    $stmt->execute();

    kembaliFavorit('Produk berhasil ditambahkan ke favorit.');
} catch (mysqli_sql_exception $e) {
    error_log('Proses favorit: ' . $e->getMessage());

    kembaliFavorit(
        'Favorit belum berhasil diperbarui. Silakan coba lagi.'
    );
}