<?php

/**
 * User: Howay
 * Date: 2015/12/8 0008
 * Time: 23:41
 */

return [

    'alpha' => [

        'parameters' => [
            'timezone' => 'Asia/Shanghai'
        ],

        'components' => [

            'log' => [
                'class' => 'Genius\Log\File',
            ],

            'url' => [

                'enablePrettyUrl' => true,
                'showScriptName' => false,
                'rules' => [

                    '/abc' => 'index/index',
                    '/ccc' => 'bbb/ccc'

                ]
            ]
        ]

    ]

];