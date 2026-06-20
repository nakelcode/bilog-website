<?php

require_once "../includes/db.php";

$stmt = $db->query("SELECT COUNT(*) FROM posts WHERE status = 'pending'");
$total_count_pending = $stmt->fetchColumn();

?>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">BF</div>
    <div class="logo-text">Blog<span>Forge</span></div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a class="nav-item" href="dashboard.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>

    <div class="nav-section-label">Content</div>
    <a class="nav-item" href="all-posts.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
      All Posts
    </a>
    <a class="nav-item" href="pending.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
      Pending Review
      <span class="nav-badge orange"><?php echo $total_count_pending; ?></span>
    </a>
    <a class="nav-item" href="create-post.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Create Post
    </a>
    <a class="nav-item" href="categories.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Categories
    </a>

    <div class="nav-section-label">Management</div>
    <a class="nav-item" href="admins.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Admins & Roles
    </a>
  </nav>


  <div class="sidebar-bottom">
      <div class="sidebar-divider"></div>
      <a href="logout.php" class="sidebar-logout">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16,17 21,12 16,7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
          <span>Logout</span>
      </a>
  </div>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar">SA</div>
      <div>
        <div class="user-name">Super Admin</div>
        <div class="user-role">⚡ Super Admin</div>
      </div>
    </div>
  </div>
</aside>
