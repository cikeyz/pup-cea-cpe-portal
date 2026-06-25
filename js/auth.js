

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
