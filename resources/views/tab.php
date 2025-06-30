<?php
/**
 * @var array $queries
 * @psalm-var array{
 *     error: int,
 *     total: int
 * } $queries
 * @var array $transactions
 * @psalm-var array{
 *      error: int,
 *      total: int
 * } $transactions
 */

if (empty($queries)) {
    echo '0 queries';
} else {
    echo sprintf('%d %s', $queries['total'], $queries['total'] === 1 ? 'query' : 'queries');
}