<?php
// admin/index.php — Dashboard Admin Pucuk Pena
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$db = getDB();

// Statistik
$totalArticles   = $db->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn();
$totalDraft      = $db->query("SELECT COUNT(*) FROM articles WHERE status='draft'")->fetchColumn();
$totalAds        = $db->query("SELECT COUNT(*) FROM ads WHERE active=1")->fetchColumn();
$totalBreaking   = $db->query("SELECT COUNT(*) FROM breaking_news WHERE active=1")->fetchColumn();

// Artikel terbaru (Urutkan dari ID terbesar / terbaru)
$recentArticles  = $db->query("SELECT id, title, type, cat, badge, status, author, date_display FROM articles ORDER BY id DESC LIMIT 10")->fetchAll();
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Admin Pucuk Pena</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <img src="../img/PUCUK%20PENA.png" alt="Logo">
    <span>Admin</span>
  </div>
  <nav>
    <a href="index.php" class="active">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>
    <a href="articles.php">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      Artikel
    </a>
    <a href="ads.php">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      Iklan
    </a>
    <a href="breaking.php">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
      Breaking News
    </a>
  </nav>
  <div class="sidebar-footer">
    <div>Login: <b><?= htmlspecialchars($_SESSION['admin_user']) ?></b></div>
    <a href="logout.php">Keluar</a> ·
    <a href="../" target="_blank">Lihat Website</a>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <h1>Dashboard</h1>
    <div class="topbar-right">
      <a href="articles.php?action=new" class="btn btn-primary btn-sm">+ Artikel Baru</a>
      <a href="../" target="_blank">Lihat Website →</a>
    </div>
  </div>

  <div class="page-body">

    <!-- Stat Cards -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--green-700)" stroke-width="2"><path d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1m2 13a2 2 0 0 1-2-2V7m2 13a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
        </div>
        <div class="stat-info">
          <div class="num"><?= number_format($totalArticles) ?></div>
          <div class="lbl">Artikel Tayang</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--green-700)" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
        <div class="stat-info">
          <div class="num"><?= number_format($totalDraft) ?></div>
          <div class="lbl">Draft</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--green-700)" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        </div>
        <div class="stat-info">
          <div class="num"><?= number_format($totalAds) ?></div>
          <div class="lbl">Iklan Aktif</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--red)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="stat-info">
          <div class="num"><?= number_format($totalBreaking) ?></div>
          <div class="lbl">Breaking News</div>
        </div>
      </div>
    </div>

    <!-- Recent Articles -->
    <div class="panel">
      <div class="panel-header">
        <h2>Artikel Terbaru</h2>
        <a href="articles.php" class="btn btn-outline btn-sm">Lihat Semua</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Judul</th>
              <th>Kategori</th>
              <th>Penulis</th>
              <th>Tanggal</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentArticles as $a): ?>
            <tr>
              <td><?= $a['id'] ?></td>
              <td class="td-title">
                <a href="articles.php?action=edit&id=<?= $a['id'] ?>">
                  <?= htmlspecialchars(mb_strimwidth($a['title'], 0, 70, '...')) ?>
                </a>
              </td>
              <td><span class="badge badge-<?= htmlspecialchars($a['badge']) ?>"><?= htmlspecialchars($a['badge']) ?></span></td>
              <td><?= htmlspecialchars($a['author']) ?></td>
              <td><?= htmlspecialchars($a['date_display']) ?></td>
              <td><span class="badge badge-<?= $a['status'] ?>"><?= strtoupper($a['status']) ?></span></td>
              <td style="white-space:nowrap">
                <a href="../#article-<?= $a['id'] ?>" target="_blank" class="btn btn-outline btn-sm" title="Lihat di Halaman Depan">Lihat</a>
                <a href="articles.php?action=edit&id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                <button onclick="confirmDelete(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['title']), ENT_QUOTES) ?>')" class="btn btn-danger btn-sm">Hapus</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentArticles)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-400)">Belum ada artikel. <a href="articles.php?action=new">Tambah sekarang</a></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick Links -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:20px">
      <div class="panel">
        <div class="panel-header"><h2>Aksi Cepat</h2></div>
        <div class="panel-body">
          <a href="articles.php?action=new" class="btn btn-primary" style="width:100%;margin-bottom:10px;justify-content:center">+ Tulis Artikel Baru</a>
          <a href="breaking.php" class="btn btn-secondary" style="width:100%;margin-bottom:10px;justify-content:center">Edit Breaking News</a>
          <a href="ads.php" class="btn btn-secondary" style="width:100%;justify-content:center">Kelola Iklan</a>
        </div>
      </div>
      <div class="panel">
        <div class="panel-header"><h2>Info Sistem</h2></div>
        <div class="panel-body" style="font-size:0.85rem;color:var(--gray-600);line-height:2">
          <div>Database: <b>ariweb</b></div>
          <div>Versi PHP: <b><?= PHP_VERSION ?></b></div>
          <div>Website: <a href="../" target="_blank" style="color:var(--green-700)">/ARIWEB/</a></div>
          <div>Folder Uploads: <a href="../uploads/" style="color:var(--green-700)">/uploads/</a></div>
          <div>Waktu Server: <b><?= date('d M Y H:i:s') ?></b></div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
function confirmDelete(id, title) {
  if (confirm('Hapus artikel:\n"' + title + '"?\n\nTindakan ini tidak dapat dibatalkan.')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'articles.php';
    form.innerHTML = `<input name="action" value="delete"><input name="id" value="${id}">`;
    document.body.appendChild(form);
    form.submit();
  }
}
</script>
</body>
</html>
