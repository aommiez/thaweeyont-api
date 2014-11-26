<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 25/11/2557
 * Time: 17:10 น.
 */

namespace Main\Service;


class MessageService extends BaseService {
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
}