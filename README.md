The Yii Tracy Panel Database package is a panel for [Yii Tracy](https://github.com/beastbytes/yii-tracy)
(integration of the [Tracy debugging tool](https://tracy.nette.org/)into [Yii3](https://www.yiiframework.com/))
that provides information from the [Yii Database Library](https://github.com/yiisoft/db)
about the database and executed queries.

## Requirements
- PHP 8.1 or higher

## Installation
Install the package using [Composer](https://getcomposer.org):

Either:
```shell
composer require-dev beastbytes/yii-tracy-panel-database
```
or add the following to the `require-dev` section of your `composer.json`
```json
"beastbytes/yii-tracy-panel-database": "<version_constraint>"
```

## Information Displayed
#### Tab
Shows the number of queries executed.

#### Panel
Shows the database DSN and lists the queries executed. Each query shows:
* SQL
* Query parameters
* Execution time

## License
The BeastBytes Yii Tracy Panel Database package is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.