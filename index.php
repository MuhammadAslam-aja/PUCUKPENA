<?php
// index.php — Pucuk Pena Dinamis (PHP Native + MySQL)
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/includes/db.php';

try {
    $db = getDB();
    // 1. Ambil artikel published (Urutkan dari ID terbesar / terbaru)
    $stmtArticles = $db->query("SELECT * FROM articles WHERE status='published' ORDER BY id DESC");
    $articlesRows = $stmtArticles->fetchAll(PDO::FETCH_ASSOC);
    $articlesData = [];
    foreach ($articlesRows as $r) {
        $tags = json_decode($r['tags'] ?? '[]', true);
        if (!is_array($tags)) $tags = [];
        $articlesData[] = [
            'id'      => (int)$r['id'],
            'type'    => $r['type'],
            'cat'     => $r['cat'],
            'badge'   => $r['badge'],
            'title'   => $r['title'],
            'excerpt' => $r['excerpt'],
            'content' => $r['content'],
            'author'  => $r['author'],
            'date'    => $r['date_display'],
            'views'   => $r['views'],
            'img'     => $r['img'],
            'tags'    => $tags,
            'related_article_id' => !empty($r['related_article_id']) ? (int)$r['related_article_id'] : null,
        ];
    }

    // 2. Ambil iklan aktif
    $adsRows = $db->query("SELECT * FROM ads WHERE active=1")->fetchAll(PDO::FETCH_ASSOC);
    $adsData = [];
    foreach ($adsRows as $ad) {
        $adsData[$ad['slot']] = $ad;
    }

    // 3. Ambil breaking news aktif
    $breakingRows = $db->query("SELECT text FROM breaking_news WHERE active=1 ORDER BY sort_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $breakingData = array_column($breakingRows, 'text');

    // 4. Ambil pengaturan website, footer, sosmed, & modal info
    $settingsRows = $db->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $siteSettings = $settingsRows ?: [];

} catch (Exception $e) {
    $articlesData = [];
    $adsData = [];
    $breakingData = [];
    $siteSettings = [];
}

// Deteksi artikel untuk Open Graph metadata (WhatsApp, Telegram, Facebook, Twitter preview)
$requestedArticleId = (int)($_GET['id'] ?? ($_GET['article'] ?? 0));
$ogArticle = null;
if ($requestedArticleId > 0) {
    foreach ($articlesData as $art) {
        if ($art['id'] === $requestedArticleId) {
            $ogArticle = $art;
            break;
        }
    }
    if (!$ogArticle && isset($db)) {
        try {
            $stmtOg = $db->prepare("SELECT * FROM articles WHERE id = ? LIMIT 1");
            $stmtOg->execute([$requestedArticleId]);
            $ogRow = $stmtOg->fetch(PDO::FETCH_ASSOC);
            if ($ogRow) {
                $ogArticle = [
                    'id'      => (int)$ogRow['id'],
                    'title'   => $ogRow['title'],
                    'excerpt' => $ogRow['excerpt'],
                    'content' => $ogRow['content'],
                    'img'     => $ogRow['img'],
                    'author'  => $ogRow['author'],
                ];
            }
        } catch (Exception $e) {}
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'pucukpena.up.railway.app';
$baseUrl = rtrim($protocol . $host, '/');

if ($ogArticle) {
    $metaTitle = htmlspecialchars($ogArticle['title']) . ' — Pucuk Pena';
    $rawExcerpt = !empty($ogArticle['excerpt']) ? $ogArticle['excerpt'] : mb_substr(strip_tags($ogArticle['content']), 0, 160);
    $metaDesc  = htmlspecialchars(trim(preg_replace('/\s+/', ' ', $rawExcerpt)));
    $rawImg    = !empty($ogArticle['img']) ? $ogArticle['img'] : 'img/desa_wisata.png';
    $metaImg   = (str_starts_with($rawImg, 'http://') || str_starts_with($rawImg, 'https://')) ? $rawImg : $baseUrl . '/' . ltrim($rawImg, '/');
    $metaUrl   = $baseUrl . '/?id=' . $ogArticle['id'];
    $metaType  = 'article';
} else {
    $metaTitle = 'Pucuk Pena | Berita Terkini, Opini, Essay & Artikel Terpercaya';
    $metaDesc  = 'Pucuk Pena menghadirkan jurnalisme independen, berita faktual, opini kritis, dan artikel mendalam untuk masyarakat Indonesia.';
    $metaImg   = $baseUrl . '/img/PUCUK%20PENA.png';
    $metaUrl   = $baseUrl . '/';
    $metaType  = 'website';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= $metaDesc ?>">
  <title><?= $metaTitle ?></title>

  <!-- Open Graph / WhatsApp / Facebook / Telegram Link Preview -->
  <meta property="og:site_name" content="Pucuk Pena">
  <meta property="og:type" content="<?= $metaType ?>">
  <meta property="og:url" content="<?= $metaUrl ?>">
  <meta property="og:title" content="<?= $metaTitle ?>">
  <meta property="og:description" content="<?= $metaDesc ?>">
  <meta property="og:image" content="<?= $metaImg ?>">
  <meta property="og:image:secure_url" content="<?= $metaImg ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">

  <!-- Twitter / X Cards -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= $metaUrl ?>">
  <meta name="twitter:title" content="<?= $metaTitle ?>">
  <meta name="twitter:description" content="<?= $metaDesc ?>">
  <meta name="twitter:image" content="<?= $metaImg ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
  <style>
    :root {
      --green-900:#064e3b; --green-800:#065f46; --green-700:#047857; --green-600:#059669;
      --green-500:#10b981; --green-400:#34d399; --green-100:#d1fae5; --green-50:#ecfdf5;
      --red:#dc2626; --gold:#f59e0b; --white:#fff; --gray-50:#f9fafb; --gray-100:#f3f4f6;
      --gray-200:#e5e7eb; --gray-400:#9ca3af; --gray-600:#4b5563; --gray-800:#1f2937;
      --shadow:0 4px 24px rgba(6,78,59,.12); --shadow-lg:0 20px 50px rgba(6,78,59,.18);
      --radius:14px; --tr:.35s cubic-bezier(.4,0,.2,1);
      
      /* Theme Semantic variables */
      --bg-body: var(--gray-50);
      --bg-card: var(--white);
      --text-main: var(--gray-800);
      --text-muted: var(--gray-600);
      --border-color: var(--gray-200);
      --header-bg: rgba(255, 255, 255, 0.85);
    }
    
    body.dark-theme {
      --gray-50:#0b0f19;
      --gray-100:#1e293b;
      --gray-200:#334155;
      --gray-400:#94a3b8;
      --gray-600:#cbd5e1;
      --gray-800:#f8fafc;
      --white:#151f32;
      --shadow:0 4px 24px rgba(0,0,0,.4); --shadow-lg:0 20px 50px rgba(0,0,0,.6);
      
      --bg-body: #0b0f19;
      --bg-card: #151f32;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --border-color: #334155;
      --header-bg: rgba(21, 31, 50, 0.85);
    }

    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth;background-color:#011510}
    body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:var(--text-main);background:var(--bg-body);line-height:1.6;overflow-x:hidden;min-height:100vh;margin:0;padding:0;transition:var(--tr)}
    h1,h2,h3,h4,h5,h6{font-family:'Lora',Georgia,serif;font-weight:700}
    a{text-decoration:none;color:inherit} img{max-width:100%;display:block} ul{list-style:none}
    .container{width:min(1280px,94%);margin:0 auto}
    .container-narrow{width:min(900px,94%);margin:0 auto}

    /* Stock Ticker Desktop */
    .ticker-bar{background:#022c22;color:#a7f3d0;font-size:.72rem;padding:6px 0;overflow:hidden;white-space:nowrap;display:flex;border-bottom:1px solid rgba(255,255,255,.05);z-index:1001;position:relative}
    .ticker-content{display:inline-flex;animation:ticker-anim 25s linear infinite;gap:45px;padding-left:100%}
    .ticker-content span{display:flex;align-items:center;gap:6px;font-weight:600}
    @keyframes ticker-anim{0%{transform:translate3d(0,0,0)}100%{transform:translate3d(-100%,0,0)}}
    @media(max-width:768px){.ticker-bar{display:none}}

    /* Breaking */
    .breaking{background:var(--red);color:#fff;font-size:.82rem;overflow:hidden}
    .breaking-inner{display:flex;align-items:center;gap:12px;padding:8px 0}
    .breaking-label{background:#fff;color:var(--red);font-weight:800;padding:4px 12px;border-radius:4px;white-space:nowrap;font-size:.75rem;flex-shrink:0}
    .breaking-track{overflow:hidden;flex:1}
    .breaking-marquee{display:flex;animation:marquee 40s linear infinite;white-space:nowrap}
    .breaking-marquee span{padding-right:60px;opacity:.95}
    @keyframes marquee{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}

    /* Topbar */
    .topbar{background:var(--green-900);color:rgba(255,255,255,.85);font-size:.78rem;padding:6px 0}
    .topbar .container{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px}
    .topbar-date{font-weight:600;color:var(--green-400)}

    /* Header */
    .header{background:var(--header-bg);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);position:sticky;top:0;z-index:1000;box-shadow:0 2px 16px rgba(0,0,0,.04);transition:var(--tr);border-bottom:1px solid var(--border-color)}
    .header.scrolled{box-shadow:var(--shadow)}
    .header-main{display:flex;align-items:center;justify-content:space-between;padding:12px 0;gap:16px;border-bottom:1px solid var(--border-color)}
    .logo{display:flex;align-items:center;gap:12px}
    .logo-img{height:46px;width:auto;object-fit:contain;transition:var(--tr)}
    body.dark-theme .logo-img, .footer .logo-img{filter:brightness(0) invert(1)}
    .logo:hover .logo-img{transform:scale(1.05)}
    .logo-mark{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,var(--green-600),var(--green-400));display:grid;place-items:center;color:#fff;font-weight:900;font-size:1.3rem;box-shadow:0 4px 20px rgba(16, 185, 129, 0.3);position:relative;overflow:hidden}
    .logo-mark::after{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:linear-gradient(45deg,transparent,rgba(255,255,255,0.3),transparent);transform:rotate(45deg);animation:logo-shine 3.5s infinite}
    @keyframes logo-shine{0%{transform:translate(-50%,-50%) rotate(45deg)}100%{transform:translate(50%,50%) rotate(45deg)}}
    .logo-text h1{font-size:1.65rem;color:var(--green-900);font-weight:800;letter-spacing:-.5px;line-height:1}
    body.dark-theme .logo-text h1{color:var(--white)}
    .logo-text h1 span{background:linear-gradient(135deg,var(--green-500),var(--green-400));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .logo-text p{font-size:.72rem;color:var(--text-muted);margin-top:4px}
    .header-actions{display:flex;align-items:center;gap:10px}
    .btn-icon{width:40px;height:40px;border:none;border-radius:10px;background:var(--gray-100);cursor:pointer;display:grid;place-items:center;transition:var(--tr);border:1px solid var(--border-color)}
    .btn-icon svg{stroke:var(--gray-600);fill:none;transition:var(--tr)}
    body.dark-theme .btn-icon{background:rgba(255,255,255,0.05)}
    body.dark-theme .btn-icon svg{stroke:var(--gray-400)}
    .btn-icon:hover{background:var(--green-50);border-color:var(--green-500)}
    .btn-icon:hover svg{stroke:var(--green-600)}
    body.dark-theme .btn-icon:hover{background:rgba(16,185,129,0.1);border-color:var(--green-500)}
    body.dark-theme .btn-icon:hover svg{stroke:var(--green-400)}
    .btn-subscribe{padding:10px 18px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--green-600),var(--green-400));color:#fff;font-weight:600;font-size:.85rem;cursor:pointer;transition:var(--tr);box-shadow:0 4px 14px rgba(5,150,105,.25)}
    .btn-subscribe:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(5,150,105,.4)}
    .hamburger{display:none;flex-direction:column;gap:5px;background:none;border:none;cursor:pointer;padding:8px}
    .hamburger span{width:24px;height:2px;background:var(--text-main);border-radius:2px;transition:var(--tr)}
    .hamburger.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px)}
    .hamburger.active span:nth-child(2){opacity:0}
    .hamburger.active span:nth-child(3){transform:rotate(-45deg) translate(5px,-5px)}
 
    /* Category Nav */
    .cat-nav{background:linear-gradient(90deg,var(--green-900),var(--green-800));overflow-x:auto;scrollbar-width:none}
    .cat-nav::-webkit-scrollbar{display:none}
    .cat-nav ul{display:flex;gap:2px;padding:0;min-width:max-content}
    .cat-nav a{display:block;padding:12px 20px;color:rgba(255,255,255,.88);font-size:.86rem;font-weight:600;transition:var(--tr);white-space:nowrap}
    .cat-nav a svg{stroke:rgba(255,255,255,0.7);fill:none;transition:var(--tr)}
    .cat-nav a:hover,.cat-nav a.active{background:var(--green-600);color:#fff}
    .cat-nav a:hover svg,.cat-nav a.active svg{stroke:#fff}

    /* Instagram-Style Stories Mobile */
    .stories-container{display:none;gap:14px;overflow-x:auto;padding:12px 0;background:transparent;scrollbar-width:none;margin-bottom:12px}
    .stories-container::-webkit-scrollbar{display:none}
    .story-circle{display:flex;flex-direction:column;align-items:center;cursor:pointer;flex-shrink:0;width:70px}
    .story-img-wrap{width:56px;height:56px;border-radius:50%;padding:2px;background:linear-gradient(45deg,var(--gold),var(--green-500),#8b5cf6);display:grid;place-items:center;margin-bottom:5px;transition:var(--tr)}
    .story-circle:hover .story-img-wrap{transform:scale(1.05)}
    .story-img-wrap img{width:100%;height:100%;border-radius:50%;object-fit:cover;border:2px solid var(--bg-card)}
    .story-label{font-size:.65rem;text-align:center;color:var(--text-main);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%;font-weight:600}

    /* Ads */
    .ad-wrap{margin:20px auto;text-align:center;position:relative}
    .ad-label{font-size:.65rem;color:var(--gray-400);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;display:flex;justify-content:center;align-items:center;gap:6px}
    .ad-opt{font-size:.6rem;color:var(--green-600);cursor:pointer;text-transform:none;letter-spacing:0}
    .ad-opt:hover{text-decoration:underline}
    .ad-banner{
      background:linear-gradient(135deg,var(--gray-100),var(--gray-200));border:2px dashed var(--gray-400);
      border-radius:var(--radius);display:flex;align-items:center;justify-content:center;position:relative;
      overflow:hidden;cursor:pointer;transition:var(--tr);margin:0 auto;color:var(--text-main);
    }
    body.dark-theme .ad-banner{background:linear-gradient(135deg,var(--gray-100),#1e293b);border-color:var(--border-color)}
    .ad-banner:hover{border-color:var(--green-500);box-shadow:var(--shadow)}
    .ad-banner::before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(16,185,129,.08),transparent);animation:shimmer 3s infinite}
    @keyframes shimmer{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}
    .ad-leaderboard{width:100%;max-width:970px;height:90px;font-size:.9rem}
    .ad-rectangle{width:100%;max-width:300px;height:250px}
    .ad-native{background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:16px;display:flex;gap:14px;align-items:center;text-align:left;max-width:none;height:auto}
    .ad-native .ad-thumb{width:80px;height:80px;border-radius:10px;background:linear-gradient(135deg,var(--green-600),var(--green-400));flex-shrink:0;display:grid;place-items:center;font-size:1.8rem}
    .ad-native h4{font-size:.9rem;color:var(--text-main);margin-bottom:4px}
    .ad-native p{font-size:.78rem;color:var(--text-muted)}
    .ad-native .ad-cta{display:inline-block;margin-top:8px;padding:6px 14px;background:var(--green-600);color:#fff;border-radius:6px;font-size:.75rem;font-weight:600}
    .ad-sticky-bottom{
      position:fixed;bottom:0;left:0;right:0;z-index:900;background:var(--bg-card);border-top:1px solid var(--border-color);
      padding:8px;transform:translateY(100%);transition:transform .4s ease;box-shadow:0 -4px 20px rgba(0,0,0,.1);
      display:flex;justify-content:center;align-items:center
    }
    .ad-sticky-bottom.show{transform:none}
    .ad-sticky-bottom .ad-banner{height:60px;max-width:728px}
    .ad-close{position:absolute;top:4px;right:8px;background:var(--gray-800);color:#fff;border:none;width:22px;height:22px;border-radius:50%;cursor:pointer;font-size:.7rem;z-index:2;display:grid;place-items:center}

    /* Hero News */
    .hero-news{padding:24px 0 16px}
    .hero-grid{display:grid;grid-template-columns:2fr 1fr;gap:16px}
    .hero-main{position:relative;border-radius:var(--radius);overflow:hidden;cursor:pointer;min-height:420px}
    .hero-main img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;transition:transform .6s ease}
    .hero-main:hover img{transform:scale(1.05)}
    .hero-main-overlay{position:absolute;inset:0;background:linear-gradient(transparent 30%,rgba(6,78,59,.95));display:flex;flex-direction:column;justify-content:flex-end;padding:28px}
    .hero-side{display:flex;flex-direction:column;gap:16px}
    .hero-sub{position:relative;border-radius:var(--radius);overflow:hidden;cursor:pointer;flex:1;min-height:130px}
    .hero-sub img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;transition:transform .5s ease}
    .hero-sub:hover img{transform:scale(1.08)}
    .hero-sub-overlay{position:absolute;inset:0;background:linear-gradient(transparent,rgba(6,78,59,.88));display:flex;flex-direction:column;justify-content:flex-end;padding:16px}
    .badge{display:inline-block;padding:4px 10px;border-radius:6px;font-size:.7rem;font-weight:700;text-transform:uppercase;margin-bottom:8px;width:fit-content}
    .badge-berita{background:var(--green-500);color:#fff}
    .badge-opini{background:var(--gold);color:#fff}
    .badge-essay{background:#8b5cf6;color:#fff}
    .badge-pendidikan{background:#3b82f6;color:#fff}
    .badge-olahraga{background:#059669;color:#fff}
    .badge-ekonomi{background:#0891b2;color:#fff}
    .badge-hikmah{background:#ec4899;color:#fff}
    .badge-foto{background:#64748b;color:#fff}
    .badge-investigasi{background:var(--red);color:#fff}
    .hero-main h2{color:#fff;font-size:clamp(1.3rem,2.5vw,1.8rem);line-height:1.25;margin-bottom:8px}
    .hero-sub h3{color:#fff;font-size:.92rem;line-height:1.35}
    .meta{color:rgba(255,255,255,.75);font-size:.75rem;display:flex;gap:12px;flex-wrap:wrap;margin-top:6px}

    /* Layout */
    .main-layout{display:grid;grid-template-columns:260px 1fr 300px;gap:28px;padding:20px 0 40px;align-items:start}
    .main-layout > main{min-width:0} /* FIX FOR CSS GRID OVERFLOW IN SIDEBAR */

    /* Left Column (Terbaru) */
    .latest-column {
      position: sticky;
      top: 130px;
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius);
      padding: 20px;
      box-shadow: 0 2px 12px rgba(0,0,0,.04);
      transition: var(--tr);
    }
    .latest-column h4 {
      font-size: 1.05rem;
      color: var(--green-900);
      margin-bottom: 8px;
      font-weight: 700;
    }
    body.dark-theme .latest-column h4 {
      color: var(--green-400);
    }
    .latest-column .divider-editorial {
      height: 3px;
      background: var(--green-600);
      margin-bottom: 16px;
      border-radius: 2px;
    }
    .latest-list {
      display: flex;
      flex-direction: column;
    }
    .latest-card {
      display: flex;
      gap: 12px;
      padding: 12px 0;
      border-bottom: 1px solid var(--border-color);
      cursor: pointer;
      transition: var(--tr);
    }
    .latest-card:last-child {
      border: none;
    }
    .latest-card:hover {
      transform: translateX(4px);
    }
    .latest-card img {
      width: 64px;
      height: 64px;
      object-fit: cover;
      border-radius: 8px;
      flex-shrink: 0;
    }
    .latest-card .lc-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-width: 0;
    }
    .latest-card h3 {
      font-size: 0.8rem;
      line-height: 1.3;
      color: var(--text-main);
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      margin: 0;
      font-weight: 600;
    }
    .latest-card .lc-meta {
      font-size: 0.65rem;
      color: var(--gray-400);
      display: flex;
      justify-content: space-between;
      margin-top: 4px;
      flex-wrap: wrap;
      gap: 4px;
    }

    /* Editorial Headline (Berita Utama) */
    .editorial-headline {
      position: relative;
      border-radius: var(--radius);
      overflow: hidden;
      height: 380px;
      cursor: pointer;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      margin-bottom: 20px;
    }
    .editorial-headline img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.6s ease;
    }
    .editorial-headline:hover img {
      transform: scale(1.03);
    }
    .editorial-headline .eh-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.2) 60%, transparent 100%);
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 24px;
      color: #fff;
      box-sizing: border-box;
    }
    .editorial-headline h2 {
      font-size: 1.65rem;
      line-height: 1.3;
      margin: 10px 0;
      font-weight: 700;
      color: #fff;
    }
    .editorial-headline .eh-meta {
      display: flex;
      gap: 15px;
      font-size: 0.75rem;
      opacity: 0.9;
    }

    /* Editorial Subgrid */
    .editorial-subgrid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }
    .editorial-subcard {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(0,0,0,.04);
      cursor: pointer;
      transition: var(--tr);
    }
    .editorial-subcard:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow);
    }
    .editorial-subcard img {
      width: 100%;
      height: 160px;
      object-fit: cover;
    }
    .editorial-subcard .esc-body {
      padding: 16px;
    }
    .editorial-subcard h3 {
      font-size: 0.95rem;
      line-height: 1.4;
      margin: 8px 0;
      color: var(--text-main);
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .editorial-subcard .esc-meta {
      display: flex;
      justify-content: space-between;
      font-size: 0.7rem;
      color: var(--gray-400);
      margin-top: 8px;
    }

    .section-block{margin-bottom:36px}
    .section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;padding-bottom:10px;border-bottom:3px solid var(--green-600)}
    .section-head h2{font-size:1.15rem;color:var(--green-900);display:flex;align-items:center;gap:8px}
    body.dark-theme .section-head h2{color:var(--green-400)}
    .section-head h2::before{content:'';width:4px;height:22px;background:var(--green-500);border-radius:2px}
    .section-head a{font-size:.82rem;color:var(--green-600);font-weight:600}
    .section-head a:hover{text-decoration:underline}

    /* Filter tabs */
    .filter-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px}
    .filter-tab{padding:7px 16px;border:2px solid var(--border-color);border-radius:50px;background:var(--bg-card);cursor:pointer;font-size:.82rem;font-weight:600;color:var(--text-muted);transition:var(--tr)}
    .filter-tab.active,.filter-tab:hover{border-color:var(--green-500);color:var(--green-700);background:var(--green-50)}
    body.dark-theme .filter-tab.active,body.dark-theme .filter-tab:hover{color:var(--green-400);background:#064e3b}

    /* Article cards */
    .article-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
    .grid-4-cols{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
    .article-card{background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius);overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.04);cursor:pointer;transition:var(--tr);opacity:1;transform:none}
    .article-card.visible{opacity:1;transform:none}
    .article-card:hover{box-shadow:var(--shadow);transform:translateY(-4px)}
    .article-card.wide{grid-column:1/-1;display:grid;grid-template-columns:1.2fr 1fr}
    .article-card img{width:100%;height:180px;object-fit:cover;transition:transform .5s ease}
    .article-card.wide img{height:100%;min-height:200px}
    .article-card:hover img{transform:scale(1.04)}
    .article-body{padding:16px 18px;position:relative}
    .article-body h3{font-size:.98rem;line-height:1.4;color:var(--text-main);margin:8px 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .article-body p{font-size:.82rem;color:var(--text-muted);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:10px}
    .article-meta{display:flex;gap:10px;font-size:.72rem;color:var(--gray-400);flex-wrap:wrap;align-items:center}
    .article-meta .author{color:var(--green-700);font-weight:600}
    body.dark-theme .article-meta .author{color:var(--green-400)}

    /* Horizontal scroll */
    .scroll-row{display:flex;gap:14px;overflow-x:auto;padding-bottom:8px;scrollbar-width:thin}
    .scroll-card{min-width:240px;max-width:240px;background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.05);cursor:pointer;flex-shrink:0;transition:var(--tr)}
    .scroll-card:hover{transform:translateY(-4px);box-shadow:var(--shadow)}
    .scroll-card img{width:100%;height:140px;object-fit:cover}
    .scroll-card .sc-body{padding:12px}
    .scroll-card h4{font-size:.85rem;line-height:1.35;margin:6px 0}

    /* Sidebar */
    .sidebar{position:sticky;top:130px;display:flex;flex-direction:column;gap:24px}
    .widget{background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius);padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
    .widget h3{font-size:.95rem;color:var(--green-900);margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid var(--green-100)}
    body.dark-theme .widget h3{color:var(--green-400);border-bottom-color:var(--border-color)}
    .trend-item{display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-color);cursor:pointer;transition:var(--tr)}
    .trend-item:last-child{border:none}
    .trend-item:hover{padding-left:4px}
    .trend-num{font-size:1.4rem;font-weight:800;color:var(--green-200,#a7f3d0);line-height:1;min-width:28px}
    .trend-item h4{font-size:.85rem;line-height:1.35;color:var(--text-main)}
    .trend-item span{font-size:.72rem;color:var(--gray-400)}
    .tag-cloud{display:flex;flex-wrap:wrap;gap:8px}
    .tag-cloud a{padding:5px 12px;background:var(--gray-100);color:var(--green-700);border-radius:50px;font-size:.75rem;font-weight:500;transition:var(--tr);border:1px solid var(--border-color)}
    body.dark-theme .tag-cloud a{color:var(--green-400)}
    .tag-cloud a:hover{background:var(--green-600);color:#fff}
    .newsletter input{width:100%;padding:10px 14px;border:2px solid var(--border-color);border-radius:8px;margin-bottom:10px;font-family:inherit;font-size:.85rem;background:var(--bg-body);color:var(--text-main)}
    .newsletter input:focus{outline:none;border-color:var(--green-500)}
    .newsletter button{width:100%;padding:10px;border:none;border-radius:8px;background:var(--green-700);color:#fff;font-weight:600;cursor:pointer;transition:var(--tr)}
    .newsletter button:hover{background:var(--green-600)}

    /* Opinion / Essay blocks */
    .opinion-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
    .opinion-card{background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius);padding:22px;border-top:4px solid var(--gold);box-shadow:0 2px 12px rgba(0,0,0,.04);cursor:pointer;transition:var(--tr)}
    .opinion-card.essay{border-top-color:#8b5cf6}
    .opinion-card.artikel{border-top-color:#3b82f6}
    .opinion-card:hover{box-shadow:var(--shadow);transform:translateY(-3px)}
    .opinion-card .quote{font-size:2rem;color:var(--green-200);line-height:1;margin-bottom:8px}
    .opinion-card h3{font-size:.95rem;line-height:1.4;margin-bottom:10px;color:var(--text-main)}
    .opinion-author{display:flex;align-items:center;gap:10px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border-color)}
    .avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--green-600),var(--green-400));display:grid;place-items:center;color:#fff;font-weight:700;font-size:.8rem}

    /* Video & Photo sections */
    .video-grid, .photo-grid{display:grid;grid-template-columns:1.5fr 1fr;gap:16px}
    .video-main, .photo-main{position:relative;border-radius:var(--radius);overflow:hidden;cursor:pointer;min-height:280px;background:var(--green-900)}
    .video-main img, .photo-main img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;opacity:.7;transition:var(--tr)}
    .video-main:hover img, .photo-main:hover img{transform:scale(1.03)}
    .play-btn, .cam-btn{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:64px;height:64px;background:rgba(255,255,255,.9);border-radius:50%;display:grid;place-items:center;font-size:1.4rem;color:var(--green-700);box-shadow:var(--shadow-lg);transition:var(--tr);z-index:2}
    .video-main:hover .play-btn, .photo-main:hover .cam-btn{transform:translate(-50%,-50%) scale(1.1)}
    .video-info, .photo-info{position:absolute;bottom:0;left:0;right:0;padding:20px;background:linear-gradient(transparent,rgba(0,0,0,.85));color:#fff;z-index:1}
    .video-list, .photo-list{display:flex;flex-direction:column;gap:12px}
    .video-item, .photo-item{display:flex;gap:12px;background:var(--bg-card);border:1px solid var(--border-color);border-radius:10px;padding:10px;cursor:pointer;transition:var(--tr)}
    .video-item:hover, .photo-item:hover{box-shadow:var(--shadow)}
    .video-thumb, .photo-thumb{width:100px;height:70px;border-radius:8px;object-fit:cover;flex-shrink:0;position:relative}
    .video-thumb-wrap, .photo-thumb-wrap{position:relative;flex-shrink:0}
    .video-thumb-wrap::after, .photo-thumb-wrap::after{position:absolute;inset:0;display:grid;place-items:center;background:rgba(6,78,59,.5);border-radius:8px;color:#fff;font-size:.7rem}
    .video-thumb-wrap::after{content:'▶'}
    .photo-thumb-wrap::after{content:'📷'}

    /* Stats bar */
    .stats-bar{background:var(--green-900);padding:28px 0;margin:20px 0}
    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;text-align:center}
    .stats-grid .num{font-size:2rem;font-weight:800;color:var(--green-400)}
    .stats-grid .lbl{font-size:.78rem;color:rgba(255,255,255,.7);margin-top:4px}

    /* Modal Article */
    .modal-overlay{position:fixed;inset:0;background:rgba(6,78,59,.8);backdrop-filter:blur(6px);z-index:2000;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px;overflow-y:auto;opacity:0;visibility:hidden;transition:var(--tr)}
    body.dark-theme .modal-overlay{background:rgba(11,15,25,.85)}
    .modal-overlay.active{opacity:1;visibility:visible}
    .modal-article{background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius);width:min(820px,100%);transform:translateY(30px);transition:var(--tr);margin:0 auto 40px;color:var(--text-main)}
    .modal-overlay.active .modal-article{transform:none}
    .modal-article img{width:100%;max-height:400px;object-fit:cover;border-radius:var(--radius) var(--radius) 0 0}
    .modal-content{padding:32px}
    .modal-content h1{font-size:clamp(1.3rem,3vw,1.8rem);color:var(--green-900);line-height:1.3;margin:12px 0}
    body.dark-theme .modal-content h1{color:var(--green-400)}
    .modal-content .lead{font-size:1.05rem;color:var(--text-muted);margin-bottom:20px;line-height:1.7;border-left:4px solid var(--green-500);padding-left:16px;text-align:justify}
    .modal-content .body-text{font-size:.95rem;color:var(--text-main);line-height:1.85;text-align:justify}
    .modal-content .body-text p{margin-bottom:16px}
    /* ===== KARTU BACA JUGA (IN-ARTICLE RECOMMENDATION) ===== */
    .baca-juga-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-left: 5px solid var(--green-600);
      border-radius: 12px;
      padding: 16px 18px;
      margin: 28px 0;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
      cursor: pointer;
      display: block;
      text-decoration: none;
      position: relative;
      max-width: 100%;
      box-sizing: border-box;
    }
    .baca-juga-card:hover {
      transform: translateY(-2px);
      border-color: var(--green-500);
      border-left-color: var(--green-600);
      box-shadow: 0 8px 24px rgba(6, 78, 59, 0.12);
    }
    body.dark-theme .baca-juga-card {
      background: #1e293b;
      border-color: #334155;
      border-left-color: var(--green-500);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
    }
    body.dark-theme .baca-juga-card:hover {
      border-color: var(--green-400);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    }
    .baca-juga-top {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 10px;
      flex-wrap: wrap;
    }
    .baca-juga-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: var(--green-100);
      color: var(--green-800);
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      padding: 3px 10px;
      border-radius: 20px;
      flex-shrink: 0;
    }
    body.dark-theme .baca-juga-badge {
      background: rgba(16, 185, 129, 0.2);
      color: var(--green-400);
    }
    .baca-juga-rubrik {
      font-size: 0.76rem;
      font-weight: 600;
      color: var(--text-muted);
    }
    .baca-juga-body {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      min-width: 0;
    }
    .baca-juga-content {
      flex: 1;
      min-width: 0;
      overflow: hidden;
    }
    .baca-juga-title {
      font-size: 1.02rem;
      font-weight: 700;
      line-height: 1.42;
      color: var(--text-main);
      margin: 0 0 6px 0;
      display: block;
      text-decoration: none;
      word-break: break-word;
      overflow-wrap: break-word;
      transition: color 0.15s ease;
    }
    .baca-juga-card:hover .baca-juga-title {
      color: var(--green-600);
    }
    body.dark-theme .baca-juga-card:hover .baca-juga-title {
      color: var(--green-400);
    }
    .baca-juga-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      font-size: 0.76rem;
      color: var(--text-muted);
      margin-top: 8px;
      flex-wrap: wrap;
    }
    .baca-juga-cta {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-weight: 700;
      color: var(--green-700);
      font-size: 0.78rem;
      transition: gap 0.2s ease;
      white-space: nowrap;
    }
    body.dark-theme .baca-juga-cta {
      color: var(--green-400);
    }
    .baca-juga-card:hover .baca-juga-cta {
      gap: 8px;
    }
    .baca-juga-thumb {
      width: 96px;
      height: 74px;
      border-radius: 8px;
      object-fit: cover;
      flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .article-modal-inner {
      padding: 28px;
      box-sizing: border-box;
    }
    @media (max-width: 768px) {
      .article-modal-inner {
        padding: 20px 16px !important;
      }
    }
    @media (max-width: 640px) {
      .article-modal-inner {
        padding: 16px 14px !important;
      }
      .baca-juga-card {
        padding: 12px 14px !important;
        margin: 20px 0 !important;
        border-radius: 10px !important;
        border-left-width: 4px !important;
      }
      .baca-juga-top {
        margin-bottom: 8px !important;
        gap: 6px !important;
      }
      .baca-juga-badge {
        font-size: 0.68rem !important;
        padding: 2px 8px !important;
      }
      .baca-juga-rubrik {
        font-size: 0.72rem !important;
      }
      .baca-juga-body {
        gap: 12px !important;
        align-items: flex-start !important;
      }
      .baca-juga-thumb {
        width: 72px !important;
        height: 72px !important;
        min-width: 72px !important;
        max-width: 72px !important;
        border-radius: 8px !important;
      }
      .baca-juga-title {
        font-size: 0.90rem !important;
        line-height: 1.38 !important;
        display: block !important;
        -webkit-line-clamp: unset !important;
        overflow: visible !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        margin-bottom: 6px !important;
      }
      .baca-juga-footer {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 4px !important;
        font-size: 0.72rem !important;
        margin-top: 6px !important;
      }
      .baca-juga-cta {
        font-size: 0.74rem !important;
      }
    }
    @media (max-width: 380px) {
      .article-modal-inner {
        padding: 12px 10px !important;
      }
      .baca-juga-card {
        padding: 10px 10px !important;
        border-left-width: 3px !important;
      }
      .baca-juga-thumb {
        width: 60px !important;
        height: 60px !important;
        min-width: 60px !important;
        max-width: 60px !important;
        border-radius: 6px !important;
      }
      .baca-juga-body {
        gap: 10px !important;
      }
      .baca-juga-title {
        font-size: 0.85rem !important;
      }
    }
    .modal-close{position:fixed;top:20px;right:20px;width:44px;height:44px;border:none;border-radius:50%;background:var(--bg-card);color:var(--text-main);border:1px solid var(--border-color);font-size:1.2rem;cursor:pointer;z-index:2001;box-shadow:var(--shadow);display:none}
    .modal-overlay.active+.modal-close,.modal-close.show{display:grid;place-items:center}
    .share-bar{display:flex;gap:8px;margin-top:24px;padding-top:20px;border-top:1px solid var(--border-color);flex-wrap:wrap}
    .share-bar button{padding:8px 16px;border:1px solid var(--border-color);border-radius:8px;background:var(--bg-card);color:var(--text-main);cursor:pointer;font-size:.8rem;transition:var(--tr)}
    .share-bar button:hover{background:var(--green-50);border-color:var(--green-500)}

    /* Search modal */
    .search-modal .modal-box{background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius);padding:24px;width:min(600px,92%);color:var(--text-main)}
    .search-modal input{width:100%;padding:14px 20px;border:2px solid var(--border-color);border-radius:12px;font-size:1rem;outline:none;background:var(--bg-body);color:var(--text-main)}
    .search-modal input:focus{border-color:var(--green-500)}
    .search-results{margin-top:16px;max-height:300px;overflow-y:auto}
    .search-result{padding:12px;border-bottom:1px solid var(--border-color);cursor:pointer;transition:var(--tr)}
    .search-result:hover{background:var(--green-50)}
    body.dark-theme .search-result:hover{background:#1e293b}

    /* Bottom Navigation Bar Mobile */
    .bottom-nav {
      display: none;
      position: fixed;
      bottom: calc(16px + env(safe-area-inset-bottom, 0px));
      left: 0 !important;
      right: 0 !important;
      margin: 0 auto !important;
      width: 90% !important;
      max-width: 360px !important;
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 24px;
      z-index: 1001;
      justify-content: space-around;
      align-items: center;
      padding: 8px 12px 6px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
      box-sizing: border-box;
    }
    .bottom-nav-item{display:flex;flex-direction:column;align-items:center;color:var(--gray-400);font-size:.65rem;font-weight:600;cursor:pointer;transition:var(--tr);position:relative;flex:1;text-align:center;text-decoration:none}
    .bottom-nav-item.active,.bottom-nav-item:hover{color:var(--green-700)}
    body.dark-theme .bottom-nav-item.active,body.dark-theme .bottom-nav-item:hover{color:var(--green-400)}
    .bottom-nav-item .icon{display:flex;align-items:center;justify-content:center;margin-bottom:3px}
    .bottom-nav-item .nav-svg{width:22px;height:22px;stroke:var(--gray-400);transition:stroke var(--tr), transform var(--tr)}
    .bottom-nav-item.active .nav-svg,.bottom-nav-item:hover .nav-svg{stroke:var(--green-700)}
    body.dark-theme .bottom-nav-item.active .nav-svg,body.dark-theme .bottom-nav-item:hover .nav-svg{stroke:var(--green-400)}
    .bottom-nav-item.active .nav-svg{transform:scale(1.1)}
    .bottom-nav-item .badge-count{position:absolute;top:-4px;right:32%;background:var(--red);color:#fff;font-size:.6rem;border-radius:50%;width:16px;height:16px;display:none;place-items:center;font-weight:800}


    /* Footer */
    .footer{background:linear-gradient(to bottom,#022c22,#011510);color:rgba(255,255,255,.7);padding:60px 0 20px;border-top:4px solid var(--green-600)}
    .footer-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr 1fr;gap:36px;margin-bottom:40px}
    .footer h4{color:#fff;margin-bottom:20px;font-size:1.05rem;font-weight:700;position:relative;padding-bottom:8px}
    .footer h4::after{content:'';position:absolute;bottom:0;left:0;width:35px;height:3px;background:var(--green-500);border-radius:2px}
    .footer-links li{margin-bottom:10px}
    .footer-links a{font-size:.88rem;color:rgba(255,255,255,.75);transition:var(--tr);position:relative;padding:2px 0}
    .footer-links a::after{content:'';position:absolute;bottom:0;left:0;width:0;height:1.5px;background:var(--green-400);transition:width var(--tr)}
    .footer-links a:hover{color:#fff}
    .footer-links a:hover::after{width:100%}
    .footer-bottom{border-top:1px solid rgba(255,255,255,.08);padding:24px 0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;font-size:.82rem;color:rgba(255,255,255,.55)}
    .social{display:flex;gap:10px;margin-top:16px}
    .social a{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);display:grid;place-items:center;font-size:.9rem;color:#fff;transition:var(--tr)}
    .social a:hover{background:var(--green-600);transform:scale(1.15) translateY(-2px);box-shadow:0 4px 12px rgba(5,150,105,0.4)}

    .back-top{position:fixed;bottom:80px;right:24px;width:46px;height:46px;border-radius:12px;border:none;cursor:pointer;background:linear-gradient(135deg,var(--green-700),var(--green-500));color:#fff;font-size:1.1rem;box-shadow:var(--shadow);opacity:0;visibility:hidden;transition:var(--tr);z-index:998}
    .back-top.visible{opacity:1;visibility:visible}
    .toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--green-800);color:#fff;padding:12px 24px;border-radius:10px;font-size:.88rem;z-index:3000;transition:transform .4s ease;box-shadow:var(--shadow)}
    .toast.show{transform:translateX(-50%) translateY(0)}

    .mobile-nav{position:fixed;top:0;right:-100%;width:min(300px,85vw);height:100vh;background:rgba(255,255,255,0.95);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);color:var(--text-main);z-index:1500;padding:80px 24px 24px;box-shadow:-10px 0 40px rgba(0,0,0,0.12);transition:right 0.4s cubic-bezier(0.16,1,0.3,1);overflow-y:auto;border-left:1px solid var(--border-color)}
    body.dark-theme .mobile-nav{background:rgba(15,23,42,0.95)}
    .mobile-nav.open{right:0}
    .mobile-nav-title{font-size:1.15rem;font-weight:800;color:var(--green-900);margin-bottom:24px;border-bottom:2px solid var(--green-100);padding-bottom:12px;display:flex;align-items:center;gap:8px}
    .mobile-nav-title svg{stroke:var(--green-700);fill:none}
    body.dark-theme .mobile-nav-title{color:var(--green-400);border-bottom-color:rgba(255,255,255,0.08)}
    body.dark-theme .mobile-nav-title svg{stroke:var(--green-400)}
    .mobile-nav a{display:flex;align-items:center;padding:12px 16px;margin-bottom:8px;border-radius:10px;font-weight:600;color:var(--text-muted);border:none;background:transparent;transition:all var(--tr);font-size:0.9rem;text-decoration:none}
    .mobile-nav a:hover{background:var(--green-50);color:var(--green-700);transform:translateX(4px)}
    body.dark-theme .mobile-nav a:hover{background:rgba(16,185,129,0.1);color:var(--green-400)}
    .mobile-nav a.active{background:var(--green-700);color:#fff !important;box-shadow:0 4px 12px rgba(4,120,87,0.25)}
    body.dark-theme .mobile-nav a.active{background:var(--green-600);box-shadow:0 4px 12px rgba(16,185,129,0.2)}
    .nav-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1400;opacity:0;visibility:hidden;transition:var(--tr)}
    .nav-backdrop.open{opacity:1;visibility:visible}

    /* Media Queries for responsive columns */
    @media(max-width:1200px){
      .main-layout {
        grid-template-columns: 1fr 300px;
      }
      .latest-column {
        display: none;
      }
    }
    @media(max-width:1024px){
      .main-layout {
        grid-template-columns: 1fr 300px;
      }
      .opinion-grid{grid-template-columns:1fr 1fr}
      .grid-4-cols{grid-template-columns:repeat(2,1fr)}
      .stats-grid{grid-template-columns:repeat(2,1fr)}
      .footer-grid{grid-template-columns:repeat(2,1fr)}
    }
    @media(max-width:768px){
      html, body {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: hidden !important;
        overscroll-behavior-x: none !important;
        touch-action: pan-y pinch-zoom;
      }
      .container {
        width: 100% !important;
        max-width: 100% !important;
        padding-left: 16px !important;
        padding-right: 16px !important;
        box-sizing: border-box !important;
      }
      .mobile-stories-wrap {
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin-top: 15px !important;
      }
      .stories-container {
        padding-left: 16px !important;
        padding-right: 16px !important;
        display: flex !important;
      }
      .main-layout {
        grid-template-columns: 1fr;
        padding: 10px 16px 80px !important;
      }
      .editorial-subgrid {
        grid-template-columns: 1fr;
        gap: 16px;
      }
      .editorial-subcard img {
        height: 200px !important;
      }
      .editorial-headline {
        height: 240px;
        margin-bottom: 16px;
      }
      .editorial-headline h2 {
        font-size: 1.2rem;
        line-height: 1.35;
      }
      .editorial-headline .eh-overlay {
        padding: 16px;
      }
      .hamburger{display:none}
      .btn-subscribe{display:none}
      .footer {
        padding: 40px 0 calc(95px + env(safe-area-inset-bottom, 16px)) !important;
        margin-bottom: 0 !important;
        box-sizing: border-box !important;
        width: 100% !important;
        overflow: hidden !important;
      }
      .footer-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 24px !important;
        width: 100% !important;
        box-sizing: border-box !important;
      }
      .footer-brand {
        grid-column: 1 / -1 !important;
        width: 100% !important;
        box-sizing: border-box !important;
      }
      .footer-bottom {
        flex-direction: column !important;
        text-align: center !important;
        gap: 8px !important;
        padding: 20px 0 10px !important;
        margin-bottom: 0 !important;
        box-sizing: border-box !important;
        width: 100% !important;
      }
      @media (max-width: 480px) {
        .footer-grid {
          grid-template-columns: 1fr !important;
          gap: 22px !important;
        }
      }
      .ad-leaderboard{height:auto !important; min-height: 60px; padding: 12px; font-size: 0.8rem;}
      .ad-sticky-bottom {
        display: none !important;
      }
      
      /* Mobile stories and nav indicators */
      body{padding-bottom:0 !important;margin-bottom:0 !important}
      .bottom-nav{display:flex !important}
      
      /* Show sidebar widgets below content on mobile instead of hiding them completely */
      .sidebar {
        display: grid !important;
        grid-template-columns: 1fr;
        gap: 20px;
        margin-top: 30px;
      }
      .sidebar .widget {
        margin-bottom: 0;
      }
      
      /* Compact mobile card layout */
      .article-grid {
        display: flex;
        flex-direction: column;
        gap: 14px;
      }
      .article-card {
        display: grid !important;
        grid-template-columns: 110px 1fr !important;
        gap: 12px;
        height: auto;
        box-shadow: none !important;
        border-bottom: 1px solid var(--border-color);
        border-radius: 0 !important;
        background: transparent !important;
        padding-bottom: 12px;
      }
      .article-card:hover {
        transform: none !important;
      }
      .article-card img {
        width: 110px;
        height: 90px !important;
        min-height: 90px !important;
        border-radius: 8px !important;
      }
      .article-body {
        padding: 0 !important;
      }
      .article-body h3 {
        font-size: 0.85rem !important;
        margin: 4px 0 !important;
        line-height: 1.3 !important;
        -webkit-line-clamp: 2 !important;
      }
      .article-body p {
        display: none !important;
      }
      .article-body .badge {
        font-size: 0.6rem !important;
        padding: 2px 6px !important;
        margin-bottom: 2px !important;
      }
      .article-meta {
        font-size: 0.65rem !important;
      }
      
      .opinion-grid{grid-template-columns:1fr}
      .grid-4-cols{grid-template-columns:1fr}
      .scroll-card{min-width:180px;max-width:180px}
      .scroll-card img{height:110px}
      .back-top{bottom:140px}

      /* Mobile Video & Photo Section Overrides */
      .video-grid, .photo-grid {
        display: flex;
        flex-direction: column;
        gap: 16px;
      }
      .video-main, .photo-main {
        min-height: 200px;
        height: 200px;
      }
      .video-list, .photo-list {
        display: flex;
        flex-direction: row !important;
        gap: 12px;
        overflow-x: auto;
        padding-bottom: 12px;
        scrollbar-width: none;
      }
      .video-list::-webkit-scrollbar, .photo-list::-webkit-scrollbar {
        display: none;
      }
      .video-item, .photo-item {
        display: flex;
        flex-direction: column !important;
        min-width: 200px !important;
        max-width: 200px !important;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 8px !important;
        gap: 8px !important;
        text-align: left;
      }
      .video-thumb-wrap, .photo-thumb-wrap {
        width: 100% !important;
        height: 110px !important;
      }
      .video-thumb, .photo-thumb {
        width: 100% !important;
        height: 100% !important;
        border-radius: 8px !important;
      }
      .video-item h4, .photo-item h4 {
        font-size: 0.8rem !important;
        margin-bottom: 2px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        white-space: normal;
      }
    }
    #backTop {
      position: fixed;
      bottom: 80px;
      right: 24px;
      width: 46px;
      height: 46px;
      border-radius: 50%;
      background: var(--green-700);
      color: #fff;
      border: none;
      box-shadow: var(--shadow-lg);
      cursor: pointer;
      display: grid;
      place-items: center;
      font-size: 1.1rem;
      opacity: 0;
      visibility: hidden;
      transition: var(--tr);
      z-index: 999;
    }
    #backTop.visible {
      opacity: 1;
      visibility: visible;
    }

    /* SPA View Containers & Premium Styling */
    .view-container {
      opacity: 1 !important;
      animation: fadeInView 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes fadeInView {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    /* Premium Back Button */
    .btn-back-premium {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 18px;
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      color: var(--text-main);
      font-weight: 700;
      font-size: 0.85rem;
      border-radius: 50px;
      cursor: pointer;
      box-shadow: 0 2px 10px rgba(0,0,0,0.03);
      transition: var(--tr);
      margin-bottom: 24px;
      font-family: inherit;
    }
    .btn-back-premium:hover {
      background: var(--green-50);
      color: var(--green-700);
      border-color: var(--green-200);
      transform: translateX(-3px);
    }
    body.dark-theme .btn-back-premium:hover {
      background: rgba(16,185,129,0.1);
      color: var(--green-400);
      border-color: var(--green-800);
    }
    .btn-back-premium svg {
      transition: transform var(--tr);
    }
    .btn-back-premium:hover svg {
      transform: translateX(-2px);
    }

    /* Category Header Glassmorphism */
    .category-header {
      background: linear-gradient(135deg, var(--green-900), var(--green-800));
      color: #fff;
      padding: 36px 28px;
      border-radius: 20px;
      margin-bottom: 30px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(6,78,59,0.15);
    }
    .category-header h1 {
      font-size: clamp(1.6rem, 4vw, 2.4rem);
      margin-bottom: 8px;
      font-weight: 800;
      font-family: 'Lora', Georgia, serif;
    }
    .category-header p {
      font-size: 0.9rem;
      opacity: 0.85;
      max-width: 600px;
      line-height: 1.6;
    }
    .category-header::after {
      content: '';
      position: absolute;
      right: -20px;
      bottom: -20px;
      width: 140px;
      height: 140px;
      background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
      border-radius: 50%;
    }

    /* Search Page Hero */
    .search-hero-box {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 36px;
      text-align: center;
      margin-bottom: 30px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.04);
    }
    .search-hero-box h2 {
      font-size: 1.4rem;
      color: var(--text-main);
      margin-bottom: 16px;
      font-family: 'Lora', Georgia, serif;
    }
    .search-input-large-wrap {
      position: relative;
      max-width: 580px;
      margin: 0 auto;
    }
    .search-input-large {
      width: 100%;
      padding: 16px 24px 16px 54px;
      border: 2px solid var(--border-color);
      border-radius: 50px;
      font-size: 1.05rem;
      font-family: inherit;
      outline: none;
      background: var(--bg-body);
      color: var(--text-main);
      transition: var(--tr);
    }
    .search-input-large:focus {
      border-color: var(--green-500);
      box-shadow: 0 0 0 4px rgba(16,185,129,0.15);
      background: var(--bg-card);
    }
    .search-input-large-wrap svg {
      position: absolute;
      left: 20px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray-400);
      pointer-events: none;
      transition: var(--tr);
    }
    .search-input-large:focus + svg {
      color: var(--green-500);
    }
  

  /* ===== ENHANCED MULTI-DEVICE RESPONSIVE DESIGN (LAPTOP, TABLET, HP) ===== */
  
  /* Universal fluid sizing & safety */
  *, *::before, *::after {
    box-sizing: border-box;
  }
  img, video, iframe {
    max-width: 100%;
    height: auto;
  }
  
  /* Header navigation smooth horizontal scroll on touch */
  .cat-nav ul {
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
    scrollbar-width: none;
  }
  .cat-nav ul::-webkit-scrollbar {
    display: none;
  }

  /* Ads responsive wrappers */
  .ad-wrap {
    max-width: 100%;
    overflow: hidden;
  }
  .ad-banner {
    max-width: 100%;
    overflow: hidden;
  }

  /* Laptop & Mid-desktop Breakpoint (1025px - 1280px) */
  @media (max-width: 1280px) {
    .container {
      max-width: 96%;
      padding-left: 20px;
      padding-right: 20px;
    }
  }

  /* Tablet Landscape & Portrait (769px - 1024px) */
  @media (min-width: 769px) and (max-width: 1024px) {
    .container {
      max-width: 100%;
      padding-left: 24px;
      padding-right: 24px;
    }
    .main-layout {
      grid-template-columns: 1fr 280px !important;
      gap: 24px !important;
    }
    .latest-column {
      display: none !important;
    }
    .opinion-grid, .grid-4-cols {
      grid-template-columns: repeat(2, 1fr) !important;
      gap: 16px !important;
    }
    .stats-grid {
      grid-template-columns: repeat(2, 1fr) !important;
      gap: 16px !important;
    }
    .footer-grid {
      grid-template-columns: repeat(2, 1fr) !important;
      gap: 28px !important;
    }
    .editorial-subgrid {
      grid-template-columns: 1fr 1fr !important;
    }
    .editorial-headline {
      height: 320px !important;
    }
    .ad-leaderboard {
      min-height: 80px;
    }
    .article-card img {
      height: 170px !important;
    }
  }

  /* Smartphone / HP Devices (<= 768px) */
  @media (max-width: 768px) {
    html, body {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: hidden !important;
        overscroll-behavior-x: none !important;
        touch-action: pan-y pinch-zoom;
      }
    .container {
      width: 100% !important;
      padding-left: 14px !important;
      padding-right: 14px !important;
    }
    
    /* Compact Sticky Header */
    .header-main {
      padding: 10px 0 !important;
    }
    .logo-img {
      height: 34px !important;
      max-height: 34px !important;
    }
    .header-actions {
      gap: 8px !important;
    }
    .btn-icon {
      width: 36px !important;
      height: 36px !important;
    }

    /* Clean Category Navigation */
    .cat-nav {
      padding: 4px 0 !important;
    }
    .cat-nav a {
      font-size: 0.8rem !important;
      padding: 6px 12px !important;
      white-space: nowrap !important;
    }

    /* Breaking News Ticker */
    .breaking-bar {
      font-size: 0.76rem !important;
      padding: 6px 10px !important;
    }

    /* Editorial Headline on Mobile */
    .editorial-headline {
      height: 220px !important;
      border-radius: 12px !important;
      margin-bottom: 12px !important;
    }
    .editorial-headline h2 {
      font-size: 1.05rem !important;
      line-height: 1.35 !important;
    }
    .eh-meta {
      font-size: 0.72rem !important;
    }

    /* Editorial Subgrid on Mobile */
    .editorial-subgrid {
      grid-template-columns: 1fr !important;
      gap: 12px !important;
    }
    .editorial-subcard {
      border-radius: 10px !important;
    }
    .editorial-subcard img {
      height: 160px !important;
    }

    /* Section Headings */
    .section-head h2 {
      font-size: 1.15rem !important;
    }

    /* Horizontal scroll cards (Hikmah, Olahraga, Essay) */
    .scroll-row {
      gap: 12px !important;
      padding-bottom: 8px !important;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior-x: contain !important;
      touch-action: pan-x !important;
      scroll-snap-type: x mandatory !important;
    }
    .scroll-card {
      min-width: 190px !important;
      max-width: 190px !important;
      border-radius: 10px !important;
      scroll-snap-align: start !important;
      scroll-snap-stop: normal !important;
    }
    .scroll-card img {
      height: 110px !important;
    }

    /* Article detail view on mobile */
    .article-detail-wrap {
      padding: 14px !important;
    }
    .article-detail-wrap h1 {
      font-size: 1.35rem !important;
      line-height: 1.35 !important;
    }
    .article-detail-wrap .article-full-img {
      max-height: 250px !important;
      border-radius: 10px !important;
    }
    .article-content-body {
      font-size: 0.98rem !important;
      line-height: 1.75 !important;
    }

    /* Search & Category view headers */
    .cat-view-header h1, .search-header h1 {
      font-size: 1.3rem !important;
    }

    /* Pastikan body dan html tidak memiliki padding atau margin bawah agar tidak muncul celah putih di bawah footer */
    body {
      padding-bottom: 0 !important;
      margin-bottom: 0 !important;
    }
    .footer {
      padding-bottom: calc(95px + env(safe-area-inset-bottom, 16px)) !important;
      margin-bottom: 0 !important;
    }
    .back-top {
      bottom: calc(85px + env(safe-area-inset-bottom, 16px)) !important;
      right: 14px !important;
      width: 42px !important;
      height: 42px !important;
      z-index: 1002 !important;
    }
  }

  /* Extra Small Smartphones (<= 380px) */
  @media (max-width: 380px) {
    .container {
      padding-left: 10px !important;
      padding-right: 10px !important;
    }
    .article-card {
      grid-template-columns: 90px 1fr !important;
      gap: 10px !important;
    }
    .article-card img {
      width: 90px !important;
      height: 80px !important;
      min-height: 80px !important;
    }
    .article-body h3 {
      font-size: 0.8rem !important;
    }
    .scroll-card {
      min-width: 160px !important;
      max-width: 160px !important;
    }
  }

</style>
</head>
<body>

  <!-- Stock Ticker Removed as requested -->



  <!-- Header -->
  <header class="header" id="header">
    <div class="container header-main">
      <a href="#" class="logo" onclick="selectCategory('all'); return false">
        <img src="img/PUCUK%20PENA.png" alt="Pucuk Pena Logo" class="logo-img">
      </a>
      <div class="header-actions">
        <button class="btn-icon" id="toggleDarkMode" aria-label="Ganti Tema">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="theme-icon"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
        </button>
        <button class="btn-icon" id="openSearch" aria-label="Cari">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </button>
        
        <button class="hamburger" id="hamburger"><span></span><span></span><span></span></button>
      </div>
    </div>
    <nav class="cat-nav">
      <ul id="catNav">
        <li><a href="#beranda" class="active" data-cat="all">Beranda</a></li>
        <li><a href="#berita" data-cat="berita">Berita Utama</a></li>
        <li><a href="#essay" data-cat="essay">Essay</a></li>
        <li><a href="#opini" data-cat="opini">Opini</a></li>
        <li><a href="#pendidikan" data-cat="pendidikan">Pendidikan</a></li>
        <li><a href="#olahraga" data-cat="olahraga">Olahraga</a></li>
        <li><a href="#ekonomi" data-cat="ekonomi">Ekonomi</a></li>
        <li><a href="#hikmah" data-cat="hikmah">Hikmah</a></li>
        <li><a href="#foto" data-cat="foto">Foto</a></li>
        <li><a href="#bookmark" data-cat="bookmark"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: middle;"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>Bookmark</a></li>
      </ul>
    </nav>
  </header>

  <div class="nav-backdrop" id="navBackdrop"></div>
  <nav class="mobile-nav" id="mobileNav"></nav>

  <div id="homeView">
  <!-- Ad Leaderboard -->
  <div class="container ad-wrap">
    <div class="ad-label">Iklan</div>
    <div class="ad-banner ad-leaderboard" id="adLeaderboard">
      <div><strong style="color:var(--green-700)">🌿 GreenLife Insurance</strong> — Proteksi keluarga Anda mulai Rp50rb/bulan · <u>Klik di sini</u></div>
    </div>
  </div>



  <!-- Main Layout -->
  <div class="container main-layout" id="beranda">
    <!-- Left Column (Terbaru) -->
    <aside class="latest-column">
      <h4>Terbaru</h4>
      <div class="divider-editorial"></div>
      <div class="latest-list" id="latestNewsList"></div>
    </aside>
    <main>
      <!-- Editorial Headline / Berita Utama -->
      <div id="editorialHeadlineSection">
        <div id="beritaUtamaContainer"></div>
      </div>

      <!-- Filter + Articles -->
      <div class="section-block" id="berita">
        <div class="section-head"><h2>Berita Terkini</h2><a href="#" id="seeAllNews">Lihat Semua →</a></div>
        <div class="filter-tabs" id="filterTabs"></div>
        <div class="article-grid" id="articleGrid"></div>
        <div id="loadMoreContainer" style="text-align:center;margin-top:24px;margin-bottom:12px">
          <button id="btnLoadMore" class="btn-subscribe" style="display:inline-block;padding:10px 28px;font-size:0.9rem;border-radius:6px;cursor:pointer;border:none">Muat Lebih Banyak</button>
        </div>
      </div>

      <!-- In-feed Ad -->
      <div class="ad-wrap">
        <div class="ad-label">Iklan</div>
        <div class="ad-banner ad-native" id="adNative1">
          <div class="ad-thumb">📱</div>
          <div>
            <h4>AriTel 5G — Internet Super Cepat di Seluruh Indonesia</h4>
            <p>Paket unlimited mulai 89rb. Gratis router untuk pelanggan baru!</p>
            <span class="ad-cta">Pelajari Lebih Lanjut</span>
          </div>
        </div>
      </div>

      <!-- Essay -->
      <div class="section-block" id="essay">
        <div class="section-head"><h2>Essay</h2><a href="#" onclick="selectCategory('essay'); return false">Baca Selengkapnya →</a></div>
        <div class="scroll-row" id="essayScroll"></div>
      </div>

      <!-- Opini -->
      <div class="section-block" id="opini">
        <div class="section-head"><h2>Kolom Opini</h2><a href="#" onclick="selectCategory('opini'); return false">Arsip Opini →</a></div>
        <div class="opinion-grid" id="opinionGrid"></div>
      </div>

      <!-- Video -->
      <div class="section-block" id="video">
        <div class="section-head"><h2>Pucuk Pena TV</h2><a href="#" onclick="selectCategory('video'); return false">Semua Video →</a></div>
        <div class="video-grid" id="videoGrid"></div>
      </div>

      <!-- Foto -->
      <div class="section-block" id="foto-section">
        <div class="section-head"><h2>Galeri Foto</h2><a href="#" onclick="selectCategory('foto'); return false">Semua Foto →</a></div>
        <div class="photo-grid" id="photoGrid"></div>
      </div>

      <!-- Investigasi -->
      <div class="section-block" id="nasional">
        <div class="section-head"><h2>Investigasi & Laporan Khusus</h2><a href="#" onclick="selectCategory('investigasi'); return false">Lihat Arsip →</a></div>
        <div class="article-grid" id="investigasiGrid"></div>
      </div>
    </main>

    <!-- Sidebar -->
    <aside class="sidebar">


      <!-- Support / Donation Card -->
      <div class="widget support-card" style="background: linear-gradient(135deg, var(--green-900), var(--green-800)); color: #fff; border: none; box-shadow: 0 8px 30px rgba(6, 78, 59, 0.2); position: relative; overflow: hidden; border-radius: var(--radius); padding: 22px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; right: -10px; top: -10px; pointer-events: none;"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
        <h3 style="color:#a7f3d0; border-bottom: 1px solid rgba(255,255,255,0.15); font-family: 'Lora', Georgia, serif; font-weight: 700; font-size: 1.1rem; margin-bottom: 10px; padding-bottom: 8px;">Dukung Jurnalisme Independen</h3>
        <p style="font-size: 0.8rem; line-height: 1.6; color: rgba(255,255,255,0.85); margin-bottom: 16px;">Pucuk Pena adalah media nirlaba independen. Dukungan Anda membantu kami menyajikan investigasi mendalam dan opini kritis berkualitas.</p>
        <button onclick="showToast('Terima kasih atas niat baik Anda untuk mendukung kami!')" style="width: 100%; padding: 10px; border: none; border-radius: 8px; background: var(--green-400); color: var(--green-900); font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: var(--tr); font-family: 'Plus Jakarta Sans', sans-serif;">Donasi Sekarang</button>
      </div>

      <div class="widget">
        <h3><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px; color: var(--green-700);"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>Trending</h3>
        <div id="trendingList"></div>
      </div>



      <div class="ad-wrap" style="margin:0">
        <div class="ad-label">Iklan</div>
        <div class="ad-banner ad-native" id="adNative2">
          <div class="ad-thumb">☕</div>
          <div>
            <h4>Kopi Nusantara Premium</h4>
            <p>Rasakan cita rasa kopi lokal terbaik. Diskon 20%!</p>
            <span class="ad-cta">Beli Sekarang</span>
          </div>
        </div>
      </div>


    </aside>
  </div>



  <!-- More sections by category -->
  <div class="container" style="padding-bottom:40px">
    <div class="section-block" id="hikmah">
      <div class="section-head"><h2>Hikmah</h2><a href="#" onclick="selectCategory('hikmah'); return false">Semua →</a></div>
      <div class="scroll-row" id="hikmahScroll"></div>
    </div>
    <div class="ad-wrap">
      <div class="ad-label">Iklan</div>
      <div class="ad-banner ad-leaderboard" id="adLeaderboard2">
        <div><strong style="color:var(--green-700)">✈️ FlyGreen Airlines</strong> — Promo tiket domestik mulai Rp299rb · Terbang nyaman, ramah lingkungan</div>
      </div>
    </div>
    <div class="section-block" id="ekonomi">
      <div class="section-head"><h2>Ekonomi</h2><a href="#" onclick="selectCategory('ekonomi'); return false">Semua →</a></div>
      <div class="grid-4-cols" id="ekonomiGrid"></div>
    </div>
    <div class="section-block" id="pendidikan">
      <div class="section-head"><h2>Pendidikan</h2><a href="#" onclick="selectCategory('pendidikan'); return false">Semua →</a></div>
      <div class="grid-4-cols" id="pendidikanGrid"></div>
    </div>
    <div class="section-block" id="olahraga">
      <div class="section-head"><h2>Olahraga</h2><a href="#" onclick="selectCategory('olahraga'); return false">Semua →</a></div>
      <div class="scroll-row" id="olahragaScroll"></div>
    </div>
    <!-- Bottom Square Ad -->
    <div class="container" id="adBottomContainer" style="margin-top: 24px; margin-bottom: 24px; display: flex; justify-content: center;">
      <div class="ad-wrap" style="margin: 0; width: 100%; max-width: 320px;">
        <div class="ad-label">Iklan</div>
        <div class="ad-banner ad-rectangle" id="adBottomBanner" style="width: 100%; height: 250px; display: flex; align-items: center; justify-content: center; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow);">
          <div style="padding:16px;text-align:center">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--green-700)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 12px; display: block;"><rect x="2" y="22" width="20" height="2"></rect><path d="M12 2L2 7h20L12 2zM5 22V11M9 22V11M15 22V11M19 22V11M2 11h20"></path></svg>
            <strong style="color:var(--green-800); font-size: 1.1rem; display: block; margin-bottom: 6px;">Bank Hijau Nusantara</strong>
            <p style="font-size:.82rem;color:var(--gray-600);margin-top:6px;line-height:1.4">Deposito 7% p.a.<br>Bunga kompetitif!</p>
            <span class="ad-cta" style="margin-top: 14px; display: inline-block; padding: 6px 14px; background: var(--green-700); color: #fff; border-radius: 6px; font-size: 0.78rem; font-weight: 600; cursor: pointer;">Info Selengkapnya</span>
          </div>
        </div>
      </div>
    </div>
  </div> <!-- Close .container padding-bottom:40px -->
</div> <!-- Close #homeView -->

  <!-- Article Detail View -->
  <div id="articleDetailView" class="container view-container" style="display: none; padding-top: 20px; padding-bottom: 80px;"></div>

  <!-- Category Index View -->
  <div id="categoryView" class="container view-container" style="display: none; padding-top: 20px; padding-bottom: 80px;"></div>

  <!-- Search Results View -->
  <div id="searchResultView" class="container view-container" style="display: none; padding-top: 20px; padding-bottom: 80px;"></div>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo" style="margin-bottom:12px">
            <a href="#" onclick="selectCategory('all'); return false">
              <img src="img/PUCUK%20PENA.png" alt="Pucuk Pena Logo" class="logo-img">
            </a>
          </div>
          <p id="footerAboutDesc" style="font-size:.86rem;line-height:1.75;margin-bottom:16px;color:rgba(255,255,255,0.8)"><?= htmlspecialchars($siteSettings['about_footer'] ?? 'Pucuk Pena menghadirkan jurnalisme independen, berita faktual, opini kritis, dan artikel mendalam untuk masyarakat Indonesia yang cerdas dan kritis.') ?></p>
          
          <div class="social" id="footerSocial">
            <a href="<?= htmlspecialchars($siteSettings['social_facebook'] ?? 'https://facebook.com') ?>" target="_blank" rel="noopener noreferrer" title="Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
            <a href="<?= htmlspecialchars($siteSettings['social_instagram'] ?? 'https://instagram.com') ?>" target="_blank" rel="noopener noreferrer" title="Instagram"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg></a>
            <a href="<?= htmlspecialchars($siteSettings['social_twitter'] ?? 'https://twitter.com') ?>" target="_blank" rel="noopener noreferrer" title="Twitter/X"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path></svg></a>
            <a href="<?= htmlspecialchars($siteSettings['social_youtube'] ?? 'https://youtube.com') ?>" target="_blank" rel="noopener noreferrer" title="YouTube"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.41 19c1.71.46 8.59.46 8.59.46s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96 29 29 0 0 0 .46-5.33 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg></a>
            <a href="<?= htmlspecialchars($siteSettings['social_tiktok'] ?? 'https://tiktok.com') ?>" target="_blank" rel="noopener noreferrer" title="TikTok"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path></svg></a>
          </div>
        </div>
        <div><h4>Kategori Utama</h4><ul class="footer-links"><li><a href="#" onclick="selectCategory('berita'); return false">Berita Utama</a></li><li><a href="#" onclick="selectCategory('essay'); return false">Essay</a></li><li><a href="#" onclick="selectCategory('opini'); return false">Opini</a></li><li><a href="#" onclick="selectCategory('pendidikan'); return false">Pendidikan</a></li></ul></div>
        <div><h4>Rubrik Lainnya</h4><ul class="footer-links"><li><a href="#" onclick="selectCategory('olahraga'); return false">Olahraga</a></li><li><a href="#" onclick="selectCategory('ekonomi'); return false">Ekonomi</a></li><li><a href="#" onclick="selectCategory('hikmah'); return false">Hikmah</a></li><li><a href="#" onclick="selectCategory('foto'); return false">Foto</a></li><li><a href="#" onclick="selectCategory('bookmark'); return false">Berita Disimpan</a></li></ul></div>
        <div><h4>Bantuan & Kontak</h4><ul class="footer-links"><li><a href="#" onclick="openInfoModal('about'); return false">Tentang Kami</a></li><li><a href="#" onclick="openInfoModal('redaksi'); return false">Susunan Redaksi</a></li><li><a href="#" onclick="openInfoModal('kontak'); return false">Kontak Kami</a></li><li><a href="#" onclick="openInfoModal('syarat'); return false">Syarat & Ketentuan</a></li><li><a href="#" onclick="openInfoModal('privasi'); return false">Kebijakan Privasi</a></li></ul></div>
      </div>
      <div class="footer-bottom">
        <span id="footerCopyrightText">&copy; <?= date('Y') ?> <?= htmlspecialchars($siteSettings['footer_copyright'] ?? 'Pucuk Pena Media Group. Hak Cipta Dilindungi Undang-Undang.') ?></span>
        <span id="footerSubtext"><?= htmlspecialchars($siteSettings['footer_subtext'] ?? 'Dibuat dengan 💚 untuk Jurnalisme Indonesia') ?></span>
      </div>
    </div>
  </footer>

  <!-- Info Modal Dialog (Tentang Kami, Susunan Redaksi, Kontak, dll.) -->
  <div id="infoModalOverlay" class="modal-overlay" onclick="closeInfoModal(event)" style="z-index:2500">
    <div class="modal-article" style="max-width:580px;margin:30px auto;border-radius:12px;overflow:hidden;box-shadow:var(--shadow-lg)" onclick="event.stopPropagation()">
      <div style="display:flex;justify-content:space-between;align-items:center;padding:18px 24px;border-bottom:1px solid var(--border-color);background:var(--bg-card)">
        <h3 id="infoModalTitle" style="margin:0;color:var(--green-700);font-size:1.1rem">Informasi</h3>
        <button onclick="closeInfoModal()" style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:var(--text-muted);padding:4px 8px" title="Tutup">✕</button>
      </div>
      <div id="infoModalBody" style="padding:24px;line-height:1.75;font-size:0.92rem;color:var(--text-main);max-height:75vh;overflow-y:auto;background:var(--bg-card)">
      </div>
      <div style="padding:12px 24px;border-top:1px solid var(--border-color);text-align:right;background:var(--bg-card)">
        <button onclick="closeInfoModal()" style="padding:7px 18px;background:var(--green-700);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:0.82rem">Tutup</button>
      </div>
    </div>
  </div>

  <!-- Sticky Bottom Ad -->
  <div class="ad-sticky-bottom" id="stickyAd">
    <button class="ad-close" id="closeStickyAd">✕</button>
    <div class="ad-banner ad-leaderboard" style="height:50px">
      <span style="font-size:.82rem"><strong>🛒 Pucuk Pena Mart</strong> — Gratis ongkir min. belanja Rp100rb · Kode: PUCUKPENA25</span>
    </div>
  </div>



  <!-- Mobile Bottom Navigation -->
  <nav class="bottom-nav">
    <a href="#" class="bottom-nav-item active" id="btnHomeMobile">
      <span class="icon"><svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
      <span class="label">Beranda</span>
    </a>
    <a href="#" class="bottom-nav-item" id="btnCatMobile">
      <span class="icon"><svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>
      <span class="label">Kategori</span>
    </a>
    <a href="#" class="bottom-nav-item" id="btnSearchMobile">
      <span class="icon"><svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
      <span class="label">Cari</span>
    </a>
    <a href="#" class="bottom-nav-item" id="btnBookmarkMobile">
      <span class="icon"><svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg></span>
      <span class="label">Simpan</span>
      <span class="badge-count" id="bookmarkCount">0</span>
    </a>
  </nav>

  <!-- Back to Top Button -->
  <button id="backTop" title="Kembali ke atas">▲</button>

  <!-- Toast Notification (WAJIB ada agar showToast tidak crash) -->
  <div id="toast" style="position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--green-800);color:#fff;padding:10px 20px;border-radius:8px;font-size:.88rem;font-weight:600;z-index:9999;transition:transform 0.3s ease, opacity 0.3s ease;opacity:0;pointer-events:none;white-space:nowrap;box-shadow:0 4px 16px rgba(0,0,0,0.25)"></div>



  <script>
  /* ===== SVG IMAGE GENERATOR (offline) ===== */
function genImg(id, colors, icon, label) {
  // Map specific IDs to our high-quality generated local images
  const localMap = {
    1: 'img/desa_wisata.png',
    2: 'img/g20_summit.png',
    3: 'img/rupiah_exchange.png',
    4: 'img/ai_startup.png',
    5: 'img/timnas_football.png',
    6: 'img/demokrasi_digital.png',
    7: 'img/pendidikan_sekolah.png',
    8: 'img/senja_desa.png',
    25: 'img/anggrek_ungu.png',
    26: 'img/badminton_court.png',
    27: 'img/mandalika_motogp.png',
    28: 'img/maraton_borobudur.png',
    33: 'img/maraton_borobudur.png',
    35: 'img/iot_polusi_air.png',
    
    // Map videos
    101: 'img/desa_wisata.png',
    102: 'img/timnas_football.png',
    103: 'img/ai_startup.png',
    104: 'img/desa_wisata.png',
    105: 'img/maraton_borobudur.png',
    106: 'img/ai_startup.png',
    107: 'img/senja_desa.png',
    108: 'img/desa_wisata.png',
    109: 'img/ai_startup.png',
    110: 'img/badminton_court.png'
  };

  if (localMap[id]) {
    return localMap[id];
  }

  // Realistic stock photography fallbacks from Unsplash
  const photos = [
    'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&auto=format&fit=crop&q=60', // Group work
    'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=600&auto=format&fit=crop&q=60', // Technology space
    'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&auto=format&fit=crop&q=60', // Beach
    'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=600&auto=format&fit=crop&q=60', // Mountains
    'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=600&auto=format&fit=crop&q=60', // Cyber technology
    'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=600&auto=format&fit=crop&q=60', // Business skyscraper
    'https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?w=600&auto=format&fit=crop&q=60', // Smart phone/tech
    'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=600&auto=format&fit=crop&q=60', // Yosemite valley
    'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=600&auto=format&fit=crop&q=60', // Nature green field
    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=60', // Headphones
    'https://images.unsplash.com/photo-1518770660439-4636190af475?w=600&auto=format&fit=crop&q=60', // Circuit board
    'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=600&auto=format&fit=crop&q=60'  // Office table
  ];
  
  const photoIndex = Math.abs(id) % photos.length;
  return photos[photoIndex];
}

  // ===== DYNAMIC DATA (DIRECT FROM MYSQL VIA PHP) =====
  let ARTICLES = <?= json_encode($articlesData, JSON_UNESCAPED_UNICODE) ?> || [];
  let ADS = <?= json_encode($adsData, JSON_UNESCAPED_UNICODE) ?> || {};
  let BREAKING_TEXTS = <?= json_encode($breakingData, JSON_UNESCAPED_UNICODE) ?> || [];
  let SITE_SETTINGS = <?= json_encode($siteSettings, JSON_UNESCAPED_UNICODE) ?> || {};

  function getArticleImg(img) {
    if (img && typeof img === 'string') {
      const clean = img.trim();
      if (clean !== '' && clean !== 'null' && clean !== 'undefined') {
        return clean;
      }
    }
    return 'img/desa_wisata.png';
  }

  function getShareUrl(id) {
    return `${window.location.origin}${window.location.pathname}?id=${id}`;
  }

  function shareArticleWhatsApp(title, id) {
    const url = getShareUrl(id);
    const text = `*${title}*\n\nBaca selengkapnya di Pucuk Pena:\n${url}`;
    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`, '_blank');
  }

  function shareArticleNative(title, id) {
    const url = getShareUrl(id);
    if (navigator.share) {
      navigator.share({
        title: title + ' — Pucuk Pena',
        text: title,
        url: url
      }).catch(e => {
        if (e.name !== 'AbortError') copyArticleLink(id);
      });
    } else {
      copyArticleLink(id);
    }
  }

  function copyArticleLink(id) {
    const url = getShareUrl(id);
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(() => {
        showToast('Link artikel berhasil disalin!');
      }).catch(() => {
        showToast('Gagal menyalin link');
      });
    } else {
      showToast('Link: ' + url);
    }
  }

  function openInfoModal(type) {
    const overlay = document.getElementById('infoModalOverlay');
    const titleEl = document.getElementById('infoModalTitle');
    const bodyEl = document.getElementById('infoModalBody');
    if (!overlay || !titleEl || !bodyEl) return;

    const contentMap = {
      'about': {
        title: 'Tentang Kami',
        body: `
          <p style="margin-bottom:14px"><strong>Pucuk Pena</strong> adalah media daring independen terdepan yang berdedikasi menyajikan jurnalisme faktual, berintegritas, mendalam, dan mencerdaskan bangsa.</p>
          <p style="margin-bottom:14px">Dengan mengusung moto <em>"Tajam Mengabarkan, Jernih Mencerahkan"</em>, redaksi kami mengawal fakta melalui investigasi, analisis ekonomi, telaah pendidikan, serta pencerahan hikmah dan opini kritis para pakar.</p>
          <p>Kami menjunjung Kode Etik Jurnalistik dan senantiasa berpihak pada kebenaran dan kepentingan publik demi literasi Indonesia.</p>
        `
      },
      'redaksi': {
        title: 'Susunan Redaksi',
        body: `
          <div style="display:flex;flex-direction:column;gap:12px;font-size:0.9rem">
            <div><strong style="color:var(--green-700)">Pemimpin Umum / Penanggung Jawab:</strong><br>Muhammad Aslam</div>
            <div><strong style="color:var(--green-700)">Pemimpin Redaksi:</strong><br>Ahmad Fauzi, M.I.Kom</div>
            <div><strong style="color:var(--green-700)">Redaktur Pelaksana:</strong><br>Siti Nurhaliza</div>
            <div><strong style="color:var(--green-700)">Dewan Redaksi & Editorial:</strong><br>Dr. Hendra Wijaya, Prof. Suryanto, M.Si</div>
            <div><strong style="color:var(--green-700)">Tim Investigasi & Liputan Khusus:</strong><br>Rian Pratama, Dewi Lestari, Budi Santoso</div>
            <div><strong style="color:var(--green-700)">Teknologi & Multimedia:</strong><br>Tim IT Pucuk Pena</div>
            <div><strong style="color:var(--green-700)">Alamat Kantor Redaksi:</strong><br>Gedung Pena Nusantara Lt. 3, Jl. Merdeka No. 45, Jakarta Pusat</div>
          </div>
        `
      },
      'kontak': {
        title: 'Kontak Kami',
        body: `
          <p style="margin-bottom:14px">Punya informasi berita, pertanyaan, kerjasama iklan, atau pengiriman tulisan opini/essay? Hubungi tim kami:</p>
          <div style="display:flex;flex-direction:column;gap:12px;font-size:0.9rem">
            <div>📧 <strong>Email Redaksi:</strong> redaksi@pucukpena.com</div>
            <div>💼 <strong>Email Bisnis & Iklan:</strong> iklan@pucukpena.com</div>
            <div>📱 <strong>WhatsApp Redaksi:</strong> +62 812-3456-7890</div>
            <div>📍 <strong>Alamat:</strong> Gedung Pena Nusantara Lt. 3, Jakarta Pusat</div>
            <div>🕒 <strong>Jam Operasional:</strong> Senin – Jumat: 08.00 – 18.00 WIB</div>
          </div>
        `
      },
      'syarat': {
        title: 'Syarat & Ketentuan',
        body: `
          <p style="margin-bottom:12px">1. <strong>Hak Cipta:</strong> Seluruh artikel, narasi, visual foto, dan video yang diterbitkan di Pucuk Pena dilindungi oleh Undang-Undang Hak Cipta.</p>
          <p style="margin-bottom:12px">2. <strong>Pengutipan:</strong> Pengutipan materi diizinkan maksimal 25% dari total isi dengan mencantumkan sumber jelas dan backlink aktif ke Pucuk Pena.</p>
          <p style="margin-bottom:12px">3. <strong>Komentar:</strong> Pembaca dilarang menyebarkan ujaran kebencian, fitnah, pornografi, maupun provokasi SARA di kolom komentar.</p>
        `
      },
      'privasi': {
        title: 'Kebijakan Privasi',
        body: `
          <p style="margin-bottom:12px">1. <strong>Perlindungan Data:</strong> Pucuk Pena berkomitmen penuh melindungi privasi pembaca sesuai peraturan perlindungan data pribadi.</p>
          <p style="margin-bottom:12px">2. <strong>Penggunaan Informasi:</strong> Informasi nama pembaca yang mengirim komentar hanya digunakan untuk atribusi diskusi di situs.</p>
          <p style="margin-bottom:12px">3. <strong>Keamanan:</strong> Kami tidak pernah menjual, menyewakan, atau menyalahgunakan data pengguna kepada pihak ketiga.</p>
        `
      }
    };

    const titles = {
      'about': 'Tentang Kami',
      'redaksi': 'Susunan Redaksi',
      'kontak': 'Kontak Kami',
      'syarat': 'Syarat & Ketentuan',
      'privasi': 'Kebijakan Privasi'
    };

    const settingKey = type + '_modal';
    if (SITE_SETTINGS && SITE_SETTINGS[settingKey] && SITE_SETTINGS[settingKey].trim() !== '') {
      titleEl.textContent = titles[type] || 'Informasi';
      bodyEl.innerHTML = SITE_SETTINGS[settingKey].split('\n\n').map(p => {
        return `<p style="margin-bottom:14px;line-height:1.75;font-size:0.9rem">${escapeHtml(p).replace(/\n/g, '<br>')}</p>`;
      }).join('');
      overlay.classList.add('active');
      return;
    }

    const item = contentMap[type] || contentMap['about'];
    titleEl.textContent = item.title;
    bodyEl.innerHTML = item.body;
    overlay.classList.add('active');
  }

  function closeInfoModal(e) {
    if (e && e.target !== e.currentTarget && e.target.tagName !== 'BUTTON') return;
    const overlay = document.getElementById('infoModalOverlay');
    if (overlay) overlay.classList.remove('active');
  }

  const DEFAULT_VIDEOS = [
    { title:'Wawancara Eksklusif: Menteri Pariwisata dan Ekonomi Kreatif', views:'128K', img:'img/desa_wisata.png' },
    { title:'Highlight Timnas Indonesia di Kualifikasi Piala Asia', views:'890K', img:'img/timnas_football.png' },
    { title:'Review Teknologi: Startup AI Indonesia Raih Series B', views:'245K', img:'img/ai_startup.png' },
    { title:'Dokumenter: Kehidupan Petani Kopi di Dataran Tinggi Gayo', views:'76K', img:'img/senja_desa.png' },
    { title:'Live Report: Pembukaan Borobudur Marathon', views:'45K', img:'img/maraton_borobudur.png' },
    { title:'Sains Populer: Ekspedisi Anggrek Langka Hutan Kalimantan', views:'112K', img:'img/anggrek_ungu.png' },
    { title:'Eksplorasi Sirkuit Mandalika & Talenta Balap Nasional', views:'89K', img:'img/mandalika_motogp.png' },
    { title:'Kota Cerdas Nusantara: Implementasi IoT Kualitas Air', views:'156K', img:'img/iot_polusi_air.png' }
  ];

  let VIDEOS = ARTICLES.filter(a => a.type === 'video' || a.cat === 'video').map(v => ({
    title: v.title,
    views: v.views,
    img: v.img || 'img/desa_wisata.png'
  }));
  if (VIDEOS.length === 0) {
    VIDEOS = DEFAULT_VIDEOS;
  }

  const AGENDA = [
  { time:'09:00', event:'Briefing Redaksi Pagi' },
  { time:'11:00', event:'Live Report: Sidang DPR RI' },
  { time:'14:00', event:'Talkshow Pucuk Pena TV: Ekonomi Digital' },
  { time:'16:30', event:'Press Release Kementerian Pariwisata' },
  { time:'19:00', event:'Prime Time News Live' }
];

const FILTERS = ['Semua','Berita Utama','Essay','Opini','Pendidikan','Olahraga','Ekonomi','Hikmah','Foto'];

/* ===== RENDER FUNCTIONS ===== */
function badgeClass(b) { return 'badge badge-' + b; }

function bookmarkSvg(id, isLightBg = false) {
  const isBookmarked = getBookmarkedIds().includes(Number(id));
  const colorClass = isBookmarked ? 'active' : '';
  
  let strokeColor = '';
  let fillVal = '';
  
  if (isLightBg) {
    strokeColor = isBookmarked ? 'var(--green-700)' : 'var(--gray-400)';
    fillVal = isBookmarked ? 'var(--green-700)' : 'none';
  } else {
    strokeColor = isBookmarked ? 'var(--green-400)' : '#fff';
    fillVal = isBookmarked ? 'var(--green-400)' : 'none';
  }
  
  return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="${fillVal}" stroke="${strokeColor}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="bookmark-svg ${colorClass}" style="vertical-align: middle;"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>`;
}

function renderLatestColumn() {
  const latestList = document.getElementById('latestNewsList');
  if (!latestList) return;
  
  const latest = ARTICLES.slice(0, 6);
  latestList.innerHTML = latest.map(a => `
    <div class="latest-card" onclick="openArticle(${a.id})">
      <img src="${getArticleImg(a.img)}" alt="${a.title.replace(/"/g, '&quot;')}" loading="lazy" onerror="this.src='img/desa_wisata.png'">
      <div class="lc-content">
        <h3>${a.title}</h3>
        <div class="lc-meta">
          <span>Oleh ${a.author}</span>
          <span>${a.date}</span>
        </div>
      </div>
    </div>
  `).join('');
}

function renderBeritaUtama() {
  const container = document.getElementById('beritaUtamaContainer');
  if (!container || !ARTICLES || ARTICLES.length === 0) return;
  
  const top = ARTICLES.slice(0, 3);
  if (!top[0]) return;
  
  container.innerHTML = `
    <div class="editorial-headline" onclick="openArticle(${top[0].id})" style="cursor:pointer">
      <img src="${getArticleImg(top[0].img)}" alt="${top[0].title.replace(/"/g, '&quot;')}" loading="eager" onerror="this.src='img/desa_wisata.png'">
      <div class="eh-overlay">
        <div style="display:flex;justify-content:space-between;align-items:center;width:100%;margin-bottom:8px">
          <span class="${badgeClass(top[0].badge)}">${top[0].type}</span>
          <button class="bookmark-btn-for-${top[0].id}" onclick="toggleBookmark(event, ${top[0].id})" style="background:rgba(0,0,0,.4);border:none;cursor:pointer;width:38px;height:38px;border-radius:50%;display:grid;place-items:center;transition:var(--tr);z-index:2">${bookmarkSvg(top[0].id, false)}</button>
        </div>
        <h2>${top[0].title}</h2>
        <div class="eh-meta">
          <span>Oleh ${top[0].author || 'Redaksi'}</span> · 
          <span>${top[0].date}</span> · 
          <span>${top[0].views || '1K'} pembaca</span>
        </div>
      </div>
    </div>
    <div class="editorial-subgrid">
      ${top.slice(1).map(a => `
        <div class="editorial-subcard" onclick="openArticle(${a.id})" style="cursor:pointer">
          <div style="position:relative">
            <img src="${getArticleImg(a.img)}" alt="${a.title.replace(/"/g, '&quot;')}" loading="lazy" onerror="this.src='img/desa_wisata.png'">
            <button class="bookmark-btn-for-${a.id}" onclick="toggleBookmark(event, ${a.id})" style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.4);border:none;cursor:pointer;width:30px;height:30px;border-radius:50%;display:grid;place-items:center;transition:var(--tr);z-index:2">${bookmarkSvg(a.id, false)}</button>
          </div>
          <div class="esc-body">
            <span class="${badgeClass(a.badge)}">${a.type}</span>
            <h3>${a.title}</h3>
            <div class="esc-meta">
              <span>Oleh ${a.author || 'Redaksi'}</span> · 
              <span>${a.date}</span>
            </div>
          </div>
        </div>
      `).join('')}
    </div>
  `;
}

function renderBreaking() {
  const container = document.getElementById('breakingMarquee');
  if (!container) return;
  const items = ARTICLES.filter(a => a.cat === 'berita').slice(0, 6);
  const html = items.map(a => `<span>🔴 ${a.title}</span>`).join('');
  container.innerHTML = html + html;
}

function renderFilters() {
  document.getElementById('filterTabs').innerHTML = FILTERS.map((f, i) =>
    `<button class="filter-tab${i === 0 ? ' active' : ''}" data-filter="${f.toLowerCase()}">${f}</button>`
  ).join('');
}

function articleCard(a, wide) {
  const safeImg = getArticleImg(a.img);
  return `<article class="article-card visible${wide ? ' wide' : ''}" onclick="openArticle(${a.id})">
    <img src="${safeImg}" alt="${a.title.replace(/"/g, '&quot;')}" loading="lazy" onerror="this.src='img/desa_wisata.png'">
    <div class="article-body">
      <div style="display:flex;justify-content:space-between;align-items:center;width:100%">
        <span class="${badgeClass(a.badge)}" onclick="event.stopPropagation(); selectCategory('${a.cat}')" style="cursor:pointer">${a.type}</span>
        <button class="bookmark-btn-for-${a.id} light-bg" onclick="toggleBookmark(event, ${a.id})" style="background:none;border:none;cursor:pointer;padding:4px;transition:var(--tr);z-index:2" title="Simpan Berita">${bookmarkSvg(a.id, true)}</button>
      </div>
      <h3>${a.title}</h3>
      <p>${a.excerpt}</p>
      <div class="article-meta"><span>Oleh ${a.author}</span> · <span>${a.date}</span></div>
    </div>
  </article>`;
}
let currentFilter = 'semua';
let articlesLimit = 8;

function toggleSectionsVisibility(filter) {
  const isHome = filter === 'semua' || filter === 'all';
  
  const layout = document.querySelector('.main-layout');
  if (layout) {
    if (isHome) {
      layout.classList.remove('filtered-view');
    } else {
      layout.classList.add('filtered-view');
    }
  }
  
  // Collect all home-only sections by their actual IDs in HTML
  const sectionIds = [
    'editorialHeadlineSection', // Berita Utama hero
    'berita',                   // Filter + article grid
    'essay',                    // Essay section
    'opini',                    // Opini section
    'video',                    // Video section
    'foto-section',             // Galeri Foto section
    'nasional',                 // Investigasi section (id="nasional")
    'hikmah',                   // Hikmah section
    'ekonomi',                  // Ekonomi section
    'pendidikan',               // Pendidikan section
    'olahraga',                 // Olahraga section
    'adBottomContainer'         // Bottom ad banner
  ];
  
  sectionIds.forEach(id => {
    const sec = document.getElementById(id);
    if (sec) {
      sec.style.display = isHome ? '' : 'none';
    }
  });
}

function renderArticles(filter = 'semua', loadMore = false) {
  if (loadMore) {
    articlesLimit += 8;
  } else {
    articlesLimit = 8;
  }
  currentFilter = filter;
  
  toggleSectionsVisibility(filter);
  
  const isHome = filter === 'semua' || filter === 'all';
  if (isHome) {
    setActiveBottomNavItem('btnHomeMobile');
  } else if (filter === 'bookmark') {
    setActiveBottomNavItem('btnBookmarkMobile');
  } else {
    setActiveBottomNavItem('btnCatMobile');
  }
  
  const grid = document.getElementById('articleGrid');
  if (!grid) return;
  
  if (filter === 'video') {
    grid.innerHTML = VIDEOS.map(v => `
      <article class="article-card" onclick="showToast('Memutar: ' + '${v.title.substring(0,40)}...')">
        <div style="position:relative">
          <img src="${v.img}" alt="${v.title}">
          <div style="position:absolute;inset:0;display:grid;place-items:center;background:rgba(0,0,0,0.25)"><span style="font-size:2.5rem;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,0.5)">▶</span></div>
        </div>
        <div class="article-body">
          <span class="badge badge-video" style="background:var(--red-600);color:#fff">Video</span>
          <h3>${v.title}</h3>
          <div class="article-meta"><span>👁 ${v.views} views</span></div>
        </div>
      </article>
    `).join('');
    const loadMoreBtn = document.getElementById('loadMoreContainer');
    if (loadMoreBtn) loadMoreBtn.style.display = 'none';
    observeCards();
    return;
  }
  
  let list = ARTICLES;
  
  if (filter === 'bookmark') {
    const bookmarkedIds = getBookmarkedIds();
    list = ARTICLES.filter(a => bookmarkedIds.includes(a.id));
    if (list.length === 0) {
      grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:50px 20px;color:var(--text-muted)">
        <div style="font-size:3rem;margin-bottom:12px">🔖</div>
        <h3>Belum ada berita yang disimpan</h3>
        <p style="margin-top:6px;font-size:.85rem">Klik ikon ☆ pada kartu berita untuk menyimpannya di sini.</p>
      </div>`;
      const loadMoreBtn = document.getElementById('loadMoreContainer');
      if (loadMoreBtn) loadMoreBtn.style.display = 'none';
      return;
    }
  } else if (filter === 'investigasi') {
    list = ARTICLES.filter(a => a.badge === 'investigasi' || a.type === 'investigasi');
  } else if (filter !== 'semua' && filter !== 'all') {
    list = ARTICLES.filter(a => a.type === filter || a.cat === filter);
  }
  
  grid.innerHTML = list.slice(0, articlesLimit).map((a, i) => articleCard(a, i === 0)).join('');
  
  const loadMoreBtn = document.getElementById('loadMoreContainer');
  if (loadMoreBtn) {
    loadMoreBtn.style.display = list.length > articlesLimit ? 'block' : 'none';
  }
  
  observeCards();
}

function renderOpinions() {
  const ops = ARTICLES.filter(a => a.cat === 'opini').slice(0, 4);
  document.getElementById('opinionGrid').innerHTML = ops.map(a => `
    <div class="opinion-card" onclick="openArticle(${a.id})">
      <div class="quote">"</div>
      <span class="${badgeClass(a.badge)}" onclick="event.stopPropagation(); selectCategory('${a.cat}')" style="cursor:pointer">${a.type}</span>
      <h3>${a.title}</h3>
      <p style="font-size:.82rem;color:var(--gray-600)">${a.excerpt}</p>
      <div class="opinion-author">
        <div class="avatar">${a.author.charAt(0)}</div>
        <div><strong style="font-size:.85rem">${a.author}</strong><br><span style="font-size:.75rem;color:var(--gray-400)">${a.date}</span></div>
      </div>
    </div>`).join('');
}

function renderEssayScroll() {
  const items = ARTICLES.filter(a => a.cat === 'essay');
  document.getElementById('essayScroll').innerHTML = items.map(a => `
    <div class="scroll-card" onclick="openArticle(${a.id})">
      <img src="${getArticleImg(a.img)}" alt="${a.title.replace(/"/g, '&quot;')}" loading="lazy" onerror="this.src='img/desa_wisata.png'">
      <div class="sc-body">
        <span class="${badgeClass(a.badge)}" onclick="event.stopPropagation(); selectCategory('${a.cat}')" style="cursor:pointer">${a.type}</span>
        <h4>${a.title}</h4>
        <span style="font-size:.72rem;color:var(--gray-400)">${a.author} · ${a.date}</span>
      </div>
    </div>`).join('');
}

function renderVideos() {
  const v = VIDEOS.slice(0, 4);
  document.getElementById('videoGrid').innerHTML = `
    <div class="video-main" onclick="showToast('Memutar: ' + '${v[0].title.substring(0,40)}...')">
      <img src="${getArticleImg(v[0].img)}" alt="" onerror="this.src='img/desa_wisata.png'">
      <div class="play-btn">▶</div>
      <div class="video-info"><span class="badge badge-berita" onclick="event.stopPropagation(); selectCategory('video')" style="cursor:pointer">Video</span><h3 style="margin-top:8px;font-size:1rem">${v[0].title}</h3><span style="font-size:.78rem;opacity:.8">${v[0].views} views</span></div>
    </div>
    <div class="video-list">${v.slice(1).map(x => `
      <div class="video-item" onclick="showToast('Memutar: ' + '${x.title.substring(0,35)}...')">
        <div class="video-thumb-wrap"><img class="video-thumb" src="${getArticleImg(x.img)}" alt="" onerror="this.src='img/desa_wisata.png'"></div>
        <div><h4 style="font-size:.85rem;line-height:1.35">${x.title}</h4><span style="font-size:.72rem;color:var(--gray-400)">${x.views}</span></div>
      </div>`).join('')}
    </div>`;
}

function renderPhotoSection() {
  const pEl = document.getElementById('photoGrid');
  if (!pEl) return;
  const p = ARTICLES.filter(a => a.cat === 'foto').slice(0, 4);
  if (p.length === 0) return;
  pEl.innerHTML = `
    <div class="photo-main" onclick="openArticle(${p[0].id})">
      <img src="${getArticleImg(p[0].img)}" alt="" onerror="this.src='img/desa_wisata.png'">
      <div class="cam-btn">📷</div>
      <div class="photo-info"><span class="${badgeClass(p[0].badge)}" onclick="event.stopPropagation(); selectCategory('foto')" style="cursor:pointer">${p[0].type}</span><h3 style="margin-top:8px;font-size:1rem">${p[0].title}</h3><span style="font-size:.78rem;opacity:.8">${p[0].date}</span></div>
    </div>
    <div class="photo-list">${p.slice(1).map(x => `
      <div class="photo-item" onclick="openArticle(${x.id})">
        <div class="photo-thumb-wrap"><img class="photo-thumb" src="${getArticleImg(x.img)}" alt="" onerror="this.src='img/desa_wisata.png'"></div>
        <div><h4 style="font-size:.85rem;line-height:1.35">${x.title}</h4><span style="font-size:.72rem;color:var(--gray-400)">${x.date}</span></div>
      </div>`).join('')}
    </div>`;
}

function renderInvestigasi() {
  const inv = ARTICLES.filter(a => a.badge === 'investigasi' || a.title.includes('INVESTIGASI'));
  document.getElementById('investigasiGrid').innerHTML = inv.map(a => articleCard(a, true)).join('');
}

function renderCategoryGrid(id, cat) {
  const el = document.getElementById(id);
  if (!el) return;
  const list = ARTICLES.filter(a => a.cat === cat);
  if (id.includes('Scroll')) {
    el.innerHTML = list.map(a => `<div class="scroll-card" onclick="openArticle(${a.id})"><img src="${getArticleImg(a.img)}" alt="" onerror="this.src='img/desa_wisata.png'"><div class="sc-body"><span class="${badgeClass(a.badge)}" onclick="event.stopPropagation(); selectCategory('${a.cat}')" style="cursor:pointer">${a.type}</span><h4>${a.title}</h4><span style="font-size:.72rem;color:var(--gray-400)">${a.date}</span></div></div>`).join('');
  } else {
    el.innerHTML = list.slice(0, 4).map(a => articleCard(a, false)).join('');
  }
}

function renderArtikelGrid() {
  const el = document.getElementById('artikelGrid');
  if (!el) return;
  const arts = ARTICLES.filter(a => a.type === 'artikel');
  el.innerHTML = arts.map(a => `
    <div class="opinion-card artikel" onclick="openArticle(${a.id})">
      <span class="${badgeClass(a.badge)}" onclick="event.stopPropagation(); selectCategory('${a.cat}')" style="cursor:pointer">Artikel</span>
      <h3 style="margin-top:8px">${a.title}</h3>
      <p style="font-size:.82rem;color:var(--gray-600)">${a.excerpt}</p>
      <div class="opinion-author"><div class="avatar">${a.author.charAt(0)}</div><div><strong style="font-size:.85rem">${a.author}</strong></div></div>
    </div>`).join('');
}

function renderTrending() {
  const sorted = [...ARTICLES].sort((a, b) => parseFloat(b.views) - parseFloat(a.views));
  document.getElementById('trendingList').innerHTML = sorted.slice(0, 7).map((a, i) => `
    <div class="trend-item" onclick="openArticle(${a.id})">
      <span class="trend-num">${i + 1}</span>
      <div><h4>${a.title}</h4><span>👁 ${a.views}</span></div>
    </div>`).join('');
}

function renderTags() {
  const container = document.getElementById('tagCloud');
  if (!container) return;
  const tags = [...new Set(ARTICLES.flatMap(a => a.tags))];
  container.innerHTML = tags.map(t => `<a href="#" onclick="filterByTag('${t}');return false">#${t}</a>`).join('');
}

function renderAgenda() {
  const container = document.getElementById('agendaList');
  if (!container) return;
  container.innerHTML = AGENDA.map(a => `
    <div style="display:flex;gap:12px;padding:8px 0;border-bottom:1px solid var(--gray-100)">
      <strong style="color:var(--green-700);font-size:.85rem;min-width:48px">${a.time}</strong>
      <span style="font-size:.82rem">${a.event}</span>
    </div>`).join('');
}

function renderMobileNav() {
  const mNav = document.getElementById('mobileNav');
  if (mNav) {
    const linksHTML = document.getElementById('catNav').innerHTML.replace(/<ul>|<\/ul>/g, '').replace(/<li>/g, '').replace(/<\/li>/g, '');
    mNav.innerHTML = `
      <div class="mobile-nav-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="folder-icon"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
        Pilih Kategori Berita
      </div>
      <div class="mobile-nav-links">
        ${linksHTML}
      </div>
    `;
    mNav.querySelectorAll('a').forEach(a => a.addEventListener('click', toggleNav));
  }
}

/* ===== ARTICLE MODAL ===== */
/* ===== BOOKMARKS SYSTEM ===== */
let _inMemoryBookmarks = [];

function getBookmarkedIds() {
  try {
    return JSON.parse(localStorage.getItem('ari_bookmarks') || '[]').map(Number);
  } catch (e) {
    return _inMemoryBookmarks;
  }
}

function toggleBookmark(e, id) {
  if (e) e.stopPropagation();
  const numericId = Number(id);
  if (isNaN(numericId)) return;
  let bookmarks = getBookmarkedIds();
  const index = bookmarks.indexOf(numericId);
  if (index > -1) {
    bookmarks.splice(index, 1);
    showToast('Berita dihapus dari simpanan.');
  } else {
    bookmarks.push(numericId);
    showToast('Berita disimpan ke bookmark.');
  }
  
  try {
    localStorage.setItem('ari_bookmarks', JSON.stringify(bookmarks));
  } catch (err) {
    console.error('Gagal menulis ke localStorage:', err);
    _inMemoryBookmarks = bookmarks;
  }
  
  // Update all instances of bookmark icons on the page for this article ID
  document.querySelectorAll(`.bookmark-btn-for-${numericId}`).forEach(btn => {
    const isLightBg = btn.classList.contains('light-bg');
    btn.innerHTML = bookmarkSvg(numericId, isLightBg);
  });
  
  // If we are currently viewing the bookmark category page, re-render it
  if (window.activeCategory === 'bookmark') {
    selectCategory('bookmark');
  }
  
  updateBookmarkCount();
}

function updateBookmarkCount() {
  const count = getBookmarkedIds().length;
  const el = document.getElementById('bookmarkCount');
  if (el) {
    el.textContent = count;
    el.style.display = count > 0 ? 'grid' : 'none';
  }
}

/* ===== XSS SANITIZER ===== */
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/* ===== COMMENTS SYSTEM (DATABASE DRIVEN) ===== */
async function renderComments(articleId) {
  const container = document.getElementById('commentsList');
  if (!container) return;
  container.innerHTML = '<div style="padding:16px 0;color:var(--text-muted);font-size:0.85rem">Memuat komentar...</div>';
  try {
    const res = await fetch('api.php?action=comments&article_id=' + encodeURIComponent(articleId));
    const comments = await res.json();
    if (!Array.isArray(comments) || comments.length === 0) {
      container.innerHTML = '<div style="padding:16px 0;color:var(--text-muted);font-size:0.85rem">Belum ada komentar. Jadilah yang pertama berkomentar!</div>';
      return;
    }
    container.innerHTML = comments.map(c => `
      <div style="padding:14px 0;border-bottom:1px solid var(--border-color);display:flex;gap:12px;align-items:flex-start">
        <div class="avatar" style="width:36px;height:36px;font-size:0.85rem;flex-shrink:0;background:var(--green-700);color:#fff;border-radius:50%;display:grid;place-items:center;font-weight:700">
          ${escapeHtml((c.name || 'P').charAt(0).toUpperCase())}
        </div>
        <div style="flex:1">
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <strong style="font-size:0.88rem;color:var(--text-main)">${escapeHtml(c.name)}</strong>
            <span style="font-size:0.72rem;color:var(--gray-400)">${escapeHtml(c.date || '')}</span>
          </div>
          <p style="font-size:0.85rem;color:var(--text-muted);margin-top:5px;line-height:1.5;white-space:pre-line">${escapeHtml(c.comment || c.text || '')}</p>
        </div>
      </div>
    `).join('');
  } catch (e) {
    console.error('Gagal mengambil komentar:', e);
    container.innerHTML = '<div style="padding:16px 0;color:var(--text-muted);font-size:0.85rem">Belum ada komentar atau gagal terhubung ke server.</div>';
  }
}

window.submitComment = async function(id) {
  const nameEl = document.getElementById('commenterName');
  const textEl = document.getElementById('commenterText');
  if (!nameEl || !textEl) return;
  const name = nameEl.value.trim();
  const text = textEl.value.trim();
  if (!name || !text) {
    showToast('Mohon lengkapi nama dan isi komentar.');
    return;
  }

  const btn = document.querySelector('#commentsList')?.previousElementSibling?.querySelector('button');
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Mengirim...';
  }

  try {
    const formData = new FormData();
    formData.append('article_id', id);
    formData.append('name', name);
    formData.append('comment', text);

    const res = await fetch('api.php?action=add_comment', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      nameEl.value = '';
      textEl.value = '';
      showToast('Komentar berhasil dikirim!');
      await renderComments(id);
    } else {
      showToast(data.error || 'Gagal mengirim komentar.');
    }
  } catch (e) {
    console.error('Gagal kirim komentar:', e);
    showToast('Gagal mengirim komentar. Periksa koneksi server.');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.textContent = 'Kirim Komentar';
    }
  }
};

