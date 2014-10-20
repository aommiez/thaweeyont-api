<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 8/5/14
 * Time: 11:08 AM
 */

namespace Main\Service;


use Main\DB;

class TimeService extends BaseService {private $collection;
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct(){
        $this->db = DB::getDB();
        $this->collection = $this->db->update_time;
    }

    public function get(){
        $entity = $this->getEntity();
        foreach($entity as $key => $value){
            /** @var \MongoTimestamp $value */
            if($value instanceof \MongoTimestamp){
                $entity[$key] = $value->sec;
            }
        }
        return $entity;
    }

    public function update($field){
        $entity = $this->getEntity();
        $this->collection->update(array('_id'=> $entity['_id']),
            array('$set'=> array($field=> new \MongoTimestamp()))
        );

        return $this->getEntity();
    }

    public function getEntity(){
        $entity = $this->collection->findOne();
        if(is_null($entity)){
            $entity = $this->makeEntityFromEmpty();
        }
        return $entity;
    }

    public function makeEntityFromEmpty(){
        $entity = array(
            'feed'=> new \MongoTimestamp(0),
            'feed_gallery'=> new \MongoTimestamp(0)
        );
        $this->collection->insert($entity);
        return $entity;
    }
}