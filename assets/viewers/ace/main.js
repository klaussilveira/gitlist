window.addEventListener('load', function () {
  var editor = document.getElementById('ace-editor');

  if (!editor) {
    return;
  }

  window.ace.config.set('basePath', editor.dataset.basePath);

  window.ace.edit(editor, {
    mode: 'ace/mode/' + editor.dataset.mode,
    maxLines: 50,
    minLines: 10,
    fontSize: 16,
    useWorker: false,
  });
});
