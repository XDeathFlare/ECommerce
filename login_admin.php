<?php
require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';

function amanAdmin($nilai)
{
    return htmlspecialchars($nilai, ENT_QUOTES, 'UTF-8');
}

if (
    !empty($_SESSION['id_user']) &&
    ($_SESSION['role'] ?? '') === 'admin'
) {
    header('Location: admin.php');
    exit;
}

if (empty($_SESSION['csrf_admin_login'])) {
    $_SESSION['csrf_admin_login'] = bin2hex(random_bytes(32));
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

    if (!hash_equals($_SESSION['csrf_admin_login'], $token)) {
        $pesan = 'Sesi formulir tidak valid. Muat ulang halaman.';
    } elseif ($identitas === '' || $password === '') {
        $pesan = 'Isi email atau username dan password admin.';
    } elseif (strlen($identitas) > 150 || strlen($password) > 72) {
        $pesan = 'Email, username, atau password admin tidak sesuai.';
    } else {
        try {
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
                $akun['role'] === 'admin'
            ) {
                session_regenerate_id(true);

                $_SESSION = [
                    'id_user' => (int) $akun['id_user'],
                    'nama_user' => $akun['nama_user'],
                    'role' => 'admin',
                    'csrf_logout' => bin2hex(random_bytes(32))
                ];

                header('Location: admin.php');
                exit;
            }

            $pesan = 'Email, username, atau password admin tidak sesuai.';
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
    <title>Login Admin | CL Collection</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .halaman-admin-login {
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

        .kartu-admin-login {
            width: 100%;
            max-width: 460px;
            box-sizing: border-box;
            padding: 36px;
            border: 1px solid #efdee5;
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 18px 55px rgba(89, 39, 61, .10);
        }

        .logo-admin-login {
            display: block;
            width: 90px;
            height: 90px;
            object-fit: contain;
            margin: 0 auto 20px;
        }

        .label-admin {
            display: block;
            margin-bottom: 10px;
            text-align: center;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 3px;
            color: #71666d;
        }

        .kartu-admin-login h1 {
            margin: 0 0 10px;
            text-align: center;
            font-family: Georgia, serif;
            font-size: 34px;
            color: #a8315b;
        }

        .pengantar-admin {
            margin: 0 0 24px;
            text-align: center;
            font-size: 13px;
            line-height: 1.8;
            color: #71666d;
        }

        .form-admin-login label {
            display: block;
            margin: 17px 0 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .form-admin-login input {
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
            padding: 13px 14px;
            border: 1px solid #e5d5dd;
            border-radius: 10px;
            background: #fff;
            font: inherit;
            font-size: 14px;
        }

        .form-admin-login input:focus {
            outline: 2px solid #c84d7a;
            outline-offset: 2px;
        }

        .form-admin-login button {
            width: 100%;
            padding: 14px;
            margin-top: 25px;
            border: 0;
            border-radius: 10px;
            background: #191519;
            color: #fff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .form-admin-login button:hover {
            background: #a8315b;
        }

        .pesan-admin-login {
            padding: 13px;
            border-radius: 10px;
            background: #fff0f2;
            color: #8c203e;
            font-size: 13px;
            line-height: 1.7;
        }

        .kembali-admin-login {
            display: block;
            margin-top: 24px;
            text-align: center;
            color: #a8315b;
            font-size: 13px;
            text-decoration: none;
        }

        .kembali-admin-login:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .kartu-admin-login {
                padding: 26px;
            }
        }
    </style>
</head>

<body class="halaman-admin-login">
    <main class="kartu-admin-login">
        <a href="index.php" aria-label="Beranda CL Collection">
            <img
                class="logo-admin-login"
                src="assets/images/logo.png"
                alt="CL Collection"
            >
        </a>

        <span class="label-admin">CL COLLECTION</span>

        <h1>Login Admin</h1>

        <p class="pengantar-admin">
            Masuk untuk mengelola toko CL Collection.
        </p>

        <?php if ($pesan !== ''): ?>
            <div class="pesan-admin-login" role="alert">
                <?= amanAdmin($pesan) ?>
            </div>
        <?php endif; ?>

        <form
            class="form-admin-login"
            method="post"
            action="login_admin.php"
        >
            <input
                type="hidden"
                name="csrf"
                value="<?= amanAdmin($_SESSION['csrf_admin_login']) ?>"
            >

            <label for="identitas">Email atau username admin</label>
            <input
                id="identitas"
                name="identitas"
                type="text"
                maxlength="150"
                autocomplete="username"
                value="<?= amanAdmin($identitas) ?>"
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

            <button type="submit">Masuk ke Dashboard</button>
        </form>

        <a class="kembali-admin-login" href="index.php">
            ← Kembali ke beranda
        </a>
    </main>
</body>
</html>