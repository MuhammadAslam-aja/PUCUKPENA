<?php
// api.php — REST API JSON untuk frontend Pucuk Pena
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/includes/db.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── GET ARTICLES ──────────────────────────────────────────────────────────
    case 'articles':
        $type = $_GET['type'] ?? null;
        $cat  = $_GET['cat'] ?? null;
        $status = 'published';

        $where = ["a.status = :status"];
        $params = [':status' => $status];

        if ($type) {
            $where[] = "a.type = :type";
            $params[':type'] = $type;
        }
        if ($cat) {
            $where[] = "a.cat = :cat";
            $params[':cat'] = $cat;
        }

        $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';
        $sql = "SELECT * FROM articles a WHERE " . implode(' AND ', $where) . " ORDER BY a.id " . $order;
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Format untuk kompatibilitas dengan JS frontend
        $out = [];
        foreach ($rows as $r) {
            $tags = json_decode($r['tags'] ?? '[]', true);
            if (!is_array($tags)) $tags = [];
            $out[] = [
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
            ];
        }
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        break;

    // ── GET ADS ───────────────────────────────────────────────────────────────
    case 'ads':
        $stmt = getDB()->query("SELECT * FROM ads WHERE active = 1 ORDER BY id ASC");
        $rows = $stmt->fetchAll();
        $ads = [];
        foreach ($rows as $r) {
            $ads[$r['slot']] = [
                'id'            => (int)$r['id'],
                'name'          => $r['name'],
                'type'          => $r['type'],
                'slot'          => $r['slot'],
                'content'       => $r['content'],
                'google_client' => $r['google_client'],
                'google_slot'   => $r['google_slot'],
                'url'           => $r['url'],
            ];
        }
        echo json_encode($ads, JSON_UNESCAPED_UNICODE);
        break;

    // ── GET BREAKING NEWS ─────────────────────────────────────────────────────
    case 'breaking':
        $stmt = getDB()->query("SELECT text FROM breaking_news WHERE active = 1 ORDER BY sort_order ASC, id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        break;

    // ── ADMIN: SAVE ARTICLE (POST) ───────────────────────────────────────────
    case 'save_article':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        }
        session_start();
        if (empty($_SESSION['admin_logged_in'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }

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
        $img     = trim($_POST['img'] ?? '');
        $tagsRaw = trim($_POST['tags'] ?? '');
        $status  = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

        // Parse tags: "Tag1, Tag2" → JSON array
        $tagsArr = array_filter(array_map('trim', explode(',', $tagsRaw)));
        $tagsJson = json_encode(array_values($tagsArr), JSON_UNESCAPED_UNICODE);

        if (empty($title)) {
            echo json_encode(['error' => 'Judul tidak boleh kosong']);
            break;
        }

        $db = getDB();
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE articles SET type=?, cat=?, badge=?, title=?, excerpt=?, content=?, author=?, date_display=?, views=?, img=?, tags=?, status=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$type, $cat, $badge, $title, $excerpt, $content, $author, $date, $views, $img, $tagsJson, $status, $id]);
            echo json_encode(['success' => true, 'id' => $id, 'action' => 'updated']);
        } else {
            $stmt = $db->prepare("INSERT INTO articles (type, cat, badge, title, excerpt, content, author, date_display, views, img, tags, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$type, $cat, $badge, $title, $excerpt, $content, $author, $date, $views, $img, $tagsJson, $status]);
            $newId = (int)$db->lastInsertId();
            echo json_encode(['success' => true, 'id' => $newId, 'action' => 'created']);
        }
        break;

    // ── ADMIN: DELETE ARTICLE ─────────────────────────────────────────────────
    case 'delete_article':
        session_start();
        if (empty($_SESSION['admin_logged_in'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id > 0) {
            getDB()->prepare("DELETE FROM articles WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'ID tidak valid']);
        }
        break;

    // ── ADMIN: SAVE ADS ────────────────────────────────────────────────────────
    case 'save_ad':
        session_start();
        if (empty($_SESSION['admin_logged_in'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            break;
        }
        $id     = (int)($_POST['id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $type   = ($_POST['type'] ?? 'manual') === 'google' ? 'google' : 'manual';
        $slot   = trim($_POST['slot'] ?? '');
        $cnt    = trim($_POST['content'] ?? '');
        $gclt   = trim($_POST['google_client'] ?? '');
        $gslot  = trim($_POST['google_slot'] ?? '');
        $url    = trim($_POST['url'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;

        $db = getDB();
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE ads SET name=?, type=?, slot=?, content=?, google_client=?, google_slot=?, url=?, active=? WHERE id=?");
            $stmt->execute([$name, $type, $slot, $cnt, $gclt, $gslot, $url, $active, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO ads (name, type, slot, content, google_client, google_slot, url, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $type, $slot, $cnt, $gclt, $gslot, $url, $active]);
        }
        header('Location: ../admin/ads.php?saved=1');
        exit;

    // ── ADMIN: SAVE BREAKING ───────────────────────────────────────────────────
    case 'save_breaking':
        session_start();
        if (empty($_SESSION['admin_logged_in'])) {
            http_response_code(401);
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            break;
        }
        $texts = $_POST['texts'] ?? [];
        $db = getDB();
        $db->exec("DELETE FROM breaking_news");
        $stmt = $db->prepare("INSERT INTO breaking_news (text, active, sort_order) VALUES (?, 1, ?)");
        foreach ($texts as $i => $text) {
            $text = trim($text);
            if ($text !== '') {
                $stmt->execute([$text, $i]);
            }
        }
        header('Location: ../admin/breaking.php?saved=1');
        exit;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
