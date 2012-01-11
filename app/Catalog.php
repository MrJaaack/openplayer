<?php
namespace App;

class Catalog extends \Lib\Base\App {
	
	public function init() {
        $statManager = new \Manager\Stat;
        $this->artists = $statManager->getTopArtists( 
            \Lib\Config::getInstance()->getOption(
                'app', 
                'catalogSize', 
                50
            ) 
        );
	}
}