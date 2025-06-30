<?php

declare(strict_types=1);

use BeastBytes\Yii\Tracy\Panel\Database\Panel as DatabasePanel;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Debug\ConnectionInterfaceProxy;
use Yiisoft\Db\Debug\DatabaseCollector;
use Yiisoft\Definitions\Reference;

return [
    'beastbytes/yii-tracy' => [
        'panels' => [
            'database' => [
                'class' => DatabasePanel::class,
                '__construct()' => [
                    Reference::to(DatabaseCollector::class),
                    [
                        ConnectionInterface::class => Reference::to(ConnectionInterfaceProxy::class),
                    ],
                ],
            ],
        ],
    ],
];