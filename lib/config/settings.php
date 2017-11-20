<?php

return array(
    'enabled' => array(
        'title' => 'Вкл/выкл',
        'value' => '1',
        'control_type' => waHtmlControl::CHECKBOX,
        'description' => 'активность плагина',
    ),
    'send_email' => array(
        'title' => 'Отрпавлять отчет',
        'value' => '1',
        'control_type' => waHtmlControl::CHECKBOX,
        'description' => 'отправка отчета на указанный email',
    ),
    'emailto' => array(
        'title' => 'Куда слать отчет',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
        'description' => 'email получателя отчетов',
    ),  
    'emailfrom' => array(
        'title' => 'От кого',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
        'description' => 'email отправителя отчетов (что-нибудь на домене магазина)',
    ),    
    'state' => array(
        'title' => 'Статус заказа',
        'value' => '',
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                'value' => 'paid',
                'title' => 'Оплачен',
            ),
            array(
                'value' => 'shipped',
                'title' => 'Отправлен',
            ),
            array(
                'value' => 'completed',
                'title' => 'Выполнен',
            ),
        ),        
        'description' => 'статус заказа для проверки',
    ),
    'tracking_status' => array(
        'title' => 'Трекинг статус',
        'value' => 'Delivery',
        'control_type' => waHtmlControl::INPUT,
        'description' => 'последний статус в истории трекинга, при котором плагин сработает',
    ),
    'error_delivery' => array(
        'title' => 'Зависшие доставки',
        'value' => '1',
        'control_type' => waHtmlControl::CHECKBOX,
        'description' => 'поиск зависших отправлений',
    ),
    'error_days' => array(
        'title' => 'Срок для беспокойства',
        'value' => '45',
        'control_type' => waHtmlControl::INPUT,
        'description' => 'количество дней, после которого отправление считается зависшим',
    ),        
    'is_update' => array(
        'title' => 'Обновлять старый трекинг',
        'value' => '1',
        'control_type' => waHtmlControl::CHECKBOX,
        'description' => 'если история трекинга устарела, запрашивается свежая инфа с трекинга почты',
    ),
    'update_inerval' => array(
        'title' => 'Срок свежести (мин)',
        'value' => '60',
        'control_type' => waHtmlControl::INPUT,
        'description' => 'количество минут, после которого история трекинга считается устаревшей',
    ),
    'api_login' => array(
        'title' => 'Логин',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
        'description' => 'логин api трекинга почты',
    ),
    'api_password' => array(
        'title' => 'Пароль',
        'value' => '',
        'control_type' => waHtmlControl::PASSWORD,
        'description' => 'пароль api трекинга почты',
    ),
    'cron' => array(
        'title' => 'cron string',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
        'readonly' => true,
        'size' => 200,
        'description' => 'если тут пусто, сохраните настройки и зайдите сюда заново',
    ),                     
);