/* ===== STORIES SYSTEM (MOBILE) ===== */
function renderStories() {
  const container = document.getElementById('storiesContainer');
  if (!container) return;
  const items = ARTICLES.filter(a => a.cat === 'opini' || a.badge === 'investigasi').slice(0, 7);
  container.innerHTML = items.map(a => `
    <div class="story-circle" onclick="openArticle(${a.id})">
      <div class="story-img-wrap">
        <img src="${getArticleImg(a.img)}" alt="${a.title.replace(/"/g, '&quot;')}" onerror="this.src='img/desa_wisata.png'">
      </div>
      <div class="story-label">${(a.author || 'Pucuk').split(' ')[0]}</div>
    </div>
  `).join('');
}

/* ===== SPA ROUTER & NAVIGATION ===== */
let historyStack = [];
window.activeCategory = 'all';

function syncNavHighlights() {
  const currentView = getActiveView();
  
  // Hapus semua kelas active dari catNav desktop dan mobile bottom nav
  document.querySelectorAll('#catNav a').forEach(a => a.classList.remove('active'));
  document.querySelectorAll('.bottom-nav-item').forEach(item => item.classList.remove('active'));
  
  if (currentView === 'homeView') {
    const homeLink = document.querySelector(`#catNav a[data-cat="all"]`);
    if (homeLink) homeLink.classList.add('active');
    setActiveBottomNavItem('btnHomeMobile');
  } else if (currentView === 'searchResultView') {
    setActiveBottomNavItem('btnSearchMobile');
  } else if (currentView === 'categoryView') {
    if (window.activeCategory) {
      const activeLink = document.querySelector(`#catNav a[data-cat="${window.activeCategory}"]`);
      if (activeLink) activeLink.classList.add('active');
      if (window.activeCategory === 'bookmark') {
        setActiveBottomNavItem('btnBookmarkMobile');
      } else {
        setActiveBottomNavItem('btnCatMobile');
      }
    } else {
      setActiveBottomNavItem('btnCatMobile');
    }
  }
}

