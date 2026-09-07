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
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    foreach ($slots as $adId => $slotName) {
        $adId = (int)$adId;
        $name    = trim($_POST['name'][$adId] ?? '');
        $type    = ($_POST['type'][$adId] ?? 'manual') === 'google' ? 'google' : 'manual';
        $gclt    = trim($_POST['google_client'][$adId] ?? '');
        $gslot   = trim($_POST['google_slot'][$adId] ?? '');
        $url     = trim($_POST['url'][$adId] ?? '');
        $active  = isset($_POST['active'][$adId]) ? 1 : 0;

        // Ambil gambar saat ini atau dari input URL
        $image   = trim($_POST['image_current'][$adId] ?? '');
        if (!empty($_POST['image_url'][$adId])) {
            $image = trim($_POST['image_url'][$adId]);
        }

        // Jika ada unggahan file gambar baru
        if (isset($_FILES['ad_file']['name'][$adId]) && $_FILES['ad_file']['error'][$adId] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['ad_file']['tmp_name'][$adId];
            $fileName = $_FILES['ad_file']['name'][$adId];
            $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'])) {
                $destName = 'ad_' . $slotName . '_' . time() . '.' . $ext;
                if (move_uploaded_file($fileTmp, $uploadDir . $destName)) {
                    $image = 'uploads/' . $destName;
                }
            }
        }

        $stmt = $db->prepare("UPDATE ads SET name=?, type=?, image=?, google_client=?, google_slot=?, url=?, active=? WHERE id=?");
        $stmt->execute([$name, $type, $image, $gclt, $gslot, $url, $active, $adId]);
    }
    $message = 'Semua perubahan iklan berhasil disimpan!';
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
    <a href="comments.php"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Komentar</a>
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
      💡 <b>Iklan Mandiri Cukup Upload Gambar:</b> Anda cukup memilih file gambar banner dan mengisi tautan tujuan (URL). Pengunjung yang mengklik banner akan otomatis diarahkan ke URL tujuan tersebut.
    </div>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="save_all_ads" value="1">

      <?php foreach ($ads as $ad):
        $meta = $slotMeta[$ad['slot']] ?? ['label' => $ad['slot'], 'pos' => '', 'type' => $ad['type']];
        $isGoogle = $ad['type'] === 'google';
      ?>
      <div class="ad-slot-card <?= $isGoogle ? 'google' : '' ?>" style="margin-bottom:24px">
        <h3>
          <?= htmlspecialchars($meta['label']) ?>
          <span class="ad-type-badge ad-type-<?= $ad['type'] ?>"><?= strtoupper($ad['type']) ?></span>
          <?php if ($ad['active']): ?><span class="badge badge-published">Aktif</span><?php else: ?><span class="badge badge-draft">Nonaktif</span><?php endif; ?>
        </h3>
        <div class="ad-slot-desc">Posisi: <?= htmlspecialchars($meta['pos']) ?> · Slot: <code><?= htmlspecialchars($ad['slot']) ?></code></div>

        <input type="hidden" name="slot[<?= $ad['id'] ?>]" value="<?= htmlspecialchars($ad['slot']) ?>">
        <input type="hidden" name="type[<?= $ad['id'] ?>]" value="<?= htmlspecialchars($ad['type']) ?>">

        <div class="form-row">
          <div class="form-group" style="flex:2">
            <label>Nama / Label Iklan (internal)</label>
            <input type="text" name="name[<?= $ad['id'] ?>]" class="form-control"
                   value="<?= htmlspecialchars($ad['name']) ?>" placeholder="Misal: Promo GreenLife / Banner Brand">
          </div>
          <div class="form-group" style="flex:1">
            <label>Status Tayang</label>
            <div class="form-check" style="margin-top:10px">
              <input type="checkbox" name="active[<?= $ad['id'] ?>]" id="active_<?= $ad['id'] ?>" value="1" <?= $ad['active'] ? 'checked' : '' ?>>
              <label for="active_<?= $ad['id'] ?>" style="font-weight:600;color:var(--text-primary)">Aktifkan iklan ini</label>
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
        <!-- Manual Ad fields: HANYA GAMBAR & URL TUJUAN SAJA -->
        <input type="hidden" name="image_current[<?= $ad['id'] ?>]" value="<?= htmlspecialchars($ad['image'] ?? '') ?>">

        <div style="margin:16px 0;padding:16px;background:var(--bg-main);border:1px solid var(--border-color);border-radius:10px">
          <label style="display:block;margin-bottom:10px;font-weight:700;font-size:0.9rem;color:var(--green-700)">Gambar Banner Iklan</label>
          
          <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap">
            <!-- Pratinjau Banner -->
            <div id="preview_wrap_<?= $ad['id'] ?>" style="min-width:200px;max-width:340px;width:100%;border-radius:8px;overflow:hidden;border:1px solid var(--border-color);background:#fff;box-shadow:0 2px 6px rgba(0,0,0,0.05);text-align:center">
              <?php if (!empty($ad['image'])): ?>
                <img src="../<?= htmlspecialchars($ad['image']) ?>" id="preview_img_<?= $ad['id'] ?>" alt="Preview Iklan" style="max-width:100%;height:auto;max-height:150px;display:block;margin:0 auto;object-fit:cover">
              <?php else: ?>
                <div id="preview_placeholder_<?= $ad['id'] ?>" style="padding:30px 16px;color:var(--gray-400);font-size:0.85rem">
                  🖼️ Belum ada gambar banner
                </div>
              <?php endif; ?>
            </div>

            <!-- Upload File & URL Alternatif -->
            <div style="flex:1;min-width:260px">
              <div class="form-group" style="margin-bottom:12px">
                <label style="font-size:0.82rem;font-weight:600">Pilih File Gambar dari Komputer/HP:</label>
                <input type="file" name="ad_file[<?= $ad['id'] ?>]" class="form-control" accept="image/*" onchange="previewAdFile(this, <?= $ad['id'] ?>)">
                <small style="color:var(--gray-400);font-size:0.75rem">Mendukung file JPG, PNG, WebP, GIF, SVG.</small>
              </div>
              <div class="form-group" style="margin-bottom:0">
                <label style="font-size:0.82rem;font-weight:600">Atau Masukkan Tautan / Path Gambar:</label>
                <input type="text" name="image_url[<?= $ad['id'] ?>]" class="form-control"
                       value="<?= htmlspecialchars($ad['image'] ?? '') ?>"
                       placeholder="uploads/nama_file.jpg atau https://..."
                       oninput="previewAdUrl(this.value, <?= $ad['id'] ?>)">
              </div>
            </div>
          </div>
        </div>

        <div class="form-group" style="margin-top:14px">
          <label style="font-weight:600;font-size:0.88rem">Link URL Tujuan Iklan (saat pengunjung mengklik banner):</label>
          <input type="url" name="url[<?= $ad['id'] ?>]" class="form-control"
                 value="<?= htmlspecialchars($ad['url'] ?? '') ?>"
                 placeholder="https://contoh-website.com/promo">
          <small style="color:var(--gray-400);font-size:0.75rem">Jika diisi, pengunjung yang mengklik banner ini akan langsung diarahkan ke tautan tersebut di tab baru.</small>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <div style="display:flex;gap:12px;margin-top:24px;position:sticky;bottom:16px;background:var(--bg-card);padding:14px;border-radius:10px;box-shadow:0 -4px 16px rgba(0,0,0,0.06);border:1px solid var(--border-color);z-index:10">
        <button type="submit" class="btn btn-primary" style="padding:12px 36px;font-size:1rem;font-weight:700">
          Simpan Semua Iklan
        </button>
        <a href="../" target="_blank" class="btn btn-outline" style="padding:12px 20px">
          Lihat di Website →
        </a>
      </div>

    </form>

  </div>
</div>

<script>
function previewAdFile(input, id) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const wrap = document.getElementById('preview_wrap_' + id);
      wrap.innerHTML = `<img src="${e.target.result}" id="preview_img_${id}" style="max-width:100%;height:auto;max-height:150px;display:block;margin:0 auto;object-fit:cover">`;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function previewAdUrl(url, id) {
  if (!url) return;
  const wrap = document.getElementById('preview_wrap_' + id);
  const src = (url.startsWith('http') || url.startsWith('data:')) ? url : '../' + url;
  wrap.innerHTML = `<img src="${src}" id="preview_img_${id}" style="max-width:100%;height:auto;max-height:150px;display:block;margin:0 auto;object-fit:cover" onerror="this.onerror=null;this.src='../img/PUCUK%20PENA.png'">`;
}
</script>
</body>
</html>
