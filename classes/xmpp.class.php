<?php

class xmpp {

    public function sendjabber($jid, $text){
    	$xmpp_options = array( "http" => array( "method" => "POST", "content" => http_build_query(array( "body" => $text )) ) );
        $xmpp_result = json_decode(file_get_contents("http://". config::xmpp_address . "/send/" . rawurlencode($jid) . "/" . rawurlencode(config::xmpp_send_from), false, stream_context_create($xmpp_options)), true);
        return $xmpp_result[sent];
    }
}

?>
