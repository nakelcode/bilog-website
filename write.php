<?php 
session_start();
require_once "includes/db.php";
require_once "includes/auth.php";

$errors = [];
$success = "";
$post_content = '';
$excerpt = '';
$category_id = 0;


if($_SERVER['REQUEST_METHOD'] === 'POST'){

  $post_title = trim($_POST['post_title'] ?? '');
  $category_id = (int)($_POST['category_id'] ?? 0);
  $excerpt = trim($_POST['excerpt'] ?? '');
  $post_content = trim($_POST['post_body'] ?? '');
  // $feat_image = trim($_POST['featured_image'] ?? '');
  
  if(empty($post_title)){
    $errors[] = "Post title is required.";
  }
  if(empty($post_content)){
    $errors[] = "Post content is required.";
  }
  if($category_id === 0){
    $errors[] = "Please select a category.";
  }
  // if(empty($feat_image)){
  //   $errors[] = "Please image is required.";
  // }

  $feat_image = null;

  if(!empty($_FILES['featured_image']['name'])){
    $file = $_FILES['featured_image'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size  = 5 * 1024 * 1024;

    if (!in_array($file['type'], $allowed)) {
      $errors[] = "Image must be JPG, PNG or WEBP.";

      } elseif ($file['size'] > $max_size) {
          $errors[] = "Image must be smaller than 5MB.";

      } else {
          // Generate a unique filename so two files with the same
          // name never overwrite each other
          $extension      = pathinfo($file['name'], PATHINFO_EXTENSION);
          $new_filename   = uniqid('post_') . '.' . $extension;
          $upload_path    = 'uploads/posts/' . $new_filename;

          if (move_uploaded_file($file['tmp_name'], $upload_path)) {
              $feat_image = $new_filename;
          } else {
              $errors[] = "Image upload failed. Please try again.";
          }
    }

  }

  if(empty($errors)){
    $stmt = $db->prepare("INSERT INTO posts (title, body, excerpt, featured_image, status, author_type, user_id, category_id) VALUES (?, ?, ?, ?, 'pending', 'user', ?, ?)");
    $stmt->execute([
      $post_title,
      $post_content,
      $excerpt ?: null,
      $feat_image,
      $_SESSION['user_id'],
      $category_id
    ]);

    header('Location: profile.php?success=1');
    exit;
  }

}

$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();



?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Write a Post — BlogForge</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/write.css"/>
  <link rel="stylesheet" href="assets/css/bilog.css"/>
</head>
<body>

  <!-- NAVBAR -->
<?php require_once "includes/header.php";?>


  <!-- ═══════ PAGE ═══════ -->
  <div class="page-wrapper">

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-tag">Share your thoughts</div>
      <h1 class="page-title">Write a Post</h1>
      <p class="page-subtitle">
        Submit your article for review. Once approved by our team,
        it will be published on BlogForge for everyone to read.
      </p>
    </div>

    <!-- SUCCESS MESSAGE (PHP shows this after redirect) -->
    <!--
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20,6 9,17 4,12"/></svg>
          Your post has been submitted! We will review it shortly and you can track its status on your profile.
        </div>
      <?php endif; ?>
    -->

    <!-- PHP ERROR OUTPUT -->
    <!--
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <?php foreach ($errors as $error): ?>
            <p><?= htmlspecialchars($error) ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    -->

    <!-- ═══════ FORM ═══════ -->
    <form id="writeForm" action="write.php" method="POST" enctype="multipart/form-data" class="write-form">

      <!-- SECTION 1: POST DETAILS -->
      <div class="form-section">
        <div class="form-section-label">Post Details</div>

        <div class="form-group">
          <label for="post_title">Post Title <span class="required">*</span></label>
          <input
            type="text"
            id="post_title"
            name="post_title"
            placeholder="Write a clear, compelling title…"
            value="<?php echo htmlspecialchars($post_title ?? ''); ?>"
            maxlength="255"
          />
        </div>

        <div class="form-group">
          <label for="category_id">Category <span class="required">*</span></label>
          <select id="category_id" name="category_id">
            <option value="">— Select a category —</option>
            <!-- PHP populates these from the database -->
             <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= (isset($category_id) && $category_id == $cat['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?> 
            <!-- Static options for HTML preview -->
            <!-- <option value="1"></option> -->
          </select>
        </div>

        <div class="form-group">
          <label for="excerpt">Short Summary</label>
          <input
            type="text"
            id="excerpt"
            name="excerpt"
            placeholder="One or two sentences about your post (optional)"
            value="<?php echo htmlspecialchars($excerpt ?? ''); ?>"
            maxlength="300"
          />
          <span class="field-hint">If left blank, the first 150 characters of your content will be used.</span>
        </div>
      </div>

      <!-- SECTION 2: CONTENT -->
      <div class="form-section">
        <div class="form-section-label">Post Content</div>

        <div class="form-group">
          <label for="post_body">Your Article <span class="required">*</span></label>

          <div class="editor-toolbar">
            <button type="button" class="toolbar-btn" onclick="insertFormat('bold')"><b>B</b></button>
            <button type="button" class="toolbar-btn" onclick="insertFormat('italic')"><i>I</i></button>
            <div class="toolbar-sep"></div>
            <button type="button" class="toolbar-btn" onclick="insertFormat('h2')">H2</button>
            <button type="button" class="toolbar-btn" onclick="insertFormat('h3')">H3</button>
            <div class="toolbar-sep"></div>
            <button type="button" class="toolbar-btn" onclick="insertFormat('ul')">• List</button>
            <button type="button" class="toolbar-btn" onclick="insertFormat('ol')">1. List</button>
            <div class="toolbar-sep"></div>
            <button type="button" class="toolbar-btn" onclick="insertFormat('quote')">" Quote</button>
            <button type="button" class="toolbar-btn" onclick="insertFormat('code')">`Code`</button>
          </div>

          <textarea
            id="post_body"
            name="post_body"
            placeholder="Write your full article here. Be clear, thoughtful, and engaging…"
            oninput="updateCounter()"
          ><?php echo htmlspecialchars($post_body ?? ''); ?></textarea>

          <div class="char-counter" id="bodyCounter">0 characters</div>
        </div>
      </div>

      <!-- SECTION 3: FEATURED IMAGE -->
      <div class="form-section">
        <div class="form-section-label">Featured Image</div>

        <div class="form-group">
          <label>Cover Photo</label>
          <div class="upload-zone" id="uploadZone">
            <input
              type="file"
              name="featured_image"
              accept="image/jpeg,image/png,image/webp"
              onchange="handleImageUpload(this)"
            />
            <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <polyline points="16,16 12,12 8,16"/>
              <line x1="12" y1="12" x2="12" y2="21"/>
              <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
            </svg>
            <div class="upload-text">
              Drop an image here or <span>browse files</span>
            </div>
            <div class="upload-hint" id="uploadHint">JPG, PNG, WEBP · Max 5MB · Optional</div>
          </div>
          <div class="upload-preview" id="imagePreview">
            <img src="" alt="Preview"/>
          </div>
        </div>
      </div>

      <!-- SUBMIT -->
      <div class="submit-area">
        <p class="submit-note">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Your post will be reviewed by our team before it goes live.
          You can track its status from your profile page.
        </p>
        <button type="submit" class="btn-submit">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="22" y1="2" x2="11" y2="13"/>
            <polygon points="22,2 15,22 11,13 2,9"/>
          </svg>
          Submit for Review
        </button>
      </div>

    </form>
  </div>

  <script src="assets/js/write.js"></script>
  <script src="assets/js/bilog.js"></script>
</body>
</html>
