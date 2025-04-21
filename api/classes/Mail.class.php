<?php

namespace Sets;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

class Mail
{
    protected $to;
    protected $subject;
    protected $message;
    protected $headers;
    protected $date;
    protected $mail_templates = array();
    protected $msg;

    function __construct($to, $subject, $message, $template) {
        $this->to = $to;
        $this->subject = $subject;
        $this->date = date("Y-m-d");
        $this->headers = "Content-type: text/html; charset=utf-8 \r\n";
        $this->headers .= "From: postmaster@c15869.nichost.ru \r\n";
        //$this->headers .= "Bcc: nightwish1989@gmail.com, ivpetrin@yandex.ru \r\n";
        $this->headers .= "Bcc: avpetrin@yandex.ru \r\n";
        $this->mail_templates["empty"] = "###";
        $event_template = '<div style="color:#444;font-family:Arial," Helvetica Neue",Helvetica,sans-serif;';
        /*$event_template .= 'font-size:14px;line-height:1.5;margin:0">Здравствуйте!<br/><br/>';
        $event_template .= 'Произошло новое событие!<br/><br/>';*/
        $event_template .= '<div style="border: 1px dashed #ccc; padding: 10px;">';
        $event_template .= '###';
        $event_template .= '</div></div>';
        $this->mail_templates["event"] = $event_template;
        $this->message = str_replace("###", $message, $this->mail_templates[$template]);
        $this->msg = $message;
    }

    function pushMail() {
        mail($this->to, $this->subject, $this->message, $this->headers);
    }

    function pushMailPhpMailer() {
        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $yourEmail = 'no-reply@xn--c1asahew.xn--p1ai'; // ваш email на яндексе

        // настройки SMTP
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();

        $mail->Host       = 'mail.nic.ru';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no-reply@xn--c1asahew.xn--p1ai';
        $mail->Password   = 'Elite177elite';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // формируем письмо

        // от кого: это поле должно быть равно вашему email иначе будет ошибка
        $mail->setFrom($mail->Username, 'ГУМПОМ.РФ');
        //$mail->setFrom('no-reply@гумпом.рф', 'ГУМПОМ.РФ');

        // кому - получатель письма
        //$mail->addAddress($this->email, "");  // кому
        $mail->addAddress($this->to, "");  // кому
        $mail->addBCC('avpetrin@yandex.ru', "");  // кому
        $mail->addBCC('ivpetrin@yandex.ru', "");  // кому

        $mail->Subject = $this->subject;  // тема письма

        $mail->msgHTML($this->msg);

        if ($mail->send()) {
            return true;
        } else {
            return('Ошибка: ' . $mail->ErrorInfo);
        }
    }

    function pushMailWithAttach($path) {
        $boundary = md5(uniqid(time()));; //Разделитель
        $this->headers = "MIME-Version: 1.0\r\n";
        $this->headers .= "From: mailto@mypolechka.ru \n";
        $this->headers .= "Bcc: avpetrin@yandex.ru \n";
        $this->headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"";
        $filename = basename($path);
        $body = "--$boundary\n";
        $body .= "Content-type: text/html; charset='utf-8'\n";
        $body .= "Content-Transfer-Encoding: quoted-printablenn";
        $body .= "Content-Disposition: attachment; filename==?utf-8?B?".base64_encode($filename)."?=\n\n";
        $body .= $this->message."\n";
        $body .= "--$boundary\n";
        $file = fopen($path, "r"); //Открываем файл
        $text = fread($file, filesize($path)); //Считываем весь файл
        fclose($file); //Закрываем файл
        /* Добавляем тип содержимого, кодируем текст файла и добавляем в тело письма */
        $body .= "Content-Type: application/octet-stream; name==?utf-8?B?".base64_encode($filename)."?=\n";
        $body .= "Content-Transfer-Encoding: base64\n";
        $body .= "Content-Disposition: attachment; filename==?utf-8?B?".base64_encode($filename)."?=\n\n";
        $body .= chunk_split(base64_encode($text))."\n";
        $body .= "--".$boundary ."--\n";
        mail($this->to, $this->subject, $body, $this->headers);
    }
}