<?php
namespace app\index\controller;

use app\index\model\PayOrder;
use think\Controller;
use think\Session;

class Index extends   Controller
{
    public function index()
    {
//        $scheme = [
//            'name'=>['n1',"n2",'n3'],
//            'comp'=>['c1',"c2",'c3'],
//            'usage'=>['u1',"u2",'u3'],
//            'price'=>['p1',"p2",'p3'],
//        ];
//
//        $s  = [
//            ['name'=>'n1','comp'=>'c1','usage'=>'u1','price'=>'p1'],
//            ['name'=>'n2','comp'=>'c2','usage'=>'u2','price'=>'p2'],
//            ['name'=>'n3','comp'=>'c3','usage'=>'u3','price'=>'p3'],
//        ];
//        $s=[];
//        foreach($scheme as $key=>$value){
//            foreach ($value as $key1=>$value1){
//               $s[$key1][$key]=$value1;
//
//            }
//        }
//        dump($s);
//        exit();
        return view('index');
    }

    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }

    /**
     * 向赢时国际 发起 支付 请求 获取订单号 （银联）
     * */
    public function simulated()
    { $Session= new Session();

        $id= $Session->get('id');
        if ($id==null){
            $this->redirect('login');
        }

        $request = request();
        $ispost=$request->isPost();
        if ($ispost){
           $amount=$_POST['amount'];
           if ($amount==''){
               return view('paynum');
           }
//           exit();
        //定义token
        $token='Officialwebsite';
        //填入key
        $key='yingshiguoji20190726userinfo';
        //生成签名
        $str=$token.$key.$id;
        $autograph=md5($str); // 签名的结果
        $data=[
            'id'=>$id,
            'amount'=>$amount,
            'token'=>$token,
            'auth'=>$autograph,
        ];
        $jsonData=json_encode($data,256);
        $arrData=[
            'data'=>$jsonData,
        ];

        $resultData=$this->curl_post('http://ysgj.affeec.com/site/api-pay',$arrData,30);

       $arrResultData=json_decode($resultData,1);
       $orderid=$arrResultData['data']['ordernumber'];
            $PayOrder= new PayOrder();  //  实例化 模型  存订单数据的 在 当前服务器
            $insertArray=[
                'order_id'=>$orderid,   //赢时国际 请求订单号
                'user_id'=>$id,         // 赢时国际的 用户 ID
                'amount'=>$amount,      // 该笔订单充值的金额
                'create_time'=>date('Y-m-d H:i:s',time()-28800),
            ];
            $resultInsert=$PayOrder->insert($insertArray);
       return view('simulated',['userid'=>$id,'orderid'=>$orderid,'money'=>$amount]);
        }
        return view('paynum');
    }

    /**
     * 向赢时国际 发起 支付 请求 获取订单号 （转账）
     * */
    public function transfer()
    { $Session= new Session();

        $id= $Session->get('id');
        if ($id==null){
            $this->redirect('login');
        }
        $amount=$_GET['amount'];
        if ($amount==''){
            return view('paynum');
        }
            //定义token
            $token='Officialwebsite';
            //填入key
            $key='yingshiguoji20190726userinfo';
            //生成签名
            $str=$token.$key.$id;
            $autograph=md5($str); // 签名的结果
            $data=[
                'id'=>$id,
                'amount'=>$amount,
                'token'=>$token,
                'auth'=>$autograph,
                'msg_code'=>'4004',
            ];
            $jsonData=json_encode($data,256);
            $arrData=[
                'data'=>$jsonData,
            ];
            $resultData=$this->curl_post('http://ysgj.affeec.com/site/api-pay',$arrData,30);

            $arrResultData=json_decode($resultData,1);
            $orderid=$arrResultData['data']['ordernumber'];
            $PayOrder= new PayOrder();  //  实例化 模型  存订单数据的 在 当前服务器
            $insertArray=[
                'order_id'=>$orderid,   //赢时国际 请求订单号
                'user_id'=>$id,         // 赢时国际的 用户 ID
                'amount'=>$amount,      // 该笔订单充值的金额
                'create_time'=>date('Y-m-d H:i:s',time()-28800),
            ];
            $resultInsert=$PayOrder->insert($insertArray);

            return view('infoConfirm',['userid'=>$id,'orderid'=>$orderid,'money'=>$amount]);

    }

    /**
     * 向赢时国际 发起支付完成 请求
    */
    public function index9()
    {
        $tradeno=$_POST['tradeno'];
        $user_id=$_POST['user_id'];
        $user_pay_name=$_POST['user_pay_name'];
        $user_pay_info=$_POST['user_pay_info'];
        $my_pay_name=$_POST['my_pay_name'];
        $user_pay_num=$_POST['user_pay_num'];
        //定义token
        $token='Officialwebsite';
        //填入key
        $key='yingshiguoji20190726userinfo';
        //生成签名
        $str=$token.$key.$tradeno;
        $autograph=md5($str); // 签名的结果
        $data=[
            'tradeno'=>$tradeno,
            'token'=>$token,
            'auth'=>$autograph,
            'user_id'=>$user_id,
            'user_pay_name'=>$user_pay_name,
            'user_pay_info'=>$user_pay_info,
            'my_pay_name'=>$my_pay_name,
            'user_pay_num'=>$user_pay_num,
        ];
        $jsonData=json_encode($data,256);
        $arrData=[
            'data'=>$jsonData,
        ];
        $resultData=$this->curl_post('http://ysgj.affeec.com/site/api-transfer',$arrData,30);
        $resultArray=json_decode($resultData,1);
        if ($resultArray['status']){
            return redirect('login');
        }else{
            return redirect('paynum');
        }
    }

    /**
 * 向第三方发起支付请求跳转支付界面 唤起支付
 * */
    public function initiatePayment(){
       $PayOrder= new PayOrder();
        $payData= $_POST;
        $orderId=isset($payData['order_id'])?$payData['order_id']:0;
        $userId=isset($payData['user_id'])?$payData['user_id']:0;
        if ($userId!=0 && $orderId!=0){ // 当参数都有，查询之前发起请求后保存 的数据是否正确

            $list = $PayOrder->where(['order_id'=>$orderId,'user_id'=>$userId])->find();
            $orderId=isset($list['order_id'])?$list['order_id']:0;
            if ($orderId!=0){  // 查询到该订单   取值 发起支付
                $key_id='07C667D33A53AF'; //  商户秘钥   找支付提供方 获取
                $account_id='10602';
                $array = array('amount'=>$list['amount'],'out_trade_no'=>$orderId);
                //计算签名
                $sig=$this->sign($key_id,$array);
                $callback_url='http://ysgj.affeec.com/pay/index7';
                echo "<form style='display:none;' id='form1' name='form1' method='post' action='http://pay.fchb1.com/index/bankCard/index.do'>
              <input name='account_id' type='text' value='{$account_id}' />
              <input name='order_id' type='text' value='{$orderId}'/>
              <input name='amount' type='text' value='{$list['amount']}'/>
              <input name='sign' type='text' value='{$sig}'/>
              <input name='callback_url' type='text' value='{$callback_url}'/>
            </form>
            <script type='text/javascript'>function load_submit(){document.form1.submit()}load_submit();</script>";
            }
        }

    }
