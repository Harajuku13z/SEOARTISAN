<?php
declare(strict_types=1);
namespace App\Services\Mail;
use RuntimeException;
final class SmtpMailer
{
    public function sendHtml(string $to,string $subject,string $html,?string $replyTo=null):void
    {
        $host=(string)config('mail.host','');$port=(int)config('mail.port',587);$username=(string)config('mail.username','');$password=(string)config('mail.password','');$from=(string)config('mail.from.address',$username);$fromName=(string)config('mail.from.name',config('app.name','Site Artisan'));$replyTo=$replyTo?:(string)config('mail.reply_to','');$hello=parse_url((string)config('app.url',''),PHP_URL_HOST)?:'localhost';
        if($username===''||$password===''||!filter_var($to,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Configuration SMTP incomplète.');
        $socket=@stream_socket_client("tcp://{$host}:{$port}",$number,$message,15);if(!is_resource($socket))throw new RuntimeException("Connexion SMTP impossible : {$message} ({$number}).");stream_set_timeout($socket,15);
        try{$this->expect($socket,[220]);$this->command($socket,'EHLO '.$hello,[250]);$this->command($socket,'STARTTLS',[220]);if(!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))throw new RuntimeException('Activation TLS SMTP impossible.');$this->command($socket,'EHLO '.$hello,[250]);$this->command($socket,'AUTH LOGIN',[334]);$this->command($socket,base64_encode($username),[334]);$this->command($socket,base64_encode($password),[235]);$this->command($socket,'MAIL FROM:<'.$from.'>',[250]);$this->command($socket,'RCPT TO:<'.$to.'>',[250,251]);$this->command($socket,'DATA',[354]);
            $headers=['Date: '.date(DATE_RFC2822),'From: '.$this->encode($fromName).' <'.$from.'>','To: <'.$to.'>','Subject: '.$this->encode($subject),'MIME-Version: 1.0','Content-Type: text/html; charset=UTF-8','Content-Transfer-Encoding: 8bit'];if($replyTo!==''&&filter_var($replyTo,FILTER_VALIDATE_EMAIL))$headers[]='Reply-To: <'.$replyTo.'>';
            $body=implode("\r\n",$headers)."\r\n\r\n".str_replace("\n.","\n..",str_replace(["\r\n","\r"],"\n",$html));fwrite($socket,str_replace("\n","\r\n",$body)."\r\n.\r\n");$this->expect($socket,[250]);$this->command($socket,'QUIT',[221]);
        }finally{fclose($socket);}
    }
    private function command($socket,string $command,array $codes):string{fwrite($socket,$command."\r\n");return $this->expect($socket,$codes);}
    private function expect($socket,array $codes):string{$response='';do{$line=fgets($socket,515);if($line===false)throw new RuntimeException('Le serveur SMTP ne répond pas.');$response.=$line;}while(isset($line[3])&&$line[3]==='-');$code=(int)substr($response,0,3);if(!in_array($code,$codes,true))throw new RuntimeException('Erreur SMTP '.$code.' : '.trim($response));return $response;}
    private function encode(string $value):string{return '=?UTF-8?B?'.base64_encode(str_replace(["\r","\n"],'',$value)).'?=';}
}
