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

```php
<?php
   require __DIR__ .'/vendor/autoload.php' ;
   use \Alisoftware\Phptgbot as Bot;
   
   Bot::setToken(''); 
   
   // webhook
   // Bot::setToken('TOKEN',true); 
   
   Bot::run(function($response){
        print_r(%response);
   });
```

