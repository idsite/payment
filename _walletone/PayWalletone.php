<?php

/**
 * Единая касса
 * @link http://www.walletone.com/ru/merchant/documentation/
 */
class PayWalletone extends PayMetod {

    public $metodName = 'webmoney';
    private $test = true;

    public function goPay($summ, $desc, $item_id, $item_type, $email = null, $data = array()) {
        $di = array();
        $di[$this->fieldCompleted] = 0;
        $di[$this->fieldDesc] = $desc;
        $di[$this->fieldSumm] = $summ;
        $di[$this->fieldItem_id] = $item_id;
        $di[$this->fieldType] = $item_type;



        if (!empty($data))
            $di[$this->fieldData] = $data;

        if (($r = $this->insertData($di))) {

            $fields = array(
                'WMI_MERCHANT_ID' => $this->dataPay['WMI_MERCHANT_ID'],
                'WMI_PAYMENT_AMOUNT' => number_format($summ, 2, '.', ''),
                'WMI_CURRENCY_ID' => '643',
                'WMI_PAYMENT_NO' => $r,
                'WMI_DESCRIPTION' => "BASE64:" . base64_encode($desc),
                'WMI_SUCCESS_URL' => $this->dataPay['WMI_SUCCESS_URL'],
                'WMI_FAIL_URL' => $this->dataPay['WMI_FAIL_URL'],
            );
            if ($email) {
                $fields['WMI_CUSTOMER_EMAIL'] = $email;
            }

            $fields["WMI_SIGNATURE"] = $this->getSignature($fields, $this->dataPay['key']);


            Yii::app()->controller->renderFile(__DIR__ . '/views/form1.php', array(
                'fields' => $fields,
                'pay_id' => $r
            ));
            exit();
        }

        throw new CHttpException(400, 'goPay');
    }

    /**
     * 
     * @param array $fields поля
     * @param string $key секретный ключ магазина
     * @return string
     */
    protected function getSignature($fields, $key) {
        unset($fields['WMI_SIGNATURE']);
        //Сортировка значений внутри полей
        foreach ($fields as $name => $val) {
            if (is_array($val)) {
                usort($val, "strcasecmp");
                $fields[$name] = $val;
            }
        }

        // Формирование сообщения, путем объединения значений формы,
        // отсортированных по именам ключей в порядке возрастания.
        uksort($fields, "strcasecmp");
        $fieldValues = "";

        foreach ($fields as $value) {
            if (is_array($value))
                foreach ($value as $v) {
                    //Конвертация из текущей кодировки (UTF-8)
                    //необходима только если кодировка магазина отлична от Windows-1251
                    $v = iconv("utf-8", "windows-1251", $v);
                    $fieldValues .= $v;
                } else {
                //Конвертация из текущей кодировки (UTF-8)
                //необходима только если кодировка магазина отлична от Windows-1251
                $value = iconv("utf-8", "windows-1251", $value);
                $fieldValues .= $value;
            }
        }

        // Формирование значения параметра WMI_SIGNATURE, путем
        // вычисления отпечатка, сформированного выше сообщения,
        // по алгоритму MD5 и представление его в Base64

        return base64_encode(pack("H*", md5($fieldValues . $key)));
    }

    public function isPay() {

        $d = $this->getDatById($_POST["WMI_PAYMENT_NO"]);

        if ($d) {
            return (bool) $d[$this->fieldCompleted];
        }
        return false;
    }

    public function isPayOK() {

        if (isset($_POST["WMI_SIGNATURE"], $_POST["WMI_PAYMENT_NO"], $_POST["WMI_ORDER_STATE"])) {

            if ($this->getSignature($_POST, $this->dataPay['key']) == $_POST["WMI_SIGNATURE"]) {

                if (strtoupper($_POST["WMI_ORDER_STATE"]) == "ACCEPTED") {
                    // TODO: Пометить заказ, как «Оплаченный» в системе учета магазина
                    $d = $this->getDatById($_POST["WMI_PAYMENT_NO"]);
                    return $this->formatData($d);

                    //$this->printAnswer("Ok", "Заказ #" . $_POST["WMI_PAYMENT_NO"] . " оплачен!");
                } else {
                    $this->printAnswer("Retry", "Неверное состояние " . $_POST["WMI_ORDER_STATE"]);
                }
            } else {
                $this->printAnswer('Retry', 'Неверная подпись');
            }
        } else {
            $this->printAnswer('Retry', 'отсутcвуют обязательные параметры');
        }
        return false;
    }

    private function printAnswer($result, $description = null) {
        echo "WMI_RESULT=" . strtoupper($result);
        if ($description) {
            echo "&WMI_DESCRIPTION=" . urlencode($description);
        }
        exit;
    }

    public function errorPay() {
        $this->printAnswer('Retry', 'ошибка');
        exit();
    }

    public function payCompleted($id = null) {
        if ($id == null && isset($_POST["WMI_PAYMENT_NO"]))
            $id = $_POST["WMI_PAYMENT_NO"];
        $this->db->createCommand()->update($this->tableName, array($this->fieldCompleted => 1), 'id=' . intval($id));
        $this->printAnswer("Ok");
    }

    public function getDataPay($id = null) {

        if ($id == null && isset($_POST["WMI_PAYMENT_NO"]))
            $id = $_POST["WMI_PAYMENT_NO"];
        if ($id !== null)
            return $this->formatData($this->getDatById($id));

        return null;
    }

}
