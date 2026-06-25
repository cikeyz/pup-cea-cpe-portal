/* =====================================================================
   enroll.js  ·  enrollment-form behavior (Activity-03 base, PUP-branded)
   ---------------------------------------------------------------------
   Adapted from Activity-03's script.js, trimmed to what the enrollment
   page actually uses: mini stepper, photo + docs preview, password
   strength meter, char counters, and a pre-submit validation pass.
   ===================================================================== */

/* --- 1. ELEMENTS ---
   Grab the main nodes once so the handlers below stay readable. */
var form = document.getElementById('enroll-form');
var stepperButtons = document.querySelectorAll('#stepper button');
var formSections = document.querySelectorAll('fieldset.form-section');
var profilePhotoInput = document.getElementById('profile_photo');
var photoPreview = document.getElementById('photo-preview');
var photoPlaceholder = document.getElementById('photo-placeholder');
var schoolDocsInput = document.getElementById('school_docs');
var schoolDocsName = document.getElementById('school-docs-filename');
var passwordInput = document.getElementById('password');
var strengthBars = document.querySelectorAll('#pwd-strength .bar');
var strengthLabel = document.querySelector('#pwd-strength .strength-label');

/* --- 2. CONFIG ---
   Required field IDs + the char-counter setup, kept in one place. */
var requiredIds = [
  'first_name', 'last_name', 'dob',
  'username', 'email', 'password', 'confirm_password'
];
var counters = [
  { input: document.getElementById('short_bio'), label: document.getElementById('bio-counter'), limit: 200 }
];

var stepperState = { locked: false, timer: null, current: '' };

/* --- 3. STARTUP --- */
if (form) init();

function init() {
  bindEvents();
  counters.forEach(refreshCounter);
  paintStrength(0);
  updatePhotoPreview();
  updateDocsName();
  initStepper();
}

function bindEvents() {
  if (profilePhotoInput) profilePhotoInput.addEventListener('change', updatePhotoPreview);
  if (schoolDocsInput) schoolDocsInput.addEventListener('change', updateDocsName);
  if (passwordInput) passwordInput.addEventListener('input', onPasswordInput);
  form.addEventListener('submit', onSubmit);
  counters.forEach(function (c) {
    if (c.input) c.input.addEventListener('input', function () { refreshCounter(c.input); });
  });
}

/* --- 4. PREVIEWS ---
   Show the chosen profile photo and the school-docs filename inline. */
function updatePhotoPreview() {
  if (!profilePhotoInput || !photoPreview) return;
  var file = profilePhotoInput.files[0];
  if (file && file.type.indexOf('image/') === 0) {
    photoPreview.src = URL.createObjectURL(file);
    photoPreview.style.display = 'block';
    if (photoPlaceholder) photoPlaceholder.style.display = 'none';
    return;
  }
  photoPreview.removeAttribute('src');
  photoPreview.style.display = 'none';
  if (photoPlaceholder) photoPlaceholder.style.display = 'block';
}

function updateDocsName() {
  if (!schoolDocsInput || !schoolDocsName) return;
  schoolDocsName.textContent = schoolDocsInput.files.length > 0
    ? schoolDocsInput.files[0].name
    : 'no file selected';
}

/* --- 5. CHAR COUNTER --- */
function refreshCounter(input) {
  counters.forEach(function (c) {
    if (c.input !== input || !c.label) return;
    var len = input.value.length;
    c.label.textContent = len + ' / ' + c.limit + ' characters';
    c.label.classList.toggle('limit-reached', len > c.limit);
  });
}

/* --- 6. PASSWORD STRENGTH ---
   Simple 4-bar meter: length, mixed case+digits, then symbols. */
function onPasswordInput() {
  if (passwordInput) paintStrength(scorePassword(passwordInput.value));
}

function scorePassword(value) {
  var score = 0;
  if (value.length > 0 && value.length < 8) score = 1;
  if (value.length >= 8) {
    score = 2;
    if (/[A-Z]/.test(value) && /[0-9]/.test(value)) score = 3;
    if (/[^a-zA-Z0-9]/.test(value)) score = 4;
  }
  return score;
}

