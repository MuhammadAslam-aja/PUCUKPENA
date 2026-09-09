<?php
// admin/articles.php — CRUD Artikel lengkap
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$db = getDB();
$action  = $_GET['action'] ?? ($_POST['action'] ?? 'list');
$message = '';
$error   = '';

// ─── DELETE ──────────────────────────────────────────────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    if ($id > 0) {
        $db->prepare("DELETE FROM articles WHERE id = ?")->execute([$id]);
        $message = 'Artikel berhasil dihapus.';
    }
    $action = 'list';
}

// ─── SAVE (INSERT / UPDATE) ───────────────────────────────────────────────────
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = (int)($_POST['id'] ?? 0);
    $type    = trim($_POST['type'] ?? 'berita');
    $cat     = trim($_POST['cat'] ?? 'nasional');
    $badge   = trim($_POST['badge'] ?? $type);
    $title   = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author  = trim($_POST['author'] ?? '');
    $date    = trim($_POST['date_display'] ?? date('d F Y'));
    $views   = trim($_POST['views'] ?? '0');
    $status  = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

    // ── Handle gambar upload ──
    $img = trim($_POST['img_current'] ?? '');
    if (empty($img) && !empty($_POST['img_url_manual'])) {
        $img = trim($_POST['img_url_manual']);
    }
    if (isset($_FILES['img_file']) && $_FILES['img_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['img_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (in_array($ext, $allowed)) {
            $newName = 'img_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['img_file']['tmp_name'], $uploadDir . $newName)) {
                $img = 'uploads/' . $newName;
            } else {
                $error = 'Gagal upload gambar.';
            }
        } else {
            $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.';
        }
    }
    // Pastikan artikel selalu memiliki gambar valid
    if (empty($img)) {
        $img = 'img/desa_wisata.png';
    }

    // ── Handle tags ──
    $tagsRaw = trim($_POST['tags'] ?? '');
    $tagsArr = array_filter(array_map('trim', explode(',', $tagsRaw)));
    $tagsJson = json_encode(array_values($tagsArr), JSON_UNESCAPED_UNICODE);

    if (empty($title)) {
        $error = 'Judul artikel tidak boleh kosong.';
        $action = $id > 0 ? 'edit' : 'new';
    } elseif (empty($error)) {
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE articles SET type=?,cat=?,badge=?,title=?,excerpt=?,content=?,author=?,date_display=?,views=?,img=?,tags=?,status=?,updated_at=NOW() WHERE id=?");
            $stmt->execute([$type,$cat,$badge,$title,$excerpt,$content,$author,$date,$views,$img,$tagsJson,$status,$id]);
            $message = 'Artikel berhasil diperbarui.';
        } else {
            $stmt = $db->prepare("INSERT INTO articles (type,cat,badge,title,excerpt,content,author,date_display,views,img,tags,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$type,$cat,$badge,$title,$excerpt,$content,$author,$date,$views,$img,$tagsJson,$status]);
            $id = (int)$db->lastInsertId();
            $message = 'Artikel berhasil ditambahkan!';
        }
        $action = 'edit';
        // Redirect to edit page
        header('Location: articles.php?action=edit&id=' . $id . '&saved=1');
        exit;
    }
}

// ─── EDIT: load data artikel ──────────────────────────────────────────────────
$article = null;
if (in_array($action, ['edit','view']) && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $article = $stmt->fetch();
    if (!$article) { $error = 'Artikel tidak ditemukan.'; $action = 'list'; }
}

if (isset($_GET['saved'])) $message = 'Artikel berhasil disimpan!';

// ─── LIST: ambil data ─────────────────────────────────────────────────────────
$articles = [];
$total = 0;
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$search = trim($_GET['q'] ?? '');
$filterCat = $_GET['cat'] ?? '';

