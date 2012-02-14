<?php
namespace OpenPlayer;

// file caching
class Cache {
    public static $cacheRoot = "/cache";
  
    public static function set( $key, $value ) {
        $path = self::$cacheRoot . "/" . $key;
        file_put_contents($path, $value);
    }
    
    public static function get( $key, $time ) {
        $time = time() - $time;
        $path = self::$cacheRoot . "/" . $key;
        
        if ( file_exists($path) ) {
            $ftime = filectime($path);
            
            if ( $ftime >= $time ) {
                return file_get_contents($path);
            }
        }
        
        return null;
    }
    
    public static function clear( $key ) {
        $path = self::$cacheRoot . "/" . $key;
        
        if ( file_exists($path) ) {
            unlink($path);
            return true;
        }
        
        return false;
    }
    
    public static function clearAll() {
        $path = self::$cacheRoot;
        
        foreach ( glob("$path/*") as $cacheFile ) {
            unlink($cacheFile);
        }
        
        return true;
    }
}

class Core {
    private $email;
    private $pass;
    private $appId;
    private $uagent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1";
    
    public function __construct( $email, $pass, $appId, $uagent = null, $cacheRoot = "cache" ) {
        $this->email = $email;
        $this->pass = $pass;
        $this->appId = $appId;

        if ( $uagent ) {
            $this->uagent = $uagent;
        }
        
        Cache::$cacheRoot = $cacheRoot;
    }
    
    public function getToken() {
        $cookies = Cache::$cacheRoot . '/cookie_' . sha1(date('H_d_m_y') . 'token' . $this->email) . '.txt';
        $cacheKey = "access_token_".sha1($this->email);
        $token = Cache::get($cacheKey, 60 * 60);

        if ( !$token ) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->uagent);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "act=login&success_url=&fail_url=&try_to_login=1&vk=&al_test=3&email={$this->email}&pass={$this->pass}&expire=");
            curl_setopt($ch, CURLOPT_URL, 'http://login.vk.com/');
            $body = $this->curl_redirect_exec($ch);
            curl_close($ch);

            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_HEADER, 1);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch2, CURLOPT_NOBODY, 0);
            curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch2, CURLOPT_COOKIEFILE, $cookies);
            curl_setopt($ch2, CURLOPT_COOKIEJAR, $cookies);
            curl_setopt($ch2, CURLOPT_URL, 'http://oauth.vk.com/authorize?client_id='.$this->appId.'&scope=audio&response_type=token');
            $responce = $this->curl_redirect_exec($ch2);
            curl_close($ch2);

            if ( preg_match_all("/access_token=(.*)&expires_in=86400/i", $responce, $res) ) {
                // everything is going fine
                $token = $res[1][0];
            } elseif ( preg_match_all("/approve\(\).*?\n.*?location.href = \"(.*?)\";/", $responce, $res) ) {
                // accept application permissions
                $href = $res[1][0];

                $ch3 = curl_init();
                curl_setopt($ch3, CURLOPT_HEADER, 1);
                curl_setopt($ch3, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch3, CURLOPT_NOBODY, 0);
                curl_setopt($ch3, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch3, CURLOPT_COOKIEFILE, $cookies);
                curl_setopt($ch3, CURLOPT_COOKIEJAR, $cookies);
                curl_setopt($ch3, CURLOPT_URL, $href);
                $responce = $this->curl_redirect_exec($ch3);
                curl_close($ch3);
                $token = $this->getTokenM();
            } else {
                $token = Cache::get('access_token', 60 * 60);
                Cache::clear('access_token');
            }

            Cache::set($cacheKey, $token);
        }

        if ( file_exists($cookies) ) {
            unlink($cookies);
        }
        
        return $token;
    }
    
    public function audioSearch( $q, $page = 0, $count = 10, $cacheTime = 86400 ) {
        if ( !$page || $page <= 1 ) $page = 1;

        $cachekey = "search_" . sha1($q.$page.$count);
        
        $audio = Cache::get( $cachekey, $cacheTime );
        if ( !$audio ) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_URL, 'https://api.vk.com/method/audio.search.xml?access_token=' . $this->getToken() . '&count=' . $count . '&q=' . urlencode($q) . '&format=JSON&offset=' . $page);

            $audio = curl_exec($ch);
            curl_close($ch);

            Cache::set($cachekey, $audio);
        }
    
        $data = new \SimpleXMLElement($audio);
        
        $result = array();
        
        if ( $count = (int)$data->count ) {
            foreach ($data->audio as $track) {
                $result[] = (array)$track;
            }
        }
        
        return array(
            'count' => $count,
            'result' => $result
        );
    }
    
    public function audioGetById( $vkId ) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, 'https://api.vk.com/method/audio.getById.xml?access_token=' . $this->getToken() . '&audios=' . $vkId);
        $all = curl_exec($ch);
        curl_close($ch);
        return $all;
    }
    
    public function getTrack( $vkId ) {
        $track = $this->audioGetById($vkId);
        $song = new \SimpleXMLElement($track);
        
        $url = $song->audio->url;
        
        return array(
            'url' => $url,
            'size' => $this->remoteFilesize($url)
        );
    }

    //public function audioGet($a) {
    //	$ch = curl_init();
    //	curl_setopt($ch, CURLOPT_HEADER, 0);
    //	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    //	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    //	curl_setopt($ch, CURLOPT_URL, 'https://api.vk.com/method/audio.search.xml?access_token=' . $this->getToken() . '&count=1&q='.urlencode($a));
    //	$all = curl_exec($ch);
    //	curl_close($ch);
    //	return $all;
    //}
    
    public function audioGetLyrics($id) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, 'https://api.vk.com/method/audio.getLyrics.xml?access_token=' . $this->getToken() . '&lyrics_id=' . $id);
        $lyrics = curl_exec($ch);
        curl_close($ch);
        
        $text = null;
        if ( $lyrics ) {
            $text = new \SimpleXMLElement($lyrics);
            $text = str_replace('&#39;', "'", htmlspecialchars_decode($text->lyrics->text));
        }
        
        return $text;
    }
    
    function remoteFilesize($url) {
        ob_start();
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $ok = curl_exec($ch);
        curl_close($ch);

        $head = ob_get_contents();
        ob_end_clean();
        
        $regex = '/Content-Length:\s([0-9].+?)\s/';
        preg_match($regex, $head, $matches);
        
        return isset($matches[1]) ? $matches[1] : "unknown";
    }
    
    public function curl_redirect_exec($ch, &$redirects = 0, $curloptHeader = false) {
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ( in_array($httpCode, array(301, 302)) ) {
            list($header) = explode("\r\n\r\n", $data, 2);
            $matches = array();

            //this part has been changes from the original
            preg_match("/(Location:|URI:)[^(\n)]*/", $header, $matches);
            $url = trim( str_replace($matches[1], "", $matches[0]) );

            if ( preg_match_all('/access_token=(.*)&expires_in=86400/i', $url, $matches) ) {
                Cache::set('access_token', $matches[1][0]);
            }
            //end changes

            $urlParsed = parse_url($url);
            if ( isset($urlParsed) ) {
                curl_setopt($ch, CURLOPT_URL, $url);
                $redirects++;
                return $this->curl_redirect_exec($ch, $redirects);
            }
        }

        if ( $curloptHeader ) {
            return $data;
        } else {
            list(, $body) = explode("\r\n\r\n", $data, 2);
            return $body;
        }
    }
    
    
}