<?php

/**
 * @filename test.php 
 * @encoding UTF-8 
 * @author CzRzChao 
 * @createtime 2016-6-7  23:08:12
 * @updatetime 2016-6-7  23:08:12
 * @version 1.0
 * @Description
 * 
 */
require('Template.php');

date_default_timezone_set('PRC');

$config = array(
    'debug' => true,
    'cache_htm' => true,
    'debug' => false
);

$tpl = new Template($config);
$tpl->assign('data', microtime(true));
$tpl->assign('vars', array(1,2,3));
$tpl->assign('title', "hhhh");
$tpl->show('test');