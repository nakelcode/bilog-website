<?php
session_start();
require_once '../includes/db.php';
require_once 'includes/auth.php';

// ─── Super admin only ───
if ($_SESSION['admin_role'] !== 'super_admin') {
    header('Location: dashboard.php');
    exit;
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // ─── ADD ADMIN ───
    if ($action === 'add') {

        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = $_POST['password']       ?? '';
        $role      = $_POST['role']           ?? '';

        // Validate
        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        }
        if (empty($email)) {
            $errors[] = "Email address is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        }
        if (!in_array($role, ['admin', 'super_admin'])) {
            $errors[] = "Please select a valid role.";
        }

        // Check email not already taken
        if (empty($errors)) {
            $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "An admin with this email already exists.";
            }
        }

        // Insert
        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $db->prepare("
                INSERT INTO admins (full_name, email, password, role)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$full_name, $email, $hashed, $role]);
            $success = "Admin \"$full_name\" added successfully.";
        }
    }

    // ─── EDIT ROLE ───
    elseif ($action === 'edit_role') {
        $admin_id = (int)($_POST['admin_id'] ?? 0);
        $new_role = $_POST['new_role'] ?? '';

        // Prevent super admin from changing their own role
        if ($admin_id === (int)$_SESSION['admin_id']) {
            $errors[] = "You cannot change your own role.";

        } elseif (!in_array($new_role, ['admin', 'super_admin'])) {
            $errors[] = "Please select a valid role.";

        } elseif ($admin_id > 0) {
            $stmt = $db->prepare("UPDATE admins SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $admin_id]);
            $success = "Role updated successfully.";
        }
    }

    // ─── REMOVE ADMIN ───
    elseif ($action === 'remove') {
        $admin_id = (int)($_POST['admin_id'] ?? 0);

        // Prevent super admin from removing themselves
        if ($admin_id === (int)$_SESSION['admin_id']) {
            $errors[] = "You cannot remove yourself.";

        } elseif ($admin_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ? AND role != 'super_admin'");
            $stmt->execute([$admin_id]);
            $success = "Admin removed successfully.";
        }
    }
}

// ─── Fetch all admins ───
$admins = $db->query("
    SELECT id, full_name, email, role, created_at
    FROM admins
    ORDER BY created_at ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admins & Roles — BlogForge Admin</title>
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
      <span class="topbar-title">Admins & Roles</span>
      <div class="topbar-right">
        <div class="topbar-profile">
          <?= strtoupper(substr($_SESSION['admin_name'], 0, 2)) ?>
        </div>
      </div>
    </header>

    <div class="content">
      <div class="section-header">
        <div>
          <h2>Admins & Roles</h2>
          <p>Manage who has access to this admin panel.</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('addAdminModal')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add Admin
        </button>
      </div>

      <!-- SUCCESS -->
      <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom:20px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20,6 9,17 4,12"/></svg>
          <div><?= htmlspecialchars($success) ?></div>
        </div>
      <?php endif; ?>

      <!-- ERRORS -->
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="margin-bottom:20px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <div>
            <?php foreach ($errors as $error): ?>
              <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- INFO ALERT -->
      <div class="alert alert-info">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div>Only <strong>Super Admins</strong> can add, remove, or change the role of other admins.</div>
      </div>

      <!-- ADMINS TABLE -->
      <div class="card" style="padding:0;margin-bottom:20px">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Added</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($admins as $admin): ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:10px">
                      <div class="admin-avatar" style="background:<?= $admin['role'] === 'super_admin' ? 'linear-gradient(135deg,var(--accent),var(--blue));color:#0a0a0f' : 'var(--blue-dim);color:var(--blue)' ?>">
                        <?= strtoupper(substr($admin['full_name'], 0, 2)) ?>
                      </div>
                      <div>
                        <div style="font-weight:600;font-size:.88rem">
                          <?= htmlspecialchars($admin['full_name']) ?>
                        </div>
                        <div style="font-size:.73rem;color:var(--text-muted)">
                          <?= $admin['id'] === (int)$_SESSION['admin_id'] ? 'You' : 'Added ' . date('M j', strtotime($admin['created_at'])) ?>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td style="color:var(--text-secondary);font-size:.82rem">
                    <?= htmlspecialchars($admin['email']) ?>
                  </td>
                  <td>
                    <?php if ($admin['role'] === 'super_admin'): ?>
                      <span class="badge badge-super">Super Admin</span>
                    <?php else: ?>
                      <span class="badge badge-admin">Admin</span>
                    <?php endif; ?>
                  </td>
                  <td style="color:var(--text-muted);font-size:.78rem">
                    <?= date('M j, Y', strtotime($admin['created_at'])) ?>
                  </td>
                  <td>
                    <?php if ($admin['id'] === (int)$_SESSION['admin_id']): ?>
                      <!-- Current logged in admin — no actions -->
                      <span style="font-size:.77rem;color:var(--text-muted)">— Owner —</span>
                    <?php else: ?>
                      <div style="display:flex;gap:6px">

                        <!-- Edit role button -->
                        <button
                          class="btn btn-ghost btn-sm"
                          onclick='openEditRole(
                            <?= $admin["id"] ?>,
                            <?= json_encode($admin["full_name"]) ?>,
                            <?= json_encode($admin["role"]) ?>
                          )'
                        >Edit Role</button>

                        <!-- Remove form -->
                        <form method="POST" style="display:inline">
                          <input type="hidden" name="action"   value="remove"/>
                          <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>"/>
                          <button
                            type="submit"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Remove <?= htmlspecialchars($admin['full_name'], ENT_QUOTES) ?>? This cannot be undone.')"
                          >Remove</button>
                        </form>

                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- PERMISSIONS TABLE -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Role Permissions</div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Permission</th>
                <th style="text-align:center">Super Admin</th>
                <th style="text-align:center">Admin</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td style="color:var(--text-secondary);font-size:.85rem">Approve / Decline Posts</td>
                <td style="text-align:center;color:var(--green)">✓</td>
                <td style="text-align:center;color:var(--green)">✓</td>
              </tr>
              <tr>
                <td style="color:var(--text-secondary);font-size:.85rem">Create &amp; Edit Posts</td>
                <td style="text-align:center;color:var(--green)">✓</td>
                <td style="text-align:center;color:var(--green)">✓</td>
              </tr>
              <tr>
                <td style="color:var(--text-secondary);font-size:.85rem">Manage Categories</td>
                <td style="text-align:center;color:var(--green)">✓</td>
                <td style="text-align:center;color:var(--green)">✓</td>
              </tr>
              <tr>
                <td style="color:var(--text-secondary);font-size:.85rem">Add / Remove Admins</td>
                <td style="text-align:center;color:var(--green)">✓</td>
                <td style="text-align:center;color:var(--text-muted)">—</td>
              </tr>
              <tr>
                <td style="color:var(--text-secondary);font-size:.85rem">View Admin Panel</td>
                <td style="text-align:center;color:var(--green)">✓</td>
                <td style="text-align:center;color:var(--green)">✓</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ─── MODAL: Add Admin ─── -->
