<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/23/14
 * Time: 10:57 AM
 */

namespace Main\CTL;
use Main\Helper\Validate;
use Main\Service\FeedGalleryService;
use Main\Service\FeedService;


/**
 * @Restful
 * @uri /feed
 */
class FeedCTL extends BaseCTL {
    /**
     * @GET
     */
    public function gets(){
        $items = FeedService::instance()->gets($this->reqInfo->params(), $this->getCtx());
        return $items;
    }

    /**
     * @POST
     */
    public function add(){
        $item = FeedService::instance()->add($this->reqInfo->params(), $this->getCtx());
        return $item;
    }

    /**
     * @GET
     * @uri /[h:id]
     */
    public function get(){
        $items = FeedService::instance()->get($this->reqInfo->urlParam('id'), $this->getCtx());
        return $items;
    }

    /**
     * @PUT
     * @uri /[h:id]
     */
    public function edit(){
        $item = FeedService::instance()->edit($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
        return $item;
    }

    /**
     * @DELETE
     * @uri /[h:id]
     */
    public function delete(){
        $response = FeedService::instance()->delete($this->reqInfo->urlParam('id'));
        return $response;
    }

    /**
     * @POST
     * @uri /sort
     */
    public function sort(){
        $res = FeedService::instance()->sort($this->reqInfo->params(), $this->getCtx());
        return $res;
    }


    /**
     * @GET
     * @uri /gallery
     */
    public function getPictures(){
        $items = FeedGalleryService::instance()->gets();
        return $items;
    }

    /**
     * @POST
     * @uri /gallery
     */
    public function addPictures(){
        $response = FeedGalleryService::instance()->add($this->reqInfo->params());
        return $response;
    }

    /**
     * @DELETE
     * @uri /gallery
     */
    public function deletePictures(){
        $params = $this->reqInfo->params();
        if(!is_array($params['id'])){
            return array(
                'error'=> array(
                    'message'=> "id is must be array",
                    'code'=> 400,
                    'type'=> 'InvalidParameter'
                )
            );
        }
        foreach($params['id'] as $value){
            if(!Validate::isIdFormat($value)){
                return array(
                    'error'=> array(
                        'message'=> $value." invalid id format",
                        'code'=> 400,
                        'type'=> 'InvalidParameter'
                    )
                );
            }
        }
        $response = FeedGalleryService::instance()->delete($params, $this->getCtx());
        return $response;
    }

    /**
     * @GET
     * @uri /detail
     */
    public function getDetail(){
        $items = FeedGalleryService::instance()->getDetail($this->getCtx());
        return $items;
    }

    /**
     * @PUT
     * @uri /detail
     */
    public function editDetail(){
        $items = FeedGalleryService::instance()->editDetail($this->reqInfo->params(), $this->getCtx());
        return $items;
    }
}