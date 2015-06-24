<?php

namespace idsite\payment\yandex;

/**
 * Яндекс
 */
class PayYandex extends \idsite\payment\PayMetod {

    public $metodName = 'yandex';


    const STATE_ERROR = 'error';
    const STATE_SUCCESS = 'success';

    public function goPay($summ, $desc, $item_id, $item_type, $email = null, $data = array()) {
        $di = array();
        $di[$this->fieldCompleted] = 0;
        $di[$this->fieldDesc] = $desc;
        $di[$this->fieldSumm] = $summ;
        $di[$this->fieldItem_id] = $item_id;
        $di[$this->fieldType] = $item_type;
        $di[$this->fieldEmail] = $email;

        if (!empty($data)) {
            $di[$this->fieldData] = $data;
        }

        if (($r = $this->insertData($di))) {
           

            $fields = array(
                'receiver' => $this->dataPay['receiver'],
                'formcomment' => $desc,
                'short-dest' => $desc,
                'quickpay-form' => 'shop',
                'targets' => 'транзакция ' . $r,
                'sum' => number_format($summ, 2, '.', ''),
                'paymentType' => @$_REQUEST['paymentType'] == 'PC' ? 'PC' : 'AC',
                'label' => $r,
            );

            \Yii::$app->view->renderFile(__DIR__ . '/views/form1.php', array(
                'fields' => $fields,
                'pay_id' => $r
            ));
            exit();
        }

        throw new \yii\web\HttpException(400, 'goPay');
    }

    /**
     * 
     * @param array $fields поля
     * @param string $key секретный ключ магазина
     * @return string
     */
    protected function getSignature($fields, $key) {

        $string = $fields['notification_type'] . '&'
                . $fields['operation_id'] . '&'
                . $fields['amount'] . '&'
                . $fields['currency'] . '&'
                . $fields['datetime'] . '&'
                . $fields['sender'] . '&'
                . $fields['codepro'] . '&'
                . $key . '&'
                . $fields['label'];
        $string = sha1($string);
        return $string;
    }

    public function isPay() {

        $d = $this->getDataById(@$_POST["label"]);
        if ($d) {
            return (bool) $d[$this->fieldCompleted];
        }
        return false;
    }

    public function isPayOK() {
        if (isset($_POST['test_notification'])) {
            exit('test?');
        }
        if (isset($_POST['notification_type'], $_POST['operation_id'], $_POST['amount'], $_POST['withdraw_amount'], $_POST['currency'], $_POST['datetime']
                        , $_POST['sender'], $_POST['codepro'], $_POST['label'], $_POST['sha1_hash'])) {

            if (strtolower($this->getSignature($_POST, $this->dataPay['secret'])) == strtolower($_POST["sha1_hash"])) {

                if ($_POST["label"] !== '') {
                    // TODO: Пометить заказ, как «Оплаченный» в системе учета магазина
                    $d = $this->getDataById($_POST["label"]);
                    if ($d[$this->fieldCompleted]) {
                        exit('complete');
                    }

                    if ($d) {
                        if (floatval($d[$this->fieldSumm]) <= floatval($_POST['withdraw_amount'])) {

                            if ($_POST['codepro'] && $_POST['codepro'] != 'false') {
                                $this->printAnswer(self::STATE_ERROR, 'Платёж с кодом протекции');
                            }
                            return $this->formatData($d);
                        } else {
                            $this->printAnswer(self::STATE_SUCCESS, 'Недостаточная сумма платежа');
                        }
                    } else {
                        $this->printAnswer(self::STATE_SUCCESS, 'label не извесный');
                    }
                } else {
                    exit('label empty');
                }
            } else {
                $this->printAnswer(self::STATE_ERROR, 'Неверная подпись');
            }
        } else {
            $this->printAnswer(self::STATE_ERROR, 'отсутcвуют обязательные параметры');
        }
        return false;
    }

    private function printAnswer($result, $description = null, $exit = true) {
        if ($result == self::STATE_ERROR) {
            throw new \yii\web\HttpException(400, $description);
        } else {
            echo $description;
        }

        if ($exit)
            exit;
    }

    public function errorPay() {
        $this->printAnswer(self::STATE_ERROR, 'ошибка');
        exit();
    }

    public function payCompleted($id = null) {
        if ($id == null && isset($_POST["label"]))
            $id = $_POST["label"];
        $d = $this->getDataById($id);
        $data = $d[$this->fieldData];
        if (is_array($data))
            $data['response_data'] = $_POST;
        else
            $data = array('response_data' => $_POST);

        $this->db->createCommand()->update($this->tableName, array($this->fieldCompleted => 1, $this->fieldData => serialize($data)), 'id=' . intval($id))->execute();
        $this->printAnswer(self::STATE_SUCCESS, 'ok', false);
    }

   

    public function getDataPay($id = null) {
        if ($id == null && isset($_POST["label"]))
            $id = $_POST["label"];
        if ($id != null) {
            return $this->formatData($this->getDataById($id));
        }
        return null;
    }

}
