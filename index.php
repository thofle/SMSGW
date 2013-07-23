<?php
require('./Config.php');
require('./NetworkHandler.php');
require('./SMSGW.php');
require('./Checks.php');


if (isOutgoingMessage())
{
  $sms = new SMSGW();
  
  if ($sms->validateNumber($_GET['recipient']))
  {
    $sms->sendMessage($_GET['message']);
  }

}
elseif (isIncomingMessage())
{
  $sms = new SMSGW();
  $sms->incomingMessage((string)$_POST['XMLDATA']);
}

?>