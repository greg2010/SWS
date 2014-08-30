<?php
$toTemplate = array();
$active = " class=active";
if ($_SESSION[user]->isLoggedIn() === FALSE) {
    if ($thisPage === 'login') {
        $toTemplate['isLogin'] = $active;
    } else {
        $toTemplate['isLogin'] = '';
    }
    if ($thisPage === 'reg') {
        $toTemplate['isReg'] = $active;
    } else {
        $toTemplate['isReg'] = '';
    }
    if ($thisPage === 'about') {
        $toTemplate['isAbout'] = $active;
    } else {
        $toTemplate['isAbout'] = '';
    }
} else {
    if ($thisPage === 'index') {
        $toTemplate['isIndex'] = $active;
    } else {
        $toTemplate['isIndex'] = '';
    }
    if ($thisPage === 'about') {
        $toTemplate['isAbout'] = $active;
    } else {
        $toTemplate['isAbout'] = '';
    }
}