function paintStrength(score) {
  var states = [
    { label: '', color: 'var(--line)' },
    { label: 'too short', color: 'var(--error)' },
    { label: 'weak', color: 'var(--pup-gold-dark, #8a5d00)' },
    { label: 'fair', color: '#8b6a19' },
    { label: 'strong', color: 'var(--success)' }
  ];
  var state = states[score] || states[0];
  strengthBars.forEach(function (bar, i) {
    bar.style.backgroundColor = i < score ? state.color : 'var(--line)';
  });
  if (strengthLabel) {
    strengthLabel.style.color = score ? state.color : 'var(--ink-soft)';
    strengthLabel.textContent = state.label;
  }
}

/* --- 7. VALIDATION + SUBMIT ---
   Server-side validation is authoritative; this is just a courtesy pass
   so the user does not wait for a round-trip on obviously empty fields. */
function onSubmit(event) {
  clearErrors();
  var firstError = null;

  requiredIds.forEach(function (id) {
    var field = document.getElementById(id);
    if (field && !field.value.trim()) {
      markError(field, 'This field is required.');
      if (!firstError) firstError = field;
    }
  });

  var email = document.getElementById('email');
  if (email && email.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    markError(email, 'Please enter a valid email.');
    if (!firstError) firstError = email;
  }

  var pwd = document.getElementById('password');
  var confirm = document.getElementById('confirm_password');
  if (pwd && pwd.value && pwd.value.length < 8) {
    markError(pwd, 'Password must be at least 8 characters.');
    if (!firstError) firstError = pwd;
  }
  if (pwd && confirm && confirm.value && confirm.value !== pwd.value) {
    markError(confirm, 'Passwords do not match.');
    if (!firstError) firstError = confirm;
  }

  if (firstError) {
    event.preventDefault();
    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function markError(field, message) {
  field.classList.add('input-error');
  var msg = document.createElement('span');
  msg.className = 'error-msg';
  msg.textContent = message;
  field.closest('.field').appendChild(msg);
}

function clearErrors() {
  document.querySelectorAll('.error-msg').forEach(function (m) { m.remove(); });
  document.querySelectorAll('.input-error').forEach(function (f) { f.classList.remove('input-error'); });
}

/* --- 8. MINI STEPPER ---
   Side-nav buttons jump to a section and track the active one while
   the user scrolls through the long form. */
function initStepper() {
  if (!stepperButtons.length || !formSections.length) return;
  stepperButtons.forEach(function (btn) {
    btn.addEventListener('click', onStepClick);
  });
  stepperState.current = formSections[0].id;
  setActiveStep(stepperState.current);
  if (!window.IntersectionObserver) return;
  var observer = new IntersectionObserver(onSectionIntersect, { rootMargin: '-10% 0px -60% 0px' });
  formSections.forEach(function (sec) { observer.observe(sec); });
  window.addEventListener('scroll', onPageScroll);
}

function onSectionIntersect(entries) {
  if (stepperState.locked) return;
  entries.forEach(function (entry) {
    if (entry.isIntersecting) {
      stepperState.current = entry.target.id;
      setActiveStep(entry.target.id);
    }
  });
}

function onStepClick(event) {
  event.preventDefault();
  var id = event.currentTarget.getAttribute('data-target');
  var target = document.getElementById(id);
  stepperState.locked = true;
  setActiveStep(id);
  if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  clearTimeout(stepperState.timer);
  stepperState.timer = setTimeout(function () { stepperState.locked = false; }, 800);
}

function onPageScroll() {
  if (stepperState.locked) return;
  var atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 5;
  if (atBottom) {
    var last = stepperButtons[stepperButtons.length - 1];
    if (last) setActiveStep(last.getAttribute('data-target'));
  } else if (stepperState.current) {
    setActiveStep(stepperState.current);
  }
}

function setActiveStep(id) {
  stepperButtons.forEach(function (btn) {
    var active = btn.getAttribute('data-target') === id;
    btn.classList.toggle('active', active);
    if (active) {
      btn.setAttribute('aria-current', 'step');
    } else {
      btn.removeAttribute('aria-current');
    }
  });
}
