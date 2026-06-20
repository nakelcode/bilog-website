<?php
session_start();
require_once '../includes/db.php';
require_once 'includes/auth.php';

$errors  = [];
$success = '';

// ─── Repopulate fields if validation fails ───
$cat_name    = '';
$cat_desc    = '';
$cat_color   = 'accent';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // ─── ADD CATEGORY ───
    if ($action === 'add') {

        $cat_name  = trim($_POST['cat_name']  ?? '');
        $cat_desc  = trim($_POST['cat_desc']  ?? '');
        $cat_color = $_POST['cat_color'] ?? 'accent';

        // Validate color is one of the allowed options
        $allowed_colors = ['accent', 'blue', 'green', 'red', 'orange', 'purple'];
        if (!in_array($cat_color, $allowed_colors)) $cat_color = 'accent';

        if (empty($cat_name)) {
            $errors[] = "Category name is required.";
        }

        // Check if category name already exists
        if (empty($errors)) {
            $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$cat_name]);
            if ($stmt->fetch()) {
                $errors[] = "A category with this name already exists.";
            }
        }

        if (empty($errors)) {
            $stmt = $db->prepare("
                INSERT INTO categories (name, description, color)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $cat_name,
                $cat_desc ?: null,
                $cat_color
            ]);

            $success  = "Category \"$cat_name\" created successfully.";
            $cat_name = '';
            $cat_desc = '';
            $cat_color = 'accent';
        }
    }

    // ─── DELETE CATEGORY ───
    elseif ($action === 'delete') {
        $cat_id = (int)($_POST['cat_id'] ?? 0);

        if ($cat_id > 0) {
            // posts that belong to this category will have
            // category_id set to NULL automatically
            // because of ON DELETE SET NULL in the database
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$cat_id]);
            $success = "Category deleted successfully.";
        }
    }

    // ─── EDIT CATEGORY ───
    elseif ($action === 'edit') {
        $cat_id       = (int)($_POST['cat_id']    ?? 0);
        $edit_name    = trim($_POST['edit_name']  ?? '');
        $edit_desc    = trim($_POST['edit_desc']  ?? '');
        $edit_color   = $_POST['edit_color'] ?? 'accent';

        $allowed_colors = ['accent', 'blue', 'green', 'red', 'orange', 'purple'];
        if (!in_array($edit_color, $allowed_colors)) $edit_color = 'accent';

        if (empty($edit_name)) {
            $errors[] = "Category name is required.";
        }

        if (empty($errors) && $cat_id > 0) {
            $stmt = $db->prepare("
                UPDATE categories SET name = ?, description = ?, color = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $edit_name,
                $edit_desc ?: null,
                $edit_color,
                $cat_id
            ]);
            $success = "Category updated successfully.";
        }
    }
}