//    官网登录
    public function login()
    {
        $Session= new Session();
        $id= $Session->get('id');
        if ($id!=null){
            $this->redirect('userInfo');
        }
       $request = request();
       $ispost=$request->isPost();
       if ($ispost){
           $postData=json_encode($_POST,256);
           $jsonData=['data'=>$postData];
           $resultJson=$this->curl_post('http://ysgj.affeec.com/site/api-login',$jsonData,30);   // 携带账户密码 往赢时国际 请求会员数据
           $resultArr=json_decode($resultJson,1);
           if ($resultArr['status']==1){   //  返回值为1 的时候 密码匹配正确
               $Session->set('id',$resultArr['data']['id']);
               $Session->set('userData',$resultArr['data']);
//               $this->success("{$resultArr['msg']}",'userInfo',0);
               $this->redirect('userInfo');
           }else{
               $this->redirect("login");
           }

       }
        return view('login');
    }
    //官网退出登录
    public function out(){
        $Session= new Session();
        $id= $Session->set('id',null);
        $this->redirect('index');

    }

//    官网信息 显示
    public function userInfo()
    {
        $Session= new Session();
        $id= $Session->get('id');
        if ($id==null){
            $this->redirect('login');
        }

        //定义token
        $token='Officialwebsite';
        //填入key
        $key='yingshiguoji20190726userinfo';
        //生成签名
        $str=$token.$key.$id;
        $autograph=md5($str); // 签名的结果
        $data=[
            'id'=>$id,
            'token'=>$token,
            'auth'=>$autograph,
        ];
        $jsonData=json_encode($data,256);
        $arrData=[
            'data'=>$jsonData,
        ];

        $resultData=$this->curl_post('http://ysgj.affeec.com/site/api-user-info',$arrData,30);
        $resultArr=json_decode($resultData,1);
        $Session->set('userData',$resultArr['data']);
//        dump($Session->get('userData'));
        return view('userinfo1');
    }

// 新闻引入
    public function news()
    {
        return view('news');
    }
    public function curl_post($url, array $params = array(), $timeout)
    {
        $ch = curl_init();//初始化
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return ($data);
    }

