<?php

function op_truncate($string, $limit = 30, $pad="...") {
    if (mb_strlen($string) <= $limit) {
        return $string;
    }

    return mb_substr(
        $string, 0, $limit, Lib\Config::getInstance()->getOption('app', 'charset')
    ) . $pad;
}

function op_clear($string) {
    return html_entity_decode(
        $string
    );
}

function op_conf($section, $key, $default = null) {
    return \Lib\Config::getInstance()->getOption(
        $section, $key, $default
    );
}

// Global class
class G {
    public static $sape = null;
}

function op_sape( $n = null, $offset = 0, $options = null ) {
    if ( $sapeUser = \Lib\Config::getInstance()->getOption('app', 'sape') ) {
        if ( null == G::$sape ) {
            if ( !defined('_SAPE_USER') ) {
                define('_SAPE_USER', $sapeUser);
            }

            $sapeFile = $_SERVER['DOCUMENT_ROOT'] . '/' . _SAPE_USER . '/sape.php';
            
            if ( !file_exists($sapeFile) ) {
                return null;
            }
            
            require_once( $sapeFile );
            G::$sape = new SAPE_client();
        }

        return G::$sape->return_links( $n, $offset, $options );
    }
    
    return null;
}