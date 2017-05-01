# PHP Telegram Bot

PHP Telegram Bot

## Installation

### Using [Composer](https://getcomposer.org)

`composer.json` :

```json
{
    "require": {
        "alisoftware/phptgbot": "*"
    }
}
```

`index.php`:

```php
<?php
   require __DIR__ .'/vendor/autoload.php' ;
   use \Alisoftware\Phptgbot as Bot;
   
   Bot::setToken(''); 
   Bot::getMe();
```

## Longpooling / Webhook

Example:

```php
<?php
   require __DIR__ .'/vendor/autoload.php' ;
   use \Alisoftware\Phptgbot as Bot;
   
   Bot::setToken(''); 
   
   // webhook
   // Bot::setToken('TOKEN',true); 
   
   Bot::run(function($response){
      if ($response['error'] == false) 
      {
        $message = isset($response['message'])?$response['message']:false;
        $onChannel = isset($response['channel_post'])?$response['channel_post']:false;
        if ($message != false) onMessage($message);
        //if ($onChannel != false) onMessage($onChannel);
      }   
   });
   
   function onMessage($message){
     print_r($message);
     if ($message['text'] == 'ping') {
            Bot::send('message','<b>PONG!</b>');
        }
   }
```



