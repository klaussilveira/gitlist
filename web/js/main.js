$(function () {
    $('.dropdown-toggle').dropdown();

    if ($('#sourcecode').length) {
        var value = $('#sourcecode').text();
        var mode = $('#sourcecode').attr('language');
        var pre = $('#sourcecode').get(0);
        var viewer = CodeMirror(function(elt) {
            pre.parentNode.replaceChild(elt, pre);
        }, {
            value: value,
            lineNumbers: true,
            matchBrackets: true,
            lineWrapping: true,
            readOnly: true,
            mode: mode
        });
    }

    if ($('#readme-content').length) {
        var converter = new Showdown.converter();
        $('#readme-content').html(converter.makeHtml($('#readme-content').text()));
    }

    function paginate() {
        var $pager = $('.pager');
        $pager.find('.next a').one('click', function (e) {
            e.preventDefault();
            $(this).css('pointer-events', 'none');
            $.get(this.href, function (html) {
                $pager.after(html);
                $pager.remove();
                paginate();
            });
        });
        $pager.find('.previous').remove();
    }
    paginate();
});
