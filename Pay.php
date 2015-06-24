<?php

namespace idsite\payment;

use Yii;

/**
 * Этот класс удобно объявить как компонент в конфигурации приложения
 */
class Pay extends \yii\base\Object {

    /**
     * массив с конфигурацией для каждого метода оплаты
     * пример: 
     * methods = [
     * 'yandex' => [
     * 'class' => '\idsite\payment\yandex\PayYandex',
     * 'dataPay'=>[...]
     * ]
     * @var array 
     */
    public $methods = [];

    /**
     * Конфигурация для класса метода оплаты PayMetod
     * @var type 
     */
    public $pay_config = [];

    /**
     * 
     * @param string $metod название метода
     * @return PayMetod
     */
    public function getPay($metod) {
        if (isset($this->methods[$metod])) {
            return Yii::createObject($this->methods[$metod]);
        } else {
            throw new \yii\base\InvalidConfigException('method pay "' . $metod . '" not defined');
        }
    }

}