<div class="modal-overlay" id="addAdminModal" onclick="closeModalOnOverlay(event,'addAdminModal')">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add New Admin</div>
      <div class="modal-close" onclick="closeModal('addAdminModal')">✕</div>
    </div>
    <form method="POST" action="admins.php">
      <input type="hidden" name="action" value="add"/>
      <div class="form-grid">

        <div class="form-group full">
          <label>Full Name <span class="required">*</span></label>
          <input type="text" name="full_name" placeholder="e.g. John Smith"/>
        </div>

        <div class="form-group full">
          <label>Email Address <span class="required">*</span></label>
          <input type="email" name="email" placeholder="john@blog.com"/>
        </div>

        <div class="form-group full">
          <label>Role <span class="required">*</span></label>
          <select name="role">
            <option value="">— Select Role —</option>
            <option value="admin">Admin</option>
            <option value="super_admin">Super Admin</option>
          </select>
        </div>

        <div class="form-group full">
          <label>Temporary Password <span class="required">*</span></label>
          <input type="password" name="password" placeholder="Min. 8 characters"/>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addAdminModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Admin</button>
      </div>
    </form>
  </div>
</div>

<!-- ─── MODAL: Edit Role ─── -->
<div class="modal-overlay" id="editRoleModal" onclick="closeModalOnOverlay(event,'editRoleModal')">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Role — <span id="editRoleName"></span></div>
      <div class="modal-close" onclick="closeModal('editRoleModal')">✕</div>
    </div>
    <form method="POST" action="admins.php">
      <input type="hidden" name="action"   value="edit_role"/>
      <input type="hidden" name="admin_id" id="editRoleAdminId"/>
      <div class="form-grid">

        <div class="form-group full">
          <label>Change Role To</label>
          <select name="new_role" id="editRoleSelect">
            <option value="admin">Admin</option>
            <option value="super_admin">Super Admin</option>
          </select>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('editRoleModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Role</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
  function openEditRole(id, name, currentRole) {
    document.getElementById('editRoleAdminId').value  = id;
    document.getElementById('editRoleName').textContent = name;
    document.getElementById('editRoleSelect').value   = currentRole;
    openModal('editRoleModal');
  }
</script>
</body>
</html>
