<?php
/**
 * User: thofle
 * Date: 24.07.13
 * Time: 21:47
 */

function executeCommandAUTH(&$SMSGW, $argument = null)
{
  $code = $SMSGW->getAndSaveAuthCode();

  if ($code > 9999 && $code < 100000)
    return $SMSGW->sendMessage('Auth code: ' . $code);
  else
    return false;
}