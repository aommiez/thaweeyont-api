<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/2/14
 * Time: 1:07 PM
 */

namespace Main\CTL;
use Main\Exception\Service\ServiceException;
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
        try {
            return UserNotifyService::getInstance()->gets($this->reqInfo->params(), $this->getCtx());
        } catch (ServiceException $e) {
            return $e->getResponse();
        }
    }

    /**
     * @GET
     * @POST
     * @uri /read/[h:id]
     */
    public function read(){
        try {
            return UserNotifyService::getInstance()->read($this->reqInfo->urlParam('id'), $this->getCtx());
        } catch (ServiceException $e) {
            return $e->getResponse();
        }
    }

    /**
     * @GET
     * @uri /unopened
     */
    public function unopened(){
        try {
            return UserNotifyService::getInstance()->unopened($this->getCtx());
        } catch (ServiceException $e) {
            return $e->getResponse();
        }
    }

    /**
     * @GET
     * @uri /clear_badge
     */
    public function clearBadge(){
        try {
            return UserNotifyService::getInstance()->clearDisplayNotificationNumber($this->getCtx());
        } catch (ServiceException $e) {
            return $e->getResponse();
        }
    }
}