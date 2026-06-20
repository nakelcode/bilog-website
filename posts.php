<?php
session_start();
require_once 'includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("
    SELECT
        p.id,
        p.title,
        p.body,
        p.excerpt,
        p.featured_image, 
        p.created_at,
        c.name      AS category_name,
        u.full_name AS author_name,
        a.full_name AS admin_name,
        p.author_type
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u      ON p.user_id  = u.id
    LEFT JOIN admins a     ON p.admin_id = a.id
    WHERE p.id = ? AND p.status = 'approved'
    LIMIT 1
");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: index.php');
    exit;
}


$stmt = $db->prepare("
    SELECT
        p.id,
        p.title, 
        p.featured_image, 
        p.created_at,
        c.name      AS category_name,
        u.full_name AS author_name,
        a.full_name AS admin_name,
        p.author_type
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u      ON p.user_id  = u.id
    LEFT JOIN admins a     ON p.admin_id = a.id
    WHERE p.status = 'approved'
      AND p.id != ?
      AND p.category_id = (SELECT category_id FROM posts WHERE id = ?)
    ORDER BY p.created_at DESC
    LIMIT 3
");
$stmt->execute([$id, $id]);
$related = $stmt->fetchAll();

if (empty($related)) {
    $stmt = $db->prepare("
        SELECT
            p.id, 
            p.title, 
            p.featured_image, 
            p.created_at,
            c.name      AS category_name,
            u.full_name AS author_name,
            a.full_name AS admin_name,
            p.author_type
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u      ON p.user_id  = u.id
        LEFT JOIN admins a     ON p.admin_id = a.id
        WHERE p.status = 'approved' AND p.id != ?
        ORDER BY p.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$id]);
    $related = $stmt->fetchAll();
}

function get_author($post) {
    if ($post['author_type'] === 'user') {
        return $post['author_name'] ?? 'Unknown';
    }
    return $post['admin_name'] ?? 'Unknown';
}

$author      = get_author($post);
$date        = date('F j, Y', strtotime($post['created_at']));
$category    = htmlspecialchars($post['category_name'] ?? 'General');
$title       = htmlspecialchars($post['title']);
$has_image   = !empty($post['featured_image']);
$image_src   = $has_image ? 'uploads/posts/' . htmlspecialchars($post['featured_image']) : 'assets/images/laptop.jpg';


