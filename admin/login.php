<?php
// admin/login.php
require_once __DIR__ . '/../includes/db.php';
session_start();

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $stmt = getDB()->prepare("SELECT id, password FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user']      = $username;
                $_SESSION['admin_id']        = $user['id'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Username atau password salah.';
            }
        } catch (Exception $e) {
            $error = 'Gagal terhubung ke database. Pastikan database sudah disetup.';
        }
    } else {
        $error = 'Mohon isi username dan password.';
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin — Pucuk Pena</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <img src="../img/PUCUK%20PENA.png" alt="Pucuk Pena">
    </div>
    <h1>Panel Admin</h1>
    <p>Masuk untuk mengelola konten Pucuk Pena</p>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control"
               placeholder="admin" required autocomplete="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control"
               placeholder="••••••••" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;padding:11px;font-size:0.95rem;margin-top:4px">
        Masuk →
      </button>
    </form>

    <p style="text-align:center;margin-top:16px;font-size:0.8rem;color:var(--gray-400)">
      <a href="../" style="color:var(--green-700);text-decoration:none">← Kembali ke Website</a>
    </p>
  </div>
</body>
</html>
