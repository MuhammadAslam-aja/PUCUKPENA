<?php
// admin/ads.php — Kelola 3 Iklan Manual + 2 Google Ads
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$db = getDB();
$message = '';
$error   = '';

// ─── HANDLE SAVE ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_all_ads'])) {
    $slots = $_POST['slot'] ?? [];
    foreach ($slots as $adId => $slotName) {
        $adId = (int)$adId;
        $name    = trim($_POST['name'][$adId] ?? '');
        $type    = $_POST['type'][$adId] === 'google' ? 'google' : 'manual';
        $content = trim($_POST['content'][$adId] ?? '');
        $gclt    = trim($_POST['google_client'][$adId] ?? '');
        $gslot   = trim($_POST['google_slot'][$adId] ?? '');
        $url     = trim($_POST['url'][$adId] ?? '');
        $active  = isset($_POST['active'][$adId]) ? 1 : 0;

        $stmt = $db->prepare("UPDATE ads SET name=?,type=?,content=?,google_client=?,google_slot=?,url=?,active=? WHERE id=?");
        $stmt->execute([$name, $type, $content, $gclt, $gslot, $url, $active, $adId]);
    }
    $message = 'Semua iklan berhasil disimpan!';
}

// ─── LOAD ADS ────────────────────────────────────────────────────────────────
$ads = $db->query("SELECT * FROM ads ORDER BY id ASC")->fetchAll();