function showView(viewId) {
  const views = ['homeView', 'articleDetailView', 'categoryView', 'searchResultView'];
  views.forEach(v => {
    const el = document.getElementById(v);
    if (el) el.style.display = 'none';
  });
  
  const target = document.getElementById(viewId);
  if (target) {
    target.style.display = 'block';
    target.querySelectorAll('.article-card').forEach(c => c.classList.add('visible'));
  }
  
  if (viewId === 'homeView') {
    historyStack = [];
    window.activeCategory = 'all';
  }
  
  syncNavHighlights();
  observeCards();
  
  try { window.scrollTo({ top: 0, behavior: 'instant' }); } catch(e) { window.scrollTo(0, 0); }
}

function navigateTo(viewId) {
  const currentView = getActiveView();
  if (currentView && currentView !== viewId) {
    historyStack.push(currentView);
  }
  showView(viewId);
}

function getActiveView() {
  const views = ['homeView', 'articleDetailView', 'categoryView', 'searchResultView'];
  for (const v of views) {
    const el = document.getElementById(v);
    if (el && el.style.display === 'block') return v;
  }
  return 'homeView';
}

function goBack() {
  if (window.history.length > 1 && window.location.hash.startsWith('#article-')) {
    window.history.back();
    return;
  }
  if (historyStack.length > 0) {
    const prevView = historyStack.pop();
    showView(prevView);
  } else {
    showView('homeView');
  }
}

