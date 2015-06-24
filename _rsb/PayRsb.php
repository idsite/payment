<?php

class PayRsb extends PayMetod {

    public $metodName = 'rsb';
    private $test = true;

    public function goPay($summ, $desc, $item_id, $email = null, $data = array()) {
        $di = array();
        $di[$this->fieldCompleted] = 0;
        $di[$this->fieldDesc] = $desc;
        $di[$this->fieldSumm] = $summ;
        $di[$this->fieldItem_id] = $item_id;

        if (!empty($data))
            $di[$this->fieldData] = $data;

        if (($r = $this->insertData($di))) {

            $post_data = array(
                'MERCHANTNUMBER' => $this->dataPay['MERCHANTNUMBER'],
                'ORDERNUMBER' =>$r,// $item_id,
                'AMOUNT' => intval($summ * 100),
                'BACKURL' => Yii::app()->createAbsoluteUrl('shop/pay/result/method/rsb',array('InvId' => $r)),
                '$ORDERDESCRIPTION' =>  iconv('UTF-8', 'CP1251', $desc) ,
                'LANGUAGE' => 'RU',
                'DEPOSITFLAG' => 1,
                'MODE' => 1,
                'MERCHANTPASSWD' => $this->dataPay['MERCHANTPASSWD'],
                
            );
            $url = $this->test ? 'https://playground.paymentgate.ru/bpcservlet/Merchant2Rbs' : 'https://engine.paymentgate.ru/bpcservlet/Merchant2Rbs';
            $ch = curl_init();
//curl_setopt($ch, CURLOPT_PROXY, «http://192.168.2.600:2323″);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//curl_setopt($ch, CURLOPT_REFERER, $refer );
//curl_setopt($ch, CURLOPT_COOKIE,$coo);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
//curl_setopt($ch, CURLOPT_USERAGENT, «Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)»);
//curl_setopt($ch, CURLOPT_VERBOSE,1);
            $MDORDER = trim(curl_exec($ch));
            curl_close($ch);
      //      print_r($post_data);
       //     echo $MDORDER;
        //      exit('hjk');

            $d = $this->getDataPay($r);
          
            $d['MDORDER'] = $MDORDER;
           
          
            
            $this->db->createCommand()->update($this->tableName, array($this->fieldData => serialize($d)), 'id=' . $r);
            Yii::app()->controller->renderFile(__DIR__ . '/views/form1.php', array(
                'MDORDER' => $MDORDER,
                'test' => $this->test
            ));
            exit();
        }

        throw new CHttpException(400, 'goPay');
    }

    public function isPay() {
        if (isset($_REQUEST["InvId"])) {
            $inv_id = $_REQUEST["InvId"];

            $d = $this->getDataPay($inv_id);

            $url = $this->test ? 'https://playground.paymentgate.ru/bpcservlet/QueryOrders' : 'https://engine.paymentgate.ru/bpcservlet/QueryOrders';
            $ch = curl_init();
            $post_data = array(
                'MDORDER' => $d['data']['MDORDER'],
                'MERCHANTPASSWD' => $this->dataPay['MERCHANTPASSWD']
            );
//curl_setopt($ch, CURLOPT_PROXY, «http://192.168.2.600:2323″);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//curl_setopt($ch, CURLOPT_REFERER, $refer );
//curl_setopt($ch, CURLOPT_COOKIE,$coo);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
//curl_setopt($ch, CURLOPT_USERAGENT, «Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)»);
//curl_setopt($ch, CURLOPT_VERBOSE,1);
            $xml = simplexml_load_string(curl_exec($ch));
            curl_close($ch);

            
      
            
            if ($xml['primaryRC'] == '0' && $xml['secondaryRC'] == '0') {
                if ($xml->PSOrder['amount'] == intval($d[$this->fieldSumm] * 100)) {
                    if ($xml->PSOrder->PaymentCollection->PSPayment['authCode'] == '0' &&
                            $xml->PSOrder->PaymentCollection->PSPayment['approvalCode'] != '000000'
                    ) {
                        return $d;
                    }
                }
            }
        }

        return false;
    }

    public function isPayOK() {

        if (isset($_REQUEST["ANSWER"])) {
            /*
              <?xml version="1.0" encoding="UTF-8">
              <PSApiResult primaryRC="0" secondaryRC="0"/>
             */
            
            $xml = simplexml_load_string($_REQUEST["ANSWER"]);
         
            if ($xml['primaryRC'] == '0' && $xml['secondaryRC'] == '0') {
                return $this->isPay();
            }
        }


        return false;
    }

    public function errorPay() {
        echo 'not fond';
        exit();
    }

    public function payCompleted($id = null) {
        if ($id == null && isset($_REQUEST["InvId"]))
            $id = $_REQUEST["InvId"];
        $this->db->createCommand()->update($this->tableName, array($this->fieldCompleted => 1), 'id=' . intval($id));
        
        echo '<html><head>
<meta http-equiv="refresh" content="3; url=http://'.$_SERVER['HTTP_HOST'].'">
</head>
<body>
<h2 style="text-align:center;">Платёж выполнен.</h2>
</body>
</html>';
    }

    public function getDataPay($id = null) {

        if ($id == null && isset($_REQUEST["InvId"]))
            $id = $_REQUEST["InvId"];
        if ($id !== null)
            return $this->formatData($this->getDatById($id));

        return null;
    }

}

