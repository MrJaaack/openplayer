<?php

namespace App;
use \Lib\Request,
    \Lib\Config,
    \Lib\AudioParser;

class Songs extends \Lib\Base\App {

    public function search( $q, $offset, $count = 1 ) {
        $songs = AudioParser::search (
            $q, 
            $offset
        );
        
        if ( !count( $songs ) && 
             !Request::get('tokenreset') && 
            ('ajax' != Request::get('app')) )
        { // токен сдох
          // фигово тем, что если нифига не найдено будет выполнен перелогин, 
          // но к сожалению это единственный безболезненый способ узнать жив ли еще токен.
            $ids = Config::getInstance()->getOption( 'vk', 'id' );
            foreach ($ids as $key => $value) {
                unlink( \Lib\VkLogin::COOK_PATH . $key );
            }

            $url = Request::getAllGet();
            $url['tokenreset'] = true;
            $location = http_build_query( $url );

            header("Location: ?{$location}");
        }
        
        // Если ничего не найдено и токен был перезагружен, то таки ничего не найдено, либо какие-то проблемы, 
        // для дебага залогируем html страницу - вконтакте, посомтрим, че да как.
        if ( !count( $songs ) && Request::get('tokenreset') ) {
//            print_r(
//                AudioParser::$html
//            );
        }
        
        
        $cut = true;
        
        if ( 1 == $count ) {
            foreach ($songs as $value) {
                if ( Request::get('q') == trim($value['artist']."-".$value['name']) ) {
                    $songs = array($value);
                    $cut = false;
                    break;
                }
            }

        } 
        
        if ( $cut ) {
            $songs = array_slice(// @todo
                $songs, 0, $count
            );
        }
        
        return $songs;
    }

    public function init() {
        $count = Request::get('l') ?: Config::getInstance()->getOption('app', 'resultsPerPage');
        
        $this->songs = $this->search(
            Request::get('q'), 
            Request::get('offset', '0'),
            $count    
        );
        
        
    }

}
