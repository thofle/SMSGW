<?php

class NetworkHandler
{
  function __construct()
  {
  }
  
  function checkInternet($site = _NET_DEFAULT_SITE)
  {
    if ($this->ping($site))
    {
      return true;
    }
    else
    {
      // site is down, try 3 more times
      for($i = 0; $i < 2; $i++)
      {
        if ($this->ping($this->getRandomSite(), 3))
        {
          return true;
        }
      }
    }
    
    return false;
  }
  
  function getStatusText()
  {
    $message = "Internet Status\n";
    if($this->routerIsUp())
    {
      $message .= "Router: Up\n";
      
      if ($this->checkInternet())
      {
        $message .= "Internet: Up\n";
      }
      else
      {
        $message .= "Internet: Down\n";
      }
    }
    else
    {
      $message .= "Router: Down\n";
    }
    
    return $message;
  }
  
  function getRandomSite()
  {
    $sites = array('vg.no', 'db.no', 'microsoft.com', 'apple.com', 'stackoverflow.com', 'php.net', 'facebook.com', 'yahoo.com', 'google.com', 'digg.com', 'digi.no');
    return $sites[mt_rand(0,count($sites)-1)];
  }

  function routerIsUp()
  {
    return $this->ping(_NET_ROUTER, 5);
  }
  
  function restartRouter()
  {
    $sshConnection = ssh2_connect(_NET_ROUTER, _NET_ROUTER_SSH_PORT);
    if (ssh2_auth_pubkey_file($sshConnection, _NET_ROUTER_USERNAME, _NET_ROUTER_PUBLIC_KEY_PATH, _NET_ROUTER_PRIVATE_KEY_PATH, null))
    {
      ssh2_exec($sshConnection, 'reboot');
      $sshConnection = null;
      return 'Reboot command sent to router.'; 
    }
    else
    {
      return 'Connection to router rejected.';
    }
  }

  function ping($address, $timeout = 1)
  {
    if (!is_numeric($timeout) || $timeout < 1 || $timeout > 10)
      return false;
  
    // address, port, error code, error string, timeout in sec
    $status = @fsockopen($address,80,$errCode,$errStr,$timeout);
    
    if(!$status)
    {   
      return false;
    }
    else 
    {
      fclose($status);
      return true;
    }  
  }
}
?>