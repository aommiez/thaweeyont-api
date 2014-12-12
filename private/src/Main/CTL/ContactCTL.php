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
use Main\Helper\NodeHelper;
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
        try {
            $item = ContactService::getInstance()->get($this->getCtx());
            MongoHelper::removeId($item);
            return $item;
        }
        catch (ServiceException $e) {
            return $e->getResponse();
        }
    }

    /**
     * @PUT
     */
    public function edit(){
        try {
            $item = ContactService::getInstance()->edit($this->reqInfo->inputs(), $this->getCtx());
            MongoHelper::removeId($item);
            return $item;
        }
        catch (ServiceException $e) {
            return $e->getResponse();
        }
    }

    /**
     * @GET
     * @uri /branches
     */
    public function getBranches () {
        try {
            $items = ContactService::getInstance()->getBranches($this->reqInfo->params(), $this->getCtx());
            foreach($items['data'] as $key => $item){
                MongoHelper::standardIdEntity($item);
                $item['pictures'] = Image::loads($item['pictures'])->toArrayResponse();
                $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
                $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
                $item['node'] = NodeHelper::branch($item['id']);
                $items['data'][$key] = $item;
            }
            return $items;
        }
        catch (ServiceException $e) {
            return $e->getResponse();
        }
    }

    /**
     * @GET
     * @uri /branches/[h:id]
     */
    public function getBranch () {
        try {
            $item = ContactService::getInstance()->getBranch($this->reqInfo->urlParam('id'), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['pictures'] = Image::loads($item['pictures'])->toArrayResponse();
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['node'] = NodeHelper::branch($item['id']);
            return $item;
        }
        catch (ServiceException $e) {
            return $e->getResponse();
        }
    }

    /**
     * @GET
     * @uri /branch/get_by_coordinate
     */
    public function getBranchByCoordinate () {
        try {
            $item = ContactService::getInstance()->getBranchByLocation($this->reqInfo->param('lat'), $this->reqInfo->param('lng'), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['pictures'] = Image::loads($item['pictures'])->toArrayResponse();
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['node'] = NodeHelper::branch($item['id']);
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
        try {
            $item = ContactService::getInstance()->addBranches($this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['pictures'] = Image::loads($item['pictures'])->toArrayResponse();
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            $item['node'] = NodeHelper::branch($item['id']);
            return $item;
        }
        catch (ServiceException $e) {
            return $e->getResponse();
        }
    }

    /**
     * @PUT
     * @uri /branches/[h:id]
     */
    public function editBranches () {
        try {
            $item = ContactService::getInstance()->editBranches($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
            MongoHelper::standardIdEntity($item);
            $item['pictures'] = Image::loads($item['pictures'])->toArrayResponse();
            $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
            $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
            return $item;
        }
        catch (ServiceException $e) {
            return $e->getResponse();
        }
    }

    /**
     * @DELETE
     * @uri /branches/[h:id]
     */
    public function deleteBranche(){
        try {
            return ContactService::getInstance()->deleteBranche($this->reqInfo->urlParam("id"), $this->getCtx());
        }
        catch (ServiceException $e) {
            return $e->getResponse();
        }
    }

    /**
     * @GET
     * @uri /branches/[h:id]/picture
     */
    public function getBranchPicture(){
        return ContactService::getInstance()->getBranchPictures($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
    }

    /**
     * @POST
     * @uri /branches/[h:id]/picture
     */
    public function postBranchPicture(){
        return ContactService::getInstance()->addBranchPictures($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
    }

    /**
     * @DELETE
     * @uri /branches/[h:id]/picture
     */
    public function deleteBranchPicture(){
        return ContactService::getInstance()->deleteBranchPictures($this->reqInfo->urlParam('id'), $this->reqInfo->params(), $this->getCtx());
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
        try {
            $item = ContactCommentService::getInstance()->gets($this->reqInfo->params(), $this->getCtx());
            MongoHelper::removeId($item);
            return $item;
        }
        catch (ServiceException $e) {
            return $e->getResponse();
        }
    }


    /**
     * @GET
     * @uri /comment/[h:id]
     */
    public function getCommentById(){
        try {
            $item = ContactCommentService::getInstance()->getCommentById($this->reqInfo->urlParam("id"), $this->getCtx());
            MongoHelper::removeId($item);
            return $item;
        }
        catch (ServiceException $e) {
            return $e->getResponse();
        }
    }


    /**
     * @DELETE
     * @uri /comment/[h:id]
     */
    public function deleteCommentById(){
        try {
            $item = ContactService::getInstance()->deleteCommentById($this->reqInfo->urlParam("id"), $this->getCtx());
            MongoHelper::removeId($item);
            return $item;
        }
        catch (ServiceException $e) {
            return $e->getResponse();
        }
    }

    /**
     * @GET
     * @uri /branches/[h:id]/tel
     */
    public function getTels(){
        try {
            $res = ContactService::getInstance()->getTels($this->reqInfo->urlParam("id"), $this->reqInfo->params(), $this->getCtx());
            foreach ($res['data'] as $key => $item) {
                MongoHelper::standardIdEntity($item);
                $item['created_at'] = MongoHelper::timeToInt($item['created_at']);
                $item['updated_at'] = MongoHelper::timeToInt($item['updated_at']);
                $item['branch_id'] = MongoHelper::standardId($item['branch_id']);

                $res['data'][$key] = $item;
            }
            return $res;
        }
        catch (ServiceException $e) {
            return $e->getResponse();
        }
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
     * @PUT
     * @uri /branches/tel/[h:id]
     */
    public function editTel(){
        try {
            $item = ContactService::getInstance()->editTel($this->reqInfo->urlParam("id"), $this->reqInfo->params(), $this->getCtx());
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

    /**
     * @POST
     * @uri /branches/sort
     */
    public function branchesSort(){
        $res = ContactService::getInstance()->branchesSort($this->reqInfo->params(), $this->getCtx());
        return $res;
    }

    /**
     * @POST
     * @uri /branches/tel/sort
     */
    public function telBranchesSort(){
        $res = ContactService::getInstance()->telBranchesSort($this->reqInfo->params(), $this->getCtx());
        return $res;
    }
}