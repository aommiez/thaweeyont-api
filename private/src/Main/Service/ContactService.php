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
            $contact = [
                'facebook'=> 'https://www.facebook.com/thaweeyont',
                'website'=> 'http://www.thaweeyont.com',
                'email'=> 'callcenter@thaweeyont.com',
                'comments'=> []
            ];
            $this->getCollection()->insert($contact);
        }
        return $contact;
    }

    public function getBranches(Context $ctx){
        $branches = $this->getBranchesCollection()->count();
        if($branches == 0){
            $img = Image::upload(base64_encode(file_get_contents("private/default/default-user.png")));
            $branches = [
                'branchName'=> 'https://www.facebook.com/thaweeyont',
                'branchTel'=> 'http://example.com',
                'branchEmail'=> 'example@example.com',
                'branchFax' => '02-111-1111',
                'branchAddress' => 'ฟหดฟดฟหกหฟกฟหกห',
                'location'=> [
                    'lat'=> "1.23044454",
                    'lng'=> "1.12315643"
                ],
                'comments'=> [],
                'pictures' => [
                    $img->toArray()
                ]

            ];
            $img = Image::upload(base64_encode(file_get_contents("private/default/default-user.png")));
            $branches1 = [
                'branchName'=> 'https://www.facebook.com/thaweeyont',
                'branchTel'=> 'http://asdasdsadd.com',
                'branchEmail'=> 'sadasdsad@example.com',
                'branchFax' => '02-333-44444',
                'branchAddress' => 'ๆไผปแกดเ้ิำๆกฟหก',
                'location'=> [
                    'lat'=> "2.23044454",
                    'lng'=> "2.12315643"
                ],
                'comments'=> [],
                'pictures' => [
                    $img->toArray()
                ]
            ];
            $this->getBranchesCollection()->insert($branches);
            $this->getBranchesCollection()->insert($branches1);
        }
        $branches = $this->getBranchesCollection()->find();
        $arr = array("data"=> array());
        foreach ($branches as $value ) {
            MongoHelper::standardIdEntity($value);
            $value['pictures'] = Image::loads($value['pictures'])->toArrayResponse();
            $arr["data"][] = $value;
        }
        return $arr;
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
        $arr = $params;
        $v = new Validator($params);
        $v->rule('required', ['branchName', 'branchTel', 'branchEmail', 'branchFax', 'branchAddress','location','pictures' ]);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        foreach ($params['pictures'] as $pic) {
            $arrPic[] = Image::upload($pic)->toArray();
        }

        $arr['pictures'] = $arrPic;
        $this->getBranchesCollection()->insert($arr);
        return $arr;
    }

    public function editBranches ($id, $params, Context $ctx) {
        $allowed = ['branchName', 'branchTel', 'branchEmail', 'branchFax', 'branchAddress','location','pictures' ];
        $set = ArrayHelper::filterKey($allowed, $params);
        $entity = $this->get($ctx);
        if(count($set)==0){
            return $entity;
        }
        $set = ArrayHelper::ArrayGetPath($set);
        $this->getBranchesCollection()->update(['_id'=> $entity['_id']], ['$set'=> $set]);
        return $this->get($ctx);
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
            ->sort(['created_at'=> -1]);

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
}