<?php
/**
 * @var array $queries
 * @psalm-var array{
 *     error: int,
 *     total: int
 * } $queries
 * @var TranslatorInterface $translator
 */

use Yiisoft\Translator\TranslatorInterface;

echo $translator->translate('database.queries', ['total' => $queries['total']]);