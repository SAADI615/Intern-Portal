/* ============================================================
   InternPortal – main.js
   SE-322 Software Engineering Web Application Lab
   Student: Dhroubo Jyoti Mondal | ID: 232-35-182
   ============================================================ */

'use strict';

/* ── Utility helpers ─────────────────────────────────────── */
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

function showAlert(elId, msg, type = 'success', duration = 3500) {
  const el = document.getElementById(elId);
  if (!el) return;
  el.textContent = msg;
  el.className = `alert ${type} show`;
  if (duration) setTimeout(() => el.classList.remove('show'), duration);
}

function openModal(id)  { document.getElementById(id)?.classList.add('show'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('show'); }

/* Close modal on overlay click */
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
  }
});

/* ── Active nav link (based on current page filename) ───── */
(function markActiveNav() {
  const page = location.pathname.split('/').pop() || 'index.php';
  $$('.nav-link[data-page]').forEach(a => {
    if (a.dataset.page === page) a.classList.add('active');
  });
})();

/* ── Auth tabs (login / register) ───────────────────────── */
function initAuthTabs() {
  const tabs    = $$('.auth-tab');
  const loginForm  = document.getElementById('login-form');
  const registerForm = document.getElementById('register-form');
  if (!tabs.length) return;

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const which = tab.dataset.tab;
      if (which === 'login') {
        if (loginForm) loginForm.classList.remove('hidden');
        if (registerForm) registerForm.classList.add('hidden');
      } else {
        if (loginForm) loginForm.classList.add('hidden');
        if (registerForm) registerForm.classList.remove('hidden');
      }
    });
  });
}

/* ── Login form validation ───────────────────────────────── */
function initLoginForm() {
  const form = document.getElementById('login-form');
  if (!form) return;

  form.addEventListener('submit', e => {
    let valid = true;

    const email = form.querySelector('[name="email"]');
    const pass  = form.querySelector('[name="password"]');

    [email, pass].forEach(f => {
      const err = f.nextElementSibling;
      if (!f.value.trim()) {
        if (err?.classList.contains('form-error')) {
          err.textContent = 'This field is required.';
          err.classList.add('show');
        }
        valid = false;
      } else {
        err?.classList.remove('show');
      }
    });

    if (!valid) e.preventDefault();
  });
}

/* ── Register form validation ───────────────────────────── */
function initRegisterForm() {
  const form = document.getElementById('register-form');
  if (!form) return;

  form.addEventListener('submit', e => {
    let valid = true;

    const pass  = form.querySelector('[name="password"]');
    const pass2 = form.querySelector('[name="confirm_password"]');
    const err2  = pass2?.nextElementSibling;

    if (pass && pass2 && pass.value !== pass2.value) {
      if (err2?.classList.contains('form-error')) {
        err2.textContent = 'Passwords do not match.';
        err2.classList.add('show');
      }
      valid = false;
    } else {
      err2?.classList.remove('show');
    }

    const required = $$('[required]', form);
    required.forEach(f => {
      const err = f.nextElementSibling;
      if (!f.value.trim()) {
        if (err?.classList.contains('form-error')) {
          err.textContent = 'This field is required.';
          err.classList.add('show');
        }
        valid = false;
      } else {
        err?.classList.remove('show');
      }
    });

    if (!valid) e.preventDefault();
  });
}

/* ── File upload drag-and-drop ───────────────────────────── */
function initUploadZone() {
  const zone  = document.getElementById('upload-zone');
  const input = document.getElementById('file-input');
  const label = document.getElementById('upload-label');
  if (!zone || !input) return;

  zone.addEventListener('click', () => input.click());

  zone.addEventListener('dragover', e => {
    e.preventDefault();
    zone.classList.add('dragover');
  });
  zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));

  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) setFile(file, input, label);
  });

  input.addEventListener('change', () => {
    if (input.files[0]) setFile(input.files[0], input, label);
  });
}

function setFile(file, input, label) {
  /* Transfer to real input via DataTransfer */
  try {
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
  } catch (_) {}

  if (label) {
    label.innerHTML = `<span style="font-size:20px">📄</span>
      <span class="upload-text">${escHtml(file.name)}</span>
      <span class="upload-sub">${(file.size / 1024).toFixed(1)} KB – click to change</span>`;
  }
}

/* ── Submission form ─────────────────────────────────────── */
function initSubmitForm() {
  const form = document.getElementById('submit-form');
  if (!form) return;

  form.addEventListener('submit', e => {
    const file = document.getElementById('file-input');
    const err  = document.getElementById('file-error');
    if (file && !file.files.length) {
      e.preventDefault();
      if (err) { err.textContent = 'Please select a file to upload.'; err.classList.add('show'); }
    } else {
      err?.classList.remove('show');
    }
  });
}

/* ── Grade display bars ──────────────────────────────────── */
function initGradeBars() {
  $$('.grade-fill[data-pct]').forEach(bar => {
    const pct = parseInt(bar.dataset.pct, 10);
    bar.style.width = pct + '%';
    bar.classList.add(pct >= 75 ? 'high' : pct >= 50 ? 'mid' : 'low');
  });
}

/* ── Sidebar active item ─────────────────────────────────── */
function initSidebarActive() {
  const page = location.pathname.split('/').pop();
  $$('.sidebar-item[data-page]').forEach(item => {
    if (item.dataset.page === page) item.classList.add('active');
  });
}

/* ── Confirm-delete buttons ──────────────────────────────── */
function initConfirmDelete() {
  $$('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', e => {
      if (!confirm(btn.dataset.confirm)) e.preventDefault();
    });
  });
}

/* ── Auto-dismiss flash messages ─────────────────────────── */
function initFlashMessages() {
  $$('.alert.show').forEach(el => {
    setTimeout(() => el.classList.remove('show'), 4000);
  });
}

/* ── Mentor: grade modal ─────────────────────────────────── */
function openGradeModal(subId, projectName, internName) {
  document.getElementById('grade-sub-id').value    = subId;
  document.getElementById('grade-modal-title').textContent = `Grade: ${projectName}`;
  document.getElementById('grade-intern-name').textContent = internName;
  openModal('grade-modal');
}

/* ── Admin: confirm status toggle ───────────────────────── */
function confirmToggle(userId, currentStatus) {
  const action = currentStatus === 'active' ? 'deactivate' : 'activate';
  return confirm(`Are you sure you want to ${action} this user?`);
}

/* ── Small helper: HTML escape ───────────────────────────── */
function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/* ── Grade slider live preview ───────────────────────────── */
function initGradeSlider() {
  const slider  = document.getElementById('grade-slider');
  const display = document.getElementById('grade-display');
  if (!slider || !display) return;
  slider.addEventListener('input', () => {
    display.textContent = slider.value + '%';
    const bar = document.getElementById('grade-preview-bar');
    if (bar) {
      bar.style.width = slider.value + '%';
      bar.className = 'grade-fill ' + (slider.value >= 75 ? 'high' : slider.value >= 50 ? 'mid' : 'low');
    }
  });
}

/* ── Boot ────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initAuthTabs();
  initLoginForm();
  initRegisterForm();
  initUploadZone();
  initSubmitForm();
  initGradeBars();
  initSidebarActive();
  initConfirmDelete();
  initFlashMessages();
  initGradeSlider();
});