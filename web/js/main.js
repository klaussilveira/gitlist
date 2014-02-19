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
            mode: mode,
            lineNumberFormatter: function(ln) {
                return '<a name="L'+ ln +'"></a><a href="#L'+ ln +'">'+ ln +'</a>';
            }
        });
    }

    // Format search results using CodeMirror
    if ($('.sourcecode').length) {
        $('.sourcecode').each( function() {
            var value = $(this).text(),
                mode = $(this).attr('language'),
                pre = $(this).get(0),
                line = parseInt($(this).attr('firstLineNumber'), 10),
                viewer = CodeMirror(function(elt) {
                pre.parentNode.replaceChild(elt, pre);
            }, {
                value: value,
                lineNumbers: true,
                firstLineNumber: line,
                matchBrackets: true,
                lineWrapping: true,
                readOnly: true,
                mode: mode
            });
        });
    }

    // CodeMirror search highlighing
    // keyword defined in search.twig
    CodeMirror.defineMode("highlightSearch", function(config, parserConfig) {
      var searchOverlay = {
        token: function(stream, state) {
            if (stream.match(keyword)) {
                return "highlightSearch";
            }

            while (stream.next() != null && !stream.match(keyword, false)) {}
            return null;
        }
      };
      return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || "text"),searchOverlay);
    });

    if ($('#md-content').length) {
        var converter = new Showdown.converter({extensions: ['table']});
        $('#md-content').html(converter.makeHtml($('#md-content').text()));
    }

    function paginate() {
        var $pager = $('.pager');

        $pager.find('.next a').one('click', function (e) {
            e.preventDefault();
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
