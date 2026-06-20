<?php
session_start();
require_once '../includes/db.php';
require_once 'includes/auth.php';

// ─── 1. Total posts ───
$stmt = $db->query("SELECT COUNT(*) FROM posts");
$total_posts = $stmt->fetchColumn();

// ─── 2. Pending posts ───
$stmt = $db->query("SELECT COUNT(*) FROM posts WHERE status = 'pending'");
$total_pending = $stmt->fetchColumn();

// ─── 3. Total categories ───
$stmt = $db->query("SELECT COUNT(*) FROM categories");
$total_categories = $stmt->fetchColumn();

// ─── 4. Total admins ───
$stmt = $db->query("SELECT COUNT(*) FROM admins");
$total_admins = $stmt->fetchColumn();

// ─── 5. Recent posts (latest 8) ───
$stmt = $db->query("
    SELECT
        p.id,
        p.title,
        p.status,
        p.author_type,
        p.created_at,
        c.name      AS category_name,
        u.full_name AS user_name,
        a.full_name AS admin_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u      ON p.user_id = u.id
    LEFT JOIN admins a     ON p.admin_id = a.id
    ORDER BY p.created_at DESC
    LIMIT 8
");
$recent_posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — BlogForge Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="layout">
  <div class="main">
    <header class="topbar">
      <button class="topbar-menu-btn" onclick="toggleSidebar()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="12" x2="21" y2="12"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <span class="topbar-title">Dashboard</span>
      <div class="topbar-right">
        <div class="topbar-profile">
          <?= strtoupper(substr($_SESSION['admin_name'], 0, 2)) ?>
        </div>
      </div>
    </header>

    <div class="content">
      <div class="section-header">
        <div>
          <h2>Welcome back 👋</h2>
          <p>Here's a quick overview of your blog.</p>
        </div>
        <a class="btn btn-primary" href="create-post.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          New Post
        </a>
      </div>

      <!-- STAT CARDS -->
      <div class="stats-grid">
        <div class="stat-card accent">
          <div class="stat-icon accent">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
          </div>
          <div class="stat-value"><?= number_format($total_posts) ?></div>
          <div class="stat-label">Total Posts</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
          </div>
          <div class="stat-value"><?= number_format($total_pending) ?></div>
          <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
          <div class="stat-value"><?= number_format($total_categories) ?></div>
          <div class="stat-label">Categories</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <div class="stat-value"><?= number_format($total_admins) ?></div>
          <div class="stat-label">Admins</div>
        </div>
      </div>

      <!-- RECENT POSTS -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Recent Posts</div>
          <a class="btn btn-ghost btn-sm" href="all-posts.php">View all</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Post</th>
                <th>Author</th>
                <th>Category</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recent_posts)): ?>
                <tr>
                  <td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted);font-size:0.85rem;">
                    No posts yet.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($recent_posts as $post): ?>
                  <tr>
                    <td>
                      <div class="post-thumb">
                        <div class="post-img"><?php if (!empty($post['featured_image'])): ?>
                                              <img
                                                  src="../uploads/posts/<?= htmlspecialchars($post['featured_image']) ?>"
                                                  alt="<?= htmlspecialchars($post['title']) ?>"
                                                  class="post-img"
                                                  style="width:40px;height:40px;object-fit:cover;border-radius:6px;"
                                              />
                                          <?php else: ?>
                                              <div class="post-img">📄</div>
                                          <?php endif; ?></div>
                        <div>
                          <div class="post-title-cell">
                            <?= htmlspecialchars($post['title']) ?>
                          </div>
                          <div class="post-meta-cell">
                            <?= date('M j, Y', strtotime($post['created_at'])) ?>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td style="color:var(--text-secondary);font-size:.82rem">
                      <?php if ($post['author_type'] === 'user'): ?>
                        <?= htmlspecialchars($post['user_name'] ?? 'Deleted User') ?>
                      <?php else: ?>
                        <?= htmlspecialchars($post['admin_name'] ?? 'Deleted Admin') ?>
                        <span class="badge badge-admin">Admin</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge badge-admin">
                        <?= htmlspecialchars($post['category_name'] ?? 'Uncategorised') ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge badge-<?= $post['status'] ?>">
                        <?= ucfirst($post['status']) ?>
                      </span>
                    </td>
                    <td style="color:var(--text-muted);font-size:.78rem">
                      <?= date('M j', strtotime($post['created_at'])) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /layout -->

<script src="../assets/js/main.js"></script>
</body>
</html>
