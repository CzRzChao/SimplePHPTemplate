<?php
/**
 * Copyright © czrzchao.com
 * User: czrzchao
 * Date: 2017/11/14 22:46
 * Desc: 测试代码
 */

require('Template.php');

date_default_timezone_set('PRC');

$config = array(
    'debug' => true,
    'cache_htm' => true,
);

$tpl = new Template($config);
$tpl->assign('data', microtime(true));
$tpl->assign('vars', array(1,2,3));
$tpl->assign('title', "hhhh");
$tpl->show('test');