<?php
/**
 * Created by PhpStorm.
 * User: MRG
 * Date: 10/21/14 AD
 * Time: 10:19 AM
 */

namespace Main\CTL;
use Main\DataModel\Image;
use Main\Exception\Service\ServiceException;
use Main\Helper\MongoHelper;
use Main\Service\ContactCommentService;
use Main\Service\ContactService;

/**
 * @Restful
 * @uri /contact
 */
class ContactCTL extends BaseCTL {

    /**
     * @GET
     */
    public function get(){
        $item = ContactService::getInstance()->get($this->getCtx());
        MongoHelper::removeId($item);
        return $item;
    }

    /**
     * @PUT
     */
    public function edit(){
        $item = ContactService::getInstance()->edit($this->reqInfo->inputs(), $this->getCtx());
        MongoHelper::removeId($item);
        return $item;
    }

    /**
     * @GET
     * @uri /branches
     */
    public function getBranches () {
        $item = ContactService::getInstance()->getBranches($this->getCtx());
        MongoHelper::removeId($item);
        return $item;
    }

    /**
     * @GET
     * @uri /branch/get_by_coordinate
     */
    public function getBranchByCoordinate () {
        try {
            $item = ContactService::getInstance()->getBranchByLocation($this->reqInfo->param('lat'), $this->reqInfo->param('lng'));
            MongoHelper::standardIdEntity($item);
        }
        catch (ServiceException $ex) {
            return $ex->getResponse();
        }
        return $item;
    }

    /**
     * @POST
     * @uri /branches
     */

    public function addBranches () {
        $item = ContactService::getInstance()->addBranches($this->reqInfo->params(), $this->getCtx());
        MongoHelper::removeId($item);
        return $item;
    }

    /**
     * @POST
     * @uri /branches
     */
    public function editBranches () {
        $item = ContactService::getInstance()->editBranches($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
        MongoHelper::removeId($item);
        return $item;
    }

    /**
     * @DELETE
     * @uri /branches/[h:id]
     */
    public function deleteBranche(){
        $item = ContactService::getInstance()->deleteBranche($this->reqInfo->urlParam("id"),$this->getCtx());
        MongoHelper::removeId($item);
        return $item;
    }

    /**
     * @POST
     * @uri /comment
     */
    public function addComment(){
            try {
                $comment = ContactCommentService::getInstance()->add($this->reqInfo->params(), $this->getCtx());
                MongoHelper::standardIdEntity($comment);
                $comment['created_at'] = MongoHelper::timeToInt($comment['created_at']);
                MongoHelper::standardIdEntity($comment['user']);
                return $comment;
            }
            catch (ServiceException $ex) {
                return $ex->getResponse();
            }
    }


    /**
     * @GET
     * @uri /comment
     */
    public function getComment(){
        $item = ContactCommentService::getInstance()->gets($this->reqInfo->params(),$this->getCtx());
        MongoHelper::removeId($item);
        return $item;
    }


    /**
     * @GET
     * @uri /comment/[h:id]
     */
    public function getCommentById(){
        $item = ContactCommentService::getInstance()->getCommentById($this->reqInfo->urlParam("id"),$this->getCtx());
        MongoHelper::removeId($item);
        return $item;
    }


    /**
     * @DELETE
     * @uri /comment/[h:id]
     */
    public function deleteCommentById(){
        $item = ContactService::getInstance()->deleteCommentById($this->reqInfo->urlParam("id"),$this->getCtx());
        MongoHelper::removeId($item);
        return $item;
    }

    /**
     * @GET
     * @uri /branches/[h:id]/tel
     */
    public function getTels(){
        $res = ContactService::getInstance()->getTels($this->reqInfo->urlParam("id"), $this->reqInfo->params(), $this->getCtx());
        foreach($res['data'] as $key=> $item){
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['branch_id'] = MongoHelper::standardId($item['branch_id']);

            $res['data'][$key] = $item;
        }
        return $res;
    }

    /**
     * @POST
     * @uri /branches/[h:id]/tel
     */
    public function addTel(){
        try {
            $item = ContactService::getInstance()->addTel($this->reqInfo->urlParam("id"), $this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['branch_id'] = MongoHelper::standardId($item['branch_id']);
            return $item;
        }
        catch (ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @GET
     * @uri /branches/tel/[h:id]
     */
    public function getTel(){
        try {
            $item = ContactService::getInstance()->getTel($this->reqInfo->urlParam('id'), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['branch_id'] = MongoHelper::standardId($item['branch_id']);
            return $item;
        }
        catch (ServiceException $ex){
            return $ex->getResponse();
        }
    }

    /**
     * @DELETE
     * @uri /branches/tel/[h:id]
     */
    public function removeTel(){
        try {
            return [
                'success'=> ContactService::getInstance()->removeTel($this->reqInfo->urlParam('id'), $this->getCtx())
            ];
        }
        catch (ServiceException $ex){
            return $ex->getResponse();
        }
    }
}