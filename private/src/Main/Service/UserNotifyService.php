<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 9/8/14
 * Time: 3:00 PM
 */

namespace Main\Service;


use Main\Context\Context;
use Main\DB;
use Main\Helper\MongoHelper;
use Main\Helper\ResponseHelper;
use Main\Helper\URL;

class UserNotifyService extends BaseService {
    public function getCollection(){
        return DB::getDB()->notify;
    }

    public function gets($options, Context $ctx){
        $user = $ctx->getUser();
        if(is_null($user)){
            return ResponseHelper::requireAuthorize();
        }
        $userId = MongoHelper::mongoId($user['_id']);

        $default = array(
            "page"=> 1,
            "limit"=> 15
        );
        $options = array_merge($default, $options);

        $skip = ($options['page']-1)*$options['limit'];

        $userId = MongoHelper::mongoId($userId);
        $condition = ['user_id' => $userId];

        $cursor = $this->getCollection()
            ->find($condition, ['created_at', 'opened', 'object', 'preview_content', 'preview_header'])
            ->limit((int)$options['limit'])
            ->skip((int)$skip)
            ->sort(['created_at'=> -1]);

        $data = [];
        foreach($cursor as $item){
            $item['created_at'] = MongoHelper::timeToStr($item['created_at']);
            MongoHelper::standardIdEntity($item);
            $item['object']['id'] = MongoHelper::standardId($item['object']['id']);
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
            $query = array_merge($_GET, ['page'=> (int)$options['page']+1, 'limit'=> (int)$options['limit']]);
            $nextQueryString = http_build_query($query);
            $res['paging']['next'] = URL::absolute('/user/notify?'.$nextQueryString);
        }

        return $res;
    }

    public function unopened(Context $ctx){
        $user = $ctx->getUser();
        if(is_null($user)){
            return ResponseHelper::requireAuthorize();
        }
        $userId = MongoHelper::mongoId($user['_id']);

        $count = $this->getCollection()->count(['user_id'=> $userId, 'opened'=> false]);

        return ['length'=> $count];
    }

    public function read($id, Context $ctx){
        $id = MongoHelper::mongoId($id);
        $this->getCollection()->update(['_id'=> $id], ['$set'=> ['opened'=> true]]);

        return ['success'=> true];
    }
}