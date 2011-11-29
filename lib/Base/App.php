<?php
namespace Lib\Base;

class App {
    public $content = null;
    
    public $title = null;
    public $layout = "default";

    public function setLayout( $layout = "default" ) {
        $this->layout = $layout;
    }
    
    public function run( $app ) {
        $user = new \Manager\User;
        $user->autologin();
        
        $this->init();
        $this->content = $this->render($app, true);
        
        if ( null != $this->layout ) {
            require ROOT . "/views/layouts/{$this->layout}.phtml";
        } else {
            echo $this->content;
        }
    }
    
    public function render($app, $current = false) {
        if ( !$current ) {
            $appClass = "\\App\\".ucfirst($app);
            $appObj = new $appClass;
            $appObj->init();
            
            return $appObj->render($app, true);
        }
        
        ob_start();
        require ROOT . "/views/templates/{$app}.phtml";
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
    
    public function init() {}
}