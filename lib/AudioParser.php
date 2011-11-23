<?php
namespace Lib;

class AudioParser {

    public static $html = null;

    public static function search($query, $offset = 0) {
        $config = Config::getInstance();
        $cacheEnabled = $config->getOption('cache', 'cacheSearch', false);
        $cachePath = CACHEROOT . "/" . sha1($query . $offset);
        
        $cached = false;
        if ( $cacheEnabled && file_exists($cachePath) ) {
            $cached = true;
            $data = unserialize(file_get_contents($cachePath));
            $songs = $data['data'];
        }
        
        if ( !$cached || ( $data['meta']['time'] < (time() - $config->getOption('cache', 'cacheSearchTime', 86400)) ) ) {
            $cookie = VkLogin::getCookie();

            $ids = $config->getOption('vk', 'id');

            $post = array(
                'act' => 'search',
                'al' => '1',
                'gid' => '0',
                'id' => $ids[VkLogin::$rnd],
                'offset' => $offset,
                'q' => $query,
    //            'count' => '5',
                'sort' => '2'
            );

            $answer = Curl::process( 
                'http://vkontakte.ru/audio',
                $cookie,
                false,
                http_build_query($post)
            );
            
            self::$html = $answer;

            $matches = explode(
                '<div class="fl_l" style="width:31px;height:21px;">', 
                $answer
            );
            
            $songs = array();
            foreach ($matches as $audioItem) {
                preg_match_all(
                    '/<div class="duration fl_r".+?>(.*)<\/div>/', 
                    $audioItem, 
                    $res
                );

                if ( ! isset( $res[1][0] ) ) continue;

                $song['duration'] = $res[1][0];
                
                preg_match_all(
                    '<input type="hidden" id=".*?" value="(.*)?" />', 
                    $audioItem, 
                    $res
                );

                $songName = explode( ',', $res[1][0] );

                $song['url'] = $songName[0];

                preg_match_all(
                    '/<div class="title_wrap">(.*)?<\/div>/', 
                    $audioItem, 
                    $res
                );

                $songname = preg_replace(
                    '/\(.*\)/', 
                    '', 
                    strip_tags($res[1][0])
                );

                $songname = mb_convert_encoding(
                    $songname, 
                    $config->getOption('app', 'charset'), 
                    'Windows-1251'
                );

                $s = mb_convert_encoding(
                    $res[1][0], 
                    Config::getInstance()->getOption('app', 'charset'), 
                    'Windows-1251'
                );

                if (preg_match('#<b>(.+?)</b>#', $s, $artist)) {
                    $song['artist'] = strip_tags(trim($artist[1]));
                }
                if (preg_match('#<span class="title">(.+?)<span class="user">#', $s, $name)) {
                    $song['name'] = strip_tags(trim($name[1]));
                }

                preg_match_all( // Ищет id вида 41613828_110901414
                    '/<div class="repeat_wrap">(.*)?<\/div>/',
                    $audioItem,
                    $res2
                );

                if (preg_match('#id="repeat(.+?)"#', $res2[1][0], $play_id)) { // Записывает id вида 41613828_110901414
                    $song['vkid'] = $play_id[1];
                }

                $song['id'] = md5($songname.$song['duration']);

                $songs[$song['id']] = $song;
            }
            
            if ( $cacheEnabled ) {
                $data = array(
                    'meta' => array(
                        'time' => time()
                    ),
                    'data' => $songs
                );

                file_put_contents( $cachePath, serialize($data) );
            }
        }
        
        return $songs;
    }
	
	/* Принимает парамерт id вида 41613828_110901414 (пользователь_песня). Данный параметр содержится в переменной songs[song[id]][vkid] */
	/* Можно генерировать ссылки виды http://yoursite.ru/?song=41613828_110901414 и гарантировано возращать ту самую песню */
	public static function searchByID($Linkid) {
        $config = Config::getInstance();
        $cookie = VkLogin::getCookie();
		
		$result = '';

        $ids = $config->getOption('vk', 'id');
		
		$AudioIDs = explode('_', $Linkid);

        $post = array(
            'act' => 'load_audios_silent',
            'al' => '1',
            'edit' => '0',
			'gid' => '0',
            'id' => $AudioIDs[0]
        );

        $answer = Curl::process(
            'http://vkontakte.ru/audio',
            $cookie,
            false,
            http_build_query($post)
        );
		
		$pos = strpos($answer, '{');
		$answer = substr($answer, $pos+8);
		$tmp = explode('<!>', $answer);
		$answer = substr($tmp[0], 0, strlen($tmp[0])-2);
		$answer = str_replace('"', '&quote;', $answer);
		$answer = str_replace('\',\'', '","', $answer);
		$answer = str_replace('[\'', '["', $answer);
		$answer = str_replace('\']', '"]', $answer);
		$answer = '{"all":['.self::win2utf($answer).']}';
		$answer = json_decode($answer, TRUE);
		
		foreach($answer['all'] as $element) {
			if(array_search($AudioIDs[1], $element)) {
				$result = $element;
				break;
			}
		}
		if($result) {
			
			$song['url'] = $result[2];
			$song['duration'] = $result[4];
			$song['artist'] = $result[5];
			$song['name'] = $result[6];
			
			return $song; // Возвращает массив с одной песней. Данная песня - 100% именно та, на которую дают ссылку
		} else {
			return array();
		}
    }
	
