var ace = require('ace-builds/src-min-noconflict/ace');
require('ace-builds/webpack-resolver');

window.addEventListener('load', function () {
  var editor = document.getElementById('ace-editor');

  if (!editor) {
    return;
  }

  ace.edit(editor, {
    mode: 'ace/mode/' + editor.dataset.mode,
    maxLines: 50,
    minLines: 10,
    fontSize: 16,
  });
});
