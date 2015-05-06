<?php

  header('Content-type: text/html; charset=UTF-8');
  require_once("bringley-api.php");

  if (isset($_POST['send'])) {

    $order = BringleyAPI::newOrder('bestpizza', '892bb3493cb9e1491a78a55579ce7ee9')
    ->addPosition(array(
      'position' => '52',
      'count' => 2,
      'options' => array('43'),
      'discount' => 20,
      'discount_type' => 'percents'
    ))
    ->addPosition(array(
      'position' => 69,
      'count' => 1,
      'size' => 1
    ))
    ->order(array(
      'phone' => '927 123-12-34',
      'name' => 'Евгения',
      'payment' => 'cash',
      'delivery' => 'none',
      'city' => 'Москва',
      'street' => 'пер. Молочный',
      'house' => '6',
      'entrance' => '2',
      'floor' => 6,
      'flat' => '44'
    ))
    ->discount('10', 'percents')
    ->done($error);

    if ($error['code'] != 200) {
      die($error['code']." ".$error['message'].": ".$order['error']);
    }
  }

?>
<html>
  <head>
    <title></title>
  </head>
  <body>
    <form action="" method="post">
      <input type="hidden" name="send" value="" />
      <input type="submit" name="submit" value="Отправить" />
    </form>
  </body>
</html>
