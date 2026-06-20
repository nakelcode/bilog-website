profile.js ;
// ─── Edit Panel Toggle ───
function toggleEditPanel() {
  const panel = document.getElementById('editPanel');
  const btn   = document.getElementById('editBtn');
  const isOpen = panel.classList.contains('open');
  panel.classList.toggle('open');
  btn.textContent = isOpen ? 'Edit Profile' : 'Cancel';
  if (!isOpen) panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ─── Avatar Preview ───
function handleAvatarChange(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  if (!file.type.startsWith('image/')) {
    showToast('Please select an image file.', true); return;
  }
  if (file.size > 2 * 1024 * 1024) {
    showToast('Image must be under 2MB.', true); return;
  }
  const reader = new FileReader();
  reader.onload = (e) => {
    const avatarEl = document.getElementById('avatarDisplay');
    avatarEl.innerHTML = `<img src="${e.target.result}" alt="Avatar"/>`;
    // Also update navbar avatar
    const navAvatar = document.getElementById('navAvatar');
    if (navAvatar) navAvatar.innerHTML = `<img src="${e.target.result}" alt="Avatar"/>`;
  };
  reader.readAsDataURL(file);
}

// ─── Trigger hidden file input ───
function triggerAvatarInput() {
  document.getElementById('avatarInput').click();
}

// ─── Save Profile (frontend demo — wire to PHP with fetch/form submit) ───
function saveProfile() {
  const name = document.getElementById('inputName').value.trim();
  const newPass = document.getElementById('inputNewPass').value;
  const confirmPass = document.getElementById('inputConfirmPass').value;

  if (!name) { showToast('Name cannot be empty.', true); return; }

  if (newPass || confirmPass) {
    if (newPass.length < 8) { showToast('Password must be at least 8 characters.', true); return; }
    if (newPass !== confirmPass) { showToast('Passwords do not match.', true); return; }
  }

  // Update displayed name
  document.getElementById('displayName').textContent = name;
  document.getElementById('navUsername').textContent  = name;

  // Clear password fields
  document.getElementById('inputNewPass').value     = '';
  document.getElementById('inputConfirmPass').value = '';

  // Close panel
  const panel = document.getElementById('editPanel');
  panel.classList.remove('open');
  document.getElementById('editBtn').textContent = 'Edit Profile';

  showToast('Profile updated successfully ✓');
}

// ─── Tab Switching ───
function switchTab(el, filter) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');

  const cards = document.querySelectorAll('.post-card');
  let visible = 0;

  cards.forEach(card => {
    const status = card.dataset.status;
    const show = filter === 'all' || status === filter;
    card.style.display = show ? 'grid' : 'none';
    if (show) visible++;
  });

  const empty = document.getElementById('emptyState');
  if (empty) empty.classList.toggle('show', visible === 0);
}

// ─── Delete Post (pending only) ───
function deletePost(btn) {
  if (!confirm('Delete this post? This cannot be undone.')) return;
  const card = btn.closest('.post-card');
  card.style.opacity = '0';
  card.style.transform = 'translateY(-6px)';
  card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
  setTimeout(() => {
    card.remove();
    showToast('Post deleted.');
  }, 300);
}

// ─── Nav Dropdown ───
function toggleDropdown() {
  document.getElementById('navUser').classList.toggle('open');
}
document.addEventListener('click', (e) => {
  const navUser = document.getElementById('navUser');
  if (navUser && !navUser.contains(e.target)) {
    navUser.classList.remove('open');
  }
});

// ─── Toast ───
function showToast(msg, isError = false) {
  const existing = document.querySelector('.toast');
  if (existing) existing.remove();

  const t = document.createElement('div');
  t.className = 'toast' + (isError ? ' error' : '');
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => {
    t.style.opacity = '0';
    t.style.transition = 'opacity 0.3s ease';
    setTimeout(() => t.remove(), 300);
  }, 2800);
}
