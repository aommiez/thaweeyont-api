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

class CouponService extends BaseService {
    public function getCouponCodeCollection(){
        return DB::getDB()->coupon_codes;
    }

    public function getCollection(){
        return DB::getDB()->coupons;
    }

    public function addCoupon($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['name', 'detail', 'condition', 'thumb']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $insert = ArrayHelper::filterKey(['name', 'detail', 'condition'], $params);
        $insert['thumb'] = Image::upload($params['thumb'])->toArray();

        // seq insert
        $agg = $this->getCollection()->aggregate([
            ['$group'=> ['_id'=> null, 'max'=> ['$max'=> '$seq']]]
        ]);
        $insert['seq'] = (int)@$agg['result'][0]['max'] + 1;

        MongoHelper::setCreatedAt($insert);
        MongoHelper::setUpdatedAt($insert);
//        $insert['cus_only'] = (bool)$params['cus_only'];
//        $insert['type'] = 'coupon';
        $insert['used_count'] = 0;
        $insert['used_users'] = [];

        $this->getCollection()->insert($insert);

        // service update timestamp (last_update)
        UpdatedTimeHelper::update('coupon', time());

        // notify
        Event::add('after_response', function() use($insert){
            NotifyHelper::sendAll($insert['_id'], 'coupon', 'ได้เพิ่มคูปอง', $insert['detail']);
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

    public function editCoupon($id, $params, Context $ctx){
        $set = ArrayHelper::filterKey(['name', 'detail', 'condition'], $params);
        $entity = $this->get($id, $ctx);
        if(isset($params['thumb'])){
            $set['thumb'] = Image::upload($params['thumb'])->toArray();
        }
        $this->getCollection()->update(['_id'=> MongoHelper::mongoId($id)], ['$set'=> ArrayHelper::ArrayGetPath($set)]);

        // service update timestamp (last_update)
        UpdatedTimeHelper::update('coupon', time());

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
        $lastUpdate = UpdatedTimeHelper::get('coupon');
        $res['last_updated'] = MongoHelper::timeToInt($lastUpdate['time']);

        return $res;
    }

    public function delete($id, Context $ctx){
        $condition = ['_id'=> MongoHelper::mongoId($id)];
        return $this->getCollection()->remove($condition);
    }

    public function requestCoupon($id, Context $ctx){
        $user = $ctx->getUser();
        if(is_null($user)){
            throw new ServiceException(ResponseHelper::requireAuthorize());
        }
        $item = $this->get($id, $ctx);
        if(is_null($item)){
            throw new ServiceException(ResponseHelper::notFound('Not found coupon'));
        }
        foreach($item['used_users'] as $value){
            if($value['user']['_id'] == $user['_id'] && MongoHelper::timeToInt($value['expire']) < time()){
                return $value;
            }
        }

        $code = $this->getCouponCodeCollection()->findOne(['used'=> false]);
        $this->getCouponCodeCollection()->update(['_id'=> $code['_id']], ['$set'=> ['used'=> true]]);

        $used_user = [
            'user'=> ArrayHelper::filterKey(['_id', 'display_name'], $user),
            'expire'=> time() + 3600,
            'created_at'=> time(),
            'code'=> $code['_id']
        ];

        $coupon = $this->get($id, $ctx);
        foreach($coupon['used_users'] as $key=> $value){
            if($value['user']['_id']==$user['_id'])
                return $value;
        }

        $this->getCollection()->update(
            ['_id'=> MongoHelper::mongoId($id)],
            ['$push'=> ['used_users'=> $used_user], '$inc'=> ['used_count'=> 1]]
        );

        return $used_user;
    }

    public function usedUsers($id, $params, Context $ctx){
        $default = array(
            "page"=> 1,
            "limit"=> 15,
        );
        $options = array_merge($default, $params);

        $item = $this->getCollection()->findOne(['_id'=> MongoHelper::mongoId($id)]);
        if(is_null($item)){
            throw new ServiceException(ResponseHelper::notFound());
        }

        $total = count($item['used_users']);
        $length = count($item['used_users']);

        $res = [
            'length'=> $length,
            'total'=> $total,
            'data'=> $item['used_users'],
            'paging'=> [
                'page'=> 1,
                'limit'=> 1
            ]
        ];

        $pagingLength = $total/(int)$options['limit'];
        $pagingLength = floor($pagingLength)==$pagingLength? floor($pagingLength): floor($pagingLength) + 1;
        $res['paging']['length'] = $pagingLength;
        $res['paging']['current'] = (int)$options['page'];
        if(((int)$options['page'] * (int)$options['limit']) < $total){
            $nextQueryString = http_build_query(['page'=> (int)$options['page']+1, 'limit'=> (int)$options['limit']]);
            $res['paging']['next'] = URL::absolute('/coupon/'.MongoHelper::mongoId($id).'/used_users?'.$nextQueryString);
        }

        return $res;
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