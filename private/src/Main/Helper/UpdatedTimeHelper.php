<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 3/11/2557
 * Time: 10:20 น.
 */

namespace Main\Helper;


use Main\DB;

class UpdatedTimeHelper {
    protected static function getCollection(){
        return DB::getDB()->last_updated;
    }

    public static function get($key){
        $item = self::getCollection()->findOne(['_id'=> $key]);
        if(!is_null($item)){
            return $item;
        }

        // if not found
        self::update($key, 0);
        return self::getCollection()->findOne(['_id'=> $key]);
    }

    public static function update($key, $value){
        self::getCollection()->update(['_id'=> $key],
            [
                '$set'=> ['time'=> MongoHelper::intToTime($value)],
//                '$setOnInsert'=> ['time'=> MongoHelper::intToTime($value)]
            ],
            ['upsert'=> true]);
    }
} 