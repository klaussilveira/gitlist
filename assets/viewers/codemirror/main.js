var CodeMirror = require('codemirror');

// Dynamically load modes
var requireContext = require.context('codemirror/mode/', true, /\.js$/);
requireContext.keys().forEach(function (key) {
  requireContext(key);
});

window.addEventListener('load', function () {
  var editor = document.getElementById('cm-editor');

  if (!editor) {
    return;
  }

  CodeMirror.fromTextArea(editor, {
    mode: editor.dataset.mode,
    lineNumbers: true,
    lineWrapping: true,
    autofocus: true,
  });
});
