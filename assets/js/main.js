// ─── Sidebar (mobile) ───
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
}

// ─── Modals ───
function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}
function closeModalOnOverlay(e, id) {
  if (e.target === document.getElementById(id)) closeModal(id);
}

// ─── Toast notification ───
function showToast(msg, color) {
  const colors = { green: 'var(--green)', red: 'var(--red)', accent: 'var(--accent)', orange: 'var(--orange)' };
  const col = colors[color] || colors.accent;
  const t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = `
    position:fixed;bottom:22px;right:22px;
    background:var(--bg-raised);border:1px solid ${col};
    color:${col};padding:11px 18px;border-radius:10px;
    font-size:.84rem;font-weight:600;
    font-family:'Instrument Sans',sans-serif;
    z-index:9998;box-shadow:0 4px 20px rgba(0,0,0,0.4);
    animation:toastIn .25s ease;
  `;
  if (!document.getElementById('toastStyle')) {
    const s = document.createElement('style');
    s.id = 'toastStyle';
    s.textContent = '@keyframes toastIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}';
    document.head.appendChild(s);
  }
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2800);
}

// ─── Highlight active nav link based on current page filename ───
document.addEventListener('DOMContentLoaded', () => {
  const path = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-item[href]').forEach(el => {
    if (el.getAttribute('href') === path) el.classList.add('active');
  });
});
