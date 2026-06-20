<?php
session_start();
require_once 'includes/db.php';

$search      = trim($_GET['search']      ?? '');
$category_id = (int)($_GET['category_id'] ?? 0);
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 9;
$offset      = ($page - 1) * $per_page;


$where  = "WHERE p.status = 'approved'";
$params = [];

if ($search !== '') {
    $where   .= " AND (p.title LIKE ? OR p.body LIKE ? OR p.excerpt LIKE ?)";
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($category_id > 0) {
    $where   .= " AND p.category_id = ?";
    $params[] = $category_id;
}

// ─── Total count for pagination ───
$count_sql  = "SELECT COUNT(*) FROM posts p $where";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_posts = (int)$count_stmt->fetchColumn();
$total_pages = ceil($total_posts / $per_page);

// ─── Fetch posts ───
$sql = "
    SELECT
        p.id, p.title, p.body, p.excerpt,
        p.featured_image, p.created_at,
        c.name      AS category_name,
        u.full_name AS author_name,
        a.full_name AS admin_name,
        p.author_type
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u      ON p.user_id  = u.id
    LEFT JOIN admins a     ON p.admin_id = a.id
    $where
    ORDER BY p.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$cat_stmt = $db->query("
    SELECT c.id, c.name, COUNT(p.id) AS post_count
    FROM categories c
    LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'approved'
    GROUP BY c.id
    HAVING post_count > 0
    ORDER BY post_count DESC
");
$categories = $cat_stmt->fetchAll();


function get_author($post) {
    return ($post['author_type'] === 'user')
        ? ($post['author_name'] ?? 'Unknown')
        : ($post['admin_name']  ?? 'Unknown');
}

function get_excerpt($post, $length = 110) {
    if (!empty($post['excerpt'])) return $post['excerpt'];
    return substr(strip_tags($post['body']), 0, $length) . '…';
}


function page_url($p) {
    $params = $_GET;
    $params['page'] = $p;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Blogs — Bilog</title>
    <link rel="stylesheet" href="assets/css/bilog.css">
    <style>
        /* ── Page header ── */
        .blogs-header {
            padding: 80px 8% 48px;
            border-bottom: 1px solid #1a1a1a;
        }

        .blogs-header h1 {
            font-size: clamp(28px, 4vw, 48px);
            font-weight: 500;
            line-height: 1.2;
            margin-bottom: 32px;
        }

        .blogs-header h1 span {
            color: #555;
        }

        /* ── Search bar (reuse existing style, override width) ── */
        .blogs-search {
            display: flex;
            justify-content: flex-start;
        }

        .blogs-search .search-bar {
            width: 100%;
            max-width: 480px;
            height: 56px;
            margin: 0;
        }

        /* ── Category filter pills ── */
        .filter-bar {
            padding: 28px 8% 0;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-pill {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 30px;
            border: 1px solid #2a2a2a;
            background: transparent;
            color: #888;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .filter-pill:hover {
            border-color: #555;
            color: #fff;
        }

        .filter-pill.active {
            background: #fff;
            color: #000;
            border-color: #fff;
        }

        /* ── Results meta ── */
        .results-meta {
            padding: 28px 8% 0;
            font-size: 14px;
            color: #555;
        }

        .results-meta strong {
            color: #aaa;
        }

        /* ── Blog grid ── */
        .all-blogs {
            padding: 32px 8% 80px;
        }

        .all-blog-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
        }

        /* Card — reuse .blog-card but override for grid */
        .all-blog-grid .blog-card {
            background: #111;
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: background 0.3s ease;
        }

        .all-blog-grid .blog-card:hover {
            background: #1a1a1a;
        }

        .all-blog-grid .blog-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .all-blog-grid .blog-card:hover img {
            transform: scale(1.05);
        }

        .all-blog-grid .blog-content {
            padding: 24px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .all-blog-grid .blog-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .all-blog-grid .blog-top .category {
            color: #fff;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .all-blog-grid .blog-top .date {
            color: #555;
            font-size: 12px;
        }

        .all-blog-grid .blog-content h3 {
            color: #fff;
            font-size: 17px;
            font-weight: 500;
            line-height: 1.45;
            margin-bottom: 10px;
        }

        .all-blog-grid .blog-content p {
            color: #888;
            font-size: 14px;
            line-height: 1.65;
            margin-bottom: 20px;
            flex: 1;
        }

        .all-blog-grid .blog-content .read-more {
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            margin-top: auto;
            display: inline-block;
        }

        .all-blog-grid .blog-content .read-more:hover {
            text-decoration: underline;
        }

        /* ── Empty state ── */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            color: #444;
        }

        .empty-state p {
            font-size: 18px;
            margin-bottom: 16px;
        }

        .empty-state a {
            color: #fff;
            font-weight: 600;
            text-decoration: none;
        }

        /* ── Pagination ── */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 0 8% 80px;
            flex-wrap: wrap;
        }

        .page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid #222;
            background: transparent;
            color: #888;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .page-btn:hover {
            border-color: #555;
            color: #fff;
        }

        .page-btn.active {
            background: #fff;
            color: #000;
            border-color: #fff;
        }

        .page-btn.disabled {
            opacity: 0.25;
            pointer-events: none;
        }

        /* ── Responsive ── */
        @media (max-width: 1023px) {
            .all-blog-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .blogs-header { padding: 60px 5% 36px; }
            .filter-bar   { padding: 24px 5% 0; }
            .results-meta { padding: 20px 5% 0; }
            .all-blogs    { padding: 28px 5% 60px; }
            .pagination   { padding: 0 5% 60px; }
        }

        @media (max-width: 768px) {
            .all-blog-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .blogs-header { padding: 80px 5% 28px; }
            .blogs-header h1 { margin-bottom: 24px; }
            .filter-bar { gap: 8px; padding: 20px 5% 0; }
            .filter-pill { font-size: 13px; padding: 6px 14px; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<?php require_once 'includes/header.php'; ?>

<!-- PAGE HEADER -->
<div class="blogs-header">
    <h1>
        <?php if ($search !== ''): ?>
            Results for <span>"<?= htmlspecialchars($search) ?>"</span>
        <?php elseif ($category_id > 0): ?>
            <?php
                $active_cat = array_filter($categories, fn($c) => $c['id'] == $category_id);
                $active_cat = reset($active_cat);
            ?>
            <?= htmlspecialchars($active_cat['name'] ?? 'Category') ?>
        <?php else: ?>
            All Blog Posts
        <?php endif; ?>
    </h1>

    <!-- SEARCH -->
    <div class="blogs-search">
        <form action="blogs.php" method="GET" class="search-bar" style="margin:0;">
            <?php if ($category_id > 0): ?>
                <input type="hidden" name="category_id" value="<?= $category_id ?>">
            <?php endif; ?>
            <input
                type="text"
                name="search"
                placeholder="Search posts…"
                value="<?= htmlspecialchars($search) ?>"
            />
            <button type="submit">Search</button>
        </form>
    </div>
</div>

<!-- CATEGORY FILTER PILLS -->
<div class="filter-bar">
    <a
        href="blogs.php<?= $search ? '?search=' . urlencode($search) : '' ?>"
        class="filter-pill <?= $category_id === 0 ? 'active' : '' ?>"
    >All</a>

    <?php foreach ($categories as $cat): ?>
        <?php
            $pill_url = '?category_id=' . $cat['id'];
            if ($search !== '') $pill_url .= '&search=' . urlencode($search);
        ?>
        <a
            href="blogs.php<?= $pill_url ?>"
            class="filter-pill <?= $category_id === (int)$cat['id'] ? 'active' : '' ?>"
        >
            <?= htmlspecialchars($cat['name']) ?>
            <span style="opacity:0.45; font-size:11px;">(<?= $cat['post_count'] ?>)</span>
        </a>
    <?php endforeach; ?>
</div>

<!-- RESULTS META -->
<div class="results-meta">
    <strong><?= $total_posts ?></strong> post<?= $total_posts !== 1 ? 's' : '' ?> found
    <?php if ($total_pages > 1): ?>
        — page <?= $page ?> of <?= $total_pages ?>
    <?php endif; ?>
</div>

<!-- BLOG GRID -->
<section class="all-blogs">
    <div class="all-blog-grid">
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <p>No posts found.</p>
                <a href="blogs.php">← Clear filters</a>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php
                    $img = !empty($post['featured_image'])
                        ? 'uploads/posts/' . htmlspecialchars($post['featured_image'])
                        : 'assets/images/1.jpg';
                ?>
                <div class="blog-card">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                    <div class="blog-content">
                        <div class="blog-top">
                            <span class="category"><?= htmlspecialchars($post['category_name'] ?? 'General') ?></span>
                            <span class="date"><?= date('M j, Y', strtotime($post['created_at'])) ?></span>
                        </div>
                        <h3><?= htmlspecialchars($post['title']) ?></h3>
                        <p><?= htmlspecialchars(get_excerpt($post)) ?></p>
                        <a href="posts.php?id=<?= $post['id'] ?>" class="read-more">Read Now →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- PAGINATION -->
<?php if ($total_pages > 1): ?>
<div class="pagination">
    <!-- Prev -->
    <a href="<?= page_url($page - 1) ?>" class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">←</a>

    <?php
        // Show smart window of pages
        $window = 2;
        $start  = max(1, $page - $window);
        $end    = min($total_pages, $page + $window);
    ?>

    <?php if ($start > 1): ?>
        <a href="<?= page_url(1) ?>" class="page-btn">1</a>
        <?php if ($start > 2): ?><span style="color:#444; padding: 0 4px;">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
        <a href="<?= page_url($i) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($end < $total_pages): ?>
        <?php if ($end < $total_pages - 1): ?><span style="color:#444; padding: 0 4px;">…</span><?php endif; ?>
        <a href="<?= page_url($total_pages) ?>" class="page-btn"><?= $total_pages ?></a>
    <?php endif; ?>

    <!-- Next -->
    <a href="<?= page_url($page + 1) ?>" class="page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">→</a>
</div>
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
        Designed by <span>AbdulRasaq</span>. Powered by <span>Oyc</span>
    </div>
</footer>

<script src="assets/js/bilog.js"></script>
</body>
</html>