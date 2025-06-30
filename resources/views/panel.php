<?php

use BeastBytes\Yii\Tracy\Helper;

/**
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
 * @var array $transactions
 * @psalm-var array{
 *  id: string,
 *  position: int,
 *  status: string,
 *  line: int,
 *  level: int,
 *  actions: list{
 *      array{
 *          action: string,
 *          time: float,
 *      },
 *      array{
 *          action: string,
 *          line: int,
 *          time: float,
 *      }
 *  }
 * } $transactions
 */
?>

<h2>DSN: <?= $dsn ?></h2>
    
<h2>Queries</h2>
<?php if (empty($queries)): ?>
    <div>No queries</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>SQL</th>
                <th>Parameters</th>
                <th>Rows</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($queries as $query): ?>
            <tr>
                <td class="yt_text-r"><?= $query['position'] ?></td>
                <td><?= Helper::highlight($query['sql']) ?></td>
                <td>
                    <?php foreach ($query['params'] as $param => $value): ?>
                        <div><?= "$param&nbsp;$value" ?></div>
                    <?php endforeach; ?>
                </td>
                <td class="yt_text-r"><?= $query['rowsNumber'] ?></td>
                <td class="yt_text-r"><?=
                    round(
                        ($query['actions'][1]['time'] - $query['actions'][0]['time']) * 1000,
                        3,
                        PHP_ROUND_HALF_EVEN
                    )
                    . '&nbsp;ms'
                ?></td>
            </tr>
        <?php endforeach; ?>    
        </tbody>
    </table>
<?php endif; ?>

<h2>Transactions</h2>
<?php if (empty($transactions)): ?>
    <div>No transactions</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Level</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td class='yt_text-r'><?= $transaction['position'] ?></td>
                <td class='yt_text-r'><?= $transaction['level'] ?></td>
                <td class='yt_text-r'><?=
                    round(
                        ($transaction['actions'][1]['time'] - $transaction['actions'][0]['time']) * 1000,
                        3,
                        PHP_ROUND_HALF_EVEN
                    )
                    . '&nbsp;ms'
                ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>