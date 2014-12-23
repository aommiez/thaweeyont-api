<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 23/12/2557
 * Time: 13:38 น.
 */

namespace Main\CTL;

/**
 * @Restful
 * @uri /example
 */
class ExampleCTL extends BaseCTL {
    /**
     * @GET
     */
    public function get(){
        $item = [
            'name'=> 'test item',
            'detail'=> 'test detail item'
        ];

        return $item;
    }

    /**
     * @POST
     */
    public function post(){
        return $this->reqInfo->params();
    }
}