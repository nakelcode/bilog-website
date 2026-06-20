<?php
session_start();
require_once '../includes/db.php';
require_once 'includes/auth.php';

$errors  = [];

// ─── Repopulate fields if validation fails ───
$title   = '';
$content = '';
$excerpt = '';
$cat_id  = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ─── 1. Collect inputs ───
    $title       = trim($_POST['title']       ?? '');
    $content     = trim($_POST['content']     ?? '');
    $excerpt     = trim($_POST['excerpt']     ?? '');
    $cat_id      = (int)($_POST['category_id'] ?? 0);

    // ─── 2. Validate ───
    if (empty($title)) {
        $errors[] = "Post title is required.";
    }
    if (empty($content)) {
        $errors[] = "Post content is required.";
    }
    if ($cat_id === 0) {
        $errors[] = "Please select a category.";
    }

    // ─── 3. Handle featured image upload ───
    $featured_image = null;

    if (!empty($_FILES['featured_image']['name'])) {

        $file      = $_FILES['featured_image'];
        $allowed   = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size  = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed)) {
            $errors[] = "Image must be JPG, PNG or WEBP.";

        } elseif ($file['size'] > $max_size) {
            $errors[] = "Image must be smaller than 5MB.";

        } else {
            // Generate a unique filename so two files with the same
            // name never overwrite each other
            $extension      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename   = uniqid('post_') . '.' . $extension;
            $upload_path    = '../uploads/posts/' . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $featured_image = $new_filename;
            } else {
                $errors[] = "Image upload failed. Please try again.";
            }
        }
    }

    // ─── 4. Insert into database 
    if (empty($errors)) {

        $stmt = $db->prepare("
            INSERT INTO posts (title, body, excerpt, featured_image, status, author_type, admin_id, category_id)
            VALUES (?, ?, ?, ?, 'approved', 'admin', ?, ?)
        ");
        $stmt->execute([
            $title,
            $content,
            $excerpt ?: null,
            $featured_image,
            $_SESSION['admin_id'],
            $cat_id
        ]);

        // ─── Redirect to all posts with success message
        header('Location: all-posts.php?success=1');
        exit;
    }
}

