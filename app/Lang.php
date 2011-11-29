<?php
namespace App;

class Lang extends \Lib\Base\App {
    public function init() {
        $lng = \Lib\Request::get('lang');
        if ( $lng && in_array( $lng, \Lib\Config::getInstance()->getOption( 'app', 'availableLangs' ) ) ) {
            $_SESSION['op']['lang'] = $lng;
            
            if ( $user = \Manager\User::getUser() ) {
                $settings = $user->settings;
                $settings['lang'] = $lng;
                \Manager\User::create()->updateSettings( $settings );
            }
        }
        
        header("Location:" . \Lib\Config::getInstance()->getOption('app', 'baseUrl'));
        die;
    }

}