/* ===== ARTICLE DETAILS VIEW ===== */
function openArticle(id) {
  if (!id) return;
  const a = ARTICLES.find(x => Number(x.id) === Number(id));
  if (!a) {
    console.warn('Article not found with id:', id);
    showView('homeView');
    return;
  }
  
  const contentText = a.content || a.excerpt || '';
  const pList = contentText.split('\n\n').filter(Boolean);

  // Ambil artikel terkait yang betul-betul ada di database dan bukan artikel saat ini
  const otherArticles = ARTICLES.filter(x => Number(x.id) !== Number(a.id) && x.type !== 'video' && x.type !== 'foto');
  
  // Prioritaskan artikel rekomendasi editorial yang dipilih Admin (related_article_id)
  let related = null;
  if (a.related_article_id) {
    related = otherArticles.find(x => Number(x.id) === Number(a.related_article_id)) || null;
  }
  // Jika tidak diset manual oleh admin, fallback otomatis sesuai kategori atau rubrik
  if (!related) {
    const sameCategory = otherArticles.filter(x => x.cat === a.cat || x.type === a.type);
    if (sameCategory.length > 0) {
      related = sameCategory[Number(a.id) % sameCategory.length];
    } else if (otherArticles.length > 0) {
      related = otherArticles[Number(a.id) % otherArticles.length];
    }
  }

  const safeTitle = related ? related.title.replace(/"/g, '&quot;') : '';
  const safeImg = getArticleImg(related ? related.img : '');
  const relatedRubrik = related ? (related.type || related.cat || 'Berita') : '';
  const relatedAuthor = related ? (related.author || 'Redaksi') : '';

  const bacaJugaHtml = related ? `
    <div class="baca-juga-card" onclick="openArticle(${related.id}); return false;">
      <div class="baca-juga-top">
        <span class="baca-juga-badge">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
          BACA JUGA
        </span>
        <span class="baca-juga-rubrik">${relatedRubrik}</span>
      </div>
      <div class="baca-juga-body">
        <img src="${safeImg}" alt="${safeTitle}" class="baca-juga-thumb" onerror="this.src='img/desa_wisata.png'">
        <div class="baca-juga-content">
          <a href="?id=${related.id}" class="baca-juga-title" onclick="event.preventDefault(); openArticle(${related.id});">${related.title}</a>
          <div class="baca-juga-footer">
            <span style="display:inline-flex;align-items:center;gap:4px">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              Oleh <b>${relatedAuthor}</b>
            </span>
            <span class="baca-juga-cta">Baca selengkapnya <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></span>
          </div>
        </div>
      </div>
    </div>
  ` : '';

  let paragraphs = '';
  if (pList.length > 1 && bacaJugaHtml) {
    const mid = Math.max(1, Math.floor(pList.length / 2));
    const firstHalf = pList.slice(0, mid).map(p => `<p style="margin-bottom:16px;line-height:1.85">${p}</p>`).join('');
    const secondHalf = pList.slice(mid).map(p => `<p style="margin-bottom:16px;line-height:1.85">${p}</p>`).join('');
    paragraphs = firstHalf + bacaJugaHtml + secondHalf;
  } else if (pList.length > 0) {
    paragraphs = pList.map(p => `<p style="margin-bottom:16px;line-height:1.85">${p}</p>`).join('') + (bacaJugaHtml ? bacaJugaHtml : '');
  } else {
    paragraphs = `<p>${contentText}</p>` + (bacaJugaHtml ? bacaJugaHtml : '');
  }
  const tagsList = Array.isArray(a.tags) ? a.tags : [];
  const imgSrc = getArticleImg(a.img);
  const authorName = a.author || 'Redaksi';
  const pubDate = a.date || '';
  const badgeName = a.badge || 'berita';
  const typeName = a.type || 'Berita';
  
  const container = document.getElementById('articleDetailView');
  if (!container) return;

  container.innerHTML = `
    <div style="margin-bottom:20px">
      <button class="btn-back-premium" onclick="goBack()" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;background:var(--bg-card);border:1px solid var(--border-color);border-radius:8px;cursor:pointer;font-weight:600;font-size:0.88rem;color:var(--text-main);box-shadow:var(--shadow)">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Kembali
      </button>
    </div>

    <article class="modal-article" style="transform:none;box-shadow:var(--shadow);border:1px solid var(--border-color);background:var(--bg-card);border-radius:12px;overflow:hidden">
      <div class="modal-content article-modal-inner">
        <span class="${badgeClass(badgeName)}" onclick="event.stopPropagation(); selectCategory('${a.cat || 'berita'}')" style="cursor:pointer;display:inline-block;margin-bottom:12px">${typeName}</span>
        <h1 style="margin:8px 0 14px;font-size:clamp(1.4rem,3vw,2rem);color:var(--text-main);line-height:1.35">${a.title}</h1>
        <div class="article-meta" style="margin-bottom:22px;font-size:0.84rem;color:var(--text-muted)"><span>Oleh <b>${authorName}</b></span> · <span>${pubDate}</span> · <span>${a.views || '1K'} pembaca</span></div>
        
        <!-- Hero Image -->
        <div style="position:relative;margin-bottom:24px;border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
          <img src="${imgSrc}" alt="${a.title.replace(/"/g, '&quot;')}" style="width:100%;aspect-ratio:16/9;height:auto;object-fit:cover;display:block" onerror="this.src='img/desa_wisata.png'">
          <button id="modalBookmarkBtn" class="bookmark-btn-for-${a.id} light-bg" onclick="toggleBookmark(event, ${a.id})" style="position:absolute;top:16px;right:16px;background:var(--bg-card);border:1px solid var(--border-color);cursor:pointer;width:40px;height:40px;border-radius:50%;display:grid;place-items:center;box-shadow:var(--shadow);z-index:10">${bookmarkSvg(a.id, true)}</button>
        </div>

        ${a.excerpt ? `<p class="lead" style="font-size:1.08rem;color:var(--text-muted);line-height:1.75;border-left:4px solid var(--green-600);padding-left:16px;margin-bottom:24px;font-style:italic">${a.excerpt}</p>` : ''}
        <div class="body-text" style="font-size:1.02rem;color:var(--text-main);line-height:1.85">${paragraphs}</div>
        
        ${tagsList.length > 0 ? `<div style="margin-top:24px;display:flex;flex-wrap:wrap;gap:8px">${tagsList.map(t => `<span style="padding:4px 12px;background:var(--gray-100);color:var(--green-700);border-radius:50px;font-size:0.78rem;font-weight:600">#${t}</span>`).join('')}</div>` : ''}
        
        <!-- Share Bar -->
        <div class="share-bar" style="margin-top:30px;padding-top:20px;border-top:1px solid var(--border-color);display:flex;gap:10px;flex-wrap:wrap;align-items:center">
          <span style="font-size:0.85rem;font-weight:700;color:var(--text-main);margin-right:4px">Bagikan Berita:</span>
          <button onclick="shareArticleWhatsApp('${a.title.replace(/'/g, "\\'")}', ${a.id})" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1px solid #25d366;color:#fff;background:#25d366;cursor:pointer;font-size:0.84rem;font-weight:600;box-shadow:0 2px 8px rgba(37,211,102,0.25)">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            WhatsApp
          </button>
          <button onclick="shareArticleNative('${a.title.replace(/'/g, "\\'")}', ${a.id})" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1px solid var(--green-600);color:#fff;background:var(--green-700);cursor:pointer;font-size:0.84rem;font-weight:600">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
            Bagikan
          </button>
          <button onclick="copyArticleLink(${a.id})" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);cursor:pointer;font-size:0.84rem;font-weight:600">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
            Salin Link
          </button>
        </div>

        <!-- Diskusi / Komentar -->
        <div style="margin-top:36px;border-top:1px solid var(--border-color);padding-top:24px">
          <h3 style="margin-bottom:16px;color:var(--green-700)">Diskusi & Komentar</h3>
          <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;background:var(--gray-100);padding:16px;border-radius:10px;border:1px solid var(--border-color)">
            <input type="text" id="commenterName" placeholder="Nama Anda" style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:6px;font-size:0.85rem;background:var(--bg-card);color:var(--text-main)">
            <textarea id="commenterText" placeholder="Tulis komentar..." rows="3" style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:6px;font-size:0.85rem;font-family:inherit;background:var(--bg-card);color:var(--text-main)"></textarea>
            <button onclick="submitComment(${a.id})" style="align-self:flex-end;padding:8px 18px;background:var(--green-700);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:0.82rem">Kirim Komentar</button>
          </div>
          <div id="commentsList"></div>
        </div>
      </div>
    </article>`;
  
  showView('articleDetailView');
  try { window.scrollTo({ top: 0, behavior: 'instant' }); } catch(e) { window.scrollTo(0, 0); }
  renderComments(a.id);
  
  try {
    document.title = a.title + ' — Pucuk Pena';
    const cleanUrl = window.location.origin + window.location.pathname + '?id=' + a.id;
    if (window.location.search !== '?id=' + a.id) {
      history.pushState({ articleId: a.id }, a.title, cleanUrl);
    }
  } catch(e) {}
}

/* ===== DEDICATED CATEGORY VIEW ===== */
function selectCategory(cat) {
  if (!cat) return;
  cat = cat.toLowerCase();
  
  if (cat === 'all' || cat === 'beranda') {
    showView('homeView');
    document.querySelectorAll('#catNav a').forEach(a => a.classList.remove('active'));
    const homeLink = document.querySelector(`#catNav a[data-cat="all"]`);
    if (homeLink) homeLink.classList.add('active');
    setActiveBottomNavItem('btnHomeMobile');
    return;
  }
  
  let list = ARTICLES.filter(a =>
    a.cat === cat ||
    a.type === cat ||
    a.badge === cat ||
    (a.cat && a.cat.toLowerCase() === cat) ||
    (a.type && a.type.toLowerCase() === cat) ||
    (a.badge && a.badge.toLowerCase() === cat) ||
    (cat === 'investigasi' && (a.badge === 'investigasi' || a.type === 'investigasi'))
  );
  
  if (cat === 'bookmark') {
    const bookmarkedIds = getBookmarkedIds();
    list = ARTICLES.filter(a => bookmarkedIds.includes(a.id));
  }
  
  window.activeCategory = cat;
  
  const catNames = {
    'berita': { title: 'Berita Utama', desc: 'Laporan berita utama terkini, teraktual, dan terpercaya dari seluruh penjuru nusantara.' },
    'essay': { title: 'Essay', desc: 'Kumpulan esai sastra, refleksi mendalam, dan tulisan naratif tentang budaya.' },
    'opini': { title: 'Opini', desc: 'Kolom analisis kritis, sumbangsih pemikiran, dan opini tajam dari para pakar.' },
    'pendidikan': { title: 'Pendidikan', desc: 'Artikel, ulasan kebijakan, dan kabar dunia pendidikan serta peningkatan literasi.' },
    'olahraga': { title: 'Olahraga', desc: 'Kabar pertandingan terbaru, profil atlet, dan liputan berbagai event olahraga nasional dan dunia.' },
    'ekonomi': { title: 'Ekonomi', desc: 'Ulasan finansial, dinamika bisnis, pasar modal, serta analisis ekonomi terkini.' },
    'hikmah': { title: 'Hikmah', desc: 'Renungan nilai-nilai kehidupan, spiritualitas, khazanah budaya, dan moralitas.' },
    'foto': { title: 'Foto', desc: 'Rekaman visual dan cerita di balik lensa kamera para jurnalis Pucuk Pena.' },
    'bookmark': { title: 'Berita Disimpan', desc: 'Daftar artikel pilihan Anda yang disimpan untuk dibaca nanti.' },
    'video': { title: 'Pucuk Pena TV', desc: 'Video liputan khusus, wawancara mendalam, dan dokumenter eksklusif.' }
  };
  
  const info = catNames[cat] || { title: cat.toUpperCase(), desc: 'Arsip artikel jurnalisme Pucuk Pena.' };
  const container = document.getElementById('categoryView');
  if (!container) return;
  
  if (cat === 'video') {
    const featured = VIDEOS[0];
    const rest = VIDEOS.slice(1);
    
    const featuredHTML = `
      <div class="editorial-headline" onclick="showToast('Memutar: ' + '${featured.title.substring(0,40)}...')" style="margin-bottom:30px;cursor:pointer;position:relative">
        <img src="${featured.img}" alt="${featured.title}">
        <div style="position:absolute;inset:0;display:grid;place-items:center;background:rgba(0,0,0,0.15)">
          <span style="font-size:4.5rem;color:#fff;text-shadow:0 4px 15px rgba(0,0,0,0.6);transition:transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">▶</span>
        </div>
        <div class="eh-overlay">
          <span class="badge badge-video" style="background:var(--red-600);color:#fff;margin-bottom:8px">Video Pilihan</span>
          <h2>${featured.title}</h2>
          <div class="eh-meta">
            <span>👁 ${featured.views} views</span>
          </div>
        </div>
      </div>
    `;
    
    const gridHTML = `
      <div class="article-grid">
        ${rest.map(v => `
          <article class="article-card visible" onclick="showToast('Memutar: ' + '${v.title.substring(0,40)}...')">
            <div style="position:relative">
              <img src="${v.img}" alt="${v.title}" style="width:100%;height:200px;object-fit:cover;border-radius:12px 12px 0 0">
              <div style="position:absolute;inset:0;display:grid;place-items:center;background:rgba(0,0,0,0.25);border-radius:12px 12px 0 0">
                <span style="font-size:2.5rem;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,0.5)">▶</span>
              </div>
            </div>
            <div class="article-body">
              <span class="badge badge-video" style="background:var(--red-600);color:#fff">Video</span>
              <h3>${v.title}</h3>
              <div class="article-meta"><span>👁 ${v.views} views</span></div>
            </div>
          </article>
        `).join('')}
      </div>
    `;
    
    container.innerHTML = `
      <button class="btn-back-premium" onclick="goBack()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Kembali
      </button>
      
      <div class="category-header">
        <h1>${info.title}</h1>
        <p>${info.desc}</p>
      </div>

      ${featuredHTML}
      ${gridHTML}
    `;
    
    navigateTo('categoryView');
    container.querySelectorAll('.article-card').forEach(c => c.classList.add('visible'));
    observeCards();
    return;
  }
  
  if (list.length === 0) {
    container.innerHTML = `
      <button class="btn-back-premium" onclick="goBack()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Kembali
      </button>
      <div class="category-header">
        <h1>${info.title}</h1>
        <p>${info.desc}</p>
      </div>
      <p style="text-align:center;padding:80px 20px;color:var(--text-muted)">Belum ada berita di kategori ini.</p>
    `;
  } else {
    container.innerHTML = `
      <button class="btn-back-premium" onclick="goBack()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Kembali
      </button>
      
      <div class="category-header">
        <h1>${info.title}</h1>
        <p>${info.desc}</p>
      </div>

      <div class="grid-4-cols">
        ${list.map(a => articleCard(a, false)).join('')}
      </div>
    `;
  }
  
  navigateTo('categoryView');
  container.querySelectorAll('.article-card').forEach(c => c.classList.add('visible'));
  observeCards();
  
  document.querySelectorAll('#catNav a').forEach(a => a.classList.remove('active'));
  const activeLink = document.querySelector(`#catNav a[data-cat="${cat}"]`);
  if (activeLink) activeLink.classList.add('active');
}

/* ===== IN-PAGE SEARCH ===== */
function openSearch() {
  const container = document.getElementById('searchResultView');
  container.innerHTML = `
    <button class="btn-back-premium" onclick="goBack()">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
      Kembali
    </button>

    <div class="search-hero-box">
      <h2>Pencarian Pucuk Pena</h2>
      <div class="search-input-large-wrap">
        <input type="text" id="searchInputLarge" class="search-input-large" placeholder="Cari berita, opini, essay, artikel..." oninput="doInPageSearch(this.value.toLowerCase())">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
      </div>
    </div>

    <div id="inPageSearchResults" class="article-grid"></div>
  `;
  navigateTo('searchResultView');
  setTimeout(() => {
    const input = document.getElementById('searchInputLarge');
    if (input) input.focus();
  }, 100);
}

function doInPageSearch(q) {
  const resultsContainer = document.getElementById('inPageSearchResults');
  if (!resultsContainer) return;
  if (!q.trim() || q.length < 2) {
    resultsContainer.innerHTML = '<p style="text-align:center;padding:40px 20px;color:var(--text-muted)">Silakan ketik minimal 2 karakter untuk mencari.</p>';
    return;
  }
  const results = ARTICLES.filter(a =>
    a.title.toLowerCase().includes(q) || a.excerpt.toLowerCase().includes(q) || a.author.toLowerCase().includes(q) || a.tags.some(t => t.toLowerCase().includes(q))
  );
  if (results.length === 0) {
    resultsContainer.innerHTML = '<p style="text-align:center;padding:40px 20px;color:var(--text-muted)">Tidak ditemukan hasil pencarian yang cocok.</p>';
  } else {
    resultsContainer.innerHTML = results.map(a => articleCard(a, false)).join('');
    observeCards();
  }
}

/* ===== FILTER BY TAG ===== */
function filterByTag(tag) {
  const found = ARTICLES.filter(a => a.tags.includes(tag));
  const container = document.getElementById('categoryView');
  container.innerHTML = `
    <button class="btn-back-premium" onclick="goBack()">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
      Kembali
    </button>
    <div class="category-header">
      <h1>Tag: #${tag}</h1>
      <p>Menampilkan semua artikel dengan tagar #${tag}.</p>
    </div>
    <div class="article-grid">
      ${found.map(a => articleCard(a, false)).join('')}
    </div>
  `;
  navigateTo('categoryView');
  observeCards();
  showToast('Menampilkan tag: #' + tag);
}

// Expose SPA Router functions globally
window.selectCategory = selectCategory;
window.openArticle = openArticle;
window.openSearch = openSearch;
window.goBack = goBack;
window.showView = showView;
window.toggleBookmark = toggleBookmark;

const _filterTabsEl = document.getElementById('filterTabs');
if (_filterTabsEl) {
  _filterTabsEl.addEventListener('click', e => {
  if (!e.target.classList.contains('filter-tab')) return;
  const filterVal = e.target.dataset.filter;
  if (filterVal === 'semua' || filterVal === 'all') {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    e.target.classList.add('active');
    renderArticles('semua');
    showView('homeView');
  } else {
    selectCategory(filterVal);
  }
});
}

const _openSearchEl = document.getElementById('openSearch');
if (_openSearchEl) {
  _openSearchEl.addEventListener('click', () => {
    openSearch();
  });
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    goBack();
  }
});

