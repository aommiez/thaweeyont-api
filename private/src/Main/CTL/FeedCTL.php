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
        $items = FeedService::getInstance()->gets($this->reqInfo->params(), $this->getCtx());
        return $items;
    }

    /**
     * @POST
     */
    public function add(){
        $item = FeedService::getInstance()->add($this->reqInfo->params(), $this->getCtx());
        return $item;
    }

    /**
     * @GET
     * @uri /[h:id]
     */
    public function get(){
        $items = FeedService::getInstance()->get($this->reqInfo->urlParam('id'), $this->getCtx());
        return $items;
    }

    /**
     * @PUT
     * @uri /[h:id]
     */
    public function edit(){
        $item = FeedService::getInstance()->edit($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
        return $item;
    }

    /**
     * @DELETE
     * @uri /[h:id]
     */
    public function delete(){
        $response = FeedService::getInstance()->delete($this->reqInfo->urlParam('id'));
        return $response;
    }

    /**
     * @POST
     * @uri /sort
     */
    public function sort(){
        $res = FeedService::getInstance()->sort($this->reqInfo->params(), $this->getCtx());
        return $res;
    }


}