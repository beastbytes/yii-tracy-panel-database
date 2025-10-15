<?php

namespace BeastBytes\Yii\Tracy\Panel\Database\Tests;

use BeastBytes\Yii\Tracy\ContainerProxy;
use BeastBytes\Yii\Tracy\Panel\Database\Helper;
use BeastBytes\Yii\Tracy\Panel\Database\Panel;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Debug\ConnectionInterfaceProxy;
use Yiisoft\Db\Debug\DatabaseCollector;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;
use Yiisoft\Db\Sqlite\Dsn;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\Translator;
use Yiisoft\View\View;

class PanelTest extends TestCase
{
    private const COLOUR_NO_QUERIES = '#404040';
    private const COLOUR_QUERIES = '#5d0ec0';
    private const PANEL = <<<HTML
<h1>Database</h1>
<div class="tracy-inner"><div class="tracy-inner-container">
<h2>DSN: %s</h2>
<h2>Queries</h2>
    %s
</div></div>
HTML;
    private const NO_QUERIES = '<div>No queries</div>';

    private const QUERIES = <<<HTML
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
%s
    </tbody>
</table>
HTML;
    private const TAB = <<<TAB
<span title="Database"><svg 
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
</svg><span class="tracy-label">%s</span></span>
TAB;

    private const LOCALE = 'en-GB';

    private static ?ContainerInterface $container = null;
    private static Dsn $dsn;
    private static ?ContainerInterface $containerProxy = null;

    private ?Panel $panel = null;

    #[BeforeClass]
    public static function setUpBeforeClass(): void
    {
        self::$dsn = new Dsn('sqlite', __DIR__ . '/Support/test.sqlite');

        if (self::$container === null) {
            self::$container = new SimpleContainer([
                ConnectionInterface::class => new Connection(
                    new Driver(self::$dsn),
                    new SchemaCache(new FileCache(__DIR__ . '/Support/runtime/cache')),
                ),
                View::class => (new View())
                    ->setParameter(
                        'translator',
                        (new Translator())
                            ->withLocale(self::LOCALE)
                            ->addCategorySources(new CategorySource(
                                Panel::MESSAGE_CATEGORY,
                                new MessageSource(
                                    dirname(__DIR__)
                                    . DIRECTORY_SEPARATOR . 'resources'
                                    . DIRECTORY_SEPARATOR . 'messages',
                                ),
                                new IntlMessageFormatter(),
                            )),
                    )
                ,
            ]);
        }
    }

    #[After]
    public function tearDown(): void
    {
        $this->panel->shutdown();
    }

    #[Before]
    public function setUp(): void
    {
        if ($this->panel === null) {
            $collector = new DatabaseCollector();
            $this->panel = (new Panel(
                $collector,
                [
                    ConnectionInterface::class => new ConnectionInterfaceProxy(
                        self::$container->get(ConnectionInterface::class),
                        $collector,
                    ),
                ],
            ));

            self::$containerProxy = new ContainerProxy(self::$container);
            $this->panel = $this->panel->withContainer(self::$containerProxy);
            $this->panel->startup();
        }
    }

    #[Test]
    public function viewPath(): void
    {
        $this->assertSame(
            dirname(__DIR__)
            . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,
            $this->panel->getViewPath());
    }

    #[DataProvider('queryProvider')]
    #[Test]
    public function queries(array|string $queries, array $parameters, array $tab, array $panel): void
    {
        $this->executeQueries($queries, $parameters);
        $this->assertSame(sprintf(self::TAB, $tab[0], $tab[1]), $this->panel->getTab());

        $this->assertStringMatchesFormat(
            $this->stripWhitespace(sprintf(
                self::PANEL,
                self::$dsn,
                $this->parsePanel($panel)
            )),
            $this->stripWhitespace($this->panel->getPanel())
        );
    }