// Slot info / metadata
$slotMeta = [
    'leaderboard'      => ['label' => 'Leaderboard (970×90)', 'pos' => 'Atas halaman utama', 'type' => 'manual', 'emoji' => ''],
    'native1'          => ['label' => 'Native In-Feed', 'pos' => 'Di antara berita terkini', 'type' => 'manual', 'emoji' => ''],
    'native2'          => ['label' => 'Native Sidebar', 'pos' => 'Widget sidebar kanan', 'type' => 'manual', 'emoji' => ''],
    'google_rectangle' => ['label' => 'Google Ads — Rectangle (300×250)', 'pos' => 'Bawah halaman', 'type' => 'google', 'emoji' => ''],
    'google_sticky'    => ['label' => 'Google Ads — Sticky Bottom', 'pos' => 'Bawah layar (sticky)', 'type' => 'google', 'emoji' => ''],
];
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Iklan — Admin Pucuk Pena</title>
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
    <a href="ads.php" class="active"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>Iklan</a>
    <a href="breaking.php"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>Breaking News</a>
  </nav>
  <div class="sidebar-footer">
    <div><?= htmlspecialchars($_SESSION['admin_user']) ?></div>
    <a href="logout.php">Keluar</a> · <a href="../" target="_blank">Website</a>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <h1>Kelola Iklan</h1>
    <div class="topbar-right">
      <a href="../" target="_blank">Lihat Website →</a>
    </div>
  </div>

  <div class="page-body">

    <?php if ($message): ?>
      <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
      Tersedia <b>3 slot iklan manual</b> dan <b>2 slot Google Ads</b>. Simpan semua sekaligus dengan tombol di bawah.
    </div>

    <form method="POST">
      <input type="hidden" name="save_all_ads" value="1">

      <?php foreach ($ads as $ad):
        $meta = $slotMeta[$ad['slot']] ?? ['label' => $ad['slot'], 'pos' => '', 'type' => $ad['type'], 'emoji' => ''];
        $isGoogle = $ad['type'] === 'google';
      ?>
      <div class="ad-slot-card <?= $isGoogle ? 'google' : '' ?>">
        <h3>
          <?= htmlspecialchars($meta['label']) ?>
          <span class="ad-type-badge ad-type-<?= $ad['type'] ?>"><?= strtoupper($ad['type']) ?></span>
          <?php if ($ad['active']): ?><span class="badge badge-published">Aktif</span><?php else: ?><span class="badge badge-draft">Nonaktif</span><?php endif; ?>
        </h3>
        <div class="ad-slot-desc">Posisi: <?= htmlspecialchars($meta['pos']) ?> · Slot: <code><?= htmlspecialchars($ad['slot']) ?></code></div>

        <input type="hidden" name="slot[<?= $ad['id'] ?>]" value="<?= htmlspecialchars($ad['slot']) ?>">
        <input type="hidden" name="type[<?= $ad['id'] ?>]" value="<?= htmlspecialchars($ad['type']) ?>">

        <div class="form-row">
          <div class="form-group">
            <label>Nama Iklan (internal)</label>
            <input type="text" name="name[<?= $ad['id'] ?>]" class="form-control"
                   value="<?= htmlspecialchars($ad['name']) ?>" placeholder="Nama iklan untuk referensi">
          </div>
          <div class="form-group">
            <label>Status</label>
            <div class="form-check" style="margin-top:10px">
              <input type="checkbox" name="active[<?= $ad['id'] ?>]" id="active_<?= $ad['id'] ?>" value="1" <?= $ad['active'] ? 'checked' : '' ?>>
              <label for="active_<?= $ad['id'] ?>" style="font-weight:normal">Aktifkan iklan ini</label>
            </div>
          </div>
        </div>

        <?php if ($isGoogle): ?>
        <!-- Google Ads fields -->
        <div class="form-row">
          <div class="form-group">
            <label>Google AdSense Client ID <code style="font-size:0.75rem;background:#e8f4fd;padding:2px 5px;border-radius:3px">ca-pub-XXXXXXXXXX</code></label>
            <input type="text" name="google_client[<?= $ad['id'] ?>]" class="form-control"
                   value="<?= htmlspecialchars($ad['google_client'] ?? '') ?>"
                   placeholder="ca-pub-1234567890123456">
          </div>
          <div class="form-group">
            <label>AdSense Slot ID</label>
            <input type="text" name="google_slot[<?= $ad['id'] ?>]" class="form-control"
                   value="<?= htmlspecialchars($ad['google_slot'] ?? '') ?>"
                   placeholder="1234567890">
          </div>
        </div>
        <div class="alert alert-info" style="margin-top:8px;font-size:0.8rem">
          Kode AdSense yang akan ditampilkan: <code>&lt;ins class="adsbygoogle" data-ad-client="<?= htmlspecialchars($ad['google_client'] ?: 'ca-pub-XXXX') ?>" data-ad-slot="<?= htmlspecialchars($ad['google_slot'] ?: 'XXXX') ?>"&gt;&lt;/ins&gt;</code>
        </div>

        <?php else: ?>
        <!-- Manual Ad fields -->
        <div class="form-group">
          <label>URL Tujuan Iklan (saat diklik)</label>
          <input type="url" name="url[<?= $ad['id'] ?>]" class="form-control"
                 value="<?= htmlspecialchars($ad['url'] ?? '') ?>"
                 placeholder="https://contoh.com/produk">
        </div>
        <div class="form-group">
          <label>Konten Iklan (HTML atau teks)</label>
          <?php if ($ad['slot'] === 'leaderboard'): ?>
          <input type="text" name="content[<?= $ad['id'] ?>]" class="form-control"
                 value="<?= htmlspecialchars($ad['content'] ?? '') ?>"
                 placeholder="🌿 Nama Iklan — Tagline iklan · Klik di sini">
          <small style="color:var(--gray-400);font-size:0.75rem">Format: &lt;strong&gt;🌿 Nama&lt;/strong&gt; — Tagline · &lt;u&gt;Klik di sini&lt;/u&gt;</small>
          <?php else: ?>
          <textarea name="content[<?= $ad['id'] ?>]" class="form-control" rows="3"
                    placeholder="Format: EMOJI|Judul Iklan|Deskripsi singkat|Teks Tombol CTA"><?= htmlspecialchars($ad['content'] ?? '') ?></textarea>
          <small style="color:var(--gray-400);font-size:0.75rem">
            Format pipe-separated: <code>📱|Nama Produk|Deskripsi singkat|Tombol CTA</code><br>
            Contoh: <code>📱|AriTel 5G|Paket internet unlimited mulai 89rb|Pelajari Lebih Lanjut</code>
          </small>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <div style="display:flex;gap:12px;margin-top:20px">
        <button type="submit" class="btn btn-primary" style="padding:12px 32px;font-size:1rem">
          Simpan Semua Iklan
        </button>
        <a href="../" target="_blank" class="btn btn-outline" style="padding:12px 20px">
          Lihat di Website
        </a>
      </div>

    </form>

  </div>
</div>
</body>
</html>
