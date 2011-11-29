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
        $cachePath = CACHEROOT . "/generated_config.php";
        
        if ( file_exists($cachePath) ) {
            $this->config = require_once $cachePath;
        } else {
            $this->config = parse_ini_file(ROOT . '/configs/app.ini', true);
            // caching
            $data = print_r($this->config, true);
            $data = str_replace('[', '"', $data);
            $data = str_replace(']', '"', $data);

            $data = preg_replace("/=> (.*)\n/", "=> \"$1\",\n", $data);

            $data = str_replace('"Array",', 'array', $data);
            $data = str_replace(')', '),', $data);

            $len = strlen($data);
            $data[$len-2] = ";";

            file_put_contents($cachePath, "<?php return " . $data);
            // /caching
        }
        
    }
    private function __clone() {}
    
    public function getOptions( $section = null ) {
        if ( null == $section) {
            return $this->config;
        } else {
            return $this->config[$section];
        }
    }
    
    public function getOption( $section, $key, $default = null ) {
        if ( !isset( $this->config[$section] ) ) return $default;
        
        if ( !isset( $this->config[$section][$key] ) ) return $default;
            
        return $this->config[$section][$key];
    }
    
}