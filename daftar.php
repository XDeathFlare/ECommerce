<?php
ini_set('session.use_strict_mode', '1');
session_start();

require_once __DIR__ . '/koneksi.php';

function tampil($nilai)
{
    return htmlspecialchars($nilai, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_daftar'])) {
    $_SESSION['csrf_daftar'] = bin2hex(random_bytes(32));
}

$pesan = '';
$berhasil = false;

$nama = '';
$email = '';
$username = '';
$telepon = '';

function inputTeks($nama)
{
    return isset($_POST[$nama]) && is_string($_POST[$nama])
        ? trim($_POST[$nama])
        : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = inputTeks('nama');
    $email = inputTeks('email');
    $username = inputTeks('username');
    $telepon = inputTeks('telepon');

    $password = isset($_POST['password']) && is_string($_POST['password'])
        ? $_POST['password']
        : '';

    $konfirmasi = isset($_POST['konfirmasi']) && is_string($_POST['konfirmasi'])
        ? $_POST['konfirmasi']
        : '';

    $token = inputTeks('csrf');

    if (!hash_equals($_SESSION['csrf_daftar'], $token)) {
        $pesan = 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif ($nama === '' || mb_strlen($nama) > 100) {
        $pesan = 'Isi nama lengkap, maksimal 100 karakter.';
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150
    ) {
        $pesan = 'Masukkan alamat email yang valid, maksimal 150 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $pesan = 'Username harus 3–50 karakter: huruf, angka, atau garis bawah.';
    } elseif (
        $telepon !== '' &&
        !preg_match('/^\+?[0-9]{8,19}$/', $telepon)
    ) {
        $pesan = 'Nomor telepon harus 8–19 angka, boleh diawali tanda +.';
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $pesan = 'Password harus 8–72 byte. Gunakan huruf, angka, atau simbol.';
    } elseif ($password !== $konfirmasi) {
        $pesan = 'Konfirmasi password belum sama.';
    } else {
        try {
            $cek = $koneksi->prepare(
                "SELECT id_user FROM `user`
                 WHERE email = ? OR username = ?
                 LIMIT 1"
            );
            $cek->bind_param('ss', $email, $username);
            $cek->execute();

            if ($cek->get_result()->num_rows > 0) {
                $pesan = 'Email atau username sudah digunakan.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $nomor = $telepon === '' ? null : $telepon;

                $simpan = $koneksi->prepare(
                    "INSERT INTO `user`
                     (nama_user, email, username, password_hash, no_telp, role)
                     VALUES (?, ?, ?, ?, ?, 'customer')"
                );
                $simpan->bind_param(
                    'sssss',
                    $nama,
                    $email,
                    $username,
                    $hash,
                    $nomor
                );
                $simpan->execute();

                $_SESSION['csrf_daftar'] = bin2hex(random_bytes(32));
                $_SESSION['daftar_sukses'] = true;

                header('Location: daftar.php');
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());

            $pesan = (int) $e->getCode() === 1062
                ? 'Email atau username sudah digunakan.'
                : 'Pendaftaran belum berhasil. Silakan coba lagi.';
        }
    }
}

if (!empty($_SESSION['daftar_sukses'])) {
    $berhasil = true;
    unset($_SESSION['daftar_sukses']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun | CL Collection</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .halaman-akun {
            min-height: 100vh;
            margin: 0;
            padding: 36px 16px;
            box-sizing: border-box;
            display: grid;
            place-items: center;
            background:
                linear-gradient(
                    rgba(255, 240, 246, .88),
                    rgba(255, 248, 251, .94)
                ),
                url("assets/images/background_butik.png")
                center / cover;
        }

        .kartu-akun {
            width: 100%;
            max-width: 460px;
            box-sizing: border-box;
            padding: 32px;
            background: white;
            border: 1px solid #efdee5;
            border-radius: 24px;
            box-shadow: 0 18px 55px rgba(89, 39, 61, .10);
        }

        .logo-akun {
            display: block;
            width: 90px;
            height: 90px;
            object-fit: contain;
            margin: 0 auto 14px;
        }

        .kartu-akun h1 {
            margin: 0 0 8px;
            text-align: center;
            font-family: Georgia, serif;
            font-size: 32px;
            color: #a8315b;
        }

        .pengantar-akun {
            margin: 0 0 24px;
            text-align: center;
            font-size: 13px;
            line-height: 1.7;
            color: #71666d;
        }

        .form-akun label {
            display: block;
            margin: 15px 0 7px;
            font-size: 13px;
            font-weight: 600;
        }

        .form-akun input {
            width: 100%;
            min-width: 0;
            padding: 12px 14px;
            box-sizing: border-box;
            border: 1px solid #e5d5dd;
            border-radius: 10px;
            font: inherit;
            font-size: 14px;
            background: #fff;
        }

        .form-akun input:focus {
            outline: 2px solid #c84d7a;
            outline-offset: 2px;
        }

        .form-akun button {
            width: 100%;
            margin-top: 24px;
            padding: 14px;
            border: 0;
            border-radius: 10px;
            background: #191519;
            color: white;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .form-akun button:hover {
            background: #a8315b;
        }

        .pesan-akun {
            padding: 13px;
            border-radius: 10px;
            background: #fff0f2;
            color: #8c203e;
            font-size: 13px;
            line-height: 1.7;
        }

        .pesan-akun.sukses {
            background: #edf8f0;
            color: #25633b;
        }

        .kembali-akun {
            display: block;
            margin-top: 22px;
            text-align: center;
            color: #a8315b;
            font-size: 13px;
        }

        @media (max-width: 480px) {
            .kartu-akun {
                padding: 24px;
            }
        }
    </style>
</head>

<body class="halaman-akun">
    <main class="kartu-akun">
        <a href="index.php" aria-label="Beranda CL Collection">
            <img
                class="logo-akun"
                src="assets/images/logo.png"
                alt="CL Collection"
            >
        </a>

        <h1>Buat Akun</h1>
        <p class="pengantar-akun">
            Temukan koleksi favoritmu bersama CL Collection.
        </p>

        <?php if ($berhasil): ?>
            <div class="pesan-akun sukses" role="status">
                
                Akun berhasil dibuat!
            <a href="login.php">Masuk sekarang</a> untuk melanjutkan belanja.
            </div>
        <?php else: ?>

            <?php if ($pesan !== ''): ?>
                <div class="pesan-akun" role="alert">
                    <?= tampil($pesan) ?>
                </div>
            <?php endif; ?>

            <form class="form-akun" method="post" action="daftar.php">
                <input
                    type="hidden"
                    name="csrf"
                    value="<?= tampil($_SESSION['csrf_daftar']) ?>"
                >

                <label for="nama">Nama lengkap</label>
                <input
                    id="nama"
                    name="nama"
                    type="text"
                    maxlength="100"
                    autocomplete="name"
                    value="<?= tampil($nama) ?>"
                    required
                >

                <label for="email">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    maxlength="150"
                    autocomplete="email"
                    value="<?= tampil($email) ?>"
                    required
                >

                <label for="username">Username</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    minlength="3"
                    maxlength="50"
                    pattern="[a-zA-Z0-9_]{3,50}"
                    autocomplete="username"
                    value="<?= tampil($username) ?>"
                    required
                >

                <label for="telepon">Nomor telepon (opsional)</label>
                <input
                    id="telepon"
                    name="telepon"
                    type="tel"
                    maxlength="20"
                    autocomplete="tel"
                    placeholder="Contoh: 081234567890"
                    value="<?= tampil($telepon) ?>"
                >

                <label for="password">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    minlength="8"
                    maxlength="72"
                    autocomplete="new-password"
                    required
                >

                <label for="konfirmasi">Ulangi password</label>
                <input
                    id="konfirmasi"
                    name="konfirmasi"
                    type="password"
                    minlength="8"
                    maxlength="72"
                    autocomplete="new-password"
                    required
                >

                <button type="submit">Daftar Akun</button>
            </form>
        <?php endif; ?>

        <a class="kembali-akun" href="index.php">
            ← Kembali ke beranda
        </a>
    </main>
</body>
</html>