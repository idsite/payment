<?php

class PayWebmoney extends PayMetod {

    public $metodName = 'webmoney';
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
            Yii::app()->controller->renderFile(__DIR__ . '/views/form1.php', array(
                'summ' => $summ,
                'desc' => $desc,
                'purse ' => $this->dataPay['purse'],
                'pay_id' => $r
            ));
            exit();
        }

        throw new CHttpException(400, 'goPay');
    }

    public function isPay() {

        $d = $this->getDatById($_REQUEST["LMI_PAYMENT_NO"]);

        if ($d) {
            return (bool) $d[$this->fieldCompleted];
        }
        return false;
    }

    public function isPayOK() {

        if (isset($_REQUEST["LMI_PAYMENT_NO"], $_REQUEST["LMI_PAYEE_PURSE"], $_REQUEST["LMI_PAYMENT_AMOUNT"], $_REQUEST['LMI_MODE'])) {


            $pred = !empty($_REQUEST['LMI_PREREQUEST']);

            if ($_REQUEST['LMI_MODE'] == '0' && !$this->test) {
                if ($pred)
                    throw new CHttpException(404, 'error test');
                return false;
            }

            $d = $this->getDatById($_REQUEST["LMI_PAYMENT_NO"]);

            if (empty($d))
                throw new CHttpException(404, 'error LMI_PAYMENT_NO');


            $isdataok = ($this->dataPay['purse'] === $_REQUEST["LMI_PAYEE_PURSE"] && $d[$this->fieldSumm] === $_REQUEST["LMI_PAYMENT_AMOUNT"]);

            if ($isdataok) {
                if ($pred) {
                    if (!$d[$this->fieldCompleted])
                        echo 'YES';
                    exit();
                } else {
                    if (isset($_REQUEST['LMI_HASH'])) {

                        $forHash = array(
                            'LMI_PAYEE_PURSE' => '',
                            'LMI_PAYMENT_AMOUNT' => '',
                            'LMI_PAYMENT_NO' => '',
                            'LMI_MODE' => '',
                            'LMI_SYS_INVS_NO' => '',
                            'LMI_SYS_TRANS_NO' => '',
                            'LMI_SYS_TRANS_DATE' => '',
                            'LMI_SECRET_KEY' => '',
                            'LMI_PAYER_PURSE' => '',
                            'LMI_PAYER_WM' => '',
                        );


                        foreach ($forHash as $key => $val) {
                            if (isset($_REQUEST[$key]))
                                $forHash[$key] = $_REQUEST[$key];
                        }

                        $forHash['LMI_SECRET_KEY'] = $this->dataPay['secret_key'];

                        // Check testing mode
                        if ($this->test === true)
                            $forHash['LMI_MODE'] = 1;
                        else
                            $forHash['LMI_MODE'] = 0;
                        $sign = strtoupper(md5(implode('', $forHash)));

                        if ($sign === $_REQUEST['LMI_HASH']) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function errorPay() {
        echo 'not fond';
        exit();
    }

    public function payCompleted($id = null) {
        if ($id == null && isset($_REQUEST["LMI_PAYMENT_NO"]))
            $id = $_REQUEST["LMI_PAYMENT_NO"];

        $this->db->createCommand()->update($this->tableName, array($this->fieldCompleted => 1), 'id=' . intval($id));
        echo "OK" . $id . "\n";
    }

    public function getDataPay($id = null) {

        if ($id == null && isset($_REQUEST["LMI_PAYMENT_NO"]))
            $id = $_REQUEST["LMI_PAYMENT_NO"];
        if ($id !== null)
            return $this->formatData($this->getDatById($id));

        return null;
    }

}

