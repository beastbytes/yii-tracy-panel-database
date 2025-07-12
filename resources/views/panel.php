<?php

use BeastBytes\Yii\Tracy\Panel\Database\Helper;

/**
 * @var string $dsn
 * @var array $queries
 * @psalm-var array{
 *  position: int,
 *  transactionId: string,
 *  sql: string,
 *  rawSql: string,
 *  params: array,
 *  line: int,
 *  status: string,
 *  rowsNumber: int,
 *  actions: list{
 *       array{
 *           action: string,
 *           time: float,
 *       },
 *       array{
 *           action: string,
 *           time: float,
 *       }
 *  }
 * } $queries
 * @var TranslatorInterface $translator
 */

use BeastBytes\Yii\Tracy\Panel\Database\Panel;
use Yiisoft\Translator\TranslatorInterface;

$translator = $translator->withDefaultCategory(Panel::MESSAGE_CATEGORY);
?>

<h2>DSN: <?= $dsn ?></h2>
<h2><?= $translator->translate('database.heading.queries') ?></h2>
<?php if (empty($queries)): ?>
    <div><?= $translator->translate('database.no-queries') ?></div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>SQL</th>
                <th><?= $translator->translate('database.heading.parameters') ?></th>
                <th><?= $translator->translate('database.heading.rows') ?></th>
                <th><?= $translator->translate('database.heading.time') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($queries as $query): ?>
            <tr>
                <td><?= $query['position'] ?></td>
                <td><?= Helper::highlight($query['sql']) ?></td>
                <td>
                    <?php if (!empty($query['params'])): ?>
                    <ul>
                        <?php foreach ($query['params'] as $param => $value): ?>
                        <li><?= "$param&nbsp;=&nbsp;$value" ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </td>
                <td><?= $query['rowsNumber'] ?></td>
                <td><?= round(
                    ($query['actions'][1]['time'] - $query['actions'][0]['time']) * 1000,
                    3,
                    PHP_ROUND_HALF_EVEN
                ) . '&nbsp;ms'?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>