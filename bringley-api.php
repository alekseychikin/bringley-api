<?php

  class BringleySockets
  {
    private static $host = 'bringley.ru';
    private static $port = '80';
    private static $init = false;
    private $connect = false;

    protected function socketInit($clientdomain)
    {
      if (!self::$init) {
        self::$init = true;
      }
      $connect = fsockopen($clientdomain.'.'.self::$host, self::$port, $errno, $errstr, 30);
      return new BringleySockets($connect, $clientdomain);
    }

    private function __construct($connect, $clientdomain)
    {
      if ($connect) {
        $this->connect = $connect;
        $this->clientdomain = $clientdomain;
      }
    }

    private function recursiveUrlencode($params, $parent = false)
    {
      $result = array();
      foreach ($params as $name => $value) {
        if (gettype($value) != 'array') {
          if ($parent === false) {
            $result[$name] = urlencode($value);
          }
          else {
            $result[$parent.'['.$name.']'] = urlencode($value);
          }
        }
        else {
          if ($parent === false) {
            $res = $this->recursiveUrlencode($value, $name);
          }
          else {
            $res = $this->recursiveUrlencode($value, $parent.'['.$name.']');
          }
          $result = array_merge($res, $result);
        }
      }
      return $result;
    }

    protected function post($uri, $params, & $error = false)
    {
      if ($this->connect) {
        $postValues = array();
        $params = $this->recursiveUrlencode($params);
        foreach ($params as $name => $value) {
          $postValues[] = $name.'='.$value;
        }
        $postValues = implode('&', $postValues);
        $request  = 'POST '.$uri.' HTTP/1.1'."\r\n";
        $request .= 'Host: '.$this->clientdomain.'.'.self::$host."\r\n";
        $request .= "Connection: Close\r\n";

        $lenght = strlen($postValues);
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Content-Length: $lenght\r\n";
        $request .= "\r\n";
        $request .= $postValues;

        fwrite($this->connect, $request);
        $content = '';
        while (!feof($this->connect)) {
          $content .= fgets($this->connect);
        }
        echo $content;
        if (strpos($content, "\r\n\r\n") !== false) {
          $header = substr($content, 0, strpos($content, "\r\n\r\n"));
          $content = substr($content, strpos($content, "\r\n\r\n"));
        }
        elseif (strpos($content, "\n\n") !== false) {
          $header = substr($content, 0, strpos($content, "\n\n"));
          $content = substr($content, strpos($content, "\n\n"));
        }
        $content = ltrim($content);
        $content = json_decode($content, true);
        $header = explode("\n", $header);
        $error = array(
          'code' => preg_replace('/HTTP[\/\d\.]+\s+(\d+).*/', '$1', $header[0]),
          'message' => preg_replace('/HTTP[\/\d\.]+\s+\d+\s+(.*)/', '$1', $header[0])
        );
        return $content;
      }
      $error = array(
        'code' => '500',
        'message' => 'no connection'
      );
      return false;
    }

    protected function get($uri, $params = array(), & $error = false)
    {
      if ($this->connect) {
        $getValues = array();
        $params = $this->recursiveUrlencode($params);
        foreach ($params as $name => $value) {
          $getValues[] = $name.'='.$value;
        }
        $getValues = implode('&', $getValues);
        $request  = 'GET '.$this->clientdomain.'.'.$uri.(!empty($getValues) ? '?'.$getValues : '').' HTTP/1.1'."\r\n";
        $request .= 'Host: '.self::$host."\r\n";
        $request .= "Connection: Close\r\n";
        $request .= "\r\n";

        fwrite($this->connect, $request);
        $content = '';
        while (!feof($this->connect)) {
          $content .= fgets($this->connect);
        }
        if (strpos($content, "\r\n\r\n") !== false) {
          $content = substr($content, strpos($content, "\r\n\r\n"));
          $content = ltrim($content);
        }
        elseif (strpos($content, "\n\n") !== false) {
          $content = substr($content, strpos($content, "\n\n"));
          $content = ltrim($content);
        }
        return $content;
      }
      $error = 'no connection';
      return false;
    }
  }

  class BringleyAPI extends BringleySockets
  {
    private $api_key;
    private $socket;
    private $version = '1.0';

    private $positions = array();
    private $order = array(
      'phone' => '',
      'name' => '',
      'payment' => 'cash',
      'delivery' => 'none',
      'city' => '',
      'street' => '',
      'house' => '',
      'entrance' => '',
      'floor' => '',
      'flat' => '',
      'comment' => ''
    );
    private $discount = 0;
    private $discount_type = 'percents';

    public static function newOrder($clientdomain, $api_key)
    {
      return new BringleyAPI($clientdomain, $api_key);
    }

    private function __construct($clientdomain, $api_key)
    {
      $this->api_key = $api_key;
      $this->socket = self::socketInit($clientdomain);
    }

    public function addPosition($params)
    {
      $this->positions[] = $params;
      return $this;
    }

    public function order($params)
    {
      $this->order = $params;
      return $this;
    }

    public function discount($amount, $type)
    {
      $this->discount = $amount;
      $this->discount_type = $type;
      return $this;
    }

    public function done(& $error)
    {
      $params = array(
        'api_key' => $this->api_key,
        'positions' => $this->positions,
        'discount' => $this->discount,
        'discount_type' => $this->discount_type
      );
      if ($this->order) {
        $params['order'] = $this->order;
      }
      $response = $this->socket->post('/'.$this->version.'/order-add/', $params, $error);
      return $response;
    }
  }
