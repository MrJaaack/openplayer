<?php
namespace Lib;

class VkLogin {
    const COOK_PATH = 'assets/cookie_';

    public static $rnd = 0;

    public static function checkLogin() {
        $ids = Config::getInstance()->getOption( 'vk', 'id' );
        VkLogin::$rnd = rand( 0, count($ids) - 1 );

        $lockFile = ROOT . '/assets/login.lock_' . VkLogin::$rnd;
        if ( !file_exists( $lockFile ) ) {
            self::login();
            file_put_contents($lockFile, '');
        }
    }

    private static function login() {
        $email = Config::getInstance()->getOption('vk', 'login');
        $pass = Config::getInstance()->getOption('vk', 'password');

        $res = Curl::process('http://vk.com/login.php?op=a_login_attempt');
        if ($res <> 'vklogin') return false;

        $res = Curl::process(
            'http://login.vk.com/', 
            1, 
            'act=login&success_url=&fail_url=&try_to_login=1&to=&vk=1&al_test=3&email=' . 
                urlencode($email[ self::$rnd ]) . '&pass=' . 
                urlencode($pass[ self::$rnd ]) . '&expire='
        );
        
        preg_match(
            '#hash=([0-9a-f]+)#', 
            $res, 
            $tmp
        );
        $hash = $tmp[1];

        $res = Curl::process(
            "http://vk.com/login.php?act=slogin&fast=1&hash={$hash}&s=1", 
            1
        );
    }

}