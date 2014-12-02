<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 26/11/2557
 * Time: 15:22 น.
 */

namespace Main\CTL;
use Main\Exception\Service\ServiceException;
use Main\Helper\MongoHelper;
use Main\Service\MessageService;

/**
 * @Restful
 * @uri /message
 */
class MessageCTL extends BaseCTL {
    /**
     * @POST
     */
    public function add(){
        try {
            $item = MessageService::getInstance()->add($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);

            return $item;
        } catch (ServiceException $e) {
            return $e->getResponse();
        }
    }

    /**
     * @GET
     * @uri /[h:id]
     */
    public function get(){
        try {
            $item = MessageService::getInstance()->get($this->reqInfo->urlParam('id'), $this->getCtx());

            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);

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
            $res = MessageService::getInstance()->gets($this->reqInfo->urlParam('id'), $this->getCtx());

            foreach($res['data'] as $key=> $item){
                MongoHelper::standardIdEntity($item);
                $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
                $res['data'][$key] = $item;
            }

            return $res;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }
}