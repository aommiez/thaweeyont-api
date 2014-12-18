<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/23/14
 * Time: 3:42 PM
 */

namespace Main\CTL;
use Main\DataModel\Image;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Helper\NodeHelper;
use Main\Service\PromotionService;

/**
 * @Restful
 * @uri /promotion
 */
class PromotionCTL extends BaseCTL {
    /**
     * @POST
     */
    public function addPromotion(){
        try {
            $item = PromotionService::getInstance()->addPromotion($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['thumb'] = Image::load($item['thumb'])->toArrayResponse();
//            ArrayHelper::pictureToThumb($item);
            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @GET
     */
    public function gets(){
        try {
            $items = PromotionService::getInstance()->gets($this->reqInfo->params(), $this->getCtx());
            foreach($items['data'] as $key=> $item){
                MongoHelper::standardIdEntity($item);
                $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
                $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
                $item['thumb'] = Image::load($item['thumb'])->toArrayResponse();

                $item['node'] = NodeHelper::promotion($item['id']);
//                ArrayHelper::pictureToThumb($item);

                // translate
//                if($this->getCtx()->getTranslate()){
//                    ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
//                }

                // make node
//                $item['node'] = NodeHelper::place($item['id']);

                $items['data'][$key] = $item;
            }
            return $items;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @GET
     * @uri /[h:id]
     */
    public function get(){
        try {
            $item = PromotionService::getInstance()->get($this->reqInfo->urlParam('id'), $this->getCtx());
            PromotionService::getInstance()->incView($this->reqInfo->urlParam('id'), $this->getCtx());

            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['thumb'] = Image::load($item['thumb'])->toArrayResponse();

            $item['node'] = NodeHelper::promotion($item['id']);
//            ArrayHelper::pictureToThumb($item);

            // translate
//            if($this->getCtx()->getTranslate()){
//                ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
//            }

            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @PUT
     * @uri /[h:id]
     */
    public function editPromotion(){
        try {
            $item = PromotionService::getInstance()->editPromotion($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['thumb'] = Image::load($item['thumb'])->toArrayResponse();
//            ArrayHelper::pictureToThumb($item);

            // translate
//            if($this->getCtx()->getTranslate()){
//                ArrayHelper::translateEntity($item, $this->getCtx()->getLang());
//            }

//            $item['node'] = NodeHelper::roomtype($item['id']);

            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @DELETE
     * @uri /[h:id]
     */
    public function delete(){
        try {
            PromotionService::getInstance()->delete($this->reqInfo->urlParam('id'), $this->getCtx());
            return ['success'=> true];
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @POST
     * @uri /request/[h:id]
     */
    public function couponRequest(){
        try {
            $res = PromotionService::getInstance()->requestCoupon($this->reqInfo->urlParam('id'), $this->getCtx());
            return ['success'=> $res];
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @POST
     * @uri /sort
     */
    public function sort(){
        try {
            $res = PromotionService::getInstance()->sort($this->reqInfo->params(), $this->getCtx());
            return $res;
        }
        catch (ServiceException $e){
            return $e->getResponse();
        }
    }
}