//sign算法
//$key_id 商户KEY
//$array = array('amount'=>'1.00','out_trade_no'=>'2018123645787452');
    static public function sign($key_id, $array)
    {
        $data = md5(sprintf("%.2f", $array['amount']) . $array['out_trade_no']);
        $cipher = '';
        $key[] = "";
        $box[] = "";
        $pwd_length = strlen($key_id);
        $data_length = strlen($data);
        for ($i = 0; $i < 256; $i++) {
            $key[$i] = ord($key_id[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;

            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;

            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }

        return md5($cipher);
    }

    /**
     * 获取用户真实iP
     *  */
    public function getIp()
    {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $cip = $_SERVER["HTTP_CLIENT_IP"];
        } else if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (!empty($_SERVER["REMOTE_ADDR"])) {
            $cip = $_SERVER["REMOTE_ADDR"];
        } else {
            $cip = '';
        }
        preg_match("/[\d\.]{7,15}/", $cip, $cips);
        $cip = isset($cips[0]) ? $cips[0] : 'unknown';
        unset($cips);
        dump($cip);
        exit();
        return $cip;
    }

    /**
     *  公司自用支付回调处理
     *
     * */
    public function Index7(){
        $request = request();
        if($request->isPost()) {
//            $Cache = new FileCache();
            $post_data =  $_POST;
            $baseOrderData = $post_data['data'];
            $jsonOrderData = base64_decode($baseOrderData);
            $ArrayOrderData = json_decode($jsonOrderData, 1);
            // 取出订单号
            $trade_no = isset($ArrayOrderData['result']['order_id']) ? $ArrayOrderData['result']['order_id'] : 0;
            //  取出金额
            $amount = isset($ArrayOrderData['result']['amount']) ? $ArrayOrderData['result']['amount'] : 0;
            //  取出时间
            $time = isset($ArrayOrderData['result']['time']) ? $ArrayOrderData['result']['time'] : 0;
            $key_id = '07C667D33A53AF'; //  商户秘钥   找支付提供方 获取
            $array = [
                'amount' => $amount,
                'out_trade_no' => $trade_no,
                'time'=>$time,
            ];
            $sig = $this->Sign1($key_id, $array);  //  调用 签名算法计算
            if ($sig == $ArrayOrderData['result']['sign']) {  // 签名对比
//                $Cache->Dir('./Self_support/post/');
//                $Cache->Write("$trade_no.txt", $post_data);
//                $userCharge = UserCharge::find()->where('trade_no = :trade_no and charge_state = :charge_state', [':trade_no' => $trade_no,':charge_state'=>1])->one();
                //有这笔订单
//                if (!empty($userCharge)) {
//                    //充值状态：1待付款，2成功，-1失败
//                    if ($userCharge->charge_state == 1) {
//                        //找到这个用户
//                        $user = User::findOne($userCharge->user_id);
//                        //给用户加钱
//                        $user->account += $userCharge->amount;
//                        if ($user->save()) {
//                            //更新充值状态---成功
//                            $userCharge->charge_state = 2;
//                            $userCharge->after_recharge =  $user->account;
//                            $this->actionIndex6($user->id,$userCharge->amount);
//                        }
//                    }
//                    //更新充值记录表
//                    $userCharge->update();
                    $resultData=[
                        'status'=>'success',
                        'code'=>'200',
                        'msg'=>'回调成功',
                    ];
                    $resultJson=json_encode($resultData,256);
                    return $resultJson;
//                }else{
//                    $userCharge = UserCharge::find()->where('trade_no = :trade_no and charge_state = :charge_state', [':trade_no' => $trade_no,':charge_state'=>2])->one();
//                    if(!empty($userCharge)){
//                        $resultData=[
//                            'status'=>'success',
//                            'code'=>'200',
//                            'msg'=>'订单已经完成，不要再次回调',
//                        ];
//                        $resultJson=json_encode($resultData,256);
//                        return $resultJson;
//                    }
//                    $resultData=[
//                        'status'=>'fail',
//                        'code'=>'0',
//                        'msg'=>'订单信息有误',
//                    ];
//                    $resultJson=json_encode($resultData,256);
//                    return $resultJson;
//                }
            }else{
                $resultData=[
                    'status'=>'fail',
                    'code'=>'0',
                    'msg'=>'签名错误'.$sig.'amount'.$amount.'out_trade_no'.$trade_no.'time'.$time,
                ];
                $resultJson=json_encode($resultData,256);
                return $resultJson;
            }
        }else{
            echo   "谢绝访问";
        }
    }

    public function Sign123()
    {
        $key_id = '07C667D33A53AF'; //  商户秘钥   找支付提供方 获取
        $array = [
            'amount' => '20.00',
            'out_trade_no' => '100470201908261521339620',
            'time'=>'1566811501545',
        ];
        dump($this->Sign1($key_id,$array));
    }
    //sign算法
//$key_id 商户KEY
//$array = array('amount'=>'1.00','out_trade_no'=>'2018123645787452');
    static public function Sign1($key_id, $array)
    {
        $data = md5(sprintf("%.2f", $array['amount']) . $array['out_trade_no'].$array['time']);
        $cipher = '';
        $key[] = "";
        $box[] = "";
        $pwd_length = strlen($key_id);
        $data_length = strlen($data);
        for ($i = 0; $i < 256; $i++) {
            $key[$i] = ord($key_id[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;

            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;

            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }

        return md5($cipher);
    }

}
