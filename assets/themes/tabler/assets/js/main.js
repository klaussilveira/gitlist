import { Dropdown, Toast } from '../vendor/tabler/js/tabler.esm.min.js';

var themeDefaults = {
  theme: 'auto',
  'theme-base': 'neutral',
  'theme-font': 'sans-serif',
  'theme-primary': 'blue',
  'theme-radius': '1',
  layout: 'default',
  navbar: 'default',
  'navbar-style': 'default',
  'navbar-theme': 'default',
};

var schemes = {
  auto: { theme: 'auto', 'navbar-theme': 'default' },
  light: { theme: 'light', 'navbar-theme': 'default' },
  dark: { theme: 'dark', 'navbar-theme': 'default' },
  'dark-nav': { theme: 'light', 'navbar-theme': 'dark' },
  colored: { theme: 'light', 'navbar-theme': 'primary' },
};

var prefersDark = window.matchMedia('(prefers-color-scheme: dark)');

var storage = {
  get: function (key) {
    try {
      return window.localStorage.getItem('tabler-' + key);
    } catch (e) {
      return null;
    }
  },
  set: function (key, value) {
    try {
      window.localStorage.setItem('tabler-' + key, value);
    } catch (e) {}
  },
  remove: function (key) {
    try {
      window.localStorage.removeItem('tabler-' + key);
    } catch (e) {}
  },
};

function currentValue(key) {
  return storage.get(key) || themeDefaults[key];
}

function applyValue(key, value) {
  var applied = value;

  if (key === 'theme' && value === 'auto') {
    applied = prefersDark.matches ? 'dark' : 'light';
  }

  if (applied !== themeDefaults[key]) {
    document.documentElement.setAttribute('data-bs-' + key, applied);
  } else {
    document.documentElement.removeAttribute('data-bs-' + key);
  }
}

function setValue(key, value) {
  applyValue(key, value);
  storage.set(key, value);
}

function showToast(message) {
  var container = document.querySelector('.toast-container');

  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(container);
  }

  var toast = document.createElement('div');
  toast.className = 'toast align-items-center';
  toast.setAttribute('role', 'status');
  toast.setAttribute('aria-live', 'polite');
  toast.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button></div>';
  toast.querySelector('.toast-body').textContent = message;
  container.appendChild(toast);

  toast.addEventListener('hidden.bs.toast', function () {
    toast.remove();
  });

  Toast.getOrCreateInstance(toast, { delay: 2000 }).show();
}

function initThemeToggle() {
  document.querySelectorAll('[data-theme-toggle]').forEach(function (el) {
    el.addEventListener('click', function () {
      setValue('theme', el.dataset.themeToggle);
      syncAppearance();
    });
  });
}

function syncAppearance() {
  var form = document.querySelector('[data-appearance]');

  if (!form) {
    return;
  }

  Object.keys(themeDefaults).forEach(function (key) {
    var value = currentValue(key);

    form.querySelectorAll('[name="' + key + '"]').forEach(function (input) {
      input.checked = input.value === value;
    });
  });

  form.querySelectorAll('[name="scheme"]').forEach(function (input) {
    var values = schemes[input.value];

    input.checked = Object.keys(values).every(function (key) {
      return currentValue(key) === values[key];
    });
  });
}

function initAppearance() {
  var form = document.querySelector('[data-appearance]');

  if (!form) {
    return;
  }

  form.addEventListener('change', function (event) {
    var name = event.target.name;
    var value = event.target.value;

    if (name === 'scheme') {
      Object.keys(schemes[value]).forEach(function (key) {
        setValue(key, schemes[value][key]);
      });
    } else if (name in themeDefaults) {
      setValue(name, value);
    }

    window.dispatchEvent(new Event('resize'));
  });

  form.querySelector('[data-appearance-reset]').addEventListener('click', function () {
    Object.keys(themeDefaults).forEach(function (key) {
      applyValue(key, themeDefaults[key]);
      storage.remove(key);
    });

    syncAppearance();
    window.dispatchEvent(new Event('resize'));
  });

  syncAppearance();
}

function initReflist() {
  document.querySelectorAll('.ref-list input').forEach(function (el) {
    el.addEventListener('input', function (event) {
      [].slice.call(document.getElementById('refs').options).forEach(function (option) {
        if (option.value !== event.target.value) {
          return;
        }

        window.location = option.dataset.target;
      });
    });
  });
}

function initCloneInput() {
  document.querySelectorAll('.clone-input').forEach(function (el) {
    var input = el.querySelector('input');
    var toggle = el.querySelector('[data-toggle="clone-input"]');
    var copy = el.querySelector('[data-bs-toggle="clipboard"]');

    input.addEventListener('click', function () {
      input.select();
    });

    el.querySelectorAll('[data-clone-url]').forEach(function (item) {
      item.addEventListener('click', function (event) {
        event.preventDefault();
        input.value = item.dataset.cloneUrl;
        toggle.textContent = item.textContent.trim();
        Dropdown.getOrCreateInstance(toggle).hide();
      });
    });

    if (copy) {
      copy.addEventListener('error.bs.clipboard', function () {
        fallbackCopy(input, copy);
      });
    }
  });
}

function fallbackCopy(source, trigger) {
  var input = source;

  if (!(source instanceof HTMLInputElement)) {
    input = document.createElement('textarea');
    input.value = source.textContent.trim();
    input.setAttribute('readonly', '');
    input.className = 'position-fixed opacity-0';
    document.body.appendChild(input);
  }

  input.select();

  var copied = false;
  try {
    copied = document.execCommand('copy');
  } catch (e) {}

  if (input !== source) {
    input.remove();
  }

  var feedback = trigger.querySelector('span.clipboard-feedback');
  if (copied && feedback) {
    showToast(feedback.textContent.trim());
  }
}

function initClipboardFallback() {
  document.querySelectorAll('[data-bs-toggle="clipboard"]').forEach(function (el) {
    if (el.closest('.clone-input')) {
      return;
    }

    el.addEventListener('error.bs.clipboard', function () {
      var target = document.querySelector(el.dataset.bsTarget);

      if (target) {
        fallbackCopy(target, el);
      }
    });
  });
}

function initRepositoryFilter() {
  var filter = document.querySelector('[data-repository-filter]');

  if (!filter) {
    return;
  }

  var empty = document.querySelector('[data-repository-filter-empty]');
  var repositories = document.querySelectorAll('[data-repository]');

  filter.addEventListener('input', function () {
    var query = filter.value.trim().toLowerCase();
    var visible = 0;

    repositories.forEach(function (el) {
      var match = el.dataset.repository.indexOf(query) !== -1;
      el.classList.toggle('d-none', !match);
      visible += match ? 1 : 0;
    });

    empty.classList.toggle('d-none', visible > 0);
  });
}

document.addEventListener('DOMContentLoaded', function () {
  initThemeToggle();
  initAppearance();
  initReflist();
  initCloneInput();
  initClipboardFallback();
  initRepositoryFilter();
});
