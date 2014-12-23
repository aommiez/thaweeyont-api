<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 26/11/2557
 * Time: 15:22 น.
 */

namespace Main\CTL;
use Main\DB;
use Main\Helper\APNHelper;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;

/**
 * @Restful
 * @uri /test
 */
class TestCTL extends BaseCTL {
    /**
     * @GET
     * @uri /a
     */

    public function a(){
        $coll = DB::getDB()->branches;
        $items = $coll->find();
        $res = [];
        foreach($items as $key=> $item){
            $set = [
                'location'=> [
                    'lat'=> sprintf("%.6f", (float)$item['location']['lat']),
                    'lng'=> sprintf("%.6f", (float)$item['location']['lng'])
                ]
            ];
            $coll->update(['_id'=> $item['_id']], ['$set'=> ArrayHelper::ArrayGetPath($set)]);
            $res[] = ArrayHelper::ArrayGetPath($set);
        }
        return $res;
    }
}