<?php
/**
 * User: slipperyNipple
 * Date: 23.07.13
 * Time: 20:33
 */


class SMSGW
{
  private $isNumberValid = false;
  private $pDB = null;
  private $number;

  function __construct()
  {
    $this->pDB = new mysqli(_SQL_SERVER, _SQL_USERNAME, _SQL_PASSWORD, _SQL_DATABASE);
  }

  function log($message)
  {
    $statement = $this->pDB->prepare('INSERT INTO `log` (`message`) VALUES(?)');
    $statement->bind_param('s', $message);
    return $statement->execute();
  }

  function convertNumber($number)
  {
    // TODO: Less static, more dynamic!
    if (strlen($number) == 11 && substr($number, 0, 3) == '+47')
      $number = substr($number, 3);
    elseif (strlen($number) == 10 && substr($number, 0, 2) == '47')
      $number = substr($number, 2);
    return $number;
  }

  function validateNumber($number)
  {
    $number = $this->convertNumber($number);

    // TODO: Less static, more dynamic!
    $this->isNumberValid = ($number == _SMS_STATIC_NUMBER ? true : false);
    $this->number = $number;
    return $this->isNumberValid;
  }

  function canSendMessage()
  {
    $query = $this->pDB->query('SELECT canSendMessage()');
    $result = $query->fetch_array(MYSQLI_NUM);

    if ((int)$result[0] == 1)
      return true;

    return false;
  }

  function logMessage($number, $message, $receive = false)
  {
    $type = 'send';

    if ($receive === true)
      $type = 'recieve';

    $statement = $this->pDB->prepare('CALL saveMessage(?, ?, ?)');
    $statement->bind_param('sss', $number, $message, $type);
    return $statement->execute();
  }

  function sendMessage($message)
  {
    if ($this->canSendMessage() !== true)
      return 'Cannot send messages at this time.';

    if ($this->isNumberValid !== true)
      return 'Invalid number';


    $socket = fsockopen(_SMS_API_SERVER,_SMS_API_PORT);
    if (!$socket)
    {
      return 'Unable to connect to SMS API';
    }
    else
    {
      $preparedMessage = $this->prepareMessage($message);
      fwrite($socket, $this->constructRequest($preparedMessage));
      $this->logMessage($this->number, $preparedMessage, false);

      if (_DEBUG_ENABLED)
      {
        // get response code from SMS Gateway API
        while (!feof($socket))
        {
          echo fgets($socket, 128);
        }
      }
    }
    fclose($socket);
  }

  function incomingMessage($xmlString)
  {
    $xml = simplexml_load_string($xmlString);
    $number = $this->convertNumber($xml->MessageNotification->SenderNumber);
    $message = $xml->MessageNotification->Message;
    $this->logMessage($number, $message, true);
    $this->smsRouter($number, $message);
  }

  function smsRouter($number, $command)
  {
    $command = strtoupper(trim($command));
    if (strlen($command) < 3)
    {
      $this->log('smsRouter: Command too short - ' . strlen($command));
      return false;
    }

    if(!$this->validateNumber($number))
    {
      $this->log('smsRouter: Invalid number - ' . $number);
      return false;
    }

    switch ($this->getBaseCommand($command))
    {
      case 'REBOOT':
      case 'RESTART':
        $net = new NetworkHandler();
        $this->sendMessage($net->restartRouter());
        break;
      case 'PING':
      case 'STATUS':
        $net = new NetworkHandler();
        $this->sendMessage($net->getStatusText());
        break;
      default:
        $this->log('smsRouter: No match for command - ' . $this->getBaseCommand($command));
        break;
    }
  }

  /**
   * @param $message
   * @return mixed
   */
  public function prepareMessage($message)
  {
    $message = str_replace(' ', '%20', $message);
    $message = str_replace("\n", '%0D', $message);
    return $message;
  }

  /**
   * @param $parsedMessage
   * @return string
   */
  public function constructRequest($parsedMessage)
  {
    $request = 'GET /sendmsg?user=' . _SMS_API_USERNAME . '&passwd=' . _SMS_API_PASSWORD . '&cat=1&to=' . $this->number . '&text=' . $parsedMessage . " HTTP/1.1\r\n";
    $request .= "User-agent: SMSGW\r\n";
    $request .= "Accept: */*\r\n";
    $request .= "Connection: Close\r\n\r\n";
    return $request;
  }

  /**
   * @param $command
   * @return mixed
   */
  public function getBaseCommand($command)
  {
    if (strpos($command, ' ') !== false)
    {
      // trims away everything from the first %20
      $baseCommand = strstr($command, ' ', true);
    }
    else
    {
      $baseCommand = $command;
    }

    return $baseCommand;
  }
}
?>