<?php require_once __DIR__ . '/sesi.php'; ?>

<?php if (!empty($_SESSION['id_user'])): ?>
<?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
    <a href="admin.php">Dashboard Admin</a>
<?php endif; ?>
    <span class="sapaan-akun">
        Hai,
        <?= htmlspecialchars(
            $_SESSION['nama_user'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </span>

    <form class="form-keluar" action="logout.php" method="post">
        <input
            type="hidden"
            name="csrf"
            value="<?= htmlspecialchars(
                $_SESSION['csrf_logout'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
        <button type="submit">Keluar</button>
    </form>
<?php else: ?>
    <a href="login.php">Masuk</a>
    <a href="daftar.php">Daftar</a>
<?php endif; ?>