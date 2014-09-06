<?php

class config {
    const hostname = "localhost";
    const username = "root";
    const password = "root";
    const database = "auth";

    const num_of_threads = 4; // max threads (4)
    const ops_in_thread = 100;  // max operations in thread (100)

    const correctKeyMask = 0;

    const notif_types = "37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 75, 76, 77, 78, 79, 80, 86, 87, 88, 93"; // http://wiki.eve-id.net/APIv2_Char_Notifications_XML

    const password_hash_type = "sha512";
    const cookie_hash_type = "sha256";
    const cookie_lifetime = 604800; //7 days
    
    const ts3_ip = '213.134.207.130';
    const ts3_queryport = '10011';
    const ts3_user = 'serveradmin';
    const ts3_pass = 'cYg9HoIr';
}
