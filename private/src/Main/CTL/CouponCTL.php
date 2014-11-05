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
use Main\Service\CouponService;
use Main\Service\PromotionService;

/**
 * @Restful
 * @uri /coupon
 */
class CouponCTL extends BaseCTL {
    /**
     * @POST
     */
    public function addCoupon(){
        try {
            $item = CouponService::getInstance()->addCoupon($this->reqInfo->params(), $this->getCtx());
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
            $items = CouponService::getInstance()->gets($this->reqInfo->params(), $this->getCtx());
            foreach($items['data'] as $key=> $item){
                MongoHelper::standardIdEntity($item);
                $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
                $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
                if($item['type']=="coupon" || true){
                    $item['thumb'] = Image::load($item['thumb'])->toArrayResponse();
                    $user = $this->getCtx()->getUser();
                    $item['used_status'] = false;
                    foreach($item['used_users'] as $value){
                        if($value['user']['_id'] == $user['_id']){
                            $item['used_status'] = true;
                            break;
                        }
                    }
                    unset($item['used_users']);
                }
                else {
                    $item['node'] = NodeHelper::promotion($item['id']);
                    ArrayHelper::pictureToThumb($item);
                }

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
            $item = CouponService::getInstance()->get($this->reqInfo->urlParam('id'), $this->getCtx());

            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            if($item['type']=="coupon" || true){
                $item['thumb'] = Image::load($item['thumb'])->toArrayResponse();
                $user = $this->getCtx()->getUser();
                $item['used_status'] = false;
                foreach($item['used_users'] as $value){
                    if($value['user']['_id'] == $user['_id'] && MongoHelper::timeToInt($value['expire']) < time()){
                        $item['used_status'] = true;
                        break;
                    }
                }
                unset($item['used_users']);
            }
            else {
                $item['node'] = NodeHelper::promotion($item['id']);
                ArrayHelper::pictureToThumb($item);
            }

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
    public function editCoupon(){
        try {
            $item = CouponService::getInstance()->editCoupon($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
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
            CouponService::getInstance()->delete($this->reqInfo->urlParam('id'), $this->getCtx());
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
            $res = CouponService::getInstance()->requestCoupon($this->reqInfo->urlParam('id'), $this->getCtx());
            return ['success'=> $res];
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }
}