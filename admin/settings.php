<?php
// admin/settings.php — Kelola Pengaturan Website, Footer, Redaksi, & Media Sosial
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$db = getDB();
$message = '';
$error   = '';

// ─── HANDLE SAVE ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $keys = [
        'site_name', 'site_tagline',
        'about_footer', 'footer_copyright', 'footer_subtext',
        'social_facebook', 'social_instagram', 'social_twitter', 'social_youtube', 'social_tiktok',
        'about_modal', 'editorial_modal', 'contact_modal', 'terms_modal', 'privacy_modal'
    ];

    $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

    foreach ($keys as $key) {
        $val = trim($_POST[$key] ?? '');
        $stmt->execute([$key, $val]);
    }

    $message = 'Pengaturan website berhasil disimpan!';
}

// ─── LOAD SETTINGS ──────────────────────────────────────────────────────────
$settingsRaw = $db->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
$s = [];
foreach ($settingsRaw as $row) {
    $s[$row['setting_key']] = $row['setting_value'];
}
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengaturan Website — Admin Pucuk Pena</title>
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
    <a href="comments.php"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Komentar</a>
    <a href="ads.php"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>Iklan</a>
    <a href="breaking.php"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>Breaking News</a>
    <a href="settings.php" class="active"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>Pengaturan</a>
  </nav>
  <div class="sidebar-footer">
    <div><?= htmlspecialchars($_SESSION['admin_user']) ?></div>
    <a href="logout.php">Keluar</a> · <a href="../" target="_blank">Website</a>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <h1>Pengaturan Website</h1>
    <div class="topbar-right">
      <a href="../" target="_blank">Lihat Website →</a>
    </div>
  </div>

  <div class="page-body">

    <?php if ($message): ?>
      <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
      ⚙️ <b>Kelola Seluruh Informasi Website & Footer:</b> Ubah nama portal, teks footer, tautan akun media sosial, serta isi halaman popup informasi (Tentang Kami, Susunan Redaksi, Kontak Kami, Syarat & Ketentuan, Kebijakan Privasi).
    </div>

    <form method="POST">
      <input type="hidden" name="save_settings" value="1">

      <!-- 1. Identitas Situs -->
      <div class="panel" style="margin-bottom:20px">
        <div class="panel-header"><h2>Identitas Situs</h2></div>
        <div class="panel-body">
          <div class="form-row">
            <div class="form-group">
              <label>Nama Media / Portal</label>
              <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($s['site_name'] ?? 'Pucuk Pena') ?>">
            </div>
            <div class="form-group">
              <label>Slogan / Tagline</label>
              <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars($s['site_tagline'] ?? 'Menggores Makna, Mengabarkan Kebenaran') ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Footer & Hak Cipta -->
      <div class="panel" style="margin-bottom:20px">
        <div class="panel-header"><h2>Konten Footer & Hak Cipta</h2></div>
        <div class="panel-body">
          <div class="form-group">
            <label>Deskripsi Singkat di Bawah Logo Footer</label>
            <textarea name="about_footer" class="form-control" rows="3"><?= htmlspecialchars($s['about_footer'] ?? '') ?></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Teks Hak Cipta (Copyright)</label>
              <input type="text" name="footer_copyright" class="form-control" value="<?= htmlspecialchars($s['footer_copyright'] ?? 'Pucuk Pena Media Group. Hak Cipta Dilindungi Undang-Undang.') ?>">
            </div>
            <div class="form-group">
              <label>Teks Sub-Footer Kanan</label>
              <input type="text" name="footer_subtext" class="form-control" value="<?= htmlspecialchars($s['footer_subtext'] ?? 'Dibuat dengan 💚 untuk Jurnalisme Indonesia') ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Tautan Media Sosial -->
      <div class="panel" style="margin-bottom:20px">
        <div class="panel-header"><h2>Tautan Akun Media Sosial (Footer)</h2></div>
        <div class="panel-body">
          <div class="form-row">
            <div class="form-group">
              <label>Facebook URL</label>
              <input type="url" name="social_facebook" class="form-control" value="<?= htmlspecialchars($s['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/nama-halaman">
            </div>
            <div class="form-group">
              <label>Instagram URL</label>
              <input type="url" name="social_instagram" class="form-control" value="<?= htmlspecialchars($s['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/nama-akun">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Twitter / X URL</label>
              <input type="url" name="social_twitter" class="form-control" value="<?= htmlspecialchars($s['social_twitter'] ?? '') ?>" placeholder="https://twitter.com/nama-akun">
            </div>
            <div class="form-group">
              <label>YouTube URL</label>
              <input type="url" name="social_youtube" class="form-control" value="<?= htmlspecialchars($s['social_youtube'] ?? '') ?>" placeholder="https://youtube.com/@nama-channel">
            </div>
            <div class="form-group">
              <label>TikTok URL</label>
              <input type="url" name="social_tiktok" class="form-control" value="<?= htmlspecialchars($s['social_tiktok'] ?? '') ?>" placeholder="https://tiktok.com/@nama-akun">
            </div>
          </div>
        </div>
      </div>

      <!-- 4. Halaman Popup Modal Informasi -->
      <div class="panel" style="margin-bottom:20px">
        <div class="panel-header"><h2>Konten Modal Informasi (Footer Links)</h2></div>
        <div class="panel-body">
          <div class="form-group">
            <label>1. Tentang Kami</label>
            <textarea name="about_modal" class="form-control" rows="5"><?= htmlspecialchars($s['about_modal'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label>2. Susunan Redaksi</label>
            <textarea name="editorial_modal" class="form-control" rows="5"><?= htmlspecialchars($s['editorial_modal'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label>3. Kontak Kami</label>
            <textarea name="contact_modal" class="form-control" rows="5"><?= htmlspecialchars($s['contact_modal'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label>4. Syarat & Ketentuan</label>
            <textarea name="terms_modal" class="form-control" rows="5"><?= htmlspecialchars($s['terms_modal'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label>5. Kebijakan Privasi</label>
            <textarea name="privacy_modal" class="form-control" rows="5"><?= htmlspecialchars($s['privacy_modal'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Save Button bar -->
      <div style="display:flex;gap:12px;position:sticky;bottom:16px;background:var(--bg-card);padding:14px;border-radius:10px;box-shadow:0 -4px 16px rgba(0,0,0,0.06);border:1px solid var(--border-color);z-index:10">
        <button type="submit" class="btn btn-primary" style="padding:12px 36px;font-size:1rem;font-weight:700">
          Simpan Semua Pengaturan
        </button>
        <a href="../" target="_blank" class="btn btn-outline" style="padding:12px 20px">
          Lihat di Website →
        </a>
      </div>

    </form>

  </div>
</div>

</body>
</html>
