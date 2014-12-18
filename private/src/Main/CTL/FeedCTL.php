<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/23/14
 * Time: 10:57 AM
 */

namespace Main\CTL;
use Main\Exception\Service\ServiceException;
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
        try {
            $items = FeedService::getInstance()->gets($this->reqInfo->params(), $this->getCtx());
            foreach ($items['data'] as $key => $item) {
                MongoHelper::standardIdEntity($item);
                $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
                $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
                $item['node'] = NodeHelper::news($item['id']);
                $items['data'][$key] = $item;
            }
            return $items;
        }
        catch (ServiceException $e){
            return $e->getResponse();
        }
    }

    /**
     * @POST
     */
    public function add(){
        try {
            $item = FeedService::getInstance()->add($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['node'] = NodeHelper::news($item['id']);
            return $item;
        }
        catch (ServiceException $e){
            return $e->getResponse();
        }
    }

    /**
     * @GET
     * @uri /[h:id]
     */
    public function get(){
        try {
            $item = FeedService::getInstance()->get($this->reqInfo->urlParam('id'), $this->getCtx());
            FeedService::getInstance()->incView($this->reqInfo->urlParam('id'), $this->getCtx());

            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['node'] = NodeHelper::news($item['id']);
            return $item;
        }
        catch (ServiceException $e){
            return $e->getResponse();
        }
    }

    /**
     * @PUT
     * @uri /[h:id]
     */
    public function edit(){
        try {
            $item = FeedService::getInstance()->edit($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['node'] = NodeHelper::news($item['id']);
            return $item;
        }
        catch (ServiceException $e){
            return $e->getResponse();
        }
    }

    /**
     * @DELETE
     * @uri /[h:id]
     */
    public function delete(){
        try {
            $response = FeedService::getInstance()->delete($this->reqInfo->urlParam('id'), $this->getCtx());
            return $response;
        }
        catch (ServiceException $e){
            return $e->getResponse();
        }
    }

    /**
     * @POST
     * @uri /sort
     */
    public function sort(){
        try {
            $res = FeedService::getInstance()->sort($this->reqInfo->params(), $this->getCtx());
            return $res;
        }
        catch (ServiceException $e){
            return $e->getResponse();
        }
    }
}