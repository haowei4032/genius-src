<?php

return [

    'alpha' => [

        'parameters' => [
            'timezone' => 'Asia/Shanghai'
        ],

        'components' => [

            'log' => [
                'class' => 'Genius\Utils\Log',
            ],

            'url' => [
                '/<id:\d+>.html' => 'index/index'
            ]
        ]

    ]

];