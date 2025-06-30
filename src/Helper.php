<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Tracy\Panel\Database;

use DateTime;
use PDO;

final class Helper
{
    /**
     * KEYWORDS1.
     *
     * @var string
     */
    const KEYWORDS1 = 'SELECT|(?:ON\s+DUPLICATE\s+KEY)?UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|CALL|UNION|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE';

    /**
     * KEYWORDS2.
     *
     * @var string
     */
    const KEYWORDS2 = 'ALL|DISTINCT|DISTINCTROW|IGNORE|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|[RI]?LIKE|REGEXP|TRUE|FALSE';

    /**
     * Returns syntax highlighted SQL command.
     *
     * @param string $sql
     * @param array $bindings
     * @param \PDO $pdo
     * @return string
     */
    public static function highlight($sql, array $bindings = [], PDO $pdo = null)
    {
        // insert new lines
        $sql = " $sql ";
        $sql = preg_replace('#(?<=[\\s,(])(' . static::KEYWORDS1 . ')(?=[\\s,)])#i', "\n\$1", $sql);

        // reduce spaces
        $sql = preg_replace('#[ \t]{2,}#', ' ', $sql);

        // syntax highlight
        $sql = htmlspecialchars($sql, ENT_IGNORE, 'UTF-8');
        $sql = preg_replace_callback(
            '#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])('
                . static::KEYWORDS1
                . ')(?=[\\s,)])|(?<=[\\s,(=])('
                . static::KEYWORDS2
                . ')(?=[\\s,)=])#is'
            ,
            function ($matches) {
                if (!empty($matches[1])) { // comment
                    return '<em style="color:grey">' . $matches[1] . '</em>';
                } elseif (!empty($matches[2])) { // error
                    return '<strong style="color:red">' . $matches[2] . '</strong>';
                } elseif (!empty($matches[3])) { // most important keywords
                    return '<strong style="color:blue; text-transform: uppercase;">' . $matches[3] . '</strong>';
                } elseif (!empty($matches[4])) { // other keywords
                    return '<strong style="color:green">' . $matches[4] . '</strong>';
                }
            },
            $sql
        );

        $sql = preg_replace('#=(:(\w+))#', '<span style="color:purple">=$1</span>', $sql);

        $bindings = array_map(function ($binding) use ($pdo) {
            if (is_array($binding) === true) {
                $binding = implode(', ', array_map(function ($value) {
                    return is_string($value) === true
                        ? htmlspecialchars('\'' . $value . '\'', ENT_NOQUOTES, 'UTF-8')
                        : $value
                    ;
                }, $binding));

                return htmlspecialchars('(' . $binding . ')', ENT_NOQUOTES, 'UTF-8');
            }

            if (
                is_string($binding) === true
                && (
                    preg_match('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u', $binding)
                    || preg_last_error()
                )) {
                return '<i title="Length ' . strlen($binding) . ' bytes">&lt;binary&gt;</i>';
            }

            if (is_string($binding) === true) {
                $text = htmlspecialchars(
                    $pdo
                        ? $pdo->quote($binding)
                        : '\'' . $binding . '\''
                    ,
                    ENT_NOQUOTES,
                    'UTF-8'
                );

                return '<span title="Length ' . strlen($text) . ' characters">' . $text . '</span>';
            }

            if (is_resource($binding) === true) {
                $type = get_resource_type($binding);
                if ($type === 'stream') {
                    $info = stream_get_meta_data($binding);
                }

                return '<i'
                    . (isset($info['uri'])
                        ? ' title="' . htmlspecialchars($info['uri'], ENT_NOQUOTES, 'UTF-8') . '"'
                        : null
                    )
                    . '>&lt;' . htmlspecialchars($type, ENT_NOQUOTES, 'UTF-8') . ' resource&gt;</i>';
            }

            if ($binding instanceof DateTime) {
                return htmlspecialchars(
                    '\'' . $binding->format('Y-m-d H:i:s') . '\'',
                    ENT_NOQUOTES,
                    'UTF-8'
                );
            }

            return htmlspecialchars((string)$binding, ENT_NOQUOTES, 'UTF-8');
        }, $bindings);
        $sql = str_replace(['%', '?'], ['%%', '%s'], $sql);

        return '<div><code>' . nl2br(trim(vsprintf($sql, $bindings))) . '</code></div>';
    }

    /**
     * explain sql.
     *
     * @param PDO $pdo
     * @param string $sql
     * @param array $bindings
     * @return array
     */
    public static function explain(PDO $pdo, $sql, $bindings = [])
    {
        $explains = [];
        if (preg_match('#\s*\(?\s*SELECT\s#iA', $sql)) {
            $statement = $pdo->prepare('EXPLAIN ' . $sql);
            $statement->execute($bindings);
            $explains = $statement->fetchAll(PDO::FETCH_CLASS);
        }

        return $explains;
    }
}