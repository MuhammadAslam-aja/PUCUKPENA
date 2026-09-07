<?php
// admin/comments.php — Moderasi Komentar Pengunjung Pucuk Pena
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$db = getDB();
$message = '';
$error   = '';

// Handle Hapus Komentar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    if ($delId > 0) {
        $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$delId]);
        $message = "Komentar #$delId berhasil dihapus!";
    }
}

// Ambil semua komentar beserta judul artikelnya
$sql = "SELECT c.*, a.title AS article_title 
        FROM comments c 
        LEFT JOIN articles a ON c.article_id = a.id 
        ORDER BY c.id DESC";
$comments = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$totalComments = count($comments);
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Komentar — Admin Pucuk Pena</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <img src="../img/PUCUK%20PENA.png" alt="Logo">
    <span>Admin</span>
  </div>
  <nav>
    <a href="index.php"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
    <a href="articles.php"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Artikel</a>
    <a href="comments.php" class="active"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Komentar</a>
    <a href="ads.php"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>Iklan</a>
    <a href="breaking.php"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>Breaking News</a>
  </nav>
  <div class="sidebar-footer">
    <div><?= htmlspecialchars($_SESSION['admin_user']) ?></div>
    <a href="logout.php">Keluar</a> · <a href="../" target="_blank">Website</a>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <h1>Kelola Komentar</h1>
    <div class="topbar-right">
      <a href="../" target="_blank">Lihat Website →</a>
    </div>
  </div>

  <div class="page-body">

    <?php if ($message): ?>
      <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-header">
        <h2>Daftar Seluruh Komentar (<?= number_format($totalComments) ?>)</h2>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:50px">ID</th>
              <th style="width:140px">Pengirim</th>
              <th>Isi Komentar</th>
              <th style="width:240px">Artikel Terkait</th>
              <th style="width:140px">Waktu</th>
              <th style="width:100px;text-align:center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($comments as $c): ?>
            <tr>
              <td><?= $c['id'] ?></td>
              <td>
                <div style="font-weight:700;color:var(--text-main)"><?= htmlspecialchars($c['name']) ?></div>
              </td>
              <td>
                <div style="font-size:0.88rem;color:var(--text-muted);line-height:1.5;white-space:pre-line">
                  <?= htmlspecialchars($c['comment']) ?>
                </div>
              </td>
              <td>
                <?php if (!empty($c['article_title'])): ?>
                  <a href="../#article-<?= $c['article_id'] ?>" target="_blank" style="color:var(--green-700);font-weight:600;font-size:0.83rem;text-decoration:none">
                    <?= htmlspecialchars(mb_strimwidth($c['article_title'], 0, 45, '...')) ?> ↗
                  </a>
                <?php else: ?>
                  <span style="color:var(--gray-400);font-size:0.8rem">Artikel #<?= $c['article_id'] ?></span>
                <?php endif; ?>
              </td>
              <td style="font-size:0.8rem;color:var(--gray-400);white-space:nowrap">
                <?= htmlspecialchars(date('d M Y, H:i', strtotime($c['created_at']))) ?>
              </td>
              <td style="text-align:center;white-space:nowrap">
                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus komentar ini?')">
                  <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($comments)): ?>
            <tr>
              <td colspan="6" style="text-align:center;padding:50px 20px;color:var(--gray-400)">
                💬 Belum ada komentar pengunjung di database.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
</body>
</html>
