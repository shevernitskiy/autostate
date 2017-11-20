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
        $settings['cron'] = 'php '.wa()->getConfig()->getPath('root').DIRECTORY_SEPARATOR.'cli.php shop AutostateCheck';
        parent::saveSettings($settings);  
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
            $model = new shopOrderModel();
            $sql = "SELECT `id` FROM `shop_order` WHERE `state_id`='$state'";
            $orderList = $model->query($sql)->fetchAll();
            if ($orderList) {
                return array_column($orderList, 'id');
            } else {
                return false;
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
        $result = $model->query($sql)->fetchAll();
        if ($result) {
            if (((strtotime('now') - strtotime($result[0]['t']))/60 > self::getPluginSettings()['update_inerval']) && self::getPluginSettings()['is_update']) {
                $status = self::trackingRequest($trackingNumber);
                $t = date("Y-m-d H:i:s");
                $data = json_encode($status);
                $sql = "UPDATE `wa_shipping_rupost_tracking` SET `t`='$t', `data`='$data' WHERE `id`='$trackingNumber'";
                $model->exec($sql);
                return $data;
            } else {
                return $result[0]['data'];
            }
        } else {
            return false;
        }       
    }

    /**
     * Запрос истории у сервиса трекинга
     * 
     * @param string $tracking_id 
     * @return array history 
     */
    public function trackingRequest($tracking_id)
    {
        $wsdlurl = 'https://tracking.russianpost.ru/rtm34?wsdl';
        $client = '';
        try {
            $client = new SoapClient($wsdlurl, array('trace' => 1, 'soap_version' => SOAP_1_2));
            $params = array (
                'OperationHistoryRequest' => array (
                    'Barcode' => $tracking_id,
                    'MessageType' => '0',
                    'Language' => 'ENG'),
                'AuthorizationHeader' => array (
                    'login' => self::getPluginSettings()['api_login'],
                    'password'=> self::getPluginSettings()['api_password']
                )
            );
            $result = $client->getOperationHistory(new SoapParam($params, 'OperationHistoryRequest'));
        } catch (SoapFault $ex) {
            return 'При запросе произошла ошибка: '.$ex->getMessage();
        }
        $array = array();
        foreach ($result->OperationHistoryData->historyRecord as $key => $record) {
            $array[$key] = array(
                waDateTime::format('datetime', $record->OperationParameters->OperDate),
                $record->AddressParameters->CountryOper->Code2A,
                $record->AddressParameters->OperationAddress->Description,
                $record->OperationParameters->OperType->Name
            );
        }
        return $array;
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
     * Проверка срока доставки с момента первой регистрации 
     * @param mixed $limit 
     * @param mixed $trackingHistory 
     * @return mixed 
     */
    public function checkTrackingTime($trackingHistory)
    {
        $array = json_decode($trackingHistory);
        if (isset($array[0][0])) {
            $interval = ((strtotime('now') - strtotime($array[0][0])) / 86400);
            if ($interval > 0) {
                return round($interval,2);
            }
        }
        return 0;
    }    

    public function getPluginSettings()
    {
        $model = new waAppSettingsModel();
        $sql = "SELECT * from `wa_app_settings` WHERE `app_id`='shop.autostate'";
        $settings = array_column($model->query($sql)->fetchAll(), 'value', 'name');        
        return $settings;        
    }
    
    public function sendStateEmail($subject, $body)
    {
        $settings = self::getPluginSettings();
        if ($settings['send_email'] && $settings['emailto']) {
            $mail_message = new waMailMessage($subject, $body, 'text/plain');
            $mail_message->setFrom($settings['emailfrom'], 'Плагин Autostate');
            $mail_message->setTo($settings['emailto'], 'manager');
            $mail_message->send();
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
