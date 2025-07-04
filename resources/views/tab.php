<?php
/**
 * @var array $queries
 * @psalm-var array{
 *     error: int,
 *     total: int
 * } $queries
 * @var TranslatorInterface $translator
 */

use BeastBytes\Yii\Tracy\Panel\Database\Panel;
use Yiisoft\Translator\TranslatorInterface;

$translator = $translator->withDefaultCategory(Panel::MESSAGE_CATEGORY);

echo $translator->translate('database.queries', ['total' => $queries['total']]);