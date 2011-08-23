var Base = {
    
    loadBase: function( data, q, callback ) {
        Loading.on();
        
        if (data) {
            location.hash = "!?" + data;
        }
        
        $.ajax({
            url: './',
            data: data + q,
            type: 'post',

            success: function(html) {
                $('#opSongsPlace').html(html);
                
                if (typeof(cb) != 'undefined') {
                    callback();
                }
                
                Loading.off();
            },
            
            error: function() {
                alert("Error loading");
                Loading.off();
            }
        });
    },
    
    loadApp: function( app ) {
        Base.loadBase('', 'app=ajax&query=getapp&getapp='+app);
    } 
    
    
}