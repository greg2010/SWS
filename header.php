<?php
$active = " class=active";
if ($_SESSION[userObject]->isLoggedIn() == 0) {
    if ($thisPage === 'index') {
        $toTemplate['isIndex'] = $active;
    } else {
        $toTemplate['isIndex'] = '';
    }
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
    $permissions = $_SESSION[userObject]->permissions->getWebPermissions();
    if ($thisPage === 'index') {
        $toTemplate['isIndex'] = $active;
    } else {
        $toTemplate['isIndex'] = '';
    }
    
    if ($thisPage === 'admin') {
        $toTemplate['isadmin'] = $active;
    } else {
        $toTemplate['isadmin'] = '';
    }
    if (in_array('webReg_AdminPanel', $permissions)) {
        $toTemplate['hasAdminAccess'] = 1;
    } else {
        $toTemplate['hasAdminAccess'] = 0;
    }
    
    if ($thisPage === 'about') {
        $toTemplate['isAbout'] = $active;
    } else {
        $toTemplate['isAbout'] = '';
    }
    
}