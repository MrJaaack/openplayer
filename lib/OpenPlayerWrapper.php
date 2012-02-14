<?php
namespace Lib;

require_once 'OpenPlayer.php';

class OpenPlayerWrapper {
  
    protected static $instance = null;
    private $player = null;
    
    private function __construct() {
        $config = Config::getInstance();
        
        $logins = $config->getOption('vk', 'login');
        $passs = $config->getOption('vk', 'password');
        
        $max = count($logins) - 1;
        $rkey = rand(0, $max);

        $this->player = new \OpenPlayer\Core(
            $logins[$rkey], 
            $passs[$rkey], 
            $config->getOption('vk', 'appId'),
            $config->getOption('vk', 'userAgent'),
            ROOT . "/assets"
        );
    }
    private function __clone() {}
    private function __wakeup() {}
 
    public static function getInstance() {
        if ( !self::$instance ) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    public function getPlayer() {
        return $this->player;
    }
    
}