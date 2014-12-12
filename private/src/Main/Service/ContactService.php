<?php
/**
 * Created by PhpStorm.
 * User: MRG
 * Date: 10/21/14 AD
 * Time: 10:20 AM
 */
namespace Main\Service;


use Main\Context\Context;
use Main\DataModel\Image;
use Main\DB;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Helper\ResponseHelper;
use Main\Helper\UpdatedTimeHelper;
use Main\Helper\URL;
use Valitron\Validator;

class ContactService extends BaseService {

    public function getCollection(){
        $db = DB::getDB();
        return $db->contacts;
    }

    public function getBranchesCollection(){
        $db = DB::getDB();
        return $db->branches;
    }

    public function getTelBranchesCollection(){
        $db = DB::getDB();
        return $db->branches_telephones;
    }

    public function get(Context $ctx){
        $contact = $this->getCollection()->findOne([], ["facebook", "website", "email"]);
        if(is_null($contact)){
            throw new ServiceException(ResponseHelper::notFound());
        }
        return $contact;
    }

    public function getBranches($options, Context $ctx){
        $default = array(
            "page"=> 1,
            "limit"=> 15
        );
        $options = array_merge($default, $options);

        $skip = ($options['page']-1)*$options['limit'];
        //$select = array("name", "detail", "feature", "price", "pictures");
        $condition = array();

        $cursor = $this->getBranchesCollection()
            ->find($condition)
            ->limit($options['limit'])
            ->skip($skip)
            ->sort(array('seq'=> -1));

        $total = $this->getBranchesCollection()->count($condition);
        $length = $cursor->count(true);

        $data = array();
        foreach($cursor as $item){
            $data[] = $item;
        }

        $res = [
            'length'=> $length,
            'total'=> $total,
            'data'=> $data,
            'paging'=> [
                'page'=> (int)$options['page'],
                'limit'=> (int)$options['limit']
            ]
        ];

        $pagingLength = $total/(int)$options['limit'];
        $pagingLength = floor($pagingLength)==$pagingLength? floor($pagingLength): floor($pagingLength) + 1;
        $res['paging']['length'] = $pagingLength;
        $res['paging']['current'] = (int)$options['page'];
        if(((int)$options['page'] * (int)$options['limit']) < $total){
            $nextQueryString = http_build_query(['page'=> (int)$options['page']+1, 'limit'=> (int)$options['limit']]);
            $res['paging']['next'] = URL::absolute('/feed'.'?'.$nextQueryString);
        }

        $lastUpdate = UpdatedTimeHelper::get('feed');
        $res['last_updated'] = MongoHelper::timeToInt($lastUpdate['time']);
        return $res;
    }

    public function getBranch($id, Context $ctx){
        $id = MongoHelper::mongoId($id);

        $item = $this->getBranchesCollection()->findOne(array("_id"=> $id));
        if(is_null($item)){
            return ResponseHelper::notFound("Branch not found");
        }

        return $item;
    }

    public function edit ($params, Context $ctx) {
        $allowed = ["facebook", "website", "email"];
        $set = ArrayHelper::filterKey($allowed, $params);
        $entity = $this->get($ctx);
        if(count($set)==0){
            return $entity;
        }
        $set = ArrayHelper::ArrayGetPath($set);
        $this->getCollection()->update(['_id'=> $entity['_id']], ['$set'=> $set]);

        return $this->get($ctx);
    }

    public function addBranches ($params, Context $ctx) {
        $arrPic = array();
        $v = new Validator($params);
        $v->rule('required', ['branchName', 'branchTel', 'branchEmail', 'branchFax', 'branchAddress','location','pictures' ]);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }
        $arr = ArrayHelper::filterKey(['branchName', 'branchTel', 'branchEmail', 'branchFax', 'branchAddress','location','pictures'], $params);

        foreach ($arr['pictures'] as $pic) {
            $arrPic[] = Image::upload($pic)->toArray();
        }

        $arr['pictures'] = $arrPic;

        $agg = $this->getBranchesCollection()->aggregate([
            ['$group'=> ['_id'=> null, 'max'=> ['$max'=> '$seq']]]
        ]);
        $insert['seq'] = (int)@$agg['result'][0]['max'] + 1;

        MongoHelper::setCreatedAt($arr);
        MongoHelper::setUpdatedAt($arr);

