<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 8/21/14
 * Time: 3:46 PM
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
use Main\Helper\UserHelper;
use Valitron\Validator;

class UserService extends BaseService {
    protected $fields = ["type", "display_name", "username", "email", "password", "gender", "birth_date", "picture", "mobile", "website", "fb_id", "fb_name", "type"];

    public function getCollection(){
        $db = DB::getDB();
        return $db->users;
    }

    public function add($params, Context $ctx){
        $allow = ["username", "email", "password", "gender", "birth_date"];
        $entity = ArrayHelper::filterKey($allow, $params);

//        Add rule
//        Validator::addRule('ruleName', function($field, $value, $params = []){
//            if(true)
//                return true;
//            return false;
//        });

        $v = new Validator($entity);
        $v->rule('required', ["username", "email", "password", "gender", "birth_date"]);
//        $v->rule('required', ["username", "email", "password", "gender"]);
        $v->rule('email', ["email"]);
        $v->rule('lengthBetween', 'username', 4, 32);
        $v->rule('lengthBetween', 'password', 4, 32);
        $v->rule('in', 'gender', ['male', 'female']);
//        $v->rule('date', 'birth_date');

        if(!$v->validate()) {
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        if($this->getCollection()->count(['username'=> $entity['username']]) != 0){
            throw new ServiceException(ResponseHelper::validateError(['username'=> ['Duplicate username']]));
        }

        $entity['password'] = md5($entity['password']);
        $entity['display_name'] = $entity['username'];
        $entity['birth_date'] = new \MongoTimestamp(strtotime($entity['birth_date']));

        // set website,mobile to ''
        $entity['website'] = '';
        $entity['mobile'] = '';

        $entity['fb_id'] = '';
        $entity['fb_name'] = '';
        $entity['display_notification_number'] = 0;
        $entity['type'] = 'normal';

        // set default setting
        $entity['setting'] = UserHelper::defaultSetting();

        // register time
        $entity['created_at'] = new \MongoTimestamp();

        // set default last login
        $entity['last_login'] = new \MongoTimestamp(0);

        $this->getCollection()->insert($entity);

        //add stat helper
//        StatHelper::add('register', time(), 1);

//        MongoHelper::standardIdEntity($entity);
        unset($entity['password']);

        return $entity;
    }

    public function gets($options, Context $ctx){
        $default = array(
            "page"=> 1,
            "limit"=> 15
        );
        $options = array_merge($default, $options);

        $skip = ($options['page']-1)*$options['limit'];
        //$select = array("name", "detail", "feature", "price", "pictures");
        $condition = ['type'=> 'normal'];

        $cursor = $this->getCollection()
            ->find($condition, ['display_name', 'username', 'picture', 'email', 'fb_name', 'gender', 'created_at', 'last_login', 'picture'])
            ->limit($options['limit'])
            ->skip($skip)
            ->sort(array('created_at'=> -1));

        $total = $this->getCollection()->count($condition);
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
            $res['paging']['next'] = URL::absolute('/user'.'?'.$nextQueryString);
        }
        return $res;
    }

    public function edit($id, $params, Context $ctx){
        $allow = ["email", "gender", "birth_date", "website", "mobile", "display_name", "username"];
        $set = ArrayHelper::filterKey($allow, $params);
        $v = new Validator($set);
        $v->rule('email', 'email');
        $v->rule('in', 'gender', ['male', 'female']);
//        $v->rule('date', 'birth_date');
        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $old = $this->get($id, $ctx);

        if(isset($set['username'])){
            if($this->getCollection()->count(['username'=> $set['username']]) > 0 && $old['username'] != $set['username']){
                throw new ServiceException(ResponseHelper::error('Duplicate username'));
            }
        }

        if(isset($params['picture'])){
            $img = Image::upload($params['picture']);
            $set['picture'] = $img->toArray();
        }
        $set = ArrayHelper::ArrayGetPath($set);

        if(isset($set['birth_date'])){
            $set['birth_date'] = new \MongoTimestamp($set['birth_date']);
        }

        if(count($set)>0){
            $id = MongoHelper::mongoId($id);
            $this->getCollection()->update(['_id'=> $id], ['$set'=> $set]);
        }

        return $this->get($id, $ctx);
    }

    public function changePassword($id, $params, Context $ctx){
        $id = MongoHelper::mongoId($id);

        $v = new Validator($params);
        $v->rule('required', ['new_password', 'old_password']);
        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $entity = $this->getCollection()->findOne(['_id'=> $id], ['password']);
        if(is_null($entity)){
            throw new ServiceException(ResponseHelper::notFound());
        }

        if((md5($params['old_password']) != $entity['password']) && isset($entity['password'])){
            throw new ServiceException(ResponseHelper::validateError(['old_password'=> ['Password not match']]));
        }

        $set = ['password'=> md5($params['new_password'])];
        $this->getCollection()->update(['_id'=> $id], ['$set'=> $set]);

        return ['success'=> true];
    }

    public function get($id, Context $ctx){
        $id = MongoHelper::mongoId($id);

        $fields = $this->fields;
        unset($fields['password']);

        $entity = $this->getCollection()->findOne(['_id'=> $id], $fields);
        if(is_null($entity)){
            throw new ServiceException(ResponseHelper::notFound());
        }

        MongoHelper::standardIdEntity($entity);
//        $entity['birth_date'] = date('Y-m-d H:i:s', MongoHelper::timeToInt($entity['birth_date']));

        if(isset($entity['picture'])){
            $entity['picture'] = Image::load($entity['picture'])->toArrayResponse();
        }
        else {
            $entity['picture'] = Image::load([
                'id'=> '54297c9390cc13a5048b4567png',
                'width'=> 200,
                'height'=> 200
            ])->toArrayResponse();
        }
        return $entity;
    }

