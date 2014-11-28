<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/2/14
 * Time: 12:20 PM
 */

namespace Main\Helper;

class NodeHelper {

    public static function news($id){
        $id = MongoHelper::standardId($id);
        return [
            'share'=> URL::share('/news.php?id='.$id)
        ];
    }

    public static function promotion($id){
        $id = MongoHelper::standardId($id);
        return [
            'share'=> URL::share('/promotion.php?id='.$id)
        ];
    }

    public static function place($id){
        $id = MongoHelper::standardId($id);
        return [
            'picture'=> URL::absolute('/place/'.$id.'/picture')
        ];
    }

    public static function overviewPromotion($id){
        $id = MongoHelper::standardId($id);
        return [
            'picture'=> URL::absolute('/overview/promotion/'.$id.'/picture')
        ];
    }

    public static function serviceItem($id){
        $id = MongoHelper::standardId($id);
        return [
            'picture'=> URL::absolute('/service/'.$id.'/picture'),
            'share'=> URL::share('/service.php?id='.$id)
        ];
    }

    public static function serviceFolder($id){
        $id = MongoHelper::standardId($id);
        return [
            'children'=> URL::absolute('/service/'.$id.'/children')
        ];
    }

    public static function branch($id){
        $id = MongoHelper::standardId($id);
        return [
            'picture'=> URL::absolute('/contact/branches/'.$id.'/picture'),
        ];
    }
}