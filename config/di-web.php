<?php

declare(strict_types=1);

use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;

$category = 'tracy-database';
$messageSource = dirname(__DIR__) . '/resources/messages';

return [
    "translation.$category" => [
        'definition' => static fn(string $category, string $messageSource): CategorySource => new CategorySource(
            $category,
            new MessageSource($messageSource),
            new IntlMessageFormatter(),
            new MessageSource($messageSource),
        ),
        'tags' => ['translation.categorySource'],
    ],
];