var Loading = {
    on: function() {
        this.changeFavicon('./web/img/loading.gif');
        $('#opLoading').show('slow');
    },
    
    off: function() {
        this.changeFavicon('./web/img/icon.png');
        $('#opLoading').hide('slow');
    },
    
    changeFavicon: function(href) {
        var icon = $('link[rel=icon]');
        
        icon.replaceWith(
            icon.clone().attr('href', href)
        );
    }
    
}