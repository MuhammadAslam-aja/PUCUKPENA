<?php
// admin/breaking.php — Kelola Breaking News Ticker
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$db = getDB();
$message = '';

// ─── HANDLE SAVE ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_breaking'])) {
    $texts = $_POST['texts'] ?? [];
    $db->exec("DELETE FROM breaking_news");
    $stmt = $db->prepare("INSERT INTO breaking_news (text, active, sort_order) VALUES (?, 1, ?)");
    $i = 0;
    foreach ($texts as $text) {
        $text = trim($text);
        if ($text !== '') {
            $stmt->execute([$text, $i++]);
        }
    }
    $message = 'Breaking news berhasil disimpan!';
}

// ─── LOAD ────────────────────────────────────────────────────────────────────
$items = $db->query("SELECT * FROM breaking_news ORDER BY sort_order ASC, id ASC")->fetchAll();
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Breaking News — Admin Pucuk Pena</title>
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
    <a href="breaking.php" class="active"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>Breaking News</a>
  </nav>
  <div class="sidebar-footer">
    <div><?= htmlspecialchars($_SESSION['admin_user']) ?></div>
    <a href="logout.php">Keluar</a> · <a href="../" target="_blank">Website</a>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <h1>Breaking News Ticker</h1>
    <div class="topbar-right">
      <a href="../" target="_blank">Lihat Website →</a>
    </div>
  </div>

  <div class="page-body">

    <?php if ($message): ?>
      <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
      Breaking news berjalan sebagai ticker merah di bawah header. Tambahkan hingga 10 baris. Urutan dari atas ke bawah.
    </div>

    <!-- Preview -->
    <div style="background:var(--red);color:#fff;padding:10px 16px;border-radius:8px;margin-bottom:20px;overflow:hidden;white-space:nowrap">
      <strong style="background:#fff;color:var(--red);padding:3px 8px;border-radius:4px;margin-right:10px;font-size:0.78rem">BREAKING</strong>
      <span id="previewTicker" style="font-size:0.85rem">Preview akan muncul di sini...</span>
    </div>

    <div class="panel">
      <div class="panel-header">
        <h2>Daftar Teks Breaking News</h2>
        <button type="button" class="btn btn-outline btn-sm" onclick="addRow()">+ Tambah Baris</button>
      </div>
      <div class="panel-body">
        <form method="POST">
          <input type="hidden" name="save_breaking" value="1">

          <div id="breakingRows">
            <?php
            $displayItems = $items ?: [
                ['text' => 'BREAKING: Masukkan teks breaking news pertama di sini', 'id' => 0],
                ['text' => 'TERKINI: Berita terbaru kedua', 'id' => 0],
            ];
            foreach ($displayItems as $idx => $item):
            ?>
            <div class="breaking-row" id="row_<?= $idx ?>">
              <span style="min-width:28px;font-size:0.9rem;color:var(--gray-400);font-weight:700"><?= $idx+1 ?>.</span>
              <input type="text" name="texts[]" class="form-control breaking-text"
                     value="<?= htmlspecialchars($item['text']) ?>"
                     placeholder="BREAKING: Teks berita yang akan berjalan...">
              <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">✕</button>
            </div>
            <?php endforeach; ?>
          </div>

          <div style="display:flex;gap:12px;margin-top:20px">
            <button type="submit" class="btn btn-primary" style="padding:11px 28px">
              Simpan Breaking News
            </button>
            <button type="button" class="btn btn-secondary" onclick="addRow()">+ Tambah Baris</button>
          </div>
        </form>
      </div>
    </div>

    <div class="panel" style="margin-top:16px">
      <div class="panel-header"><h2>Tips Format Breaking News</h2></div>
      <div class="panel-body" style="font-size:0.88rem;color:var(--gray-600);line-height:2">
        <div>• Gunakan emoji di awal untuk visual menarik: <code>BREAKING:</code> <code>TERKINI:</code> <code>INFO:</code></div>
        <div>• Pisahkan label dan teks dengan tanda titik dua: <code>BREAKING: Pemerintah umumkan...</code></div>
        <div>• Maksimal rekomendasi ~100 karakter per baris agar nyaman dibaca</div>
        <div>• Urutan baris = urutan tampil di ticker (dari kiri ke kanan)</div>
      </div>
    </div>

  </div>
</div>

<script>
let rowCount = <?= count($displayItems ?? [0]) ?>;

function addRow() {
  rowCount++;
  const div = document.createElement('div');
  div.className = 'breaking-row';
  div.id = 'row_' + rowCount;
  div.innerHTML = `
    <span style="min-width:28px;font-size:0.9rem;color:var(--gray-400);font-weight:700">${rowCount}.</span>
    <input type="text" name="texts[]" class="form-control breaking-text"
           placeholder="BREAKING: Teks berita yang akan berjalan...">
    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">✕</button>
  `;
  document.getElementById('breakingRows').appendChild(div);
  div.querySelector('input').focus();
  updatePreview();
}

function removeRow(btn) {
  const row = btn.closest('.breaking-row');
  if (document.querySelectorAll('.breaking-row').length > 1) {
    row.remove();
    renumberRows();
  } else {
    row.querySelector('input').value = '';
  }
  updatePreview();
}

function renumberRows() {
  document.querySelectorAll('.breaking-row').forEach((r, i) => {
    const span = r.querySelector('span');
    if (span) span.textContent = (i + 1) + '.';
  });
}

function updatePreview() {
  const texts = [...document.querySelectorAll('.breaking-text')]
    .map(i => i.value.trim())
    .filter(t => t);
  document.getElementById('previewTicker').textContent = texts.join('  ·  ') || 'Tambahkan teks breaking news di atas...';
}

document.addEventListener('input', e => {
  if (e.target.classList.contains('breaking-text')) updatePreview();
});

// Init preview
updatePreview();
</script>
</body>
</html>
