// ─── Password visibility toggle ───
function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  const isText = input.type === 'text';
  input.type = isText ? 'password' : 'text';
  btn.querySelector('.eye-show').style.display = isText ? 'block' : 'none';
  btn.querySelector('.eye-hide').style.display = isText ? 'none'  : 'block';
}
