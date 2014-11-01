<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 7/23/14
 * Time: 10:40 AM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DataModel\Image;
use Main\DB;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Helper\ResponseHelper;
use Main\Helper\URL;
use Valitron\Validator;

class FeedService extends BaseService {

    public function getCollection(){
        $db = DB::getDB();
        return $db->feed;
    }

    public function add($params, Context $ctx = null){
        $v = new Validator($params);
        $v->rule('required', ['thumb']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $insert = ArrayHelper::filterKey(['name', 'detail','thumb'], $params);

        $insert['thumb'] = Image::upload($params['thumb'])->toArray();

        MongoHelper::setCreatedAt($insert);
        MongoHelper::setUpdatedAt($insert);

        $this->getCollection()->insert($insert);


        return $insert;
    }

    public function edit($id, $params, Context $ctx = null){
        if(is_null($ctx))
            $ctx = $this->getCtx();

        if(!($id instanceof \MongoId)){
            $id = new \MongoId($id);
        }

        $entity = $params;
        if(isset($entity['thumb'])){
            $imgModel = Image::instance()->add($entity['thumb']);
            $entity['thumb'] = $imgModel->getDBRef();
        }
        if(isset($entity['name']['en'])){
            $entity['name.en'] = $entity['name']['en'];
            unset($entity['name']['en']);
        }
        if(isset($entity['name']['th'])){
            $entity['name.th'] = $entity['name']['th'];
            unset($entity['name']['th']);
        }
        if(isset($entity['detail']['en'])){
            $entity['detail.en'] = $entity['detail']['en'];
            unset($entity['detail']['en']);
        }
        if(isset($entity['detail']['th'])){
            $entity['detail.th'] = $entity['detail']['th'];
            unset($entity['detail']['th']);
        }
        unset($entity['name'], $entity['detail']);

        // insert
        $this->collection->update(array('_id'=> $id), array('$set'=> $entity));


        // feed update timestamp (last_update)
        TimeService::instance()->update('feed');

        return $this->get($id, $ctx);
    }

    public function get($id, Context $ctx = null){
        if(is_null($ctx))
            $ctx = $this->getCtx();

        if(!($id instanceof \MongoId)){
            $id = new \MongoId($id);
        }

        $item = $this->collection->findOne(array("_id"=> $id));
        if(is_null($item)){
            return ResponseHelper::notFound("Room type not found");
        }

        $thumbModel = \Main\Helper\Image::instance()->findByRef($item['thumb']);
        $item['thumb'] = $thumbModel->toArrayResponse();

        $item['id'] = $item['_id']->{'$id'};
        unset($item['_id']);

        if(!$ctx->isAdminConsumer()){
            $item['name'] = $item['name'][$ctx->getLang()];
            $item['detail'] = $item['detail'][$ctx->getLang()];
        }

        $item['node'] = $this->makeNode($item);

        return $item;
    }

    public function gets($options = array(), Context $ctx = null){


        $default = array(
            "page"=> 1,
            "limit"=> 15
        );
        $options = array_merge($default, $options);

        $skip = ($options['page']-1)*$options['limit'];
        //$select = array("name", "detail", "feature", "price", "pictures");
        $condition = array();

        $cursor = $this->getCollection()
            ->find($condition)
            ->limit($options['limit'])
            ->skip($skip)
            ->sort(array('seq'=> -1));

        $total = $this->getCollection()->count($condition);
        $length = $cursor->count(true);

        $data = array();
        foreach($cursor as $item){
            $item['thumb'] = Image::load($item['thumb'])->toArrayResponse();


            $item['id'] = $item['_id']->{'$id'};
            unset($item['_id']);


            $item['node'] = $this->makeNode($item);

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

        return $res;
    }

    public function sort($param, Context $ctx = null){
        foreach($param['id'] as $key=> $id){
            $mongoId = new \MongoId($id);
            $seq = $key+$param['offset'];
            $this->collection->update(array('_id'=> $mongoId), array('$set'=> array('seq'=> $seq)));
        }
        return array('success'=> true);
    }

    public function delete($id, Context $ctx = null){
        if(is_null($ctx))
            $ctx = $this->getCtx();

        if(!($id instanceof \MongoId)){
            $id = new \MongoId($id);
        }

        $this->collection->remove(array("_id"=> $id));

        // feed update timestamp (last_update)
        TimeService::instance()->update('feed');

        return array("success"=> true);
    }

    public function makeNode($item){
        return array(
            "share"=> URL::share('')
        );
    }
}