        $this->getBranchesCollection()->insert($arr);
        return $arr;
    }

    public function editBranches ($id, $params, Context $ctx) {
        $allowed = ['branchName', 'branchTel', 'branchEmail', 'branchFax', 'branchAddress','location' ];
        $set = ArrayHelper::filterKey($allowed, $params);
        if(isset($set['location'])){
            $set['location'] = ArrayHelper::filterKey(['lat', 'lng'], $set['location']);;
        }
        $entity = $this->getBranch($id, $ctx);
        if(count($set)==0){
            return $entity;
        }
        MongoHelper::setUpdatedAt($set);
        $this->getBranchesCollection()->update(['_id'=> $entity['_id']], ['$set'=> ArrayHelper::ArrayGetPath($set)]);
        return $this->getBranch($id, $ctx);
    }

    public function deleteBranche ($id, Context $ctx) {
        $this->getBranchesCollection()->remove(['_id'=> MongoHelper::mongoId($id)]);
        return ['success'=> true];
    }


    // telephone

    public function addTel($branchId, $params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['name', 'tel']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $insert = ArrayHelper::filterKey(['name', 'tel'], $params);
        $insert['branch_id'] = MongoHelper::mongoId($branchId);

        $agg = $this->getTelBranchesCollection()->aggregate([
            ['$group'=> ['_id'=> null, 'max'=> ['$max'=> '$seq']]]
        ]);
        $insert['seq'] = (int)@$agg['result'][0]['max'] + 1;

        // set field created_at, updated_at
        MongoHelper::setCreatedAt($insert);
        MongoHelper::setUpdatedAt($insert);

        $this->getTelBranchesCollection()->insert($insert);

        // feed update timestamp (last_update)
        UpdatedTimeHelper::update('/contact/branch/'.$branchId.'/tel', time());

        return $insert;
    }

    public function getTels($branchId, $params, Context $ctx){
        $default = array(
            "page"=> 1,
            "limit"=> 15,
        );
        $options = array_merge($default, $params);
        $skip = ($options['page']-1)*$options['limit'];

        $condition = ['branch_id'=> MongoHelper::mongoId($branchId)];
        $cursor = $this->getTelBranchesCollection()
            ->find($condition)
            ->limit((int)$options['limit'])
            ->skip((int)$skip)
            ->sort(['seq'=> -1]);

        $data = [];

        foreach($cursor as $item){
            $data[] = $item;
        }

        $total = $this->getTelBranchesCollection()->count($condition);
        $length = $cursor->count(true);

        $res = [
            'length'=> $length,
            'total'=> $total,
            'data'=> $data,
            'paging'=> [
                'page'=> (int)$options['page'],
                'limit'=> (int)$options['limit']
            ]
        ];

        $pagingLength = $total/(int)$options['limit'];
        $pagingLength = floor($pagingLength)==$pagingLength? floor($pagingLength): floor($pagingLength) + 1;
        $res['paging']['length'] = $pagingLength;
        $res['paging']['current'] = (int)$options['page'];
        if(((int)$options['page'] * (int)$options['limit']) < $total){
            $nextQueryString = http_build_query(['page'=> (int)$options['page']+1, 'limit'=> (int)$options['limit']]);
            $res['paging']['next'] = URL::absolute('/contact/branch/'.$branchId.'/tel?'.$nextQueryString);
        }

        // add last_update to response
        $lastUpdate = UpdatedTimeHelper::get('/contact/branch/'.$branchId.'/tel');
        $res['last_updated'] = MongoHelper::timeToInt($lastUpdate['time']);

        return $res;
    }

    public function getTel($id, Context $ctx){
        $item = $this->getTelBranchesCollection()->findOne(['_id'=> MongoHelper::mongoId($id)]);
        if(is_null($item)){
            throw new ServiceException(ResponseHelper::notFound());
        }

        return $item;
    }

    public function editTel ($id, $params, Context $ctx) {
        $allowed = ['name', 'tel'];
        $set = ArrayHelper::filterKey($allowed, $params);
        $entity = $this->getTel($id, $ctx);
        if(count($set)==0){
            return $entity;
        }
        MongoHelper::setUpdatedAt($set);
        $this->getTelBranchesCollection()->update(['_id'=> $entity['_id']], ['$set'=> ArrayHelper::ArrayGetPath($set)]);
        return $this->getTel($id, $ctx);
    }

    public function removeTel($id, Context $ctx){
        $item = $this->getTel($id, $ctx);
        $condition = ['_id'=> MongoHelper::mongoId($id)];
        $this->getTelBranchesCollection()->remove($condition);

        // feed update timestamp (last_update)
        UpdatedTimeHelper::update('/contact/branch/'.MongoHelper::standardId($item['branch_id']).'/tel', time());

        return true;
    }

    public function getBranchByLocation($lat, $lng){
        $item = $this->getBranchesCollection()->findOne(['location.lat'=> $lat, 'location.lng'=> $lng]);
        if(is_null($item)){
            throw new ServiceException(ResponseHelper::notFound());
        }

        return $item;
    }

    // pictures

    public function getBranchPictures($id, $params, Context $ctx){
        $id = MongoHelper::mongoId($id);
        if($this->getBranchesCollection()->count(['_id'=> $id]) == 0){
            return ResponseHelper::notFound();
        }

//        $this->collection->update(['_id'=> $id], ['$setOnInsert'=> ['history'=> []]], ['upsert'=> true]);

        $default = ["page"=> 1, "limit"=> 15];
        $options = array_merge($default, $params);
        $arg = $this->getBranchesCollection()->aggregate([
            ['$match'=> ['_id'=> $id]],
            ['$project'=> ['pictures'=> 1]],
            ['$unwind'=> '$pictures'],
            ['$group'=> ['_id'=> null, 'total'=> ['$sum'=> 1]]]
        ]);

        $total = (int)@$arg['result'][0]['total'];
        $limit = (int)$options['limit'];
        $page = (int)$options['page'];

//        $slice = MongoHelper::createSlice($page, $limit, $total);
        $slice = [($page-1)*$page, $limit];

        if($slice[1] == 0){
            $data = [];
        }
        else {
            $entity = $this->getBranchesCollection()->findOne(['_id'=> $id], ['pictures'=> ['$slice'=> $slice]]);
            $data = Image::loads($entity['pictures'])->toArrayResponse();
        }

        // reverse data
        // $data = array_reverse($data);

        $res = array(
            'length'=> count($data),
            'total'=> $total,
            'data'=> $data,
            'paging'=> array(
                'page'=> (int)$options['page'],
                'limit'=> (int)$options['limit']
            )
        );

        $pagingLength = $total/(int)$options['limit'];
        $pagingLength = floor($pagingLength)==$pagingLength? floor($pagingLength): floor($pagingLength) + 1;
        $res['paging']['length'] = $pagingLength;
        $res['paging']['current'] = (int)$options['page'];
        if(((int)$options['page'] * (int)$options['limit']) < $total){
            $nextQueryString = http_build_query(['page'=> (int)$options['page']+1, 'limit'=> (int)$options['limit']]);
            $res['paging']['next'] = URL::absolute('/contact/branches/'.MongoHelper::standardId($id).'/picture?'.$nextQueryString);
        }

        return $res;
    }

    public function addBranchPictures($id, $params, Context $ctx){
        $id = MongoHelper::mongoId($id);
        $v = new Validator($params);
        $v->rule('required', ['pictures']);
        if(!$v->validate()){
            return ResponseHelper::validateError($v->errors());
        }

        if($this->getBranchesCollection()->count(['_id'=> $id]) == 0){
            return ResponseHelper::notFound();
        }

        $res = [];
        foreach($params['pictures'] as $value){
            $img = Image::upload($value);
            $this->getBranchesCollection()->update(['_id'=> $id], ['$push'=> ['pictures'=> $img->toArray()]]);
            $res[] = $img->toArrayResponse();
        }

        return $res;
    }

    public function deleteBranchPictures($id, $params, Context $ctx){
        $id = MongoHelper::mongoId($id);
        $v = new Validator($params);
        $v->rule('required', ['id']);
        if(!$v->validate()){
            return ResponseHelper::validateError($v->errors());
        }

        if($this->getBranchesCollection()->count(['_id'=> $id]) == 0){
            return ResponseHelper::notFound();
        }

        $res = [];
        foreach($params['id'] as $value){
            $arg = $this->getBranchesCollection()->aggregate([
                ['$match'=> ['_id'=> $id, 'type'=> 'item']],
                ['$project'=> ['pictures'=> 1]],
                ['$unwind'=> '$pictures'],
                ['$group'=> ['_id'=> null, 'total'=> ['$sum'=> 1]]]
            ]);

            $total = (int)@$arg['result'][0]['total'];
            if($total==1){
                break;
            }

            $this->getBranchesCollection()->update(['_id'=> $id], ['$pull'=> ['pictures'=> ['id'=> $value]]]);
            $res[] = $value;
        }

        return $res;
    }

    public function branchesSort($param){
        $v = new Validator($param);
        $v->rule('required', ['id', 'offset']);
        if(!$v->validate()){
            return ResponseHelper::validateError($v->errors());
        }

        foreach($param['id'] as $key=> $id){
            $mongoId = MongoHelper::mongoId($id);
            $seq = $key+$param['offset'];
            $this->getBranchesCollection()->update(array('_id'=> $mongoId), array('$set'=> array('seq'=> $seq)));
        }
        return array('success'=> true);
    }

    public function telBranchesSort($param){
        $v = new Validator($param);
        $v->rule('required', ['id', 'offset']);
        if(!$v->validate()){
            return ResponseHelper::validateError($v->errors());
        }

        foreach($param['id'] as $key=> $id){
            $mongoId = MongoHelper::mongoId($id);
            $seq = $key+$param['offset'];
            $this->getTelBranchesCollection()->update(array('_id'=> $mongoId), array('$set'=> array('seq'=> $seq)));
        }
        return array('success'=> true);
    }
}