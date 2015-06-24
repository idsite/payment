<?php

namespace idsite\payment;

use yii\db\Query;
use yii\di\Instance;
use yii\base\InvalidConfigException;
use Yii;

/**
 * Класс метода оплаты, для добавления новых методов, нужно создать класс  с именем Pay<Method> в папке с названием метода, наслевованный от этого класса
 * определить metodName, и релизовать все необходимые методы
 */
abstract class PayMetod extends \yii\base\Object {

    protected $metodName;

    /**
     * название таблицы для сохранения истории платежей
     * @var string 
     */
    public $tableName = 'tbl_payments';

    /**
     * название поля для сохранения суммы
     * @var string 
     */
    public $fieldSumm = 'amount';

    /**
     * название поля для сохранения ИД пользователя
     * @var string 
     */
    public $fieldUser = 'user_id';

    /**
     * название поля для сохранения описания платежа
     * @var string 
     */
    public $fieldDesc = 'desc';

    /**
     * название поля заввершонности платежа. 0 или 1
     * @var string 
     */
    public $fieldCompleted = 'completed';

    /**
     * поле, куда записываеться способ оплаты
     * @var string 
     */
    public $fieldMethod = 'method';

    /**
     * поле куда записываеться ИД, сущьности за которую происходит оплата, например ИД товара
     * @var string 
     */
    public $fieldItem_id = 'item_id';

    /**
     * Поле которое хранит дополнительную информацию о платеже
     * @var string 
     */
    public $fieldData = 'data';

    /**
     * название поля, обозначающее тип платежа и тип сущьности, для товара может быть Product, для пополнения личного счета пользователя User
     * @var string 
     */
    public $fieldType = 'action';

    /**
     * E-mail пользователя
     * @var string 
     */
    public $fieldEmail = 'email';
    
    /**
     * данные для платежа (номер счета, секретные ключи)
     * @var array 
     */
    protected $dataPay;
    private $_transaction;

    /**
     *
     * @var \yii\db\Connection;
     */
    public $db = 'db';

    public function init() {
        $this->db = Instance::ensure($this->db, \yii\db\Connection::className());

        if ($this->metodName === null) {
            throw new InvalidConfigException('metodName is NULL');
        }

        if ($this->dataPay === null) {
            throw new InvalidConfigException('dataPay is NULL');
        }
    }

    abstract public function goPay($summ, $desc, $item_id, $item_type, $email = null, $data = array()); //перенаправление на платёж $data - дополнительные данные для сохранения

    abstract public function isPay(); //оплачен ли платёж false или данный платёжа

    abstract public function isPayOK(); //верный ли запрос false или данный платёжа

    abstract public function payCompleted($id = null); //если верный после всех действий вызываем это

    abstract public function getDataPay($id = null); //данные платежа

    abstract public function errorPay(); //данные платежа

    protected function insertData($params = array()) {
        $di = array_merge(
                [$this->fieldCompleted => 0, $this->fieldMethod => $this->metodName]
                , $params);

        if (isset($di[$this->fieldData])) {
            $di[$this->fieldData] = serialize($di[$this->fieldData]);
        }

        if (!isset($di[$this->fieldUser]) && isset(Yii::$app->user->id)) {
            $di[$this->fieldUser] = Yii::$app->user->id;
        }

        $this->beginTransaction();

        $r = $this->db->createCommand()->insert($this->tableName, $di)->execute();
        if ($r) {
            $id = $this->db->getLastInsertID();
            $this->transCommit();
            return $id;
        } else {
            $this->transRollback();
            return false;
        }
    }

    protected function beginTransaction() {
        $this->_transaction = $this->db->beginTransaction();
    }

    protected function transCommit() {
        $this->_transaction->commit();
    }

    protected function transRollback() {
        $this->_transaction->rollback();
    }

    protected function getDataById($id) {
        return (new Query)->select()->from($this->tableName)->where('id=' . intval($id))->one();
    }

    protected function formatData($data) {
        if (is_array($data) && isset($data[$this->fieldData])) {
            $data[$this->fieldData] = unserialize($data[$this->fieldData]);
        }
        return $data;
    }

}
