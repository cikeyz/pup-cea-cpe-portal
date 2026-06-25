/* =====================================================================
   auth.js  ·  shared behavior for the Activity-08 auth pages
   ---------------------------------------------------------------------
   Only two small jobs that every auth page needs:
     1. Toggle password visibility on any [data-toggle-password] button.
     2. Confirm-password match check on the pages that have one.
   Kept vanilla and dependency-free so it loads on every auth screen.
   ===================================================================== */

/* --- 1. PASSWORD TOGGLE ---
   Why: a "show" button next to every password field so the user can
   verify what they typed before submitting. */
function initPasswordToggles() {
  var toggles = document.querySelectorAll('[data-toggle-password]');
  toggles.forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      var targetId = toggle.getAttribute('data-toggle-password');
      var input = document.getElementById(targetId);
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        toggle.textContent = 'hide';
      } else {
        input.type = 'password';
        toggle.textContent = 'show';
      }
    });
  });
}

/* --- 2. CONFIRM-PASSWORD MATCH ---
   Why: warn early (before submit) when the two password fields disagree,
   using the native setCustomValidity so the browser blocks the submit. */
function initConfirmMatch() {
  var pwd = document.getElementById('password');
  var confirm = document.getElementById('confirm_password');
  if (!pwd || !confirm) return;
  var sync = function () {
    confirm.setCustomValidity(
      confirm.value !== pwd.value ? 'Passwords do not match.' : ''
    );
  };
  pwd.addEventListener('input', sync);
  confirm.addEventListener('input', sync);
}

document.addEventListener('DOMContentLoaded', function () {
  initPasswordToggles();
  initConfirmMatch();
});
