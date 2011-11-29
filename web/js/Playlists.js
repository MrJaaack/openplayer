var Playlists = {
    reload:function() {
        Loading.on();
        $.ajax({
            url: './',
            data: {
                app:    'ajax',
                query:  'reloadPL'
            },
            type: 'post',
            
            success: function(html) {
                Loading.off();
                $('#opContainerPlaylists').html(html);
            }
        });
    },
    
    player_init: false,
    
    init: function() {
    	if ( !this.player_init ) {
    		this.player_init = true;
	        $("#jquery_jplayer_1").jPlayer({
	        	swfPath: "./web/lib",
	        	solution: "flash, html",
	        	supplied: "mp3",
	
	        	ready: function() {
	        		// should we wait until it will
	        		// say it is ready or leave it like this is ok?
	        	},
                
		        ended: function() {
		        	Playlists.next();
		        },
                
		        pause: function() {
		        	$(".jp-pause").hide();
		        	$(".jp-play").show();
		        },
                
		        play: function() {
                    Loading.off();
		        	$(".jp-pause").show();
		        	$(".jp-play").hide();
		        },
                
		        mute: function() {
		        	$(".jp-mute").hide();
		        	$(".jp-unmute").show();
		        },
                
		        unmute: function() {
		        	$(".jp-mute").show();
		        	$(".jp-unmute").hide();
		        }
	        });
            
	    	$('.jp-progress').css("width", 
    			($('.jp-progress').parent().width()-25-$('.jp-right').width())
	    	);
                
	    	$(window).resize(function() {
	    		$('.jp-progress').css("width", 
	    			($('.jp-progress').parent().width()-25-$('.jp-right').width())
	    		);
	    	});
            
	        $(".jp-progress").hover(function() {
	        	$("#song-title").show();
	        }, function() {
	        	$("#song-title").hide();
	        });
            
	        $("#song-title").click(function(e) {
	        	var offset;
	        	if (typeof(e.offsetX) == 'undefined') {
	        		offset = e.layerX;
	        	} else {
	        		offset = e.offsetX;
	        	}
	        	$("#jquery_jplayer_1").jPlayer("playHead", 
	        		(100*offset)/$(this).width()
	        	);
	        });
            
	        $(".jp-shuffle").click(function() {
	        	Playlists.shuffle = !Playlists.shuffle;
                
	        	$(this).attr(
                    "title",
	        		Playlists.shuffle
                        ? Lang.__('Shuffle [ON]')
                        : Lang.__('Shuffle [OFF]')
	        	);
	        	$(this).toggleClass("enabled");
	        });
            
	        $(".jp-repeat").click(function() {
	        	if (Playlists.repeat == Playlists.NO_REPEAT) {
	        		Playlists.repeat = Playlists.REPEAT_PLAYLIST;
	        		$(this).attr("title", Lang.__('Repeat [PLAYLIST]'));
	        		$(this).addClass("playlist");
	        	} else if(Playlists.repeat == Playlists.REPEAT_PLAYLIST) {
	        		Playlists.repeat = Playlists.REPEAT_SONG;
	        		$(this).attr("title", Lang.__('Repeat [SONG]'));
	        		$(this).removeClass("playlist");
	        		$(this).addClass("one_song");
	        	} else {
	        		Playlists.repeat = Playlists.NO_REPEAT;
	        		$(this).attr("title", Lang.__('Repeat [OFF]'));
	        		$(this).removeClass("one_song");
	        	}
	        });
    	}
        
        $('.op-link-song-del').unbind();
        $('.op-link-song-del').click(function() {
            if ( confirm( Lang.__('Sure you want delete song from playlist?') ) ) {
                var id = $(this).data('id');
                var plId = $(this).data('plid');
                
                $(this).parents('.op-song').remove();

                $.ajax({
                    url: './',
                    data: {
                        app:    'ajax',
                        query:  'delSongFromPL',
                        id:     id,
                        plId:   plId
                    },
                    type: 'post'
                });

            }
        });
        
        // встраивание на сторонние сайты
        $('.op-link-pl-html').unbind();
        $('.op-link-pl-html').click(function() {
            prompt(
                Lang.__('Embed code:'), 
                '<iframe src="'+baseUrl+'?app=embed&plId='+$(this).data('id')+'" width="340" height="420" style="border: 1px solid #ccc;"></iframe>'
            );
        });
        // /встраивание на сторонние сайты
        
        // встраивание на сторонние сайты
        $('.op-link-song-html').unbind();
        $('.op-link-song-html').click(function() {
            prompt(
                Lang.__('Embed code:'), 
                '<iframe src="'+baseUrl+'?app=embed&songs=1&tl='+$(this).data('tl')+'" width="340" height="45" style="border: 1px solid #ccc;overflow:hidden;"></iframe>'
            );
        });
        // /встраивание на сторонние сайты
        
        $('.op-link-pl-edit').unbind();
        $('.op-link-pl-edit').click(function() {
            var id = $(this).data('id');
            
            var name = prompt( 
                Lang.__('Input playlist new name'), 
                $( '#opLinkPlaylistName' + id ).html().trim()
            );

            if ( name && name.trim() ) {
                Loading.on();
                
                $.ajax({
                    url: './',
                    data: {
                        app:    'ajax',
                        query:  'editPL',
                        id:     id,
                        name:   name.trim()
                    },
                    dataType:   'json',
                    type:     'post',

                    success: function(data) {
                        Playlists.reload();
                    }
                });
            }
        });
        
        $('.op-link-pl-del').unbind();
        $('.op-link-pl-del').click(function() {
            if ( confirm( Lang.__('Sure you want delete playlist?') ) ) {
                var id = $(this).data('id');
                
                $(this).parents('.op-playlist').remove();
                
                $.ajax({
                    url: './',
                    data: {
                        app:    'ajax',
                        query:  'delPL',
                        id:     id
                    },
                    dataType:   'json',
                    type:       'post'
                });
            }
        });
        
        $('.op-link-pl-openhide').unbind();
        $('.op-link-pl-openhide').click(function() {
            var id = $(this).data('id');

            $('#opLinkPlaylistSongs'+id).toggleClass('op-hide');

            $(this).toggleClass('op-icon-open');
            $(this).toggleClass('op-icon-closed');

            $.ajax({
                url: './',
                data: {
                    app:    'ajax',
                    query:  'plStatus',
                    id:     id,
                    status: $(this).hasClass('op-icon-open')
                },
                type: 'post'
            });
        });
        
        $('#opLinkNewPlaylist').unbind();
        $('#opLinkNewPlaylist').click(function() {
            var name = prompt( Lang.__('Input new playlist name'), Lang.__('New playlist') );

            if ( name && name.trim() ) {
                Loading.on();
                
                $.ajax({
                    url: './',
                    data: {
                        app:    'ajax',
                        query:  'addPL',
                        name:   name.trim()
                    },
                    dataType:   'json',
                    type:     'post',

                    success: function(data) {
                        if ( data.status ) {
                            Playlists.reload();
                        } else {
                            alert( Lang.__('Something went wrong:(') );
                            Loading.off();
                        }
                    }
                });
            }
        });
        
        if ( $('#opContainerSongs').length ) {
            $('.op-container-songbox, #opContainerSongs').sortable({
                connectWith: ".op-container-songbox",
                revert: 100,

                stop: function(event, ui) {
                    var fromId = $(this).parents('.op-playlist').data('id');
                    var song = $(ui.item);

                    var toId = $(ui.item).parents('.op-playlist').data('id');
                    if ( toId ) {
                        var delLink = $(ui.item).find('.op-song-del-span');
                        delLink.find('.op-link-song-del').data('plid', toId);
                        delLink.show();

                        Loading.on();

                        var afterId = $(ui.item).prev().data('id');
                        if (undefined == afterId) {
                            afterId = null;
                        }

                        $.ajax({
                            url: './',
                            data: {
                                app:        'ajax',
                                query:      'moveSongToPL',
                                fromId:     fromId,
                                toId:       toId,
                                afterId:    afterId,
                                songData: {
                                    id:         song.data('id'),
                                    plid:       song.data('plid'),
                                    name:       song.data('name'),
                                    artist:     song.data('artist'),
                                    url:        song.data('url'),
                                    duration:   song.data('duration'),
                                    position:   song.data('position')
                                }
                            },
                            dataType:   'json',
                            type:       'post',

                            success: function(data) {
                                Loading.off();
    //                            Playlists.reload(); // @todo, make without
                            }
                        });

                    } else {
                        $(this).sortable('cancel');
                    }

                }
            }).disableSelection();
        }
        
        $('.op-link-song-play').unbind();
        $('.op-link-song-play').click(function() {
            Playlists.playSong( $(this).parents('.op-song') );
        });
        
    },
    
    shuffle: false,
    
    NO_REPEAT: false,
    REPEAT_PLAYLIST: 1,
    REPEAT_SONG: 2,
    
    repeat: false,
    
    getFirstSong: function(isNextSearchPage) {
        if ( isNextSearchPage ) {
            return $("#opContainerSongs").children('.op-song').get(0);
        }
//        $(this.prevSong.parents(".op-container-songbox").children().get(0));
    	//используем родителей предыдущей песни для того чтобы повторять
    	//тот плейлист который проигрывался, а не прыгать из плейлиста в поиск
        return this.prevSong.parents(".op-container-songbox").children('.op-song').get(0);
    },
    
    next: function() {
        var self = this;
        
    	if ( this.prevSong == null ) {
            // Maybe search for some song?
    		return; 
    	}
        
    	if ( this.repeat == this.REPEAT_SONG ) {
    		this.playSong(this.prevSong);
    		return;
    	}
        
    	if ( !this.shuffle ) {
    		var next = this.prevSong.next();
            
    		if ( 0 == next.size() ) {
    			if ( this.repeat == this.REPEAT_PLAYLIST ) {
                    self.playSong(
                        self.getFirstSong()
                    );
    			} else if ( Playlists.prevSong.parents("#opContainerSongs").size() ) {
                    Search.loadNext( function() {
                        self.playSong(
                            self.getFirstSong(true)
                        );
                    });

                    return;
    			}
    		} else {
        		this.playSong(next);
    		}
            
    		return;
    	} else {
    		var list = this.prevSong.parents(".op-container-songbox").children();
            
    		if ( this.repeat == this.NO_REPEAT ) {
    			list = list.filter(":not(.played)");
    		}
            
    		if ( list.size() == 0 ) {
    			return;
    		}
            
    		this.playSong ( 
                $(list.get(
                    Math.round(
                        Math.random() * (
                            list.size() - 1
                        )
                    )
                ))
            );
    	}
    },
    
    prevSong: null,
    
    playSong: function( par ) {
        Loading.on();
        
        $('.op-nowplaying').removeClass('op-nowplaying');
        $('.op-song[data-id='+$(par).data('id')+']').addClass('op-nowplaying');
        
        this.prevSong = $(par);
        
        var url = "./?app=ajax&query=getSong"
            +"&artist="+$(par).data('uartist')
//            +"&artist="+$(par).data('artist')
            +"&name="+$(par).data('uname')
//            +"&name="+$(par).data('name')
            +"&url="+$(par).data('url')
            +"&id="+$(par).data('id');
        
        $("#jquery_jplayer_1").jPlayer("setMedia", {
            "mp3": url
        }).jPlayer("play");

        var title = this.prevSong.data('artist') + ' - ' + this.prevSong.data('name');

        this.prevSong.addClass("played");

        $("#song-title").html(title);
        $("title").html(title);
    }
}
