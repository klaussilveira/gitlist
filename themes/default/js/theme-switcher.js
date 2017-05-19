$(function(){
    function getCookie(c_name) {
        if (document.cookie.length > 0) {
            c_start = document.cookie.indexOf(c_name + "=");
            if (c_start != -1) {
                c_start = c_start + c_name.length + 1;
                c_end = document.cookie.indexOf(";", c_start);
                if (c_end == -1) c_end = document.cookie.length;
                return unescape(document.cookie.substring(c_start, c_end));
            }
        }
        return "bootstrap-cosmo";
    }

    function setCookie(c_name, value) {
        var exdate = new Date();
        exdate.setDate(exdate.getDate() + 7);
        document.cookie = c_name + "=" + escape(value) + ";path=/;expires=" + exdate.toUTCString();
    }


    var currentCookie = getCookie('gitlist-bootstrap-theme');
    var themeList = $('#theme-list');
    for(var key in themes) {
        var menu = '<li class="' + (currentCookie  === key ? 'active' : '') + '" style="text-transform: capitalize"><a href="#" data-theme="' + key + '" class="theme-link">' + key.substring(10) + '</a></li>';
        themeList.append(menu);
    }

    var getLink = function(theme) {
        return gitlist.basepath + themes[theme];
    }
    var themesheet = $('#bootstrap-theme');
    //themesheet.attr('href',getLink(currentCookie));

    $('.theme-link').click(function(){
        themeList.find('.active').removeClass('active');
        var $this = $(this);
        $this.parent().addClass('active');
        var themeurl = themes[$this.attr('data-theme')];
        setCookie('gitlist-bootstrap-theme', $this.attr('data-theme'));
        themesheet.attr('href',themeurl);
    });
});