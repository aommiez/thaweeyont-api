<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/2/14
 * Time: 1:07 PM
 */

namespace Main\CTL;
use Main\Service\UserNotifyService;


/**
 * @Restful
 * @uri /user/notify
 */
class UserNotifyCTL extends BaseCTL {
    /**
     * @GET
     */
    public function gets(){
        return UserNotifyService::getInstance()->gets($this->reqInfo->params(), $this->getCtx());
    }

    /**
     * @GET
     * @POST
     * @uri /read/[h:id]
     */
    public function read(){
        return UserNotifyService::getInstance()->read($this->reqInfo->urlParam('id'), $this->getCtx());
    }

    /**
     * @GET
     * @uri /unopened
     */
    public function unopened(){
        return UserNotifyService::getInstance()->unopened($this->getCtx());
    }
}