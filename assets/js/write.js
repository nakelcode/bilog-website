// ─── Nav dropdown ───
function toggleDropdown() {
  document.getElementById('navUser').classList.toggle('open');
}
document.addEventListener('click', (e) => {
  const navUser = document.getElementById('navUser');
  if (navUser && !navUser.contains(e.target)) navUser.classList.remove('open');
});

// ─── Featured image preview ───
function handleImageUpload(input) {
  const preview = document.getElementById('imagePreview');
  const zone    = document.getElementById('uploadZone');
  const hint    = document.getElementById('uploadHint');

  if (!input.files || !input.files[0]) return;

  const file = input.files[0];

  if (!file.type.startsWith('image/')) {
    showError('Please upload a valid image file (JPG, PNG, WEBP).');
    input.value = '';
    return;
  }
  if (file.size > 5 * 1024 * 1024) {
    showError('Image is too large. Maximum size is 5MB.');
    input.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = (e) => {
    preview.querySelector('img').src = e.target.result;
    preview.classList.add('show');
    zone.classList.add('has-file');
    hint.textContent = file.name;
  };
  reader.readAsDataURL(file);
}

// ─── Body character counter ───
function updateCounter() {
  const body    = document.getElementById('post_body');
  const counter = document.getElementById('bodyCounter');
  const len     = body.value.length;
  counter.textContent = len + ' characters';
  counter.classList.toggle('warn', len > 0 && len < 100);
}

// ─── Toolbar formatting (basic textarea insert) ───
function insertFormat(tag) {
  const ta  = document.getElementById('post_body');
  const start = ta.selectionStart;
  const end   = ta.selectionEnd;
  const sel   = ta.value.substring(start, end);

  const formats = {
    bold:   `**${sel || 'bold text'}**`,
    italic: `*${sel || 'italic text'}*`,
    h2:     `\n## ${sel || 'Heading'}`,
    h3:     `\n### ${sel || 'Subheading'}`,
    ul:     `\n- ${sel || 'List item'}`,
    ol:     `\n1. ${sel || 'List item'}`,
    quote:  `\n> ${sel || 'Quote here'}`,
    code:   `\`${sel || 'code'}\``,
  };

  const insert = formats[tag] || sel;
  ta.value = ta.value.substring(0, start) + insert + ta.value.substring(end);
  ta.focus();
  updateCounter();
}

// ─── Form validation before submit ───
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('writeForm');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    const title    = document.getElementById('post_title').value.trim();
    const category = document.getElementById('category_id').value;
    const body     = document.getElementById('post_body').value.trim();

    if (!title) {
      e.preventDefault();
      showError('Please enter a post title.');
      document.getElementById('post_title').focus();
      return;
    }
    if (!category) {
      e.preventDefault();
      showError('Please select a category for your post.');
      document.getElementById('category_id').focus();
      return;
    }
    // if (body.length < 100) {
    //   e.preventDefault();
    //   showError('Your post content is too short. Please write at least 100 characters.');
    //   document.getElementById('post_body').focus();
    //   return;
    // }
  });
});

// ─── Show inline error ───
function showError(msg) {
  let box = document.getElementById('jsError');
  if (!box) {
    box = document.createElement('div');
    box.id = 'jsError';
    box.className = 'alert alert-error';
    const form = document.getElementById('writeForm');
    form.parentNode.insertBefore(box, form);
  }
  box.innerHTML = `<p>${msg}</p>`;
  box.scrollIntoView({ behavior: 'smooth', block: 'center' });
  setTimeout(() => { if (box) box.remove(); }, 5000);
}
