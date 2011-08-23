var Search = {
//    data: null,
    
    artistClick: function() {
        $('.op-atrist').unbind();
        $('.op-atrist').click(function() {
            var artist = $(this).html();
            $('.op-form-search form input[type=text]').val(artist);
            $('.op-form-search form').submit();
        });
    },
    
    init: function() {
        if ( Settings.suggest ) {
            $('.op-form-search input[type=text]').autocomplete({
                minLength: 0,
                source: "?app=ajax&query=suggest"
            });
        }
        
        $('.op-form-search form').submit(function() {
            var data = $(this).serialize();
//            Search.data = data;
            
            Search.loadSongs(data);
            
            
            return false;
        });
    
        $(document).ready(function() {
            Search.artistClick();
        });
        
        Search.pagerEvents();
    },
    
    pagerEvents: function() {
        $('#opPagerSongsPrev').unbind();
        $('#opPagerSongsPrev').click(function() {
            Search.loadSongs($(this).attr('href').replace('?',''));
            return false;
        });
        
        $('#opPagerSongsNext').unbind();
        $('#opPagerSongsNext').click(function() {
            Search.loadSongs($(this).attr('href').replace('?',''));
            return false;
        });
    },
    
    loadNext: function(cb) {
        Search.loadSongs(
            $('#opPagerSongsNext').attr('href').replace('?',''), 
            cb
        );
    },
    
    loadSongs: function(data, cb) {
        Base.loadBase(data, '&app=ajax&query=search', function() {
            Search.pagerEvents();
            Search.artistClick();

            Playlists.init();

            if (typeof(cb) != 'undefined') {
                cb();
            }
        });
    }
    
}

Search.init();