<?php

$_SESSION[userObject]->preparePage($pagePermissions);

if ($toTemplate[loggedIN] == 1) {
    $pilotInfo = $_SESSION[userObject]->getApiPilotInfo();
    $toTemplate['characterName'] = $pilotInfo[mainAPI][characterName];
    $toTemplate['characterID'] = $pilotInfo[mainAPI][characterID];
}

$_SESSION[logObject]->setSessionInfo();
$_SESSION[logObject]->pushToDb('hits');
$toTemplate['hasAccess'] = strval($_SESSION[userObject]->hasPermission());

$loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/templates');
$twig = new Twig_Environment($loader, array(
    'cache' => False
));
$template = $twig->loadTemplate($templateName . '.twig');
echo $template->render($toTemplate);