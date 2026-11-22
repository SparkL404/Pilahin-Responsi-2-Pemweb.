(function () {
  'use strict';

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form || form.tagName !== 'FORM') {
      return;
    }

    if (form.dataset.submitting === '1') {
      event.preventDefault();
      return;
    }

    var confirmMessage = form.getAttribute('data-confirm');
    if (confirmMessage && !window.confirm(confirmMessage)) {
      event.preventDefault();
      return;
    }

    var method = (form.getAttribute('method') || 'get').toLowerCase();
    if (method === 'post') {
      form.dataset.submitting = '1';
      var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
      window.setTimeout(function () {
        buttons.forEach(function (btn) {
          btn.disabled = true;
        });
      }, 0);
    }
  });

  function clearFieldInvalid(event) {
    var el = event.target;
    if (!el || !el.matches('input, select, textarea')) {
      return;
    }
    if (el.getAttribute('aria-invalid') === 'true') {
      el.removeAttribute('aria-invalid');
    }
  }

  document.addEventListener('input', clearFieldInvalid);
  document.addEventListener('change', clearFieldInvalid);

  document.addEventListener('click', function (event) {
    var dismissBtn = event.target.closest('[data-dismiss-flash]');
    if (dismissBtn) {
      var flashEl = dismissBtn.closest('.flash');
      if (flashEl) {
        flashEl.remove();
      }
      return;
    }

    var togglePwBtn = event.target.closest('[data-toggle-password]');
    if (togglePwBtn) {
      var targetId = togglePwBtn.getAttribute('data-toggle-password');
      var pwInput = targetId ? document.getElementById(targetId) : null;
      if (pwInput) {
        var reveal = pwInput.type === 'password';
        pwInput.type = reveal ? 'text' : 'password';
        togglePwBtn.setAttribute('aria-pressed', reveal ? 'true' : 'false');
        togglePwBtn.textContent = reveal ? 'Sembunyikan' : 'Tampilkan';
      }
    }
  });

  document.addEventListener('input', function (event) {
    var picker = event.target;
    if (!picker || !picker.matches('input[type="color"][data-color-output]')) {
      return;
    }
    var outId = picker.getAttribute('data-color-output');
    var outEl = outId ? document.getElementById(outId) : null;
    if (outEl) {
      outEl.textContent = String(picker.value || '').toUpperCase();
    }
  });
})();
