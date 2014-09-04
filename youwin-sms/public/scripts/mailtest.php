<?php
  require("../core/bootstrap.php");
  
  $tr = new Zend_Mail_Transport_Sendmail('-f info@campionet.net');
  Zend_Mail::setDefaultTransport($tr);
  
  $mail = new Zend_Mail();
  $mail->setBodyText('This is the text of the mail.');
  $mail->setFrom('info@campionet.net', 'Info');
  $mail->addTo('leonardoperria@yahoo.com', '');
  $mail->setSubject('TestSubject');
  $mail->send();