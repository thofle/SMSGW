<?php
/**
 * User: thofle
 * Date: 24.07.13
 * Time: 18:09
 */


function executeCommandRESTART(&$SMSGW, $argument = null)
{
  require_once('./NetworkHandler.php');
  if ($SMSGW->isAuthCodeValid($argument))
  {
    $net = new NetworkHandler();
    return $SMSGW->sendMessage($net->restartRouter());
  }
  else
  {
    $SMSGW->log(__FUNCTION__, 'Invalid auth code - ' . $argument);
    return false;
  }
}