$word_count   = str_word_count(strip_tags($post['body']));
$reading_time = max(1, ceil($word_count / 200));
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> — Bilog Blog</title>
    <link rel="stylesheet" href="assets/css/bilog.css">
    <style>
        .post-hero {
            position: relative;
            width: 100%;
            height: 900px;
            overflow: hidden;
        }

        .post-hero img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .post-hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.75) 100%);
        }

        .post-hero-meta {
            position: absolute;
            bottom: 48px;
            left: 0;
            right: 0;
            padding: 0 8%;
        }

        .post-hero-meta .category-badge {
            display: inline-block;
            background: #fff;
            color: #000;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 20px;
            margin-bottom: 18px;
        }

        .post-hero-meta h1 {
            font-size: clamp(28px, 4vw, 52px);
            font-weight: 600;
            color: #fff;
            line-height: 1.2;
            max-width: 800px;
            margin-bottom: 18px;
        }

        .post-hero-meta .post-byline {
            display: flex;
            align-items: center;
            gap: 20px;
            color: #ccc;
            font-size: 14px;
        }

        .post-byline .dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #666;
            display: inline-block;
        }

        /* ── Body ── */
        .post-body-wrapper {
            max-width: 760px;
            margin: 0 auto;
            padding: 64px 24px 80px;
        }

        .post-body {
            color: #d4d4d4;
            font-size: 18px;
            line-height: 1.85;
            font-family: 'Inter', sans-serif;
        }

        .post-body p {
            margin-bottom: 1.6em;
        }

        .post-body h2 {
            color: #fff;
            font-size: 26px;
            font-weight: 600;
            margin: 2em 0 0.8em;
            line-height: 1.3;
        }

        .post-body h3 {
            color: #fff;
            font-size: 20px;
            font-weight: 600;
            margin: 1.8em 0 0.6em;
        }

        .post-body a {
            color: #fff;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .post-body blockquote {
            border-left: 3px solid #444;
            padding: 4px 0 4px 24px;
            margin: 2em 0;
            color: #aaa;
            font-style: italic;
            font-size: 19px;
        }

        .post-body ul, .post-body ol {
            padding-left: 1.5em;
            margin-bottom: 1.6em;
        }

        .post-body li {
            margin-bottom: 0.5em;
        }

        .post-body img {
            width: 100%;
            border-radius: 12px;
            margin: 2em 0;
        }

        .post-body code {
            background: #1a1a1a;
            border: 1px solid #333;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 15px;
            color: #e0e0e0;
        }

        .post-body pre {
            background: #111;
            border: 1px solid #222;
            border-radius: 10px;
            padding: 20px 24px;
            overflow-x: auto;
            margin-bottom: 1.6em;
        }

        /* ── Divider ── */
        .post-divider {
            border: none;
            border-top: 1px solid #222;
            max-width: 760px;
            margin: 0 auto;
        }

        /* ── Author card ── */
        .author-card {
            max-width: 760px;
            margin: 48px auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .author-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #222;
            border: 1px solid #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
            text-transform: uppercase;
        }

        .author-info .author-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 4px;
        }

        .author-info .author-name {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }

        /* ── Related posts ── */
        .related-section {
            padding: 60px 8%;
            border-top: 1px solid #1a1a1a;
        }

        .related-section h2 {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 30px;
            color: #fff;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .related-card {
            background: #111;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: background 0.3s ease;
        }

        .related-card:hover {
            background: #1a1a1a;
        }

        .related-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .related-card:hover img {
            transform: scale(1.05);
        }

        .related-card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .related-card-body .cat {
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .related-card-body h3 {
            font-size: 16px;
            font-weight: 500;
            color: #fff;
            line-height: 1.4;
            margin-bottom: 12px;
            flex: 1;
        }

        .related-card-body .date {
            font-size: 13px;
            color: #666;
            margin-bottom: 14px;
        }

        .related-card-body a.read-more {
            font-size: 14px;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            margin-top: auto;
        }

        .related-card-body a.read-more:hover {
            text-decoration: underline;
        }

        /* ── Back button ── */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: 1px solid #333;
            border-radius: 30px;
            color: #aaa;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            margin: 32px 8% 0;
        }

        .back-btn:hover {
            border-color: #555;
            color: #fff;
        }

        /* ── Progress bar ── */
        #read-progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: #fff;
            width: 0%;
            z-index: 9999;
            transition: width 0.1s linear;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .post-hero { height: 320px; }
            .post-hero-meta { bottom: 28px; padding: 0 5%; }
            .post-body { font-size: 16px; }
            .post-body-wrapper { padding: 40px 20px 60px; }
            .related-grid { grid-template-columns: 1fr; }
            .related-section { padding: 40px 5%; }
            .back-btn { margin: 24px 5% 0; }
            .author-card { padding: 0 20px; }
        }

        @media (max-width: 1023px) and (min-width: 769px) {
            .related-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<!-- Reading progress bar -->
<div id="read-progress"></div>

<!-- NAVBAR -->
<?php require_once 'includes/header.php'; ?>

<!-- Back button -->
<a href="javascript:history.back()" class="back-btn">← Back</a>

<!-- HERO IMAGE -->
<section class="post-hero" style="margin-top: 20px;">
    <img src="<?= $image_src ?>" alt="<?= $title ?>">
    <div class="post-hero-overlay"></div>
    <div class="post-hero-meta">
        <span class="category-badge"><?= $category ?></span>
        <h1><?= $title ?></h1>
        <div class="post-byline">
            <span>By <?= htmlspecialchars($author) ?></span>
            <span class="dot"></span>
            <span><?= $date ?></span>
            <span class="dot"></span>
            <span><?= $reading_time ?> min read</span>
        </div>
    </div>
</section>

<!-- POST BODY -->
<div class="post-body-wrapper">
    <article class="post-body">
        <?php
        // If body is plain text (not HTML), convert newlines to paragraphs
        $body = $post['body'];
        $is_html = $body !== strip_tags($body);
        if (!$is_html) {
            $paragraphs = array_filter(explode("\n\n", trim($body)));
            foreach ($paragraphs as $p) {
                echo '<p>' . nl2br(htmlspecialchars(trim($p))) . '</p>';
            }
        } else {
            echo $body;
        }
        ?>
    </article>
</div>

<hr class="post-divider">

<!-- AUTHOR CARD -->
<div class="author-card">
    <div class="author-avatar">
        <?= strtoupper(substr($author, 0, 2)) ?>
    </div>
    <div class="author-info">
        <div class="author-label">Written by</div>
        <div class="author-name"><?= htmlspecialchars($author) ?></div>
    </div>
</div>

<!-- RELATED POSTS -->
<?php if (!empty($related)): ?>
<section class="related-section">
    <h2>You might also like</h2>
    <div class="related-grid">
        <?php foreach ($related as $r): ?>
            <?php
                $r_author = get_author($r);
                $r_date   = date('M j, Y', strtotime($r['created_at']));
                $r_img    = !empty($r['featured_image'])
                    ? 'uploads/posts/' . htmlspecialchars($r['featured_image'])
                    : 'assets/images/1.jpg';
            ?>
            <div class="related-card">
                <img src="<?= $r_img ?>" alt="<?= htmlspecialchars($r['title']) ?>">
                <div class="related-card-body">
                    <div class="cat"><?= htmlspecialchars($r['category_name'] ?? 'General') ?></div>
                    <h3><?= htmlspecialchars($r['title']) ?></h3>
                    <div class="date"><?= $r_date ?></div>
                    <a href="posts.php?id=<?= $r['id'] ?>" class="read-more">Read Now →</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-container">

        <div class="footer-left">
            <h2>Subscribe to our email newsletter</h2>
            <p>Subscribe to our email newsletter.</p>
            <div class="subscribe-box">
                <input type="email" placeholder="Enter your email">
                <button class="footer-btn">Subscribe</button>
            </div>
        </div>

        <div class="footer-links">
            <ul>
                <li><a href="index.php"    class="read-more">Home</a></li>
                <li><a href="blogs.php"    class="read-more">Blog</a></li>
                <li><a href="login.php"    class="read-more">Login</a></li>
                <li><a href="register.php" class="read-more">Register</a></li>
            </ul>
        </div>

        <div class="footer-links">
            <ul>
                <li><a href="#" class="read-more">Privacy Policy</a></li>
                <li><a href="#" class="read-more">Style Guide</a></li>
                <li><a href="#" class="read-more">Instructions</a></li>
                <li><a href="#" class="read-more">Licenses</a></li>
                <li><a href="#" class="read-more">Changelog</a></li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        Designed by <span>Owolabi</span>. Powered by <span>Ahmid</span>
    </div>
</footer>

<script src="assets/js/bilog.js"></script>
<script>
    // Reading progress bar
    const bar = document.getElementById('read-progress');
    window.addEventListener('scroll', () => {
        const doc  = document.documentElement;
        const scrolled = doc.scrollTop;
        const total    = doc.scrollHeight - doc.clientHeight;
        bar.style.width = (total > 0 ? (scrolled / total) * 100 : 0) + '%';
    });
</script>
</body>
</html>