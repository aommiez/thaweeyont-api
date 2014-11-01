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

    public function get(Context $ctx){
        $contact = $this->getCollection()->findOne( ["facebook", "website", "email"]);
        if(is_null($contact)){
            $contact = [
                'facebook'=> 'https://www.facebook.com/thaweeyont',
                'website'=> 'http://example.com',
                'email'=> 'example@example.com',
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
        $arr = array();
        foreach ($branches as $value ) {
            MongoHelper::standardIdEntity($value);
            $value['pictures'] = Image::loads($value['pictures'])->toArrayResponse();
            $arr["data"][] = $value;
        }
        return $arr;
    }

    public function edit ($params,Context $ctx) {
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

    public function addBranches ($params,Context $ctx) {
        $arrPic = array();
        $arr = $params;
        $v = new Validator($params);
        $v->rule('required', ['branchName', 'branchTel', 'branchEmail', 'branchFax', 'branchAddress','location','pictures' ]);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        foreach ($params['picture'] as $pic) {
            $arrPic[] = Image::upload($pic)->toArray();
        }

        $arr['picture'] = $arrPic;
        $this->getBranchesCollection()->insert($arr);
        return $arr;
    }

    public function editBranches ($params,Context $ctx) {
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

    public function deleteBranche ($id,Context $ctx) {
        $this->getBranchesCollection()->remove(['_id'=> MongoHelper::mongoId($id)]);
        return ['success'=> true];
    }

}