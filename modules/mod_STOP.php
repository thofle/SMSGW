<?php
/**
 * User: thofle
 * Date: 25.07.13
 * Time: 19:23
 */


function executeCommandSTOP(&$SMSGW, $argument = null)
{
  list($mainArgument, $secondaryArguments) = $SMSGW->parseIncomingMessage($argument);

  if($mainArgument != null)
  {
    switch ($mainArgument)
    {
      case 'CATFACTS':
      case 'CATFACT':
        $SMSGW->sendMessage('Thanks for subscribing to daily cat facts! Send STOP CATFACTS to stop.');
        require_once('./extensions/ext_CATFACTS.php');
        return $SMSGW->sendMessage(extentsionGetCatFact($SMSGW->pDB));

      default:
        $this->log(__FUNCTION__, 'Undefined mainArgument - ' . $mainArgument);
        $SMSGW->error = 'Undefined mainArgument';
        return false;
    }
  }

  $this->log(__FUNCTION__, 'Invalid mainArgument');
  $SMSGW->error = 'Invalid mainArgument';

  return false;
}