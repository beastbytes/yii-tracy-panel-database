<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Tracy\Panel\Database;

use BeastBytes\Yii\Tracy\Panel\ProxyCollectorPanel;
use BeastBytes\Yii\Tracy\ProxyContainer;
use BeastBytes\Yii\Tracy\ViewTrait;
use Yiisoft\Db\Connection\ConnectionInterface;

final class Panel extends ProxyCollectorPanel
{
    use ViewTrait;

    public const MESSAGE_CATEGORY = 'tracy-database';

    private const BINARY_COLUMN = 'binary';

    private const COLOUR_NO_QUERIES = '#404040';
    private const COLOUR_QUERIES = '#5d0ec0';

    private const ICON = <<<ICON
<svg 
    xmlns="http://www.w3.org/2000/svg" 
    height="24px" 
    viewBox="0 -960 960 960" 
    width="24px" 
    fill="%s"
>
    <path
        d="M480-120q-151 0-255.5-46.5T120-280v-400q0-66 105.5-113T480-840q149 0 254.5 47T840-680v400q0 67-104.5 
        113.5T480-120Zm0-479q89 0 179-25.5T760-679q-11-29-100.5-55T480-760q-91 0-178.5 25.5T200-679q14 30 101.5 
        55T480-599Zm0 199q42 0 81-4t74.5-11.5q35.5-7.5 67-18.5t57.5-25v-120q-26 14-57.5 25t-67 18.5Q600-528 561-524t-81 
        4q-42 0-82-4t-75.5-11.5Q287-543 256-554t-56-25v120q25 14 56 25t66.5 18.5Q358-408 398-404t82 4Zm0 200q46 0 
        93.5-7t87.5-18.5q40-11.5 67-26t32-29.5v-98q-26 14-57.5 25t-67 18.5Q600-328 561-324t-81 4q-42 
        0-82-4t-75.5-11.5Q287-343 256-354t-56-25v99q5 15 31.5 29t66.5 25.5q40 11.5 88 18.5t94 7Z"
/>
</svg>
ICON;

    private array $tableSchemas = [];

    protected function panelParameters(): array
    {
        $panelParameters = $this->getCollected();

        $connection = $this->container->get(ProxyContainer::BYPASS . ConnectionInterface::class);
        $panelParameters['dsn'] = $connection->getDriver()->getDsn();

        $this->tableSchemas = $connection
            ->getSchema()
            ->getTableSchemas()
        ;

        foreach ($panelParameters['queries'] as &$query) {
            foreach ($query['params'] as $param => $value) {
                if ($this->isBinary($param, $query['sql'])) {
                    $query['params'][$param] = 'HEX2BIN(' . bin2hex($value) . ')';
                }
            }
        }

        return $panelParameters;
    }

    protected function panelTitle(): array
    {
        return [
            'id' => 'database.title.panel',
            'category' => self::MESSAGE_CATEGORY,
        ];
    }

    protected function tabIcon(array $parameters): string
    {
        return sprintf(
            self::ICON,
            $parameters['queries']['total'] === 0 ? self::COLOUR_NO_QUERIES : self::COLOUR_QUERIES,
        );
    }

    protected function tabParameters(): array
    {
        return $this->getSummary();
    }

    protected function tabTitle(): array
    {
        return [
            'id' => 'database.title.tab',
            'category' => self::MESSAGE_CATEGORY,
        ];
    }

    private function isBinary(string $param, string $sql): bool
    {
        $matches = [];
        if (preg_match(sprintf('|\s((\W?\w+\W?\.)?\W?\w+\W?)=.?%s|', $param), $sql, $matches) === 1) {
            if (str_contains($matches[1], '.')) { // table alias
                [$tableAlias, $columnName] = explode('.', $matches[1]);
                preg_match(sprintf('|\W?(\w+)\W?\s.?%s.?\s|', $tableAlias), $sql, $matches);
            } else { // not a table alias so get the table name from SQL FROM clause
                preg_match('|(\w+)|', $matches[1], $matches);
                $columnName = $matches[1];
                preg_match('|FROM\s\W?(\w+)\W?\s|', $sql, $matches);
            }

            $tableName = $matches[1];

            foreach ($this->tableSchemas as $tableSchema) {
                if ($tableSchema->getName() === $tableName) {
                    foreach ($tableSchema->getColumns() as $column) {
                        if ($column->getName() === $columnName) {
                            return $column->getType() === self::BINARY_COLUMN;
                        }
                    }
                }
            }
        }

        return false;
    }
}