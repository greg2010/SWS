<?php
require_once 'init.php';

$toTemplate = array();

$loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/templates');
$twig = new Twig_Environment($loader, array(
    'cache' => False,
));
$template = $twig->loadTemplate( 'exampleLogIn.twig');
echo $template->render($toTemplate);
;