// ─── Fetch categories for dropdown ───
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create Post — BlogForge Admin</title>
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
      <span class="topbar-title">Create Post</span>
      <div class="topbar-right">
        <div class="topbar-profile">
          <?= strtoupper(substr($_SESSION['admin_name'], 0, 2)) ?>
        </div>
      </div>
    </header>

    <div class="content">
      <div class="section-header">
        <div>
          <h2>Create New Post</h2>
          <p>Write and publish a post directly to the blog.</p>
        </div>
        <button class="btn btn-primary" form="createPostForm" type="submit">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13">
            <polyline points="20,6 9,17 4,12"/>
          </svg>
          Publish Post
        </button>
      </div>

      <!-- ERRORS -->
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

      <!-- FORM -->
      <form id="createPostForm" action="create-post.php" method="POST" enctype="multipart/form-data">

        <div style="display:grid;grid-template-columns:1fr 270px;gap:20px;align-items:start">

          <!-- LEFT: MAIN CONTENT -->
          <div>
            <div class="card">
              <div class="form-grid">

                <div class="form-group full">
                  <label>Post Title <span class="required">*</span></label>
                  <input
                    type="text"
                    name="title"
                    placeholder="Enter a clear, compelling title…"
                    style="font-size:.97rem;padding:11px 13px"
                    value="<?= htmlspecialchars($title) ?>"
                  />
                </div>

                <div class="form-group full">
                  <label>Content <span class="required">*</span></label>
                  <div class="editor-toolbar">
                    <button type="button" title="Bold" onclick="insertFormat('bold')"><b>B</b></button>
                    <button type="button" title="Italic" onclick="insertFormat('italic')"><i>I</i></button>
                    <button type="button" title="Underline" onclick="insertFormat('underline')"><u>U</u></button>
                    <div class="toolbar-divider"></div>
                    <button type="button" onclick="insertFormat('h1')">H1</button>
                    <button type="button" onclick="insertFormat('h2')">H2</button>
                    <button type="button" onclick="insertFormat('h3')">H3</button>
                    <div class="toolbar-divider"></div>
                    <button type="button" onclick="insertFormat('ul')">• List</button>
                    <button type="button" onclick="insertFormat('ol')">1. List</button>
                    <div class="toolbar-divider"></div>
                    <button type="button" onclick="insertFormat('code')">`Code`</button>
                  </div>
                  <textarea
                    class="editor-area"
                    name="content"
                    rows="16"
                    placeholder="Write your post content here…"
                  ><?= htmlspecialchars($content) ?></textarea>
                </div>

                <div class="form-group full">
                  <label>Excerpt</label>
                  <textarea
                    name="excerpt"
                    rows="3"
                    placeholder="Short summary shown in post listings (optional)"
                  ><?= htmlspecialchars($excerpt) ?></textarea>
                </div>

              </div>
            </div>
          </div>

          <!-- RIGHT: SIDEBAR OPTIONS -->
          <div style="display:flex;flex-direction:column;gap:16px">

            <div class="card">
              <div class="card-title" style="margin-bottom:14px">Category</div>
              <div class="form-group">
                <label>Select Category <span class="required">*</span></label>
                <select name="category_id">
                  <option value="0">— Choose one —</option>
                  <?php foreach ($categories as $cat): ?>
                    <option
                      value="<?= $cat['id'] ?>"
                      <?= $cat_id === (int)$cat['id'] ? 'selected' : '' ?>
                    >
                      <?= htmlspecialchars($cat['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="card">
              <div class="card-title" style="margin-bottom:14px">Featured Image</div>
              <div class="upload-zone" id="uploadZone" onclick="document.getElementById('imageInput').click()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <rect x="3" y="3" width="18" height="18" rx="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5"/>
                  <polyline points="21,15 16,10 5,21"/>
                </svg>
                <p>Drop an image or <span>browse</span></p>
                <p style="font-size:.72rem;color:var(--text-muted);margin-top:5px" id="uploadHint">PNG, JPG · Max 5MB</p>
              </div>
              <input
                type="file"
                id="imageInput"
                name="featured_image"
                accept="image/jpeg,image/png,image/webp"
                style="display:none"
                onchange="handleImagePreview(this)"
              />
              <div id="imagePreview" style="display:none;margin-top:12px;border-radius:6px;overflow:hidden">
                <img id="previewImg" src="" alt="Preview" style="width:100%;height:140px;object-fit:cover;display:block"/>
              </div>
            </div>

          </div>
        </div>
      </form>

    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
  // ─── Image preview ───
  function handleImagePreview(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('previewImg').src = e.target.result;
      document.getElementById('imagePreview').style.display = 'block';
      document.getElementById('uploadHint').textContent = input.files[0].name;
    };
    reader.readAsDataURL(input.files[0]);
  }

  // ─── Toolbar formatting ───
  function insertFormat(tag) {
    const ta    = document.querySelector('textarea[name="content"]');
    const start = ta.selectionStart;
    const end   = ta.selectionEnd;
    const sel   = ta.value.substring(start, end);

    const formats = {
      bold:      `**${sel || 'bold text'}**`,
      italic:    `*${sel || 'italic text'}*`,
      underline: `__${sel || 'underline text'}__`,
      h1:        `\n# ${sel || 'Heading 1'}`,
      h2:        `\n## ${sel || 'Heading 2'}`,
      h3:        `\n### ${sel || 'Heading 3'}`,
      ul:        `\n- ${sel || 'List item'}`,
      ol:        `\n1. ${sel || 'List item'}`,
      code:      `\`${sel || 'code'}\``,
    };

    const insert = formats[tag] || sel;
    ta.value = ta.value.substring(0, start) + insert + ta.value.substring(end);
    ta.focus();
  }
</script>
</body>
</html>
