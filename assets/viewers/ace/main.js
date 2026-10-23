document.addEventListener('DOMContentLoaded', function () {
  var editor = document.getElementById('ace-editor');

  if (!editor) {
    return;
  }

  window.ace.config.set('basePath', editor.dataset.basePath);

  var instance = window.ace.edit(editor, {
    mode: 'ace/mode/' + editor.dataset.mode,
    maxLines: 50,
    minLines: 10,
    fontSize: 16,
    useWorker: false,
  });

  var applyTheme = function () {
    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    instance.setTheme(dark ? 'ace/theme/tomorrow_night_eighties' : 'ace/theme/textmate');
  };

  applyTheme();
  new MutationObserver(applyTheme).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-bs-theme'],
  });
});
