window.addEventListener('load', function () {
  var editor = document.getElementById('cm-editor');

  if (!editor) {
    return;
  }

  window.CodeMirror.modeURL = editor.dataset.modeUrl;

  var instance = window.CodeMirror.fromTextArea(editor, {
    mode: editor.dataset.mode,
    lineNumbers: true,
    lineWrapping: true,
    autofocus: true,
  });

  if (editor.dataset.mode) {
    window.CodeMirror.autoLoadMode(instance, editor.dataset.mode);
  }
});
