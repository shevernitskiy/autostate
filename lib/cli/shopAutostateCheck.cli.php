<?php

class shopAutostateCheckCli extends waCliController
{
    public function execute()
    {
        $settings =  shopAutostatePlugin::getPluginSettings();
        $orders = shopAutostatePlugin::getStateOrders($settings['state']);
        if (!$orders) {
            return false;
        } else {
            $tn = shopAutostatePlugin::isTrackingOrders($orders);
            if (!$tn) {
                return false;
            } else {
                $completed = array();
                $failed = array();
                $workflow = new shopWorkflow();
                foreach ($tn as $order_id => $trackingNumber) {
                    $history = shopAutostatePlugin::getTrackingByNumber($trackingNumber);
                    if (shopAutostatePlugin::checkTrackingStatus($settings['tracking_status'], $history)) {
                        $workflow->getActionById('complete')->run($order_id);
                        $completed[] = $order_id;
                    }
                    if ($settings['error_delivery']) {
                        $interval = shopAutostatePlugin::checkTrackingTime($history);
                        if ($interval > $settings['error_days']) {
                            if (array_search($order_id, $completed) === false) {
                                $failed[] = $order_id;
                            }
                        }
                    }
                }
                if ($settings['send_email'] && (count($completed) > 0 || count($failed) > 0)) {
                    $subject = 'Статус заказов изменен';
                    $body = 'При вызове cli скрипта плагина Autostate произошли следующие события:'.PHP_EOL.PHP_EOL;
                    if (count($completed) > 0) {
                        foreach ($completed as $o_id) {
                            $body .= '- заказ '.$o_id.' доставлен и помещен в завершенные'.PHP_EOL;
                        }
                    }
                    if (count($failed) > 0) {
                        foreach ($failed as $o_id) {
                            $body .= '- заказ '.$o_id.' очень долго доставляется, стоит проверить'.PHP_EOL;
                        }
                    }                    
                    $body .= PHP_EOL.'Робот поработал за тебя, так что  жизнь прекрасна!:)';
                    shopAutostatePlugin::sendStateEmail($subject, $body);
                }
            }
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