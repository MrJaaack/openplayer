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