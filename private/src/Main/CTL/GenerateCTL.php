<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/22/14
 * Time: 1:03 PM
 */

namespace Main\CTL;
use Main\DB;

/**
 * @Restful
 * @uri /generate
 */
class GenerateCTL extends BaseCTL {
    /**
     * @GET
     */
    public function get(){
        $db = DB::getDB();
        $count = $db->coupon_codes->count();
        if($count >= 1000000-1){
            return ['success'=> false];
        }
        for($i=$count; $i < 1000000-$count; $i++){
            $item = [
                '_id'=> sprintf("%013s", uniqid()),
                'used'=> false
            ];
            $db->coupon_codes->insert($item);
        }
        return ['success'=> true];
    }
}