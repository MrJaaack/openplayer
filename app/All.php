<?php
namespace App;

class All extends \Lib\Base\App {
	
	public function init() {
        // all menu
//        $randomhash = "allppl";
//        
//        if ( file_exists(CACHEROOT . "/{$randomhash}") ) {
//            $cont = file_get_contents( CACHEROOT . "/{$randomhash}" );
//        } else {
//            $cont = file_get_contents("http://en.vpleer.ru/list/");
//            file_put_contents( CACHEROOT . "/{$randomhash}", $cont );
//        }
//        
//        $cont = file_get_contents("http://en.vpleer.ru/list/");
//        
//        preg_match("/<div class=\"listartist text_c\">(.+?)<\/div>/s", $cont, $matches);
//        
//        
//        preg_match_all("/<a href=\"(.+)?\" title=\"Go to the list of the artists\">(.+?)<\/a>/", $matches[0], $matches);
//        
//        $links = array();
//        foreach ( $matches[0] as $key => $value ) {
//            $links[$matches[2][$key]] = $matches[1][$key];
//        }
//        
//        
//        $file = CACHEROOT . "/allMenu.json";
//        
//        file_put_contents( $file, json_encode($links) );
        
        
        $this->allMenu = json_decode( file_get_contents( ROOT . "/db/allMenu.json" ) );
	}
}