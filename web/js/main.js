$(function() {
    $('.dropdown-toggle').dropdown();

    // Format files and search results using CodeMirror
    // keyword defined in search.twig
    if ($('.sourcecode').length) {
        $('.sourcecode').each(function() {
            var value = $(this).text(),
                search = (typeof keyword === 'undefined') ? false : true,
                mode = $(this).attr('language'),
                pre = $(this).get(0),
                line = ($(this).attr('firstLineNumber') == undefined) ? 1 : parseInt($(this).attr('firstLineNumber'), 10);

            // CodeMirror search highlighing

            // this seems redundent, slow, and messy
            if (search) {
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
                    return CodeMirror.overlayMode(CodeMirror.getMode(config, mode), searchOverlay, true);
                });
            }

            var viewer = CodeMirror(function(elt) {
                pre.parentNode.replaceChild(elt, pre);
            }, {
                value: value,
                lineNumbers: true,
                firstLineNumber: line,
                matchBrackets: true,
                lineWrapping: true,
                readOnly: true,
                mode: (search) ? 'highlightSearch' : mode,
            });
        });
    }

    // New line formatter

    function lineFormater() {
        if ($('.CodeMirror-linenumber.CodeMirror-gutter-elt').length) {
            $('.CodeMirror-linenumber.CodeMirror-gutter-elt').each(function() {
                var ln = $(this).text();
                var file =
                    $(this).html('<a href="#' + ln + '">' + ln + '</a>');
            });
        }
    }

    // Run once when the page loads
    lineFormater();

    if ($('#md-content').length) {
        var converter = new Showdown.converter({
            extensions: ['table']
        });
        $('#md-content').html(converter.makeHtml($('#md-content').text()));
    }

    function paginate() {
        var $pager = $('.pager');

        $pager.find('.next a').one('click', function(e) {
            e.preventDefault();
            $.get(this.href, function(html) {
                $pager.after(html);
                $pager.remove();
                paginate();
            });
        });

        $pager.find('.previous').remove();
    }
    paginate();
});
