<?php

namespace App;

class Cache extends \Lib\Base\App {

    public function init() {
        // @todo secure this
        
        foreach ( glob(CACHEROOT . "/*") as $file ) {
            unlink($file);
        }
        
        die('Cleaned');
    }
    
    
}
