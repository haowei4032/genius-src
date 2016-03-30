<?php

/**
 * @link http://genius.haowei.me
 * @copyright Copyright (c) 2015 Genius Software LLC
 * @license http://genius.haowei.me/license
 */

return [

    'alpha' => [

        'parameters' => [
            'timezone' => 'Asia/Shanghai'
        ],

        'components' => [

            'log' => [
                'class' => 'Genius\Utils\Log',
                'path' => '~/runtime/log',
                'name' => '%y-%m-%d.log'
            ],

            'url' => [
                '<id:\d+>.html' => 'index/index'
            ]
        ]

    ]

];