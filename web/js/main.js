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

    if ($('#readme-content').length) {
        var converter = new Showdown.converter();
        $('#readme-content').html(converter.makeHtml($('#readme-content').text()));
    }

    var loadingMore = false;

    (function paginate() {
        var $pager = $('.pager');
        $pager.find('.next a').one('click', function (e) {
            e.preventDefault();
            loadingMore = true;
            $(this).css('pointer-events', 'none');
            $.get(this.href, function (html) {
                $pager.after(html);
                $pager.remove();
                paginate();
                loadingMore = false;
            });
        });
        $pager.find('.previous').remove();
    }());

    (function moreCommits() {

        var MAX_AUTOMORE = 99, // number of automatic mores
            AUTOMORE_TRIGGER = 350, // automatic mores are triggered when this number of pixels from the bottom is reached
            CHECK_INTERVAL = 100,
            $doc = $(document),
            $body = $('body'),
            isScrolled = false,
            autoMoreCount = 0;

        function autoMore() {
            var $autoMore = $('.pager .next a'),
                screenHeight = window.innerHeight || document.documentElement.clientHeight || $('body')[0].clientHeight;
            if ($autoMore.length) {
                if ($body.outerHeight() - $doc.scrollTop() - screenHeight < AUTOMORE_TRIGGER) {
                    $autoMore.click();
                    autoMoreCount += 1;
                }
            } else {
                clearInterval(timer);
            }
        }

        var timer = setInterval(function () {
            if (isScrolled) {
                isScrolled = false;
                if (!loadingMore) {
                    if (autoMoreCount < MAX_AUTOMORE) {
                        autoMore();
                    } else {
                        clearInterval(timer);
                    }
                }
            }
        }, CHECK_INTERVAL);

        $doc.on(
            'scroll resize',
            function () {
                isScrolled = true;
            }
        );
    }());

});
