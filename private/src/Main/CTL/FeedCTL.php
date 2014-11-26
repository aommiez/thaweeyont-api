<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/23/14
 * Time: 10:57 AM
 */

namespace Main\CTL;
use Main\Helper\MongoHelper;
use Main\Helper\NodeHelper;
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
        foreach($items['data'] as $key=> $item){
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['node'] = NodeHelper::news($item['id']);
            $items['data'][$key] = $item;
        }
        return $items;
    }

    /**
     * @POST
     */
    public function add(){
        $item = FeedService::getInstance()->add($this->reqInfo->params(), $this->getCtx());
        $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
        $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
        $item['node'] = NodeHelper::news($item['id']);
        return $item;
    }

    /**
     * @GET
     * @uri /[h:id]
     */
    public function get(){
        $item = FeedService::getInstance()->get($this->reqInfo->urlParam('id'), $this->getCtx());
        $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
        $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
        $item['node'] = NodeHelper::news($item['id']);
        return $item;
    }

    /**
     * @PUT
     * @uri /[h:id]
     */
    public function edit(){
        $item = FeedService::getInstance()->edit($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
        $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
        $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
        $item['node'] = NodeHelper::news($item['id']);
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