<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 10/10/14
 * Time: 11:49 AM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DataModel\Image;
use Main\DB;
use Main\Event\Event;
use Main\Exception\Service\ServiceException;
use Main\Helper\ArrayHelper;
use Main\Helper\MongoHelper;
use Main\Helper\NotifyHelper;
use Main\Helper\ResponseHelper;
use Main\Helper\UpdatedTimeHelper;
use Main\Helper\URL;
use Valitron\Validator;

class PromotionService extends BaseService {
    public function getCollection(){
        return DB::getDB()->promotions;
    }

    public function addPromotion($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['name', 'detail', 'thumb']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $insert = ArrayHelper::filterKey(['name', 'detail'], $params);
        $insert['thumb'] = Image::upload($params['thumb'])->toArray();

        // seq insert
        $agg = $this->getCollection()->aggregate([
            ['$group'=> ['_id'=> null, 'max'=> ['$max'=> '$seq']]]
        ]);
        $insert['seq'] = (int)@$agg['result'][0]['max'] + 1;

        MongoHelper::setCreatedAt($insert);
        MongoHelper::setUpdatedAt($insert);
//        $insert['cus_only'] = (bool)$params['cus_only'];
//        $insert['type'] = 'promotion';
        $insert['used_count'] = 0;
        $insert['used_users'] = [];

        $this->getCollection()->insert($insert);

        // service update timestamp (last_update)
        UpdatedTimeHelper::update('promotion', time());

        // notify
        Event::add('after_response', function() use($insert){
            NotifyHelper::sendAll($insert['_id'], 'promotion', 'ได้เพิ่มโปรโมชั่น', $insert['detail']);
        });

        return $insert;
    }

    public function get($id, Context $ctx){
        $item = $this->getCollection()->findOne(['_id'=> MongoHelper::mongoId($id)]);
        if(is_null($item)){
            throw new ServiceException(ResponseHelper::notFound());
        }
        return $item;
    }

    public function editPromotion($id, $params, Context $ctx){
        $set = ArrayHelper::filterKey(['name', 'detail'], $params);
        $entity = $this->get($id, $ctx);
        if(isset($params['thumb'])){
            $set['thumb'] = Image::upload($params['thumb'])->toArray();
        }
        $this->getCollection()->update(['_id'=> MongoHelper::mongoId($id)], ['$set'=> ArrayHelper::ArrayGetPath($set)]);

        // service update timestamp (last_update)
        UpdatedTimeHelper::update('promotion', time());

        return $this->get($id, $ctx);
    }

    public function gets($params, Context $ctx){
        $default = array(
            "page"=> 1,
            "limit"=> 15,
        );
        $options = array_merge($default, $params);

        $skip = ($options['page']-1)*$options['limit'];

//        $isCus = false;
//        $user = $ctx->getUser();
//        if(isset($user['check_in'])){
//            $isCus = true;
//        }

        $condition = [];
//        if(!$isCus && @$params['consumer_key'] != 'admin'){
//            $condition = [
//                '$or'=> [
//                    ['cus_only'=> ['$exists'=> false]],
//                    ['cus_only'=> false]
//                ]
//            ];
//        }
        $cursor = $this->getCollection()
            ->find($condition)
            ->limit((int)$options['limit'])
            ->skip((int)$skip)
            ->sort(['seq'=> -1]);

        $data = [];

        foreach($cursor as $item){
            $data[] = $item;
        }

        $total = $this->getCollection()->count($condition);
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
            $res['paging']['next'] = URL::absolute('/promotion'.'?'.$nextQueryString);
        }

        // add last_update to response
        $lastUpdate = UpdatedTimeHelper::get('promotion');
        $res['last_updated'] = MongoHelper::timeToInt($lastUpdate['time']);

        return $res;
    }

    public function delete($id, Context $ctx){
        $condition = ['_id'=> MongoHelper::mongoId($id)];
        return $this->getCollection()->remove($condition);
    }

    public function sort($param, Context $ctx = null){
        foreach($param['id'] as $key=> $id){
            $mongoId = MongoHelper::mongoId($id);
            $seq = $key+$param['offset'];
            $this->getCollection()->update(array('_id'=> $mongoId), array('$set'=> array('seq'=> $seq)));
        }

        // feed update timestamp (last_update)
        UpdatedTimeHelper::update('feed', time());

        return array('success'=> true);
    }

    public function incView($id){
        $this->getCollection()->update(['_id'=> MongoHelper::mongoId($id)], ['$inc'=> ['view_count'=> 1]]);
    }

}