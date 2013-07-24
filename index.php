<?php
require_once('./Config.php');
require_once('./LoadModules.php');
require_once('./SMSGW.php');
require_once('./Checks.php');


if (isOutgoingMessage())
{
  $sms = new SMSGW();
  
  if ($sms->validateNumber($_GET['recipient']))
  {
    if(!$sms->sendMessage($_GET['message']) && _DEBUG_ENABLED)
    {
      echo $sms->getError();
    }
  }

}
elseif (isIncomingMessage())
{
  $sms = new SMSGW();
  if (!$sms->incomingMessage((string)$_POST['XMLDATA']) && _DEBUG_ENABLED)
  {
    echo $sms->getError();
  }
}