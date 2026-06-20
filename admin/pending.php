<?php
session_start();
require_once '../includes/db.php';
require_once 'includes/auth.php';

// ─── Handle approve / decline actions ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $post_id = (int)($_POST['post_id'] ?? 0);
    $action  = $_POST['action'] ?? '';

    if ($post_id > 0) {
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE posts SET status = 'approved' WHERE id = ? AND status = 'pending'");
            $stmt->execute([$post_id]);

        } elseif ($action === 'decline') {
            $stmt = $db->prepare("UPDATE posts SET status = 'declined' WHERE id = ? AND status = 'pending'");
            $stmt->execute([$post_id]);
        }
    }

    header('Location: pending.php');
    exit;
}

// ─── Count pending posts ───
$stmt = $db->query("SELECT COUNT(*) FROM posts WHERE status = 'pending'");
$total_pending = $stmt->fetchColumn();

// ─── Fetch all pending posts ───
$stmt = $db->query("
    SELECT
        p.id,
        p.title,
        p.body,
        p.featured_image,
        p.created_at,
        c.name      AS category_name,
        u.full_name AS user_name,
        u.email     AS user_email,
        a.full_name AS admin_name,
        a.email     AS admin_email,
        p.author_type
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u      ON p.user_id  = u.id
    LEFT JOIN admins a     ON p.admin_id = a.id
    WHERE p.status = 'pending'
    ORDER BY p.created_at ASC
");
$pending_posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Pending Review — BlogForge Admin</title>
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
      <span class="topbar-title">Pending Review</span>
      <div class="topbar-right">
        <div class="topbar-profile">
          <?= strtoupper(substr($_SESSION['admin_name'], 0, 2)) ?>
        </div>
      </div>
    </header>

    <div class="content">
      <div class="section-header">
        <div>
          <h2>Pending Review</h2>
          <p>Posts submitted by authors awaiting your decision.</p>
        </div>
      </div>

      <!-- ALERT -->
      <?php if ($total_pending > 0): ?>
        <div class="alert alert-warning">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
          <div>You have <strong><?= $total_pending ?> post<?= $total_pending > 1 ? 's' : '' ?></strong> waiting for review.</div>
        </div>
      <?php else: ?>
        <div class="alert alert-warning">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20,6 9,17 4,12"/>
          </svg>
          <div>All caught up! No posts are currently pending review.</div>
        </div>
      <?php endif; ?>

      <!-- TABLE -->
      <div class="card" style="padding:0">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Post</th>
                <th>Author</th>
                <th>Category</th>
                <th>Submitted</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($pending_posts)): ?>
                <tr>
                  <td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted);font-size:0.85rem;">
                    No pending posts at the moment.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($pending_posts as $post): ?>

                  <?php
                    // Work out author name and email
                    if ($post['author_type'] === 'user') {
                        $author_name  = $post['user_name']  ?? 'Deleted User';
                        $author_email = $post['user_email'] ?? '—';
                    } else {
                        $author_name  = $post['admin_name']  ?? 'Deleted Admin';
                        $author_email = $post['admin_email'] ?? '—';
                    }
                  ?>

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
                            <?= htmlspecialchars($author_email) ?>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td style="color:var(--text-secondary);font-size:.82rem">
                      <?= htmlspecialchars($author_name) ?>
                    </td>
                    <td>
                      <span class="badge badge-admin">
                        <?= htmlspecialchars($post['category_name'] ?? 'Uncategorised') ?>
                      </span>
                    </td>
                    <td style="color:var(--text-muted);font-size:.78rem">
                      <?= date('M j, g:i A', strtotime($post['created_at'])) ?>
                    </td>
                    <td>
                      <div style="display:flex;gap:6px">

                        <!-- Preview button — opens modal -->
                        <button
                          class="btn btn-ghost btn-sm"
                          onclick='openPreview(
                            <?= $post['id'] ?>,
                            <?= json_encode($post['title']) ?>,
                            <?= json_encode($author_name) ?>,
                            <?= json_encode($post['category_name'] ?? 'Uncategorised') ?>,
                            <?= json_encode(substr($post['body'], 0, 600)) ?>
                          )'>Preview</button>

                        <!-- Approve form -->
                        <form method="POST" style="display:inline">
                          <input type="hidden" name="post_id" value="<?= $post['id'] ?>"/>
                          <input type="hidden" name="action"  value="approve"/>
                          <button type="submit" class="btn btn-success btn-sm">✓ Approve</button>
                        </form>

                        <!-- Decline form -->
                        <form method="POST" style="display:inline">
                          <input type="hidden" name="post_id" value="<?= $post['id'] ?>"/>
                          <input type="hidden" name="action"  value="decline"/>
                          <button
                            type="submit"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Decline this post?')"
                          >✗ Decline</button>
                        </form>

                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ─── PREVIEW MODAL ─── -->
<div id="previewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:999;align-items:center;justify-content:center;">
  <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:14px;width:90%;max-width:640px;max-height:80vh;overflow-y:auto;padding:32px;position:relative;">
    <button onclick="closePreview()" style="position:absolute;top:16px;right:16px;background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem;">✕</button>
    <div id="previewTag" style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:8px;"></div>
    <h2 id="previewTitle" style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;letter-spacing:-0.02em;margin-bottom:6px;"></h2>
    <div id="previewMeta" style="font-size:0.8rem;color:var(--text-muted);margin-bottom:20px;"></div>
    <div style="height:1px;background:var(--border);margin-bottom:20px;"></div>
    <div id="previewBody" style="font-size:0.9rem;color:var(--text-secondary);line-height:1.8;white-space:pre-wrap;"></div>
    <p style="font-size:0.75rem;color:var(--text-muted);margin-top:16px;font-style:italic;">Showing first 600 characters only.</p>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
  function openPreview(id, title, author, category, body) {
    document.getElementById('previewTag').textContent   = category;
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewMeta').textContent  = 'by ' + author;
    document.getElementById('previewBody').textContent  = body;
    const modal = document.getElementById('previewModal');
    modal.style.display = 'flex';
  }
  function closePreview() {
    document.getElementById('previewModal').style.display = 'none';
  }
  // Close modal if clicking outside
  document.getElementById('previewModal').addEventListener('click', function(e) {
    if (e.target === this) closePreview();
  });
</script>
</body>
</html>
