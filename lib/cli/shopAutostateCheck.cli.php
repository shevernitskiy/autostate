<?php

class shopAutostateCheckCli extends waCliController
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $sql = "SELECT * from `wa_app_settings` WHERE `app_id`='shop.autostate'";
        $settings = array_column($model->query($sql)->fetchAll(), 'value', 'name');
        $orders = shopAutostatePlugin::getStateOrders($settings['state']);
        if (!$orders) {
            return false;
        } else {
            $tn = shopAutostatePlugin::isTrackingOrders($orders);
            if (!$tn) {
                return false;
            } else {
                $completed = array();
                $workflow = new shopWorkflow();
                foreach ($tn as $order_id => $trackingNumber) {
                    $history = shopAutostatePlugin::getTrackingByNumber($trackingNumber);
                    if (shopAutostatePlugin::checkTrackingStatus($settings['tracking_status'], $history)) {
                        $workflow->getActionById('complete')->run($order_id);
                        $completed[] = $order_id;
                    }
                }
                if (count($completed) > 0) {
                    if ($settings['send_email'] && $settings['email']) {
                        // ТУТ ОТПРАВКА УВЕДОМЛЕНИЯ ОБ УСПЕШНОМ ПЕРЕНОСЕ
                        $subject = 'Статус заказов изменен';
                        $body = 'При вызове cli скрипта плагина Autostate были изменены статусы:'.PHP_EOL;
                        foreach ($completed as $o_id) {
                            $body .= 'Заказ '.$o_id.' - completed'.PHP_EOL;
                        }
                        $body .= PHP_EOL.'Робот поработал за тебя, так что  жизнь прекрасна!:)';
                        $mail_message = new waMailMessage($subject, $body, 'text/plain');
                        $mail_message->setFrom('hwork@list.com', 'Плагин Autostate');
                        $mail_message->setTo($settings['email'], 'manager');
                        $mail_message->send();
                    }

                }
            }
        }


        $this->lg($body);
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