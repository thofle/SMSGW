<?php
/**
 * User: thofle
 * Date: 23.07.13
 * Time: 20:33
 */


class SMSGW
{
  private $isNumberValid = false;
  private $pDB = null;
  private $number;

  private $error = null;

  function __construct()
  {
    $this->pDB = new mysqli(_SQL_SERVER, _SQL_USERNAME, _SQL_PASSWORD, _SQL_DATABASE);
  }

  function log($method, $message)
  {
    $logMessage = $method . ': ' . $message;
    $statement = $this->pDB->prepare('INSERT INTO `log` (`message`) VALUES(?)');
    $statement->bind_param('s', $logMessage);
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

  function getAndSaveAuthCode()
  {
    $code = mt_rand(10000, 99999);

    $timeoutInMinutes = _SEC_AUTH_CODE_TIMEOUT;

    $statement = $this->pDB->prepare('CALL saveAuthCode(?, ?, ?)');
    $statement->bind_param('sii', $this->number, $timeoutInMinutes, $code);

    if ($statement->execute())
    {
      $statement->close();
      return $code;
    }
    $statement->close();
    return 0;
  }

  function isAuthCodeValid($code)
  {
    if ((int)$code < 10000 || (int)$code > 99999)
      return false;

    $statement = $this->pDB->prepare('SELECT authCodeIsValid(?, ?)');
    $statement->bind_param('si', $this->number, $code);

    if ($statement->execute())
    {
      $statement->bind_result($result);
      $statement->fetch();
      $statement->close();
      if((int)$result == 1)
        return true;
    }

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
    {
      $this->error = 'Cannot send messages at this time.';
      return false;
    }

    if ($this->isNumberValid !== true)
    {
      $this->error = 'Invalid number';
      return false;
    }


    $socket = fsockopen(_SMS_API_SERVER,_SMS_API_PORT);
    if (!$socket)
    {
      $this->log(__METHOD__, 'Unable to connect to SMS API, ' . _SMS_API_SERVER . ':' . _SMS_API_PORT);
      $this->error = 'Unable to connect to SMS API';
      return false;
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
    return true;
  }


  /**
   * Parses the XML from the MultiTech SF100-G SMS Gateway
   * @param $xmlString
   * @return bool
   */
  function incomingMessage($xmlString)
  {
    $xml = simplexml_load_string($xmlString);
    $number = $this->convertNumber($xml->MessageNotification->SenderNumber);
    $message = $xml->MessageNotification->Message;
    $this->logMessage($number, $message, true);

    if($this->validateNumber($number))
    {
      list($command, $argument) = $this->parseIncomingMessage($message);
      return $this->smsRouter($command, $argument);
    }
    else
    {
      $this->log(__METHOD__, 'Unknown/invalid number, ' . $number);
    }

    return false;
  }


  /**
   * Splits the command from the argument and returns array($command, $argument)
   * @param string $message
   * @return array
   */
  function parseIncomingMessage($message)
  {
    $argument = null;

    if (strstr($message, ' '))
    {
      $command = strstr($message, ' ', true);
      $argument = trim(substr($message, strlen($command)));
    }
    else
    {
      $command = trim($message);
    }

    return array(strtoupper($command), $argument);
  }


  /**
   * Routes the $command and $argument to the correct function.
   * @param string $command
   * @param string $argument
   * @return bool
   */
  function smsRouter($command, $argument)
  {
    if (in_array($command, unserialize(_ENABLED_MODULES)))
    {
      $function = 'executeCommand'.$command;

      if (function_exists($function))
      {
        // calls variable function
        return $function($this, $argument);
      }
      else
      {
        $this->log(__METHOD__, 'Tried calling non-existing function ' . $function);
      }
    }
    else
    {
      $this->log(__METHOD__, 'No match for command - ' . $command);
    }

    $this->error = 'Invalid command';
    return false;
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
   * @return string
   */
  function getError()
  {
    return $this->error;
  }
}