/* ===== ADS MANAGEMENT ===== */
document.querySelectorAll('.ad-banner, .ad-native').forEach(ad => {
  ad.addEventListener('click', (e) => {
    if (e.target.classList.contains('ad-opt')) return;
    showToast('Terima kasih! Mengarahkan ke halaman iklan...');
  });
});

setTimeout(() => document.getElementById('stickyAd').classList.add('show'), 4000);
document.getElementById('closeStickyAd').addEventListener('click', e => {
  e.stopPropagation();
  document.getElementById('stickyAd').classList.remove('show');
});

window.dismissAd = function(el) {
  const wrap = el.closest('.ad-wrap');
  if (wrap) {
    wrap.style.transition = 'opacity 0.3s ease';
    wrap.style.opacity = '0';
    setTimeout(() => wrap.style.display = 'none', 300);
    showToast('Iklan disembunyikan untuk sesi ini.');
  }
};

window.reportAd = function(el) {
  showToast('Iklan telah dilaporkan. Terima kasih atas masukan Anda.');
  const wrap = el.closest('.ad-wrap');
  if (wrap) {
    wrap.style.opacity = '0.3';
    wrap.style.pointerEvents = 'none';
  }
};


/* ===== CATEGORY NAVIGATION SYSTEM ===== */
document.getElementById('catNav').addEventListener('click', function(e) {
  const link = e.target.closest('a');
  if (!link) return;
  const cat = link.dataset.cat;
  if (cat) {
    e.preventDefault();
    selectCategory(cat);
    showToast('Menyaring kategori: ' + cat.toUpperCase());
  }
});