    public static function queryProvider(): array
    {
        return [
            'No queries' => [
                'queries' => '',
                'parameters' => [],
                'tab' => [self::COLOUR_NO_QUERIES, '0 Queries'],
                'panel' => [],
            ],
            'Single query' => [
                'queries' => 'SELECT * FROM {{%user}} WHERE [[id]] = :id',
                'parameters' => [':id' => 1],
                'tab' => [self::COLOUR_QUERIES, '1 Query'],
                'panel' => [
                    [
                        'SELECT * FROM "user" WHERE "id" = :id',
                        [':id' => 1],
                        1
                    ]
                ],
            ],
            'Multiple queries' => [
                'queries' => [
                    'SELECT * FROM {{%user}} WHERE [[id]] = :id',
                    'SELECT * FROM {{%user}} WHERE [[id]] < :id',
                    'SELECT * FROM {{%user}} WHERE [[id]] > :id',
                ],
                'parameters' => [
                    [':id' => 1],
                    [':id' => 3],
                    [':id' => 2],
                ],
                'tab' => [self::COLOUR_QUERIES, '3 Queries'],
                'panel' => [
                    [
                        'SELECT * FROM "user" WHERE "id" = :id',
                        [':id' => 1],
                        1
                    ],
                    [
                        'SELECT * FROM "user" WHERE "id" < :id',
                        [':id' => 3],
                        2
                    ],
                    [
                        'SELECT * FROM "user" WHERE "id" > :id',
                        [':id' => 2],
                        4
                    ],
                ],
            ],
        ];
    }

    /**
     * $queries is string && $parameters is parameter array - query executed once with the parameter array
     * $queries is string && $parameters is array of parameter arrays - query executed with each parameter array
     * $queries ia array && $parameters is parameter array - each query is executed with the parameter array
     * $queries ia array && $parameters is array of parameter arrays - each query is executed with the corresponding parameter array
     * @param string|string[] $queries A query string or an array of query strings
     * @param array|array[] $parameters A parameter array, or an array of parameter arrays
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     * @throws Exception
     * @throws InvalidConfigException
     */
    private function executeQueries(array|string $queries, array $parameters): void
    {
        $connection = self::$containerProxy->get(ConnectionInterface::class);
        $connection->open();

        if (is_string($queries)) {
            if (!empty($parameters) && is_string(array_keys($parameters)[0])) {
                $connection->createCommand($queries, $parameters)->queryAll();
            } else {
                foreach ($parameters as $params) {
                    $connection->createCommand($queries, $params)->queryAll();
                }
            }
        } else {
            foreach ($queries as $n => $query) {
                if (!empty($parameters) && is_string(array_keys($parameters)[0])) {
                    $connection->createCommand($query, $parameters)->queryAll();
                } else {
                    $connection->createCommand($query, $parameters[$n])->queryAll();
                }
            }
        }

        $connection->close();
    }

    private function parsePanel(array $panel): string
    {
        if (empty($panel)) {
            return self::NO_QUERIES;
        }

        $tbody = '';
        foreach ($panel as $i => $query) {
            $tbody .= strtr(
                <<<ROW
            <tr>
                <td>{i}</td>
                <td>{query}</td>
                <td>{parameters}</td>
                <td>{rows}</td>
                <td>%f&nbsp;ms</td>
            </tr>
ROW,
                [
                    '{i}' => $i,
                    '{query}' => Helper::highlight($query[0]),
                    '{parameters}' => $this->parameters2String($query[1]),
                    '{rows}' => $query[2],
                ]
            );
        }
        return sprintf(self::QUERIES, $tbody);
    }

    private function parameters2String(array $parameters): string
    {
        $result = [];
        foreach ($parameters as $param => $value) {
            $result[] = sprintf('%s&nbsp;=&nbsp;%s', $param, $value);
        }
        return empty($result)
            ? ''
            : '<ul><li>' . implode("</li><li>", $result) . "</li></ul>";
    }

    private function stripWhitespace(string $string): string
    {
        return preg_replace('/>\s+</', '><', $string);
    }
}