<?php

class email {

    public function sendmail($email, $subj, $text){
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
    }
}

?>
