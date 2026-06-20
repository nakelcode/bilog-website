<?php
session_start();
require_once 'includes/db.php';

// ─── Featured post — single latest approved post ───
$stmt = $db->query("
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
    WHERE p.status = 'approved'
    ORDER BY p.created_at DESC
    LIMIT 1
");
$featured = $stmt->fetch();

// ─── Recent posts grid — 3 latest approved (excluding featured) ───
$featured_id = $featured ? $featured['id'] : 0;
$stmt = $db->prepare("
    SELECT
        p.id, p.title, p.excerpt, p.body,
        p.featured_image, p.created_at,
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
$stmt->execute([$featured_id]);
$recent_posts = $stmt->fetchAll();

// ─── Categories ───
$stmt = $db->query("
    SELECT c.id, c.name, c.color, COUNT(p.id) AS post_count
    FROM categories c
    LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'approved'
    GROUP BY c.id
    ORDER BY post_count DESC
");
$categories = $stmt->fetchAll();

// ─── You might also like — 4 random approved posts ───
$stmt = $db->query("
    SELECT
        p.id, p.title, p.featured_image,
        u.full_name AS author_name,
        a.full_name AS admin_name,
        p.author_type
    FROM posts p
    LEFT JOIN users u  ON p.user_id  = u.id
    LEFT JOIN admins a ON p.admin_id = a.id
    WHERE p.status = 'approved'
    ORDER BY RAND()
    LIMIT 4
");
$recommended = $stmt->fetchAll();

// ─── Helper: get author name ───
function get_author($post) {
    if ($post['author_type'] === 'user') {
        return $post['author_name'] ?? 'Unknown';
    }
    return $post['admin_name'] ?? 'Unknown';
}

// ─── Helper: get excerpt ───
function get_excerpt($post, $length = 120) {
    if (!empty($post['excerpt'])) {
        return $post['excerpt'];
    }
    return substr(strip_tags($post['body']), 0, $length) . '...';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilog Blog</title>
    <link rel="stylesheet" href="assets/css/bilog.css">
    <style>
        .card a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: auto;
        padding-top: 16px;
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        letter-spacing: 0.03em;
        border-top: 1px solid #1f1f1f;
        transition: color 0.2s ease, opacity 0.2s ease;
        }

        .card a:hover {
        opacity: 0.7;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<?php require_once 'includes/header.php'; ?>

<!-- HERO SECTION -->
<section class="hero">
    <h1>
        Hey, we're bilog.<br>
        Checkout trends, travel tips, and tech news.
    </h1>
    <div class="search-bar">
        <form action="blogs.php" method="GET">
            <input type="text" name="search" placeholder="Search..."/>
            <button type="submit">Search</button>
        </form>
    </div>
</section>

<!-- ================= FEATURED BLOG ================= -->
<section class="featured-section">
    <?php if ($featured): ?>
        <div class="featured-container">

            <div class="featured-image">
                <?php if (!empty($featured['featured_image'])): ?>
                    <img
                        src="uploads/posts/<?= htmlspecialchars($featured['featured_image']) ?>"
                        alt="<?= htmlspecialchars($featured['title']) ?>"
                    />
                <?php else: ?>
                    <img src="assets/images/laptop.jpg" alt="Featured post"/>
                <?php endif; ?>
            </div>

            <div class="featured-content">
                <div class="featured-meta">
                    <span class="category">
                        <?= htmlspecialchars($featured['category_name'] ?? 'General') ?>
                    </span>
                    <span class="date">
                        <?= date('F j, Y', strtotime($featured['created_at'])) ?>
                    </span>
                </div>

                <h2 class="featured-title">
                    <?= htmlspecialchars($featured['title']) ?>
                </h2>

                <p class="featured-desc">
                    <?= htmlspecialchars(get_excerpt($featured, 200)) ?>
                </p>

                <a href="posts.php?id=<?= $featured['id'] ?>" class="read-more">
                    Read Now →
                </a>
            </div>

        </div>
    <?php else: ?>
        <div class="featured-container">
            <div class="featured-content">
                <p style="color:#888">No posts published yet.</p>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- ================= BLOG POSTS ================= -->
<section class="blogs">
    <div class="blog-grid">
        <?php if (empty($recent_posts)): ?>
            <p style="color:#888">No recent posts yet.</p>
        <?php else: ?>
            <?php foreach ($recent_posts as $post): ?>
                <div class="blog-card">

                    <?php if (!empty($post['featured_image'])): ?>
                        <img
                            src="uploads/posts/<?= htmlspecialchars($post['featured_image']) ?>"
                            alt="<?= htmlspecialchars($post['title']) ?>"
                        />
                    <?php else: ?>
                        <img src="assets/images/1.jpg" alt="Post image"/>
                    <?php endif; ?>

                    <div class="blog-content">
                        <div class="blog-top">
                            <span class="category">
                                <?= htmlspecialchars($post['category_name'] ?? 'General') ?>
                            </span>
                            <span class="date">
                                <?= date('F j, Y', strtotime($post['created_at'])) ?>
                            </span>
                        </div>

                        <h3><?= htmlspecialchars($post['title']) ?></h3>

                        <p><?= htmlspecialchars(get_excerpt($post, 120)) ?></p>

                        <a href="posts.php?id=<?= $post['id'] ?>" class="read-more">
                            Read Now →
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="button">
        <a href="blogs.php"><button class="blog-btn">Browse All Blogs</button></a>
    </div>
</section>

<!-- Newsletter -->
<section class="newsletter">
    <div class="newsletter-box">
        <h2>Subscribe to our email<br>newsletter</h2>
        <form class="newsletter-form">
            <input type="email" placeholder="Enter Your Email">
            <button type="submit">Submit</button>
        </form>
    </div>
</section>

<!-- ================= BLOG CATEGORIES ================= -->
<section class="categories">
    <h2>Blog categories</h2>
    <div class="category-grid">
        <?php if (empty($categories)): ?>
            <p style="color:#888">No categories yet.</p>
        <?php else: ?>
            <?php foreach ($categories as $cat): ?>
                <div class="category-card" style="background-color: <?= htmlspecialchars($cat['color'] ?? '#333333') ?>;">
                    <a href="blogs.php?category_id=<?= $cat['id'] ?>">
                        <span><?= htmlspecialchars($cat['name']) ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- You might also like -->
<section class="recommend">
    <h2>You might also like</h2>
    <div class="recommend-container">
        <?php if (empty($recommended)): ?>
            <p style="color:#888">No posts yet.</p>
        <?php else: ?>
            <?php foreach ($recommended as $post): ?>
                <div class="card">
                    <?php if (!empty($post['featured_image'])): ?>
                        <img
                            src="uploads/posts/<?= htmlspecialchars($post['featured_image']) ?>"
                            alt="<?= htmlspecialchars($post['title']) ?>"
                        />
                    <?php else: ?>
                        <img src="assets/images/l1.jpg" alt="Post"/>
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($post['title']) ?></h3>
                    <p>By <?= htmlspecialchars(get_author($post)) ?></p>
                    <a href="posts.php?id=<?= $post['id'] ?>">
                        Read Now →
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- ================= FOOTER ================= -->
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
                <li><a href="index.php"   class="read-more">Home</a></li>
                <li><a href="blogs.php"   class="read-more">Blog</a></li>
                <li><a href="login.php"   class="read-more">Login</a></li>
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