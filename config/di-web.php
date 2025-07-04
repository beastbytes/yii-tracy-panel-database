<?php

declare(strict_types=1);

use BeastBytes\Yii\Tracy\Panel\Database\Panel;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;

$category = Panel::MESSAGE_CATEGORY;
$messageSource = dirname(__DIR__) . '/resources/messages';

return [
    "translation.$category" => [
        'definition' => static function() use ($category, $messageSource)  {
            return new CategorySource(
                $category,
                new MessageSource($messageSource),
                new IntlMessageFormatter(),
                new MessageSource($messageSource),
            );
        },
        'tags' => ['translation.categorySource'],
    ],
];