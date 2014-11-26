<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 26/11/2557
 * Time: 15:22 น.
 */

namespace Main\CTL;
use Main\Helper\APNHelper;

/**
 * @Restful
 * @uri /test
 */
class TestCTL extends BaseCTL {
    /**
     * @GET
     * @uri /push
     */

    public function push(){
        $apnHelper = new APNHelper(file_get_contents("private/apple/dev.pem"), 'gateway.sandbox.push.apple.com', 2195);

        $res = array();
        $tokens = array(
            "0294875d882cbdc65e8a5e2062897e565a9c5c6fc9db9cd40d1bb5ec318ffd06",
//            "yyqCreAwHvgEJzrhDjycaoEJidzveqgciFucClqBBnuIfGdfsBbBnojmdklhcux",
            "26fea0c78d7eb91817bfdafe91c59f2144a31ee7ce5e4c53eb62a9675db3c05d"
        );

        foreach($tokens as $key=> $token){
            $res[] = $apnHelper->send($token, "test", array(
                "test"=> "test"
            ));
//            echo pack('H*', $token);
        }

        return $res;
    }
}