if ($action === 'list') {
    $where = [];
    $params = [];
    if ($search) {
        $where[] = "(title LIKE ? OR author LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($filterCat) {
        $where[] = "type = ?";
        $params[] = $filterCat;
    }
    $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $total = $db->prepare("SELECT COUNT(*) FROM articles $whereStr");
    $total->execute($params);
    $total = (int)$total->fetchColumn();

    $stmt = $db->prepare("SELECT id,type,cat,badge,title,author,date_display,status,views FROM articles $whereStr ORDER BY id DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $articles = $stmt->fetchAll();
}

// Helper: badge class
function badgeCls($b) {
    $map = ['berita'=>'berita','opini'=>'opini','essay'=>'essay','artikel'=>'artikel','investigasi'=>'investigasi','video'=>'video','foto'=>'foto','ekonomi'=>'ekonomi','olahraga'=>'olahraga','nasional'=>'nasional'];
    return $map[$b] ?? 'berita';
}
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Artikel — Admin Pucuk Pena</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <img src="../img/PUCUK%20PENA.png" alt="Logo">
    <span>Admin</span>
  </div>
  <nav>
    <a href="index.php">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>
    <a href="articles.php" class="active">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Artikel
    </a>
    <a href="comments.php">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Komentar
    </a>
    <a href="ads.php">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      Iklan
    </a>
    <a href="breaking.php">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
      Breaking News
    </a>
  </nav>
  <div class="sidebar-footer">
    <div><?= htmlspecialchars($_SESSION['admin_user']) ?></div>
    <a href="logout.php">Keluar</a> · <a href="../" target="_blank">Website</a>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <h1>
      <?php if ($action === 'list'): ?>Daftar Artikel
      <?php elseif ($action === 'new'): ?>Tambah Artikel Baru
      <?php else: ?>Edit Artikel
      <?php endif; ?>
    </h1>
    <div class="topbar-right">
      <?php if ($action !== 'list'): ?>
        <a href="articles.php" class="btn btn-secondary btn-sm">← Daftar Artikel</a>
      <?php else: ?>
        <a href="articles.php?action=new" class="btn btn-primary btn-sm">+ Artikel Baru</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="page-body">

    <?php if ($message): ?>
      <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
    <!-- ─── LIST VIEW ─────────────────────────────────────────────────────── -->
    <div class="panel">
      <div class="panel-header">
        <h2>Semua Artikel (<?= $total ?>)</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
          <input type="hidden" name="action" value="list">
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul/penulis..." class="form-control" style="width:200px;padding:6px 10px">
          <select name="cat" class="form-control" style="width:140px;padding:6px 10px">
            <option value="">Semua Tipe</option>
            <?php foreach (['berita','opini','essay','artikel','video','foto'] as $t): ?>
              <option value="<?= $t ?>" <?= $filterCat === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
        </form>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Judul</th><th>Tipe</th><th>Kategori</th><th>Penulis</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($articles as $a): ?>
            <tr>
              <td><?= $a['id'] ?></td>
              <td class="td-title">
                <a href="articles.php?action=edit&id=<?= $a['id'] ?>">
                  <?= htmlspecialchars(mb_strimwidth($a['title'], 0, 65, '...')) ?>
                </a>
              </td>
              <td><span class="badge badge-<?= badgeCls($a['type']) ?>"><?= htmlspecialchars($a['type']) ?></span></td>
              <td><span style="font-size:0.78rem;color:var(--gray-600)"><?= htmlspecialchars($a['cat']) ?></span></td>
              <td style="font-size:0.82rem"><?= htmlspecialchars($a['author']) ?></td>
              <td style="font-size:0.8rem;white-space:nowrap"><?= htmlspecialchars($a['date_display']) ?></td>
              <td><span class="badge badge-<?= $a['status'] ?>"><?= $a['status'] ?></span></td>
              <td style="white-space:nowrap">
                <a href="articles.php?action=edit&id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                <button onclick="confirmDelete(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes(mb_strimwidth($a['title'],0,40,'...'))) ?>')" class="btn btn-danger btn-sm">🗑️</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($articles)): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">
              <?= $search ? 'Tidak ditemukan hasil pencarian.' : 'Belum ada artikel. <a href="articles.php?action=new">Tambah sekarang</a>' ?>
            </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($total > $perPage): ?>
      <div style="padding:16px 22px;border-top:1px solid var(--gray-200)">
        <div class="pagination">
          <?php for ($p = 1; $p <= ceil($total/$perPage); $p++): ?>
            <a href="?action=list&page=<?= $p ?>&q=<?= urlencode($search) ?>&cat=<?= urlencode($filterCat) ?>"
               class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- ─── FORM EDIT / NEW ───────────────────────────────────────────────── -->
    <?php
    $art = $article ?? [
      'id'=>0,'type'=>'berita','cat'=>'nasional','badge'=>'berita',
      'title'=>'','excerpt'=>'','content'=>'','author'=>'',
      'date_display'=>date('d F Y'),'views'=>'0','img'=>'','tags'=>'[]','status'=>'draft'
    ];
    $tagsStr = implode(', ', json_decode($art['tags'] ?? '[]', true) ?? []);
    ?>
    <form method="POST" enctype="multipart/form-data" id="articleForm">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($art['id'] ?? 0) ?>">
      <input type="hidden" name="img_current" value="<?= htmlspecialchars($art['img'] ?? '') ?>" id="imgCurrentField">

      <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

        <!-- Left Column: Main content -->
        <div>
          <div class="panel" style="margin-bottom:16px">
            <div class="panel-header"><h2>Konten Artikel</h2></div>
            <div class="panel-body">

              <div class="form-group">
                <label>Judul Artikel *</label>
                <input type="text" name="title" class="form-control" required
                       value="<?= htmlspecialchars($art['title']) ?>"
                       placeholder="Tulis judul artikel yang menarik...">
              </div>

              <div class="form-group">
                <label>Ringkasan / Excerpt</label>
                <textarea name="excerpt" class="form-control" rows="3"
                          placeholder="Ringkasan singkat 1-2 kalimat yang muncul di halaman utama..."><?= htmlspecialchars($art['excerpt']) ?></textarea>
              </div>

              <div class="form-group">
                <label>Isi Artikel *</label>
                <textarea name="content" class="form-control" rows="14"
                          placeholder="Tulis isi artikel lengkap di sini. Pisahkan paragraf dengan satu baris kosong."><?= htmlspecialchars($art['content']) ?></textarea>
                <small style="color:var(--gray-400);font-size:0.76rem">Pisahkan paragraf dengan baris kosong. HTML dasar diizinkan.</small>
              </div>

            </div>
          </div>

          <!-- Gambar -->
          <div class="panel">
            <div class="panel-header"><h2>Gambar Artikel</h2></div>
            <div class="panel-body">
              <?php 
              $imgSrc = $art['img'] ?? ''; 
              $previewSrc = '';
              if ($imgSrc) {
                  $previewSrc = (str_starts_with($imgSrc, 'http://') || str_starts_with($imgSrc, 'https://')) ? $imgSrc : '../' . ltrim($imgSrc, '/');
              }
              ?>
              <div style="margin-bottom:12px">
                <img src="<?= htmlspecialchars($previewSrc ?: '../img/desa_wisata.png') ?>" id="imgPreview" class="img-preview" style="width:220px;height:140px;border-radius:8px;object-fit:cover;display:block;border:1px solid var(--gray-200)" onerror="this.src='../img/desa_wisata.png'">
              </div>

              <div class="form-group" style="margin-bottom:14px">
                <label>Pilih Gambar Cepat (Preset):</label>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px">
                  <button type="button" class="btn btn-sm btn-outline" onclick="selectPresetImg('img/desa_wisata.png')">Desa Wisata</button>
                  <button type="button" class="btn btn-sm btn-outline" onclick="selectPresetImg('img/g20_summit.png')">G20 Summit</button>
                  <button type="button" class="btn btn-sm btn-outline" onclick="selectPresetImg('img/ai_startup.png')">AI Startup</button>
                  <button type="button" class="btn btn-sm btn-outline" onclick="selectPresetImg('img/timnas_football.png')">Timnas</button>
                  <button type="button" class="btn btn-sm btn-outline" onclick="selectPresetImg('img/rupiah_exchange.png')">Ekonomi</button>
                  <button type="button" class="btn btn-sm btn-outline" onclick="selectPresetImg('img/pendidikan_sekolah.png')">Pendidikan</button>
                  <button type="button" class="btn btn-sm btn-outline" onclick="selectPresetImg('img/maraton_borobudur.png')">Olahraga</button>
                </div>
              </div>

              <div class="form-group" style="margin-bottom:10px">
                <label>Upload Gambar Baru (JPG, PNG, WebP)</label>
                <input type="file" name="img_file" class="form-control" accept="image/*" onchange="previewImg(this)">
              </div>
              <div class="form-group">
                <label>— ATAU — URL / Path Gambar</label>
                <input type="text" name="img_url_manual" id="imgUrlManual" class="form-control"
                       value="<?= htmlspecialchars($imgSrc) ?>"
                       placeholder="img/nama_file.png atau https://...">
                <small style="color:var(--gray-400);font-size:0.76rem">Gambar dari folder /img/ tersedia atau gunakan link URL gambar online.</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Meta -->
        <div>
          <div class="panel" style="margin-bottom:16px">
            <div class="panel-header"><h2>Meta Artikel</h2></div>
            <div class="panel-body">

              <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                  <option value="draft" <?= ($art['status']??'draft')==='draft'?'selected':'' ?>>Draft</option>
                  <option value="published" <?= ($art['status']??'')==='published'?'selected':'' ?>>Publish</option>
                </select>
              </div>

              <div class="form-group">
                <label>Tipe Konten (Rubrik)</label>
                <select name="type" class="form-control">
                  <?php foreach (['Berita Utama', 'Essay', 'Opini', 'Pendidikan', 'Olahraga', 'Ekonomi', 'Hikmah', 'Foto', 'Video'] as $t): ?>
                    <option value="<?= $t ?>" <?= (strcasecmp($art['type']??'', $t)===0)?'selected':'' ?>><?= $t ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label>Kategori Menu</label>
                <select name="cat" class="form-control">
                  <?php 
                  $catLabels = [
                    'berita'     => 'Berita Utama',
                    'essay'      => 'Essay',
                    'opini'      => 'Opini',
                    'pendidikan' => 'Pendidikan',
                    'olahraga'   => 'Olahraga',
                    'ekonomi'    => 'Ekonomi',
                    'hikmah'     => 'Hikmah',
                    'foto'       => 'Foto',
                    'video'      => 'Video'
                  ];
                  foreach ($catLabels as $cVal => $cLbl): ?>
                    <option value="<?= $cVal ?>" <?= ($art['cat']??'')===$cVal?'selected':'' ?>><?= $cLbl ?> (<?= $cVal ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label>Badge Label</label>
                <select name="badge" class="form-control">
                  <?php foreach (['berita','essay','opini','pendidikan','olahraga','ekonomi','hikmah','investigasi','foto','video'] as $b): ?>
                    <option value="<?= $b ?>" <?= ($art['badge']??'')===$b?'selected':'' ?>><?= ucfirst($b) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label>Nama Penulis</label>
                <input type="text" name="author" class="form-control"
                       value="<?= htmlspecialchars($art['author'] ?? '') ?>"
                       placeholder="Nama penulis / desk">
              </div>

              <div class="form-group">
                <label>Tanggal Tampil</label>
                <input type="text" name="date_display" class="form-control"
                       value="<?= htmlspecialchars($art['date_display'] ?? date('d F Y')) ?>"
                       placeholder="28 Juni 2025">
              </div>

              <div class="form-group">
                <label>Views (display)</label>
                <input type="text" name="views" class="form-control"
                       value="<?= htmlspecialchars($art['views'] ?? '0') ?>"
                       placeholder="45.2K">
              </div>

              <div class="form-group">
                <label>Tags (pisah dengan koma)</label>
                <input type="text" name="tags" class="form-control"
                       value="<?= htmlspecialchars($tagsStr) ?>"
                       placeholder="Olahraga, Timnas, Sepak Bola">
              </div>

            </div>
          </div>

          <!-- Action buttons -->
          <div class="panel">
            <div class="panel-body" style="display:flex;flex-direction:column;gap:10px">
              <button type="submit" name="status_override" value="published"
                      onclick="document.querySelector('[name=status]').value='published'"
                      class="btn btn-primary" style="width:100%;justify-content:center;padding:11px">
                Simpan & Publish
              </button>
              <button type="submit"
                      onclick="document.querySelector('[name=status]').value='draft'"
                      class="btn btn-secondary" style="width:100%;justify-content:center">
                Simpan sebagai Draft
              </button>
              <?php if (!empty($art['id'])): ?>
              <a href="../#article-<?= $art['id'] ?>" target="_blank" class="btn btn-outline" style="width:100%;justify-content:center">
                Lihat di Website
              </a>
              <hr style="border-color:var(--gray-200)">
              <button type="button"
                      onclick="confirmDelete(<?= $art['id'] ?>, '<?= htmlspecialchars(addslashes(mb_strimwidth($art['title'],0,40,'...'))) ?>')"
                      class="btn btn-danger" style="width:100%;justify-content:center">
                Hapus Artikel
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div>
    </form>

    <?php endif; ?>

  </div><!-- /page-body -->
</div><!-- /main-content -->

<script>
function previewImg(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const prev = document.getElementById('imgPreview');
      if (prev.tagName === 'IMG') {
        prev.src = e.target.result;
      } else {
        const img = document.createElement('img');
        img.src = e.target.result;
        img.id = 'imgPreview';
        img.className = 'img-preview';
        img.style.cssText = 'width:200px;height:130px;margin-bottom:12px;border-radius:8px;object-fit:cover';
        prev.replaceWith(img);
      }
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// Sync manual URL input to hidden field
const manualUrl = document.getElementById('imgUrlManual');
const currentField = document.getElementById('imgCurrentField');
if (manualUrl && currentField) {
  manualUrl.addEventListener('input', () => {
    currentField.value = manualUrl.value;
    const preview = document.getElementById('imgPreview');
    if (preview && manualUrl.value.trim()) {
      const src = manualUrl.value.trim();
      preview.src = (src.startsWith('http://') || src.startsWith('https://')) ? src : '../' + src.replace(/^\/+/, '');
    }
  });
}

function selectPresetImg(path) {
  const manual = document.getElementById('imgUrlManual');
  const current = document.getElementById('imgCurrentField');
  const preview = document.getElementById('imgPreview');
  if (manual) manual.value = path;
  if (current) current.value = path;
  if (preview) preview.src = '../' + path;
}

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
