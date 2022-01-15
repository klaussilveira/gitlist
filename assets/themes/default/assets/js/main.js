var bootstrap = require('bootstrap');

document.addEventListener('DOMContentLoaded', function () {
  // Initialize all dropdowns
  var dropdowns = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="dropdown"]')
  );
  dropdowns.map(function (el) {
    return new bootstrap.Dropdown(el);
  });

  // Initialize all tooltip
  var tooltip = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltip.map(function (el) {
    return new bootstrap.Tooltip(el);
  });

  // Initialize all popovers
  var popovers = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="popover"]')
  );
  popovers.map(function (el) {
    return new bootstrap.Popover(el);
  });

  // Initialize commit body popover
  var commitBody = [].slice.call(
    document.querySelectorAll('[data-toggle="commit-body"]')
  );
  commitBody.map(function (el) {
    return new bootstrap.Popover(el, {
      template:
        '<div class="popover commit-body-popover" role="tooltip"><div class="arrow"></div><div class="popover-body"></div></div>',
    });
  });

  // Initialize tabs
  var reflist = [].slice.call(document.querySelectorAll('#reflist button'));
  reflist.forEach(function (el) {
    var tab = new bootstrap.Tab(el);

    el.addEventListener('click', function (event) {
      event.preventDefault();
      tab.show();
    });
  });

  var reflistInput = [].slice.call(
    document.querySelectorAll('.ref-list input')
  );
  reflistInput.forEach(function (el) {
    el.addEventListener('input', function (event) {
      // Check if datalist option is valid
      [].slice
        .call(document.getElementById('refs').options)
        .forEach(function (option) {
          if (option.value != event.target.value) {
            return;
          }

          window.location = option.dataset.target;
        });
    });
  });

  // Initialize clone input
  var cloneInput = [].slice.call(document.querySelectorAll('.clone-input'));
  cloneInput.map(function (el) {
    var dropdown = new bootstrap.Dropdown(
      el.querySelector('[data-toggle="clone-input"]')
    );
    var input = el.querySelector('input');
    var toggle = el.querySelector('.dropdown-toggle');

    input.addEventListener('click', function (e) {
      input.select();
    });

    el.querySelector('button').addEventListener('click', function (e) {
      input.select();
      document.execCommand('copy');
      dropdown.toggle();
    });

    var dropdownItems = [].slice.call(el.querySelectorAll('.dropdown-item'));
    dropdownItems.map(function (el) {
      el.addEventListener('click', function (e) {
        input.value = el.dataset.cloneUrl;
        toggle.innerText = el.innerText;
        input.select();
        document.execCommand('copy');
        dropdown.toggle();
      });
    });
  });
});