const _mobileNavEl = document.getElementById('mobileNav');
if (_mobileNavEl) {
  _mobileNavEl.addEventListener('click', function(e) {
  const link = e.target.closest('a');
  if (!link) return;
  const cat = link.dataset.cat;
  if (cat) {
    e.preventDefault();
    selectCategory(cat);
    showToast('Menyaring kategori: ' + cat.toUpperCase());
  }
});
}

/* ===== LOAD MORE & SEE ALL NEWS ===== */
const btnLoadMore = document.getElementById('btnLoadMore');
if (btnLoadMore) {
  btnLoadMore.addEventListener('click', () => {
    renderArticles(currentFilter, true);
  });
}

const seeAllNews = document.getElementById('seeAllNews');
if (seeAllNews) {
  seeAllNews.addEventListener('click', (e) => {
    e.preventDefault();
    articlesLimit = ARTICLES.length;
    renderArticles(currentFilter, true);
    const beritaSec = document.getElementById('berita');
    if (beritaSec) beritaSec.scrollIntoView({ behavior: 'smooth' });
  });
}

const sunIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="theme-icon"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`;
const moonIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="theme-icon"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;

/* ===== DARK MODE THEME ===== */
const toggleDarkModeBtn = document.getElementById('toggleDarkMode');
if (toggleDarkModeBtn) {
  let savedTheme = 'light';
  try {
    savedTheme = localStorage.getItem('theme') || 'light';
  } catch (e) {}

  if (savedTheme === 'dark') {
    document.body.classList.add('dark-theme');
    toggleDarkModeBtn.innerHTML = sunIcon;
  } else {
    toggleDarkModeBtn.innerHTML = moonIcon;
  }
  toggleDarkModeBtn.addEventListener('click', () => {
    document.body.classList.toggle('dark-theme');
    const isDark = document.body.classList.contains('dark-theme');
    toggleDarkModeBtn.innerHTML = isDark ? sunIcon : moonIcon;
    try {
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
    } catch (e) {}
    showToast(isDark ? 'Mode Gelap diaktifkan' : 'Mode Terang diaktifkan');
  });
}

/* ===== MOBILE BOTTOM NAVIGATION EVENTS ===== */
const btnHomeMobile = document.getElementById('btnHomeMobile');
if (btnHomeMobile) {
  btnHomeMobile.addEventListener('click', (e) => {
    e.preventDefault();
    selectCategory('all');
  });
}

const btnCatMobile = document.getElementById('btnCatMobile');
if (btnCatMobile) {
  btnCatMobile.addEventListener('click', (e) => {
    e.preventDefault();
    setActiveBottomNavItem('btnCatMobile');
    toggleNav();
  });
}

const btnSearchMobile = document.getElementById('btnSearchMobile');
if (btnSearchMobile) {
  btnSearchMobile.addEventListener('click', (e) => {
    e.preventDefault();
    setActiveBottomNavItem('btnSearchMobile');
    openSearch();
  });
}

const btnBookmarkMobile = document.getElementById('btnBookmarkMobile');
if (btnBookmarkMobile) {
  btnBookmarkMobile.addEventListener('click', (e) => {
    e.preventDefault();
    setActiveBottomNavItem('btnBookmarkMobile');
    selectCategory('bookmark');
    showToast('Menampilkan berita disimpan.');
  });
}

const btnMenuMobile = document.getElementById('btnMenuMobile');
if (btnMenuMobile) {
  btnMenuMobile.addEventListener('click', (e) => {
    e.preventDefault();
    setActiveBottomNavItem('btnMenuMobile');
    toggleNav();
  });
}

function setActiveBottomNavItem(id) {
  document.querySelectorAll('.bottom-nav-item').forEach(item => item.classList.remove('active'));
  const el = document.getElementById(id);
  if (el) el.classList.add('active');
}

/* ===== MOBILE NAV ===== */
const hamburger = document.getElementById('hamburger');
const mobileNav = document.getElementById('mobileNav');
const navBackdrop = document.getElementById('navBackdrop');
function toggleNav() {
  if (hamburger) hamburger.classList.toggle('active');
  if (mobileNav) mobileNav.classList.toggle('open');
  if (navBackdrop) navBackdrop.classList.toggle('open');
  document.body.style.overflow = (mobileNav && mobileNav.classList.contains('open')) ? 'hidden' : '';
}
if (hamburger) hamburger.addEventListener('click', toggleNav);
if (navBackdrop) navBackdrop.addEventListener('click', toggleNav);

/* ===== SCROLL ===== */
window.addEventListener('scroll', () => {
  const header = document.getElementById('header');
  if (header) header.classList.toggle('scrolled', scrollY > 40);
  const backTop = document.getElementById('backTop');
  if (backTop) backTop.classList.toggle('visible', scrollY > 500);
});
const backTopBtn = document.getElementById('backTop');
if (backTopBtn) {
  backTopBtn.addEventListener('click', () => scrollTo({ top: 0, behavior: 'smooth' }));
}

/* ===== COUNTERS ===== */
function animateCounters() {
  document.querySelectorAll('[data-count]').forEach(el => {
    const target = +el.dataset.count;
    let cur = 0;
    const step = target / 80;
    const t = setInterval(() => {
      cur += step;
      if (cur >= target) { cur = target; clearInterval(t); }
      el.textContent = Math.floor(cur).toLocaleString('id-ID') + (target > 100 ? '+' : '');
    }, 20);
  });
}
const statsBarEl = document.querySelector('.stats-bar');
if (statsBarEl) {
  const statsObs = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) { animateCounters(); statsObs.disconnect(); }
  }, { threshold: 0.3 });
  statsObs.observe(statsBarEl);
}

/* ===== CARD REVEAL ===== */
function observeCards() {
  document.querySelectorAll('.article-card').forEach(c => c.classList.add('visible'));
  if (typeof IntersectionObserver !== 'undefined') {
    try {
      const obs = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
      }, { threshold: 0.05 });
      document.querySelectorAll('.article-card').forEach(c => obs.observe(c));
    } catch(e) {}
  }
}

/* ===== TOAST ===== */
function showToast(msg) {
  const t = document.getElementById('toast');
  if (!t) return; // null guard agar tidak crash
  t.textContent = msg;
  t.style.opacity = '1';
  t.style.transform = 'translateX(-50%) translateY(0)';
  clearTimeout(t._hideTimer);
  t._hideTimer = setTimeout(() => {
    t.style.opacity = '0';
    t.style.transform = 'translateX(-50%) translateY(80px)';
  }, 3200);
}

/* ===== NEWSLETTER ===== */
// Newsletter subscription handlers removed
window.subscribeFooter = function() {};

/* ===== DATE ===== */
const dateEl = document.getElementById('currentDate');
if (dateEl) {
  const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  const now = new Date();
  dateEl.textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}


/* ===== ADS RENDERING (3 MANUAL + 2 GOOGLE ADS) ===== */
function renderAds() {
  // 1. Leaderboard (Manual / Google Ads)
  const lbEl = document.getElementById('adLeaderboard');
  if (lbEl && ADS.leaderboard) {
    const ad = ADS.leaderboard;
    if (ad.type === 'google' && ad.google_client && !ad.google_client.includes('XXXX')) {
      lbEl.innerHTML = `<ins class="adsbygoogle" style="display:block;width:100%;max-width:970px;height:90px" data-ad-client="${ad.google_client}" data-ad-slot="${ad.google_slot}"></ins>`;
      try { (adsbygoogle = window.adsbygoogle || []).push({}); } catch(e) {}
    } else if (ad.image || ad.img) {
      const imgSrc = ad.image || ad.img;
      lbEl.innerHTML = `<div onclick="if('${ad.url || ''}')window.open('${ad.url}','_blank')" style="cursor:pointer;width:100%;text-align:center;padding:4px 0">
        <img src="${imgSrc}" alt="${escapeHtml(ad.name || 'Iklan')}" style="max-width:100%;height:auto;max-height:110px;border-radius:8px;object-fit:contain;margin:0 auto;display:block;box-shadow:0 2px 8px rgba(0,0,0,0.06)">
      </div>`;
    } else if (ad.content) {
      lbEl.innerHTML = `<div onclick="if('${ad.url || ''}')window.open('${ad.url}','_blank')" style="cursor:pointer;width:100%;text-align:center;padding:10px">${ad.content}</div>`;
    }
  }

  // 2. Native 1 In-feed
  const n1El = document.getElementById('adNative1');
  if (n1El && ADS.native1) {
    renderNativeAd(n1El, ADS.native1);
  }

  // 3. Native 2 Sidebar
  const n2El = document.getElementById('adNative2');
  if (n2El && ADS.native2) {
    renderNativeAd(n2El, ADS.native2);
  }

  // 4. Google Rectangle (300x250)
  const rectEl = document.getElementById('adBottomBanner');
  if (rectEl && ADS.google_rectangle) {
    const ad = ADS.google_rectangle;
    if (ad.type === 'google' && ad.google_client && !ad.google_client.includes('XXXX')) {
      rectEl.innerHTML = `<ins class="adsbygoogle" style="display:block;width:300px;height:250px;margin:0 auto" data-ad-client="${ad.google_client}" data-ad-slot="${ad.google_slot}"></ins>`;
      try { (adsbygoogle = window.adsbygoogle || []).push({}); } catch(e) {}
    }
  }

  // 5. Google Sticky Bottom (728x60)
  const stickyBannerEl = document.querySelector('#stickyAd .ad-banner');
  if (stickyBannerEl && ADS.google_sticky) {
    const ad = ADS.google_sticky;
    if (ad.type === 'google' && ad.google_client && !ad.google_client.includes('XXXX')) {
      stickyBannerEl.innerHTML = `<ins class="adsbygoogle" style="display:block;width:100%;max-width:728px;height:60px;margin:0 auto" data-ad-client="${ad.google_client}" data-ad-slot="${ad.google_slot}"></ins>`;
      try { (adsbygoogle = window.adsbygoogle || []).push({}); } catch(e) {}
    }
  }
}

function renderNativeAd(el, ad) {
  if (!ad) return;
  const imgSrc = ad.image || ad.img;
  if (imgSrc) {
    el.innerHTML = `
      <div style="width:100%;cursor:pointer;overflow:hidden;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.06);transition:transform 0.2s ease" onmouseover="this.style.transform='scale(1.01)'" onmouseout="this.style.transform='scale(1)'">
        <img src="${imgSrc}" alt="${escapeHtml(ad.name || 'Iklan')}" style="width:100%;height:auto;display:block;border-radius:10px;object-fit:cover">
      </div>`;
    el.style.cursor = 'pointer';
    if (ad.url) {
      el.onclick = () => window.open(ad.url, '_blank');
    }
    return;
  }
  const parts = (ad.content || '').split('|');
  if (parts.length >= 4) {
    const [emoji, title, desc, cta] = parts;
    el.innerHTML = `
      <div class="ad-thumb" style="display:grid;place-items:center;width:48px;height:48px;background:rgba(5,150,105,0.08);border-radius:10px;flex-shrink:0;color:var(--green-700)">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      </div>
      <div style="flex:1">
        <h4 style="margin:0 0 4px;font-size:0.95rem;font-weight:700;color:var(--text-primary)">${escapeHtml(title)}</h4>
        <p style="margin:0 0 8px;font-size:0.83rem;color:var(--text-secondary);line-height:1.4">${escapeHtml(desc)}</p>
        <span class="ad-cta" style="display:inline-block;padding:4px 12px;background:var(--green-600);color:#fff;border-radius:6px;font-size:0.75rem;font-weight:600">${escapeHtml(cta)}</span>
      </div>`;
  } else if (ad.content) {
    el.innerHTML = `<div style="padding:12px">${ad.content}</div>`;
  }
  el.style.cursor = 'pointer';
  if (ad.url) {
    el.onclick = () => window.open(ad.url, '_blank');
  }
}

/* ===== DYNAMIC APPLICATION INITIALIZER ===== */

/* ===== SYNCHRONOUS APP INITIALIZATION ===== */
function initApp() {
  renderBreaking();
  renderBeritaUtama();
  renderLatestColumn();
  renderFilters();
  renderOpinions();
  renderEssayScroll();
  renderVideos();
  renderPhotoSection();
  renderInvestigasi();
  renderCategoryGrid('hikmahScroll', 'hikmah');
  renderCategoryGrid('ekonomiGrid', 'ekonomi');
  renderCategoryGrid('pendidikanGrid', 'pendidikan');
  renderCategoryGrid('olahragaScroll', 'olahraga');
  renderTrending();
  renderTags();
  renderAgenda();
  renderMobileNav();
  renderStories();
  renderArticles();
  renderAds();
  updateBookmarkCount();
  observeCards();

  // Route according to initial URL hash or query param
  handleUrlHash();
}

function handleUrlHash() {
  const hash = window.location.hash.replace('#', '').trim();
  if (hash) {
    if (hash.startsWith('article-')) {
      const artId = hash.replace('article-', '');
      if (artId) {
        openArticle(artId);
        return;
      }
    }
    const validCats = ['berita', 'essay', 'opini', 'pendidikan', 'olahraga', 'ekonomi', 'hikmah', 'foto', 'video', 'investigasi', 'bookmark', 'beranda', 'all'];
    if (validCats.includes(hash.toLowerCase())) {
      selectCategory(hash.toLowerCase() === 'beranda' ? 'all' : hash.toLowerCase());
      return;
    }
  }
  
  // Support ?id=XXX or ?article=XXX
  try {
    const urlParams = new URLSearchParams(window.location.search);
    const paramId = urlParams.get('id') || urlParams.get('article');
    if (paramId) {
      openArticle(paramId);
      return;
    }
  } catch(e) {}

  showView('homeView');
}

window.addEventListener('hashchange', handleUrlHash);
window.addEventListener('popstate', handleUrlHash);

// Start app immediately
initApp();

// Ad options
document.querySelectorAll('.ad-label').forEach(el => {
  el.innerHTML = 'Iklan · <span class="ad-opt" onclick="dismissAd(this)">Sembunyikan</span> · <span class="ad-opt" onclick="reportAd(this)">Laporkan</span>';
});

</script>
</body>
</html>
