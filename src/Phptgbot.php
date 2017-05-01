<?php
/**
 * PHP Telegram Bot
 *
 * PHP version 5 or above
 *
 * @category  PHP
 * @package   PHPtgbot
 * @author    Ali <admin@situsali.com>
 * @copyright 2017 Alisoftware
 * @license   https://github.com/aliterm/phptgbot/blob/master/licence.txt MIT Licence
 * @link      http://github.com/aliterm/phptgbot.git
 */
namespace Alisoftware;
/**
 * PHP Telegram Bot
 *
 * PHP version 5 or above
 *
 * @category  PHP
 * @package   PHPtgbot
 * @author    Ali <admin@situsali.com>
 * @copyright 2017 Alisoftware
 * @license   https://github.com/aliterm/phptgbot/blob/master/licence.txt MIT Licence
 * @link      http://github.com/aliterm/phptgbot.git
 */
class Phptgbot
{

    private static $_params=[];
    private static $_token;
    private static $_iswebhook = false;
    private static $_update_id = 0;
    private static $_query_id = 0;
    
    /**
     * First, we must set a token and method of the bot.
     * 
     * @param string  $token     The bot token
     * @param boolean $iswebhook Method of the bot
     * 
     * @return void
     */ 
    public static function setToken($token, $iswebhook=false)
    {
        self::$_token = $token;
        self::$_iswebhook = $iswebhook;
    }
    
    /**
     * Set Bot Paramenter.
     * 
     * @param array $params Paramenters
     * 
     * @return void
     */ 
    public static function setParam($params=[])
    {
        self::$_params = array_merge(self::$_params, $params);
    }
    
    /**
     * GetMe
     * 
     * @return array
     */ 
    public static function getMe()
    {
        return self::_botSend(['cmd' => 'getMe']);
    }
    
    /**
     * Get Webhook Information
     * 
     * @return array
     */ 
    public static function webHookInfo()
    {
        return $webHookInfo = self::_botSend(['cmd' => 'getWebhookInfo']);
    }
        
    /**
     * Run the Webhook Method
     * 
     * @param function $callback callback
     * 
     * @return array/bool if success return an array, if not then false
     */
    private static function _runWebHook($callback)
    {
        $request = ($_SERVER['REQUEST_METHOD'] == 'POST')?true:false;
        $cType = ($_SERVER['CONTENT_TYPE'] == 'application/json')?true:false;
        if ($request && $cType) {
            $ret = json_decode(file_get_contents('php://input'), true);
            
            if (is_null($ret)) {
                throw new Exception('Error invalid JSON format');
            }
            
            $chat_id = '';
            if (isset($ret['message']['chat']['id'])) {
                $chat_id = ['chat_id' => $ret['message']['chat']['id']];
            }
                
            if (isset($ret['channel_post']['chat']['id'])) {
                $chat_id = ['chat_id' => $ret['channel_post']['chat']['id']];
            }
            self::setParam($chat_id);
            return $callback($ret);
        
        } else {
            return false;
        }
    }
    
