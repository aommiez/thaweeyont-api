<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 8/22/14
 * Time: 3:09 PM
 */

namespace Main\CTL;
use Main\Exception\Service\ServiceException;
use Main\Helper\MongoHelper;
use Main\Service\UserService;


/**
 * @Restful
 * @uri /user
 */
class UserCTL extends BaseCTL {
    /**
     * @GET
     * @uri /[h:id]
     */
    public function get(){
        try{
            $item = UserService::getInstance()->get($this->reqInfo->urlParam('id'), $this->getCtx());
            MongoHelper::standardId($item);
            if(isset($item['birth_date'])){
                $item['birth_date'] = MongoHelper::timeToInt($item['birth_date']);
            }
            if(isset($item['setting'])){
                unset($item['setting']);
            }
            if(isset($item['password'])){
                unset($item['password']);
            }
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
    public function edit(){
        try{
            $item = UserService::getInstance()->edit($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardId($item);
            if(isset($item['birth_date'])){
                $item['birth_date'] = MongoHelper::timeToInt($item['birth_date']);
            }
            if(isset($item['setting'])){
                unset($item['setting']);
            }
            if(isset($item['password'])){
                unset($item['password']);
            }
            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @PUT
     * @uri /change_password/[h:id]
     */
    public function changePassword(){
        try {
            return UserService::getInstance()->changePassword($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @POST
     * @uri /request_reset_code
     */
    public function requestResetCode(){
        try {
            return UserService::getInstance()->requestResetCode($this->reqInfo->params(), $this->getCtx());
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @GET
     * @uri /get_user_by_code
     */
    public function getUserByCode(){
        try {
            $item = UserService::getInstance()->getUserByCode($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);

            return $item;
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @POST
     * @uri /set_password_by_code
     */
    public function setPasswordByCode(){
        try {
            $item = UserService::getInstance()->setPasswordByCode($this->reqInfo->params(), $this->getCtx());

            return ['success'=> $item];
        }
        catch(ServiceException $ex){
            return $ex->getResponse();
        }
    }
}