// ─── Fetch all categories with post count ───
$categories = $db->query("
    SELECT
        c.id,
        c.name,
        c.description,
        c.color,
        COUNT(p.id) AS post_count
    FROM categories c
    LEFT JOIN posts p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();

$total_categories = count($categories);

// ─── Map color names to CSS variables ───
function color_dot($color) {
    $map = [
        'accent' => 'var(--accent)',
        'blue'   => 'var(--blue)',
        'green'  => 'var(--green)',
        'red'    => 'var(--red)',
        'orange' => 'var(--orange)',
        'purple' => '#b47fff',
    ];
    return $map[$color] ?? 'var(--accent)';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Categories — BlogForge Admin</title>
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
      <span class="topbar-title">Categories</span>
      <div class="topbar-right">
        <div class="topbar-profile">
          <?= strtoupper(substr($_SESSION['admin_name'], 0, 2)) ?>
        </div>
      </div>
    </header>

    <div class="content">
      <div class="section-header">
        <div>
          <h2>Categories</h2>
          <p>Organise your blog posts into categories.</p>
        </div>
      </div>

      <!-- SUCCESS MESSAGE -->
      <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom:20px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="20,6 9,17 4,12"/>
          </svg>
          <div><?= htmlspecialchars($success) ?></div>
        </div>
      <?php endif; ?>

      <!-- ERROR MESSAGE -->
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="margin-bottom:20px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <div>
            <?php foreach ($errors as $error): ?>
              <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

        <!-- CATEGORIES TABLE -->
        <div class="card" style="padding:0">
          <div class="card-header" style="padding:18px 18px 0">
            <div class="card-title">All Categories</div>
            <span style="font-size:.77rem;color:var(--text-muted)"><?= $total_categories ?> total</span>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Posts</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($categories)): ?>
                  <tr>
                    <td colspan="3" style="text-align:center;padding:32px;color:var(--text-muted);font-size:0.85rem;">
                      No categories yet. Add one using the form.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($categories as $cat): ?>
                    <tr>
                      <td>
                        <div style="display:flex;align-items:center;gap:9px">
                          <div style="width:10px;height:10px;border-radius:50%;background:<?= color_dot($cat['color']) ?>;flex-shrink:0"></div>
                          <strong><?= htmlspecialchars($cat['name']) ?></strong>
                        </div>
                      </td>
                      <td style="color:var(--text-secondary)">
                        <?= $cat['post_count'] ?>
                      </td>
                      <td>
                        <div style="display:flex;gap:6px">

                          <!-- Edit button — opens edit form -->
                          <button
                            class="btn btn-ghost btn-sm"
                            onclick='openEdit(
                              <?= $cat["id"] ?>,
                              <?= json_encode($cat["name"]) ?>,
                              <?= json_encode($cat["description"] ?? "") ?>,
                              <?= json_encode($cat["color"]) ?>
                            )'
                          >Edit</button>

                          <!-- Delete form -->
                          <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="delete"/>
                            <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>"/>
                            <button
                              type="submit"
                              class="btn btn-danger btn-sm"
                              onclick="return confirm('Delete this category? Posts will become uncategorised.')"
                            >Delete</button>
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

        <!-- RIGHT COLUMN -->
        <div style="display:flex;flex-direction:column;gap:16px">

          <!-- ADD CATEGORY FORM -->
          <div class="card">
            <div class="card-title" style="margin-bottom:16px">Add New Category</div>
            <form method="POST" action="categories.php">
              <input type="hidden" name="action" value="add"/>
              <div class="form-grid">

                <div class="form-group full">
                  <label>Category Name <span class="required">*</span></label>
                  <input
                    type="text"
                    name="cat_name"
                    placeholder="e.g. Technology"
                    value="<?= htmlspecialchars($cat_name) ?>"
                  />
                </div>

                <div class="form-group full">
                  <label>Description</label>
                  <textarea
                    name="cat_desc"
                    rows="3"
                    placeholder="Short description (optional)"
                  ><?= htmlspecialchars($cat_desc) ?></textarea>
                </div>

                <div class="form-group full">
                  <label>Colour</label>
                  <div class="color-swatches">
                    <div class="swatch <?= $cat_color === 'accent' ? 'selected' : '' ?>" style="background:var(--accent)" data-color="accent" onclick="selectSwatch(this)"></div>
                    <div class="swatch <?= $cat_color === 'blue'   ? 'selected' : '' ?>" style="background:var(--blue)"   data-color="blue"   onclick="selectSwatch(this)"></div>
                    <div class="swatch <?= $cat_color === 'green'  ? 'selected' : '' ?>" style="background:var(--green)"  data-color="green"  onclick="selectSwatch(this)"></div>
                    <div class="swatch <?= $cat_color === 'red'    ? 'selected' : '' ?>" style="background:var(--red)"    data-color="red"    onclick="selectSwatch(this)"></div>
                    <div class="swatch <?= $cat_color === 'orange' ? 'selected' : '' ?>" style="background:var(--orange)" data-color="orange" onclick="selectSwatch(this)"></div>
                    <div class="swatch <?= $cat_color === 'purple' ? 'selected' : '' ?>" style="background:#b47fff"       data-color="purple" onclick="selectSwatch(this)"></div>
                  </div>
                  <input type="hidden" name="cat_color" id="cat_color" value="<?= $cat_color ?>"/>
                </div>

                <div class="form-group full">
                  <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">
                    Create Category
                  </button>
                </div>

              </div>
            </form>
          </div>

          <!-- EDIT CATEGORY FORM (hidden by default) -->
          <div class="card" id="editCard" style="display:none">
            <div class="card-title" style="margin-bottom:16px">Edit Category</div>
            <form method="POST" action="categories.php">
              <input type="hidden" name="action"  value="edit"/>
              <input type="hidden" name="cat_id"  id="edit_cat_id"/>
              <div class="form-grid">

                <div class="form-group full">
                  <label>Category Name <span class="required">*</span></label>
                  <input type="text" name="edit_name" id="edit_name" placeholder="Category name"/>
                </div>

                <div class="form-group full">
                  <label>Description</label>
                  <textarea name="edit_desc" id="edit_desc" rows="3" placeholder="Short description (optional)"></textarea>
                </div>

                <div class="form-group full">
                  <label>Colour</label>
                  <div class="color-swatches" id="editSwatches">
                    <div class="swatch" style="background:var(--accent)" data-color="accent" onclick="selectEditSwatch(this)"></div>
                    <div class="swatch" style="background:var(--blue)"   data-color="blue"   onclick="selectEditSwatch(this)"></div>
                    <div class="swatch" style="background:var(--green)"  data-color="green"  onclick="selectEditSwatch(this)"></div>
                    <div class="swatch" style="background:var(--red)"    data-color="red"    onclick="selectEditSwatch(this)"></div>
                    <div class="swatch" style="background:var(--orange)" data-color="orange" onclick="selectEditSwatch(this)"></div>
                    <div class="swatch" style="background:#b47fff"       data-color="purple" onclick="selectEditSwatch(this)"></div>
                  </div>
                  <input type="hidden" name="edit_color" id="edit_color" value="accent"/>
                </div>

                <div class="form-group full" style="display:flex;gap:8px">
                  <button class="btn btn-primary" type="submit" style="flex:1;justify-content:center">
                    Save Changes
                  </button>
                  <button class="btn btn-ghost" type="button" onclick="closeEdit()" style="flex:1;justify-content:center">
                    Cancel
                  </button>
                </div>

              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script src="../assets/css/main.js"></script>
<script>
  // ─── Add form colour swatch ───
  function selectSwatch(el) {
    document.querySelectorAll('.color-swatches:first-of-type .swatch').forEach(s => s.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('cat_color').value = el.dataset.color;
  }

  // ─── Edit form colour swatch ───
  function selectEditSwatch(el) {
    document.querySelectorAll('#editSwatches .swatch').forEach(s => s.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('edit_color').value = el.dataset.color;
  }

  // ─── Open edit form ───
  function openEdit(id, name, desc, color) {
    document.getElementById('edit_cat_id').value = id;
    document.getElementById('edit_name').value   = name;
    document.getElementById('edit_desc').value   = desc;
    document.getElementById('edit_color').value  = color;

    // Highlight the correct swatch
    document.querySelectorAll('#editSwatches .swatch').forEach(s => {
      s.classList.toggle('selected', s.dataset.color === color);
    });

    document.getElementById('editCard').style.display = 'block';
    document.getElementById('editCard').scrollIntoView({ behavior: 'smooth' });
  }

  // ─── Close edit form ───
  function closeEdit() {
    document.getElementById('editCard').style.display = 'none';
  }
</script>
</body>
</html>
