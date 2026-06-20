<?php
session_start();
require_once '../includes/db.php';
require_once 'includes/auth.php';

// ─── Handle approve / decline / delete actions ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $post_id = (int)($_POST['post_id'] ?? 0);
    $action  = $_POST['action'] ?? '';

    if ($post_id > 0) {

        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE posts SET status = 'approved' WHERE id = ?");
            $stmt->execute([$post_id]);

        } elseif ($action === 'decline') {
            $stmt = $db->prepare("UPDATE posts SET status = 'declined' WHERE id = ?");
            $stmt->execute([$post_id]);

        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
        }
    }

    header('Location: all-posts.php?filter=' . urlencode($_POST['filter'] ?? 'all'));
    exit;
}

// ─── Active filter tab ───
$filter = $_GET['filter'] ?? 'all';
$allowed_filters = ['all', 'approved', 'pending', 'declined'];
if (!in_array($filter, $allowed_filters)) $filter = 'all';

// ─── Search and category filter ───
$search      = trim($_GET['search'] ?? '');
$category_id = (int)($_GET['category_id'] ?? 0);

// ─── Count totals for tabs ───
$stmt = $db->query("SELECT COUNT(*) FROM posts");
$count_all = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM posts WHERE status = 'approved'");
$count_approved = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM posts WHERE status = 'pending'");
$count_pending = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM posts WHERE status = 'declined'");
$count_declined = $stmt->fetchColumn();

// ─── Pagination ───
$per_page    = 10;
$page        = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($page - 1) * $per_page;

// ─── Build query with filters ───
$where    = [];
$params   = [];

