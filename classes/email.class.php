<?php

class email {

    public function sendmail($email, $subj, $text){
        $subject  = '=?UTF-8?B?' . base64_encode($subj) . '?=';
        $headers  = "MIME-Version: 1.0\r\n"; 
        $headers .= "Content-type: text/plain; charset=utf-8\r\n";
        $headers .= "From: POS tracker notification services <mailer@buaco.ru>\r\n";
        $headers .= "Reply-to: No-Reply <no_reply@buaco.ru>\r\n";
        return mail($email, $subject, $text, $headers);
    }
}

?>
