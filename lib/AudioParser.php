<?php
namespace Lib;

class AudioParser {

    public static function search($query, $offset = 0) {
        $config = Config::getInstance();
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
        
        $matches = explode(
            '<div class="fl_l" style="width:31px;height:21px;">', 
            $answer
        );

        $songs = array();
        $songsManager = new \Manager\Songs;
        foreach ($matches as $audioItem) {
            preg_match_all(
                '/<div class="duration fl_r">(.*)<\/div>/', 
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
			
			preg_match_all( // »щет id вида 41613828_110901414
                '/<div class="repeat_wrap">(.*)?<\/div>/',
                $audioItem,
                $res2
            );
			
			if (preg_match('#id="repeat(.+?)"#', $res2[1][0], $play_id)) { // «аписывает id вида 41613828_110901414
				$song['vkid'] = $play_id[1];
            }

			if ( $config->getOption('app', 'fair_id') == 'yes' ) {
				$headers = \Lib\Curl::get_headers($song['url'], true);
                
				if (!isset($headers['Content-Length'])) {
					//this could be caused by expired token...invoke re-search or skip track?
					continue;
				}
                
				$song['id'] = md5($songname.$headers['Content-Length']);
			} else {
				$song['id'] = md5($songname.$song['duration']);
			}

            $songs[$song['id']] = $song;
            
            if ( $config->getOption( 'app', 'logSongs' ) ) {
                $songsManager->addSong( 
                    $song['id'], 
                    '', 
                    $song['name'], 
                    $song['artist']
                );
            }
        }

        return $songs;
    }
	
	/* ѕринимает парамерт id вида 41613828_110901414 (пользователь_песн€). ƒанный параметр содержитс€ в переменной songs[song[id]][vkid] */
	/* ћожно генерировать ссылки виды http://yoursite.ru/?song=41613828_110901414 и гарантировано возращать ту самую песню */
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
			$song['translate'] = '0';
			
			return $song; // ¬озвращает массив с одной песней. ƒанна€ песн€ - 100% именно та, на которую дают ссылку
		} else {
			return array();
		}
    }
	
	function win2utf($str)	{ // ѕереводит win-1251 в utf8, так как  онтакт на запрос act=load_audios_silent отвечает в этой древней кодировке
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