if ($filter !== 'all') {
    $where[]  = "p.status = ?";
    $params[] = $filter;
}
if (!empty($search)) {
    $where[]  = "(p.title LIKE ? OR u.full_name LIKE ? OR a.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category_id > 0) {
    $where[]  = "p.category_id = ?";
    $params[] = $category_id;
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ─── Count filtered results for pagination ───
$count_stmt = $db->prepare("
    SELECT COUNT(*)
    FROM posts p
    LEFT JOIN users u  ON p.user_id  = u.id
    LEFT JOIN admins a ON p.admin_id = a.id
    $where_sql
");
$count_stmt->execute($params);
$total_filtered = $count_stmt->fetchColumn();
$total_pages    = ceil($total_filtered / $per_page);

// ─── Fetch posts ───
$stmt = $db->prepare("
    SELECT
        p.id,
        p.title,
        p.status,
        p.author_type,
        p.featured_image,
        p.created_at,
        c.name      AS category_name,
        u.full_name AS user_name,
        a.full_name AS admin_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u      ON p.user_id  = u.id
    LEFT JOIN admins a     ON p.admin_id = a.id
    $where_sql
    ORDER BY p.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$posts = $stmt->fetchAll();

// ─── Fetch categories for filter drodbwn ───
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// ─── Helper: build URL keeping current filters ───
function page_url($p) {
    $params = $_GET;
    $params['page'] = $p;
    return 'all-posts.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>All Posts — BlogForge Admin</title>
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
      <span class="topbar-title">All Posts</span>
      <div class="topbar-right">
        <div class="topbar-profile">
          <?= strtoupper(substr($_SESSION['admin_name'], 0, 2)) ?>
        </div>
      </div>
    </header>

    <div class="content">
      <div class="section-header">
        <div>
          <h2>All Posts</h2>
          <p>View and manage every post on the blog.</p>
        </div>
        <a class="btn btn-primary" href="create-post.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          New Post
        </a>
      </div>

      <!-- TABS -->
      <div class="tabs">
        <a class="tab <?= $filter === 'all'      ? 'active' : '' ?>" href="all-posts.php?filter=all">
          All (<?= $count_all ?>)
        </a>
        <a class="tab <?= $filter === 'approved' ? 'active' : '' ?>" href="all-posts.php?filter=approved">
          Approved (<?= $count_approved ?>)
        </a>
        <a class="tab <?= $filter === 'pending'  ? 'active' : '' ?>" href="all-posts.php?filter=pending">
          Pending (<?= $count_pending ?>)
        </a>
        <a class="tab <?= $filter === 'declined' ? 'active' : '' ?>" href="all-posts.php?filter=declined">
          Declined (<?= $count_declined ?>)
        </a>
      </div>

      <!-- FILTER BAR -->
      <form method="GET" action="all-posts.php" class="filter-bar">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"/>
        <input
          type="text"
          name="search"
          class="search-input"
          placeholder="Search by title or author…"
          value="<?= htmlspecialchars($search) ?>"
        />
        <select name="category_id" onchange="this.form.submit()">
          <option value="0">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $category_id === (int)$cat['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>

      <!-- TABLE -->
      <div class="card" style="padding:0">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Post</th>
                <th>Author</th>
                <th>Category</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($posts)): ?>
                <tr>
                  <td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted);font-size:0.85rem;">
                    No posts found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($posts as $post): ?>
                  <tr>
                    <td>
                      <div class="post-thumb">
                        <?php if (!empty($post['featured_image'])): ?>
                          <img
                            src="../uploads/posts/<?= htmlspecialchars($post['featured_image']) ?>"
                            alt="<?= htmlspecialchars($post['title']) ?>"
                            class="post-img"
                            style="width:40px;height:40px;object-fit:cover;border-radius:6px;"
                          />
                        <?php else: ?>
                          <div class="post-img">📄</div>
                        <?php endif; ?>
                        <div>
                          <div class="post-title-cell">
                            <?= htmlspecialchars($post['title']) ?>
                          </div>
                          <div class="post-meta-cell">
                            <?php if ($post['author_type'] === 'user'): ?>
                              by <?= htmlspecialchars($post['user_name'] ?? 'Deleted User') ?>
                            <?php else: ?>
                              by <?= htmlspecialchars($post['admin_name'] ?? 'Deleted Admin') ?>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td style="color:var(--text-secondary);font-size:.82rem">
                      <?php if ($post['author_type'] === 'user'): ?>
                        <?= htmlspecialchars($post['user_name'] ?? 'Deleted User') ?>
                      <?php else: ?>
                        <?= htmlspecialchars($post['admin_name'] ?? 'Deleted Admin') ?>
                        <span class="badge badge-admin" style="margin-left:4px">Admin</span>
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
                      <?= date('M j, Y', strtotime($post['created_at'])) ?>
                    </td>
                    <td>
                      <div style="display:flex;gap:6px">
                        <?php if ($post['status'] === 'pending'): ?>
                          <!-- Pending: approve or decline -->
                          <form method="POST">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>"/>
                            <input type="hidden" name="action"  value="approve"/>
                            <input type="hidden" name="filter"  value="<?= htmlspecialchars($filter) ?>"/>
                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                          </form>
                          <form method="POST">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>"/>
                            <input type="hidden" name="action"  value="decline"/>
                            <input type="hidden" name="filter"  value="<?= htmlspecialchars($filter) ?>"/>
                            <button type="submit" class="btn btn-danger btn-sm">Decline</button>
                          </form>

                        <?php elseif ($post['status'] === 'declined'): ?>
                          <!-- Declined: re-approve or delete -->
                          <form method="POST">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>"/>
                            <input type="hidden" name="action"  value="approve"/>
                            <input type="hidden" name="filter"  value="<?= htmlspecialchars($filter) ?>"/>
                            <button type="submit" class="btn btn-success btn-sm">Re-approve</button>
                          </form>
                          <form method="POST">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>"/>
                            <input type="hidden" name="action"  value="delete"/>
                            <input type="hidden" name="filter"  value="<?= htmlspecialchars($filter) ?>"/>
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this post permanently?')">Delete</button>
                          </form>

                        <?php else: ?>
                          <!-- Approved: delete only -->
                          <form method="POST">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>"/>
                            <input type="hidden" name="action"  value="delete"/>
                            <input type="hidden" name="filter"  value="<?= htmlspecialchars($filter) ?>"/>
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this post permanently?')">Delete</button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>
          <div style="padding:12px 16px;display:flex;align-items:center;justify-content:space-between;border-top:1px solid var(--border);font-size:.78rem;color:var(--text-muted);flex-wrap:wrap;gap:8px">
            <span>
              Showing <?= $offset + 1 ?>–<?= min($offset + $per_page, $total_filtered) ?> of <?= $total_filtered ?> posts
            </span>
            <div style="display:flex;gap:5px">
              <?php if ($page > 1): ?>
                <a href="<?= page_url($page - 1) ?>" class="btn btn-ghost btn-sm">← Prev</a>
              <?php endif; ?>

              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a
                  href="<?= page_url($i) ?>"
                  class="btn btn-ghost btn-sm"
                  <?php if ($i === $page): ?>
                    style="background:var(--accent-glow);border-color:rgba(232,255,71,.2);color:var(--accent)"
                  <?php endif; ?>
                >
                  <?= $i ?>
                </a>
              <?php endfor; ?>

              <?php if ($page < $total_pages): ?>
                <a href="<?= page_url($page + 1) ?>" class="btn btn-ghost btn-sm">Next →</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

      </div>

    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
