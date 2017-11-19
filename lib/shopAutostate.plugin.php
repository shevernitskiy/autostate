<?php

class shopAutostatePlugin extends shopPlugin
{
    /**
     * Функция сохранение настроек плагина
     * 
     * @param array $settings - массив из шаблона настроек плагина
     * @return mixed 
     */
    public function saveSettings($settings = array())
    {
        $this->lg($settings);
        parent::saveSettings($settings);
//        $this->checkTrackingStatus('Delivery',$this->getTrackingByNumber('ZA036792743LV'));
        return true;
    }

    /**
     * Получение массива order_id, которые находяться на стадии $state
     * 
     * @param string $state 
     * @return array [order_id,order_id,order_id,order_id...]
     */
    public function getStateOrders($state)
    {
        if (empty($state)) {
            throw new waExeption('Для получения списка заказов нужно указать стадию');
            return false;
        } else {
            switch ($state) {
                case 'ship':
                    $model = new shopOrderModel();
                    $sql = "SELECT `id` FROM `shop_order` WHERE `state_id`='shipped'";
                    $orderList = $model->query($sql)->fetchAll();
                    if ($orderList) {
                        return array_column($orderList, 'id');
                    } else {
                        return false;
                    }
                    break;
                default:
                    return false;
                    break;
            }
            return true;
        }
    }

    /**
     * Получение массива трекинг номеров для массива order_id заказов $orders
     * 
     * @param array $orders 
     * @return array [$order_id => $trackingNumber]
     */
    public function isTrackingOrders($orders)
    {
        if (!is_array($orders) || count($orders) <= 0) {
            throw new waExeption('Некорректный массив для проверки трекинга');
            return false;            
        } else {
            $result = array();
            $model = new shopOrderModel();
            foreach ($orders as $order_id) {
                $sql = "SELECT `value` FROM `shop_order_params` WHERE `order_id`='$order_id' AND `name`='tracking_number'";
                $trackingNumber = $model->query($sql)->fetchAll();
                if ($trackingNumber) {
                    $result[$order_id] = $trackingNumber[0]['value'];
                }
            }
            if (count($result) > 0) {
                return $result;
            } else {
                return false;
            }
        }   
    }

    /**
     * Получение трекинг истории в виде json-строки для указанного трек-номера
     * 
     * @param string $trackingNumber 
     * @return string json[[tracking]]
     */
    public function getTrackingByNumber($trackingNumber)
    {
        $model = new waModel();
        $sql = "SELECT `data`, `t`  FROM `wa_shipping_rupost_tracking` WHERE `id`='$trackingNumber'";
        $trackingNumber = $model->query($sql)->fetchAll();
        if ($trackingNumber) {
            return $trackingNumber[0]['data'];
        } else {
            return false;
        }       
    }


    /**
     * Проверка соответствия последнего статуса из трек-истории $trackingHistory указанному $status
     * 
     * @param string $status 
     * @param string $trackingHistory - json_string
     * @return bool true|false
     */
    public function checkTrackingStatus($status, $trackingHistory)
    {
        $array = json_decode($trackingHistory);
        if ($status == $array[count($array)-1][3]) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Вспомогательная функция для отладки
     * 
     * @param mixed $text 
     * @return mixed 
     */
    public function lg($text)
    {
        if (is_array($text)) {
            file_put_contents('log.txt', print_r($text, true));
        } else {
            file_put_contents('log.txt', $text);
        }
    }    
}