    public function me($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', 'access_token');
        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $tokenEntity = $this->getCollection()->findOne(['access_token'=> $params['access_token']]);
        if(is_null($tokenEntity)){
            throw new ServiceException(ResponseHelper::notAuthorize());
        }

        return $this->get($tokenEntity['_id'], $ctx);
    }

    public function requestResetCode($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['email']);
        $v->rule('email', 'email');

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $user = $this->getCollection()->findOne(['email'=> $params['email']]);
        if(is_null($user)){
            throw new ServiceException(ResponseHelper::notFound());
        }

        $resetPasswordCode = md5(uniqid(MongoHelper::mongoId($user['_id']).'_reset', true));
        $this->getCollection()->update(['_id'=> $user['_id']], ['$set'=> ['reset_password_code'=> $resetPasswordCode]]);

        $strTo = $user['email'];
        $strSubject = "Thaweeyont Recovery Password.";
        $strHeader = "MIME-Version: 1.0' . \r\n";
        $strHeader .= "Content-type: text/html; charset=utf-8\r\n";
        $strHeader .= "From: Thaweeyont-service<admin@thaweeyont.com>";
        $strMessage = "You are code: ".$resetPasswordCode;
        if(isset($params['callback_url'])){
            $callback = parse_url($params['callback_url']);
            $url = 'http://'.$callback['host'].$callback['path'];
            $query = [];
            if(isset($callback['query'])){
                parse_str($callback['query'], $query);
            }
            $query['reset_password_code'] = $resetPasswordCode;
            $callback['query'] = http_build_query($query);
            $url .= '?'.$callback['query'];
            $strMessage .= <<<HTML
            <br />
or follow link: <a href="{$url}">{$url}</a>
HTML;
        }

        $flgSend = @mail($strTo, $strSubject, $strMessage, $strHeader);  // @ = No Show Error //
//            $flgSend = mail($strTo, $strSubject, $strMessage, $strHeader);  // @ = No Show Error //
        if(!$flgSend)
        {
            throw new ServiceException(ResponseHelper::error('Email not send.'));
        }

        return [
            'id'=> MongoHelper::standardId($user['_id']),
            'email'=> $user['email']
        ];
    }

    public function getUserByCode($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['reset_password_code']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $user = $this->getCollection()->findOne(['reset_password_code'=> $params['reset_password_code']], ['username', 'display_name']);
        if(is_null($user)){
            throw new ServiceException(ResponseHelper::notFound());
        }

        return $user;
    }

    public function setPasswordByCode($params, Context $ctx){
        $v = new Validator($params);
        $v->rule('required', ['reset_password_code', 'new_password']);

        if(!$v->validate()){
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        $user = $this->getCollection()->findOne(['reset_password_code'=> $params['reset_password_code']], ['username', 'display_name']);
        if(is_null($user)){
            throw new ServiceException(ResponseHelper::notFound());
        }

        $newPassword = md5($params['new_password']);
        $this->getCollection()->update(['_id'=> $user['_id']], ['$set'=> ['password'=> $newPassword], '$unset'=> ['reset_password_code'=> '']]);

        return true;
    }

    public function getAdmins($options){
        $default = array(
            "page"=> 1,
            "limit"=> 15
        );
        $options = array_merge($default, $options);

        $skip = ($options['page']-1)*$options['limit'];
        //$select = array("name", "detail", "feature", "price", "pictures");
        $condition = ['type'=> ['$in'=> ['admin', 'super_admin']]];

        $cursor = $this->getCollection()
            ->find($condition, ['display_name', 'username', 'picture', 'email', 'fb_name', 'gender', 'created_at', 'last_login', 'picture', 'type'])
            ->limit($options['limit'])
            ->skip($skip)
            ->sort(array('created_at'=> -1));

        $total = $this->getCollection()->count($condition);
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
            $res['paging']['next'] = URL::absolute('/user'.'?'.$nextQueryString);
        }
        return $res;
    }

    public function addAdmin($params, Context $ctx){
        $allow = ["username", "email", "password", "gender", "birth_date"];
        $entity = ArrayHelper::filterKey($allow, $params);

        $v = new Validator($entity);
        $v->rule('required', ["username", "email", "password"]);
        $v->rule('email', ["email"]);
        $v->rule('lengthBetween', 'username', 4, 32);
        $v->rule('lengthBetween', 'password', 4, 32);
//        $v->rule('date', 'birth_date');

        if(!$v->validate()) {
            throw new ServiceException(ResponseHelper::validateError($v->errors()));
        }

        if($this->getCollection()->count(['username'=> $entity['username']]) != 0){
            throw new ServiceException(ResponseHelper::error('Duplicate username'));
        }

        if(isset($params['picture'])){
            $entity['picture'] = Image::upload($params['picture'])->toArray();
        }

        $entity['password'] = md5($entity['password']);
        $entity['display_name'] = $entity['username'];

        $entity['display_notification_number'] = 0;
        $entity['type'] = 'admin';

        // register time
        $entity['created_at'] = new \MongoTimestamp();

        // set default last login
        $entity['last_login'] = new \MongoTimestamp(0);

        $this->getCollection()->insert($entity);

        //add stat helper
//        StatHelper::add('register', time(), 1);

//        MongoHelper::standardIdEntity($entity);
        unset($entity['password']);

        return $entity;
    }

    public function remove($id, Context $ctx){
        $id = MongoHelper::mongoId($id);

        $this->getCollection()->remove(array("_id"=> $id));

        return array("success"=> true);
    }
}