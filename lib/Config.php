<?php
namespace Lib;

class Config {
    private static $instance = null;
    private $config= null;
    
    /**
     *
     * @return Config
     */
    public static function getInstance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    private function __construct() {
        $this->config = parse_ini_file(ROOT . '/configs/app.ini', true);
    }
    private function __clone() {}
    
    public function getOptions( $section = null ) {
        if ( null == $section) {
            return $this->config;
        } else {
            return $this->config[$section];
        }
    }
    
    public function getOption( $section, $key ) {
        if ( !isset( $this->config[$section] ) ) return null;
        
        if ( !isset( $this->config[$section][$key] ) ) return null;
            
        return $this->config[$section][$key];
    }
    
}