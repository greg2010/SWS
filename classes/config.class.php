<?php

class config {
    const hostname = "localhost";
    const username = "root";
    const password = "root";
    const database = "auth";

    const num_of_threads = 4; // max threads (4)
    const ops_in_thread = 100;  // max operations in thread (100)

    const ok_message_in_log = false;

    const correctKeyMask = 49160;

    const notif_types = "37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 75, 76, 77, 79, 80, 86, 87, 88, 93"; // http://wiki.eve-id.net/APIv2_Char_Notifications_XML

    const password_hash_type = "sha512";
    const cookie_hash_type = "sha256";
    const cookieNumber = 4;
    const cookie_lifetime = 604800; //7 days
    
    const ts3_ip = '5.135.164.103';
    const ts3_queryport = '10011';
    const ts3_user = 'serveradmin';
    const ts3_pass = 'cYg9HoIr';
    const ts3_debug = 0;

    const xmpp_address = 'localhost:7092';
    const xmpp_send_from = 'Angel Cartel';
}
