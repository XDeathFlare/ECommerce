<?php
ini_set('session.use_strict_mode', '1');
session_start();

require_once __DIR__ . '/koneksi.php';

function amanLogin($nilai)
{
    return htmlspecialchars($nilai, ENT_QUOTES, 'UTF-8');
}

if (!empty($_SESSION['id_user'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}

$pesan = '';
$identitas = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identitas = isset($_POST['identitas']) && is_string($_POST['identitas'])
        ? trim($_POST['identitas'])
        : '';

    $password = isset($_POST['password']) && is_string($_POST['password'])
        ? $_POST['password']
        : '';

    $token = isset($_POST['csrf']) && is_string($_POST['csrf'])
        ? $_POST['csrf']
        : '';

    if (!hash_equals($_SESSION['csrf_login'], $token)) {
        $pesan = 'Sesi formulir tidak valid. Muat ulang halaman.';
    } elseif ($identitas === '' || $password === '') {
        $pesan = 'Isi email atau username dan password.';
    } elseif (strlen($identitas) > 150 || strlen($password) > 72) {
        $pesan = 'Email, username, atau password tidak sesuai.';
    } else {
        try {
            // Jika mengandung @, cari berdasarkan email.
            $kolom = strpos($identitas, '@') !== false
                ? 'email'
                : 'username';

            $stmt = $koneksi->prepare(
                "SELECT id_user, nama_user, password_hash, role
                 FROM `user`
                 WHERE $kolom = ?
                 LIMIT 1"
            );
            $stmt->bind_param('s', $identitas);
            $stmt->execute();

            $akun = $stmt->get_result()->fetch_assoc();

            if (
                $akun &&
                password_verify($password, $akun['password_hash']) &&
                $akun['role'] === 'customer'
            ) {
                session_regenerate_id(true);

                $_SESSION = [
                    'id_user' => (int) $akun['id_user'],
                    'nama_user' => $akun['nama_user'],
                    'role' => $akun['role'],
                    'csrf_logout' => bin2hex(random_bytes(32))
                ];

                header('Location: index.php');
                exit;
            }

            $pesan = 'Email, username, atau password tidak sesuai.';
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());
            $pesan = 'Login belum berhasil. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CL Collection</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .halaman-login {
            margin: 0;
            min-height: 100vh;
            box-sizing: border-box;
            padding: 36px 16px;
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

        .kartu-login {
            width: 100%;
            max-width: 460px;
            box-sizing: border-box;
            padding: 32px;
            border: 1px solid #efdee5;
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 18px 55px rgba(89, 39, 61, .10);
        }

        .kartu-login img {
            display: block;
            width: 90px;
            height: 90px;
            object-fit: contain;
            margin: 0 auto 16px;
        }

        .kartu-login h1 {
            margin: 0 0 10px;
            text-align: center;
            font-family: Georgia, serif;
            font-size: 32px;
            color: #a8315b;
        }

        .kartu-login .pengantar {
            text-align: center;
            font-size: 13px;
            line-height: 1.7;
            color: #71666d;
            margin-bottom: 24px;
        }

        .form-login label {
            display: block;
            margin: 16px 0 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .form-login input {
            width: 100%;
            min-width: 0;
            padding: 12px 14px;
            box-sizing: border-box;
            border: 1px solid #e5d5dd;
            border-radius: 10px;
            font: inherit;
            font-size: 14px;
        }

        .form-login input:focus {
            outline: 2px solid #c84d7a;
            outline-offset: 2px;
        }

        .form-login button {
            width: 100%;
            padding: 14px;
            margin-top: 24px;
            border: 0;
            border-radius: 10px;
            background: #191519;
            color: #fff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .form-login button:hover {
            background: #a8315b;
        }

        .pesan-login {
            padding: 13px;
            background: #fff0f2;
            border-radius: 10px;
            color: #8c203e;
            font-size: 13px;
            line-height: 1.7;
        }

        .tautan-login {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            line-height: 1.8;
        }

        .tautan-login a {
            color: #a8315b;
        }
    </style>
</head>

<body class="halaman-login">
    <main class="kartu-login">
        <a href="index.php" aria-label="Beranda CL Collection">
            <img src="assets/images/logo.png" alt="CL Collection">
        </a>

        <h1>Selamat Datang</h1>
        <p class="pengantar">
            Masuk untuk melanjutkan perjalanan gayamu.
        </p>

        <?php if ($pesan !== ''): ?>
            <div class="pesan-login" role="alert">
                <?= amanLogin($pesan) ?>
            </div>
        <?php endif; ?>

        <form class="form-login" method="post" action="login.php">
            <input
                type="hidden"
                name="csrf"
                value="<?= amanLogin($_SESSION['csrf_login']) ?>"
            >

            <label for="identitas">Email atau username</label>
            <input
                id="identitas"
                name="identitas"
                type="text"
                maxlength="150"
                autocomplete="username"
                value="<?= amanLogin($identitas) ?>"
                required
            >

            <label for="password">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                maxlength="72"
                autocomplete="current-password"
                required
            >

            <button type="submit">Masuk</button>
        </form>

        <p class="tautan-login">
            Belum punya akun?
            <a href="daftar.php">Daftar sekarang</a>
        </p>

        <p class="tautan-login">
            <a href="index.php">← Kembali ke beranda</a>
        </p>
    </main>
</body>
</html>