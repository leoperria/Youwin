<?php
  require("../../core/xmlrpc_bootstrap.php");
  
  
  $tr = new Zend_Mail_Transport_Sendmail('-finfo@host179-168-149-62.serverdedicati.aruba.it');
  Zend_Mail::setDefaultTransport($tr);

  $mail = new Zend_Mail();
  $userText="CIAO COME STAI ?\n\n";
  
  $userText.="This e-mail was sent by Omicronmedia - Kinesistemi S.r.l., located at
Via 2 giugno 1946, n.12, Oristano, Sardegna  09170 (Italia). To
receive no further e-mails, please reply to this e-mail with \"unlist\"
in the Subject line.";
   
  $mail->setBodyText($userText)
       ->setFrom("info@campionet.net")
       ->addTo("leonardoperria@yahoo.com")
       ->setSubject("Domanda di ammissione")
       ->send();
       
       