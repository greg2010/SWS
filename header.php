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
    $permissions = $_SESSION[userObject]->permissions->getAllPermissions();
    if ($thisPage === 'index') {
        $toTemplate['isIndex'] = $active;
    } else {
        $toTemplate['isIndex'] = '';
    }
    
    if ($thisPage === 'standings') {
        $toTemplate['isStandings'] = $active;
    } else {
        $toTemplate['isStandings'] = '';
    }
    
    if ($thisPage === 'posmon') {
        $toTemplate['isPosmon'] = $active;
    } else {
        $toTemplate['isPosmon'] = '';
    }
    
    if ($thisPage === 'stats') {
        $toTemplate['isStats'] = $active;
    } else {
        $toTemplate['isStats'] = '';
    }
    
    if ($thisPage === 'admin') {
        $toTemplate['isadmin'] = $active;
    } else {
        $toTemplate['isadmin'] = '';
    }
    
    if ($thisPage === 'about') {
        $toTemplate['isAbout'] = $active;
    } else {
        $toTemplate['isAbout'] = '';
    }
    if (in_array('posMon_Valid', $permissions) || in_array('XMPP_Overmind', $permissions)) {
        $toTemplate['hasPosmonAccess'] = 1;
    } else {
        $toTemplate['hasPosmonAccess'] = 0;
    }
    if (in_array('webReg_AdminPanel', $permissions)) {
        $toTemplate['hasAdminAccess'] = 1;
    } else {
        $toTemplate['hasAdminAccess'] = 0;
    }
    
}