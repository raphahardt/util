<?php

namespace Djck\mailer;

Core::import('PHPMailerAutoload', '/plugin/phpmailer');
/**
 * Description of Mailer
 *
 * @author usuario
 */
class Mailer extends \PHPMailer {
  
  function __construct($exceptions = false) {
    parent::__construct(true); // sempre jogar exceptions
    
    // configuração padrao
    // TODO: CRIAR UMA CLASSE MailerConfig E CRIAR VARIAS CONFIGURAÇÕES NUM ARQUIVO PARA CARREGALAS
    // QUANDO NECESSARIO (COMO A BD FAZ)
    $this->isSMTP();                                      // Set mailer to use SMTP
    $this->Host = 'smtp.xxx.com';  // Specify main and backup server
    $this->SMTPAuth = true;                               // Enable SMTP authentication
    $this->Username = 'email@xxx.com';                            // SMTP username
    $this->Password = 'secret';                           // SMTP password
    $this->SMTPSecure = '';                            // Enable encryption, 'ssl' also accepted
    $this->CharSet = SITE_CHARSET;

    $this->setFrom('email@xxx.com', 'Xxx');

    $this->WordWrap = 50;                                 // Set word wrap to 50 characters
  }
  
}