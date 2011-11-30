<?php

namespace App;

class Index extends \Lib\Base\App {

    public function init() {
        $q = \Lib\Request::get('q');
        if ( $q ) {
            $this->title = "Слушать {$q} онлайн, Скачать {$q}";
        }
    }
    
    
}