    /**
     * Run
     * 
     * @param function $callback callback
     * 
     * @return array
     */ 
    public static function run($callback)
    {
        if (self::$_iswebhook) {
            return self::_runWebHook($callback);
        } else {
            while (true) {
                sleep(1);
                $params = ['offset' => self::$_update_id,
                           'limit' => 100,
                           'timeout' => 0
                          ];
                      
                $result['error'] = true;
                $ret = self::_botSend(['cmd' => 'getUpdates', 'params' => $params]);
                $results = (isset($ret['result']))?$ret['result']:'';
    
                if (!empty($results)) {
                    foreach ($results as $key) {
                        $result['error'] = false;
                        $update_id = $key['update_id'];
                        $result = array_merge($result, $key);
                    
                        if (!isset(self::$_params['chat_id'])) {
                            $chat_id = '';
                            if (isset($result['message']['chat']['id'])) {
                                $chat_id = $result['message']['chat']['id']; 
                            }

                            if (isset($result['channel_post']['chat']['id'])) {
                             $chat_id = $result['channel_post']['chat']['id'];    
                            }

                            self::setParam(['chat_id' => $chat_id]);
                        }
                        
                        $callback($result);
                       
                    }
                    
                self::$_update_id = $update_id + 1;
             
                } else {
                    $result = array_merge($result, $ret);
                    $callback($result);
                    if (isset($ret['error_code'])) {
                        if ($ret['error_code']==404) {
                            break;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Send a Command
     *
     * @param string       $command bot command
     * @param string/array $req     (required)
     * @param array        $params  (additional command)
     *
     * @return array
     */
    public static function send($command, $req, $params=[])
    {
        $command = strtolower($command);
    
        $params = array_merge(self::$_params, $params);
              
        switch ($command) {
        case 'message':
            if (!isset($params['parse_mode'])) {
                $params['parse_mode'] = 'HTML';
            }
                
            $params['text'] = $req;
            $result = self::_botSend(['cmd'=>'sendMessage','params' => $params]);
            break;
            
        case 'forward':
            if (!is_array($req)) {
                throw new Exception('Parameter must be an array');
            }
                
            if (!isset($req['from_chat_id'])) {
                throw new Exception('Parameter of from_chat_id must be set');
            }
                  
            if (!isset($req['message_id'])) {
                throw new Exception('Parameter of message_id must be set');
            }
                
            $params['from_chat_id'] = $req['from_chat_id'];
            $params['message_id'] = $req['message_id'];
            $result = self::_botSend(['cmd'=>'forwardMessage','params'=>$params]);
            break;
            
        case 'photo':
        case 'document':
        case 'audio':
        case 'video':
        case 'voice':
        case 'sticker':
            $params[$command] = self::_curlFile($req);
            $send = ['cmd'=>'send'.ucfirst($command),
                     'sendFile' => true,
                     'params' => $params
                    ];
            $result = self::_botSend($send);
            break;
                
        case 'location':
        case 'venue':
            if (!is_array($req)) {
                throw new Exception('Parameter must be an array');
            }
                
            if (!isset($req['latitude'])) {
                throw new Exception('Required a number of Latitude');
            }
                    
            if (!isset($req['longitude'])) {
                throw new Exception('Required a number of Longitude');
            }
                
            $params['latitude'] = $req['latitude'];
            $params['longitude'] = $req['longitude'];
                
            if ($command == 'venue') {
                if (!isset($req['title'])) {
                    throw new Exception('Required a title name');
                }
                        
                if (!isset($req['address'])) {
                    throw new Exception('Required an address name');
                }
                    
                $params['title'] = $req['title'];
                $params['address'] = $req['address'];
            }
             
            $send = ['cmd' => 'send'.ucfirst($command),
                     'params' => $params
                    ];
                    
            $result = self::_botSend($send);
            break;
                
        case 'contact':
            if (!is_array($req)) {
                throw new Exception('Parameter must be an array');
            }
                
            if (!isset($req['phone_number'])) {
                throw new Exception('Required a phone number');
            }
                    
            if (!isset($req['first_name'])) {
                throw new Exception('Required a first name');
            }
    
            $params['phone_number'] = $req['phone_number'];
            $params['first_name'] = $req['first_name'];
            
            $send = ['cmd'=>'sendContact',
                     'params'=>$params
                    ];
                    
            $result = self::_botSend($send);
            break;
    
        case 'chataction':
            $params['action'] = $req;
            $result = self::_botSend(['cmd'=>'sendChatAction','params'=>$params]);
            break;
        }
        
        return $result;
    }
    /**
     * Get
     * 
     * @param string       $command command
     * @param string/array $req     Required
     * @param array        $params  Params
     * 
     * @return array
     */ 
    public static function get($command, $req, $params=[])
    {
        $command = strtolower($command);
        $params = array_merge(self::$_params, $params);
        
        switch ($command) {
        case 'userprofilephotos':
            $params['user_id'] = $req;
            break;
                
        case 'file':
            $params['file_id'] = $req;
            break;
                
        case 'chat':
        case 'chatadministrators':
        case 'chatmemberscount':
        case 'chatmember':
            if ($command == 'chatmember') {
                if (!is_array($req)) {
                    throw new Exception('Parameter must be an array');
                }
                
                $params['chat_id'] = $req['chat_id'];
                $params['user_id'] = $req['user_id'];
                
            } else {
                    $params['chat_id'] = $req;
            }
            break;
        }
        
        $ret = self::_botSend(['cmd'=>'get'.ucwords($command),'params'=>$params]);
        return $ret;
    }
    
    /**
     * Get a File from file ID
     * 
     * @param string $file_id File ID
     * 
     * @return string/boolean
     */ 
    public static function getFile($file_id)
    {
        $theURL = 'https://api.telegram.org/file/bot' . self::$_token . '/';
        $result = self::get('file', $file_id);
        if (isset($result['result']['file_path'])) {
            return $theURL . $result['result']['file_path'];
        } else {
            return false;
        }
    }
    
    /**
     * Get type of the chat message
     * 
     * @param array $message Message from the bot
     * 
     * @return string
     */ 
    public static function getChatType($message)
    {
        if (isset($message['text'])) {
            return 'text';
        }
        if (isset($message['photo'])) {
            return 'photo';
        }
        if (isset($message['sticker'])) {
            return 'sticker';
        }
        if (isset($message['video'])) {
            return 'video';
        }
        if (isset($message['voice'])) {
            return 'voice';
        }
        if (isset($message['contact'])) {
            return 'contact';
        }
        if (isset($message['location'])) {
            return 'location';
        }
        if (isset($message['venue'])) {
            return 'venue';
        }
        if (isset($message['new_chat_member'])) {
            return 'join';
        }
        if (isset($message['left_chat_member'])) {
            return 'left';
        }
        if (isset($message['new_chat_title'])) {
            return 'change_title';
        }
        if (isset($message['new_chat_photo'])) {
            return 'change_photo';
        }
        if (isset($message['delete_chat_photo'])) {
            return 'delete_photo';
        }
        if (isset($message['group_chat_created'])) {
            return 'group_created';
        }
        
        if (isset($message['supergroup_chat_created'])) {
            return 'supergroup_created';
        }
            
        if (isset($message['channel_chat_created'])) {
            return 'channel_created';
        }
            
        if (isset($message['migrate_to_chat_id'])) {
            return 'to_supergroup';
        }
        if (isset($message['migrate_from_chat_id'])) {
            return 'backto_group';
        }
        if (isset($message['pinned_message'])) {
            return 'pinned';
        }
    }
    
    /**
     * Answer Inline Query
     * 
     * @param int   $query_id Query ID
     * @param array $results  The Results
     * 
     * @return array
     */ 
    public static function answerInlineQuery($query_id, $results)
    {
        $params = self::$_params;
        $params['inline_query_id'] = $query_id;
        $params['results'] = $results;
        
        return self::_botSend(['cmd'=>'answerInlineQuery','params'=>$params]);
    }
    
    /**
     * Banned member
     * 
     * @param int $chat_id Chat ID
     * @param int $user_id User ID
     * 
     * @return array
     */ 
    public static function bannedMember($chat_id, $user_id)
    {
        $params['chat_id'] = $chat_id;
        $params['user_id'] = $user_id;
        return self::_botSend(['cmd'=>'kickChatMember','params'=>$params]);
    }
    
    /**
     * Unbanned member
     * 
     * @param int $chat_id Chat ID
     * @param int $user_id User ID
     * 
     * @return array
     */  
    public static function unbanMember($chat_id, $user_id)
    {
        $params['chat_id'] = $chat_id;
        $params['user_id'] = $user_id;
        return self::_botSend(['cmd'=>'unbanChatMember','params'=>$params]);
    }
   
    /**
     * Kick member
     * 
     * @param int $chat_id Chat ID
     * @param int $user_id User ID
     * 
     * @return array
     */  
    public static function kickMember($chat_id, $user_id)
    {
        //Check group type
        $checkGroup = Bot::get('chat', $chat_id);
        $supergroup = false;
        if ($checkGroup['result']['type'] == 'supergroup') {
            $supergroup = true;
        }
            
        if ($supergroup) {
            self::bannedMember($chat_id, $user_id);
            return self::unbanMember($chat_id, $user_id);
        } else {
            return self::bannedMember($chat_id, $user_id);
        }
    }
    
    /**
     * Create Self Certificate from Open SSL
     * 
     * @param string $name    The name of certificate
     * @param string $save    Key
     * @param array  $subject Subject
     * 
     * @return boolean
     */ 
    public static function createSelfCertificate(
        $name,
        $save = __DIR__ .'/',
        $subject = ['country'=>'ID',
                    'street'=>'Jakarta',
                    'location'=>'Jakarta',
                    'company'=>'Alisoftware',
                    'domain'=> 'example.com',
                    'days' => 365
                   ]
    ) {
        $exec = '/usr/bin/openssl req -newkey rsa:2048 -sha256 -nodes ' .
                '-keyout '. $name .'.key -x509 -days '. $subject['days'] . 
                ' -out '. $save . $name.'.pem -subj "' .
                '/C='.$subject['country'] . '/ST='.$subject['street'] .
                '/L='.$subject['location'] . '/O='.$subject['company'] .
                '/CN='. $subject['domain'].'"';
                
        $output = null;        
        exec($exec);
        
        if (file_exists($save . $name)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Set Webhook
     * 
     * @param string $url    URL
     * @param array  $params Paramenters
     * 
     * @return array
     */ 
    public static function setWebhook(
        $url, 
        $params = ['certificate' => '',
                   'autogen' => true,
                   'certificateName' => 'phptgbot',
                   'removed' => false 
                  ]
    ) {
        $_params['url'] = $url;
        
        if ($params['autogen']) {
            self::createSelfCertificate($params['certificateName']);
            $_params['certificate'] = __DIR__ .'/'.$params['certificateName'];
            $_params['certificate'] = self::_curlFile($_params['certificate']);
        }
        
        if ($params['removed']) {
            $_params['certificate'] = '';
        }

        $send = ['cmd' => 'setWebhook', 'params' => $_params];
        return self::_botSend($send);
    }
    
    /**
     * Convert to Curl File
     * 
     * @param string $filename Filename
     * 
     * @return string
     */ 
    private static function _curlFile($filename)
    {
        // set realpath
        $filename = realpath($filename);
        // check a file
        if (!is_file($filename)) {
            throw new Exception('File does not exists');
        }
        // PHP 5.5 introduced a CurlFile object that deprecates
        // the old @filename syntax
        // See: https://wiki.php.net/rfc/curl-file-upload
        if (function_exists('curl_file_create')) {
            return curl_file_create($filename);
        }
        // Use the old style if using an older version of PHP
        return "@$filename";
        
    }
    
    /**
     * Bot Send
     * 
     * @param array $args Arguments
     * 
     * @return array
     */ 
    private function _botSend($args)
    {
        $botCommand = isset($args['cmd'])?$args['cmd']:'';
        $isPost = isset($args['isPost'])?$args['isPost']:true;
        $sendFile = isset($args['sendFile'])?$args['sendFile']:false;
        $params = isset($args['params'])?$args['params']:'';
        $filename = isset($args['filename'])?$args['filename']:'';

        if (!isset(self::$token)) {
            if (defined('TOKEN')) {
                self::$token=TOKEN;
            }
        }

        $ch = curl_init();
        $config = [
            CURLOPT_URL => 'https://api.telegram.org/bot'.
                            self::$_token . '/' . $botCommand,
            CURLOPT_POST => $isPost,
            CURLOPT_RETURNTRANSFER => true
        ];
        
        if ($sendFile) {
            $config[CURLOPT_HTTPHEADER] = ['Content-Type: multipart/form-data'];
        }
        
        if (!empty($params)) {
            $config[CURLOPT_POSTFIELDS] = $params;
        }
        
        curl_setopt_array($ch, $config);
        $result = curl_exec($ch);
        curl_close($ch);
        
        // return and decode to JSON
        return !empty($result) ? json_decode($result, true) : false;
    }
}
