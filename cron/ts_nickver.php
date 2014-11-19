<?php
require_once dirname(__FILE__) . '/../init.php';
$ts3 = new ts3;

$wow=$ts3->nick_verify();
var_dump($wow);
?>