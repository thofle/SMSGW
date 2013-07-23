<?php
/**
 * User: slipperyNipple
 * Date: 23.07.13
 * Date: 23.07.13
 * Time: 20:35
 */

/**
 * @return bool
 */
function isOutgoingMessage()
{
  return isset($_GET['message']) && isset($_GET['recipient']);
}

/**
 * @return bool
 */
function isIncomingMessage()
{
  return isset($_POST['XMLDATA']);
}

?>