	/* Принимает парамерт id пользователя Вконтакте вида 41613828 и отдает все его песни */
	/* Можно генерировать плейлисты */
	public static function vkUserSongs($Userid) {
        $config = Config::getInstance();
        $cookie = VkLogin::getCookie();
		
		$result = '';

        $ids = $config->getOption('vk', 'id');

        $post = array(
            'act' => 'load_audios_silent',
            'al' => '1',
            'edit' => '0',
			'gid' => '0',
            'id' => $Userid
        );

        $answer = Curl::process(
            'http://vkontakte.ru/audio',
            $cookie,
            false,
            http_build_query($post)
        );
		
		/* Структурируем песни */
		$pos = strpos($answer, '{');
		$answer = substr($answer, $pos+8);
		$tmp = explode('<!>', $answer);
		$answer = substr($tmp[0], 0, strlen($tmp[0])-2);
		$answer = str_replace('"', '&quote;', $answer);
		$answer = str_replace('\',\'', '","', $answer);
		$answer = str_replace('[\'', '["', $answer);
		$answer = str_replace('\']', '"]', $answer);
		$answer = '{"all":['.self::win2utf($answer).']}';
		$answer = json_decode($answer, TRUE);
		
		$songs = Array();
		
		/* Структурируем плейлисты */
		$playlists = $tmp[1];
		$pos = strpos($playlists, '"albums"');
		$playlists = substr($playlists, $pos+8);
		$pos = strpos($playlists, ',"hashes"');
		$playlists = substr($playlists, 0, $pos);
		$playlists = '{"playlists"'.self::win2utf($playlists).'}';
		$playlists = json_decode($playlists, TRUE);
		
		foreach($answer['all'] as $element) {
			$song['url'] = $element[2];
			$song['duration'] = $element[4];
			$song['artist'] = htmlspecialchars($element[5]);
			$song['name'] = htmlspecialchars($element[6]);
			$song['id'] = md5($song['name'].$song['duration']);
			
			$playlists['playlists'][$element[8]][$song['id']] = $song;
		}
		
		return $playlists['playlists']; // Возвращает массив песен пользователя вконтакте
		
		/* Формат ответа: */
		/* Array( "playlist-id" => Array( "playlist-id", "playlist-title", Array("playlist-songs") ) ); */
		/* В нулевом массиве содержатся песни без плейлистов */
    }
	
	function win2utf($str)	{ // Переводит win-1251 в utf8, так как Контакт на запрос act=load_audios_silent отвечает в этой древней кодировке
		static $table = array(
		"\xA8" => "\xD0\x81",
		"\xB8" => "\xD1\x91",
		"\xA1" => "\xD0\x8E",
		"\xA2" => "\xD1\x9E",
		"\xAA" => "\xD0\x84",
		"\xAF" => "\xD0\x87",
		"\xB2" => "\xD0\x86",
		"\xB3" => "\xD1\x96",
		"\xBA" => "\xD1\x94",
		"\xBF" => "\xD1\x97",
		"\x8C" => "\xD3\x90",
		"\x8D" => "\xD3\x96",
		"\x8E" => "\xD2\xAA",
		"\x8F" => "\xD3\xB2",
		"\x9C" => "\xD3\x91",
		"\x9D" => "\xD3\x97",
		"\x9E" => "\xD2\xAB",
		"\x9F" => "\xD3\xB3",
		);
		return preg_replace('#[\x80-\xFF]#se',
		' "$0" >= "\xF0" ? "\xD1".chr(ord("$0")-0x70) :
						   ("$0" >= "\xC0" ? "\xD0".chr(ord("$0")-0x30) :
							(isset($table["$0"]) ? $table["$0"] : "")
						   )',
		$str
		);
	}

}