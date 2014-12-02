<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 8/14/14
 * Time: 5:05 PM
 */

namespace Main\Helper;


use Main\DB;

class NotifyHelper {
    protected static $apnHelperDev = null, $apnHelperProduct = null;

    protected static function getApnHelperDev(){
        if(is_null(self::$apnHelperDev)){
            self::$apnHelperDev = new APNHelper(file_get_contents('private/apple/dev.pem'), 'gateway.sandbox.push.apple.com', 2195);
        }
        return self::$apnHelperDev;
    }

    protected static function getApnHelperProduct(){
        if(is_null(self::$apnHelperProduct)){
            self::$apnHelperProduct = new APNHelper(file_get_contents('private/apple/product.pem'), 'gateway.push.apple.com', 2195);
        }
        return self::$apnHelperProduct;
    }

    public static function cutMessage($message){
        $pushMessage = $message;
        if(is_array($pushMessage)){
            $pushMessage = $message;
        }

        if(strlen(utf8_encode($pushMessage)) > 84){
            $pushMessage = mb_substr($pushMessage, 0, 14, 'utf-8').'...';
        }

        return $pushMessage;
    }

    public static function sendAll($objectId, $type, $header, $message){
        $db = DB::getDB();
        $users = $db->users->find([], ['setting', 'ios_device_token', 'android_token', 'display_notification_number']);
        foreach($users as $item){
            $userId = MongoHelper::mongoId($item['_id']);
            $entity = self::create($objectId, $type, $header, $message, $userId);

            // inc
            self::incBadge($item['_id']);
            $item['display_notification_number']++;

            $entity['object']['id'] = MongoHelper::standardId($objectId);
            $entity['id'] = MongoHelper::standardId($entity['_id']);

            $args = [
                'id'=> $entity['id'],
                'object_id'=> $entity['object']['id'],
                'type'=> $type
            ];

            if(!isset($item['setting']))
                continue;

            if(!$item['setting']['notify_update'] && $type != "message")
                continue;

            if(!$item['setting']['notify_message'] && $type == "message")
                continue;

            self::send($item, $message, $args);
        }
    }

    public static function send($user, $message, $args){
        $pushMessage = self::cutMessage($message);
        if(isset($user['ios_device_token'])){
            foreach($user['ios_device_token'] as $token){
                try {
                    if($token['type'] == "dev"){
                        self::getApnHelperDev()->send($token['key'], $pushMessage, $args, $user['display_notification_number']);
                    }
                    else if($token['type'] == "product"){
                        self::getApnHelperProduct()->send($token['key'], $pushMessage, $args, $user['display_notification_number']);
                    }
                }
                catch (\Exception $e){
                    error_log($e->getCode()." ".$e->getMessage()." *FILE:".$e->getFile()." ".$e->getLine());
                }
            }
        }
        if(isset($user['android_token'])){
            if(count($user['android_token']) > 0){
                $tokens = [];
                foreach($user['android_token'] as $token){
                    $tokens[] = $token;
                }

                try {
                    GCMHerlper::send($tokens, [
                        'message'=> $pushMessage,
                        'object'=> $args
                    ]);
                }
                catch(\Exception $e){
                    error_log($e->getMessage());
                }
            }
        }
    }

    public static function create($objectId, $type, $header, $message, $userId){
        $db = DB::getDB();
        $objectId = MongoHelper::mongoId($objectId);
        $userId = MongoHelper::mongoId($userId);

        $now = new \MongoTimestamp();
        $entity = array(
            'preview_header'=> $header,
            'preview_content'=> $message,
            'object'=> array(
                'type'=> $type,
                'id'=> $objectId
            ),
            'user_id'=> $userId,
            'opened'=> false,
            'created_at'=> $now
        );

        $db->notify->insert($entity);
        return $entity;
    }

    public static function clearBadge($user){
        try {
            if(isset($user['ios_device_token'])){
                foreach($user['ios_device_token'] as $token){
                    try {
                        if($token['type'] == "dev"){
                            self::getApnHelperDev()->sendClearBadge($token['key']);
                        }
                        else if($token['type'] == "product"){
                            self::getApnHelperProduct()->sendClearBadge($token['key']);
                        }
                    }
                    catch (\Exception $e){
                        error_log($e->getCode()." ".$e->getMessage()." *FILE:".$e->getFile()." ".$e->getLine());
                    }
                }
            }
        }
        catch (\Exception $e){
            error_log($e->getMessage());
        }
    }

    public static function sendBadge($user, $badge){
        try {
            if(isset($user['ios_device_token'])){
                foreach($user['ios_device_token'] as $token){
                    try {
                        if($token['type'] == "dev"){
                            self::getApnHelperDev()->sendBadge($token['key'], $badge);
                        }
                        else if($token['type'] == "product"){
                            self::getApnHelperProduct()->sendBadge($token['key'], $badge);
                        }
                    }
                    catch (\Exception $e){
                        error_log($e->getCode()." ".$e->getMessage()." *FILE:".$e->getFile()." ".$e->getLine());
                    }
                }
            }
        }
        catch (\Exception $e){
            error_log($e->getMessage());
        }
    }

    public static function incBadge($userId){
        DB::getDB()->users->update(['_id'=> MongoHelper::mongoId($userId)], ['$inc'=> ['display_notification_number'=> 1]]);
    }
}