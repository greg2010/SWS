<?php

require ("sendemail.class.php");

class email {

    public function sendmail($email, $subj, $text){
        $e = new SendEmail();
        $e->set_auth('coalition@redalliance.pw', 'coalmailred2234');
        $e->set_sender('Red Menace Coalition Services','admin@redalliance.pw');
        $e->mail($email, $subj, $text);
    }

    public function sendmailHtml($email, $subj, $text){
        $e = new SendEmail();
        $e->set_content_type('text/html');
        $e->set_headers(null);
        $e->set_auth('coalition@redalliance.pw', 'coalmailred2234');
        $e->set_sender('Red Menace Coalition Services','admin@redalliance.pw');
        $e->mail($email, $subj, $text);
    }

    /*public function sendmail($email, $subj, $text){
        $subject  = '=?UTF-8?B?' . base64_encode($subj) . '?=';
        $headers  = "MIME-Version: 1.0\r\n"; 
        $headers .= "Content-type: text/plain; charset=utf-8\r\n";
        $headers .= "From: Red Menace Coalition Services <mailer@coalition.redalliance.pw>\r\n";
        $headers .= "Reply-to: No-Reply <mailer@coalition.redalliance.pw>\r\n";
        return mail($email, $subject, $text, $headers);
    }

    public function sendmailHtml($email, $subj, $text){
        $subject  = '=?UTF-8?B?' . base64_encode($subj) . '?=';
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: Red Menace Coalition Services <mailer@coalition.redalliance.pw>\r\n";
        $headers .= "Reply-to: No-Reply <mailer@coalition.redalliance.pw>\r\n";
        return mail($email, $subject, $text, $headers);
    }*/
}

?>
