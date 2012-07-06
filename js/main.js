$(function(){
    $.ajax({
        url: 'https://api.github.com/repos/klaussilveira/gitlist/contributors',
        dataType: 'jsonp',
        success: function(data) {
            var items = [];

            $.each(data.data, function(key, val) {
                items.push('<li><a href="' + val.url + '" rel="avatarover" data-placement="top" data-title="' + val.login + '" data-content="' + val.login + ' has made ' + val.contributions + ' contributions to GitList"><img src="' + val.avatar_url + '" width="32" height="32" /></a></li>');
            });

            $('<ul/>', {
                'class': 'contributor-list',
                html: items.join('')
            }).appendTo('#contributors');
            $('[rel=avatarover]').popover();
        }
    });

    $('[rel=carousel]').carousel();
    $('[rel=tooltip]').tooltip();
    $('[rel=popover]').popover();
    $('.feature').each(function() {
        $(this).css('top',$(this).data('top')).css('left',$(this).data('left'));
    });
});