<?php

namespace Lib;

class Log {
    public static $log = '';
    
    public static function log( $data ) {
        if ( DEBUG ) {
            if ( is_object($data) || is_array($data) ) {
                $data = json_encode($data);
            }

            $data .= "\n\n\n ========== \n\n\n";
        
            Log::$log .= $data;
        }
    }
    
}