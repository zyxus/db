<?php

namespace Zyxus\DB;

use \PDO;
use \PDOStatement;
use \PDOException;

class DB
{
    protected static $instance = null;

    public static $DB_HOST;
    public static $DB_NAME;
    public static $DB_USER;
    public static $DB_PASS;
    public static $DB_SOCKET;
    public static $DB_CHAR;

    protected static $table = 'table';
    protected static $fields = [];
    protected static $where = [];
    protected static $whereRaw = [];
    protected static $join = [];
    protected static $order = [];
    protected static $limit = '';

//    final private function __construct()
    public function __construct($host, $user, $pass, $db, $socket = '', $char = 'utf-8')
    {
        self::$DB_HOST = $host;
        self::$DB_NAME = $db;
        self::$DB_USER = $user;
        self::$DB_PASS = $pass;
        self::$DB_SOCKET = $socket;
        self::$DB_CHAR = $char;
    }

    final private function __clone()
    {
    }

    public static function instance()
    {
        if (self::$instance === null) {

            $opt = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => TRUE,
//                PDO::ATTR_STATEMENT_CLASS => array('myPDOStatement'),
                PDO::ATTR_STATEMENT_CLASS => array('PDOStatement'),
//                PDOExtended::ATTR_STRICT_MODE => false,
            );
            if (isset(self::$DB_SOCKET) && self::$DB_SOCKET != "") {
                $dsn = 'mysql:unix_socket=' . self::$DB_SOCKET . ';dbname=' . self::$DB_NAME . ';charset=' . self::$DB_CHAR;
            } else {
                $dsn = 'mysql:host=' . self::$DB_HOST . ';dbname=' . self::$DB_NAME . ';charset=' . self::$DB_CHAR;
            }

            self::$instance = new PDO($dsn, self::$DB_USER, self::$DB_PASS, $opt);
            self::query('SET NAMES ' . self::$DB_CHAR);
        }
        return self::$instance;
    }

    public static function __callStatic($method, $args)
    {
        return self::instance()->$method($args);
    }

    public static function table($table)
    {
        self::$table = $table;
        self::$fields = [];
        self::$where = [];
        self::$whereRaw = [];
        self::$join = [];
        self::$order = [];
        self::$limit = '';

        return new static();
    }

    public function fields($fields)
    {
        if (is_array($fields))
            self::$fields = array_merge(self::$fields, $fields);
        else
            self::$fields = explode(', ', $fields);
        return $this;
    }

    public function join($table, $field1, $field2, $condition = ' = ', $alias = '')
    {
        $condition = ($condition == null) ? ' = ' : $condition;
        $alias = (!empty($alias)) ? ' AS ' . $alias : '';
        self::$join[] = 'JOIN ' . $table . $alias . ' ON ' . $field1 . $condition . $field2;
        return $this;
    }

    public function innerJoin($table, $field1, $field2, $condition = ' = ', $alias = '')
    {
        $condition = ($condition == null) ? ' = ' : $condition;
        $alias = (!empty($alias)) ? ' AS ' . $alias : '';
        self::$join[] = 'INNER JOIN ' . $table . $alias . ' ON ' . $field1 . $condition . $field2;
        return $this;
    }

    public function where($field, $value, $condition = ' = ', $combine_condition = 'AND')
    {
        self::$where[$field] = [
            'value' => $value,
            'condition' => $condition,
            'combine_condition' => $combine_condition,
        ];
        return $this;
    }

    public function whereRaw($where)
    {
        self::$whereRaw[] = ' AND ' . $where;
        return $this;
    }

    public function andWhereRaw($where)
    {
        self::whereRaw($where);
        return $this;
    }

    public function orWhereRaw($where)
    {
        self::$whereRaw[] = ' OR ' . $where;
        return $this;
    }

    public function order($field, $direction = 'ASC')
    {
        self::$order[] = $field . ' ' . $direction;
        return $this;
    }

    public function limit($limit, $from = '0')
    {
        $from = intval($from);
        $from = intval($from);
        self::$limit = 'LIMIT ' . $from . ', ' . $limit;
        return $this;
    }

    public function exec()
    {

        $values = [];

        $query[] = 'SELECT';
        if (count(self::$fields))
            $query[] = implode(',' . PHP_EOL, self::$fields);
        else
            $query[] = '*';
        $query[] = 'FROM';
        $query[] = self::$table;

        if (count(self::$join))
            $query[] = implode(',' . PHP_EOL, self::$join);

        $query[] = 'WHERE 1';
        if (count(self::$where)) {

            $where = [];
            foreach (self::$where as $field => $value) {

                if (!empty($value)) {
                    $field_placeholder = str_replace('.', '_', $field);
                    $where[] = $value['combine_condition'] . ' ' . $field . ' ' . $value['condition'] . ' :' . $field_placeholder;
                    $values[':' . $field_placeholder] = $value['value'];
                } else {
                    $where[] = $value['combine_condition'] . ' ' . $field . ' ' . $value['condition'] . ' \'\'';
                }
            }

            $query[] = implode(' ', $where);
        }

        if (count(self::$whereRaw)) {
            $query[] = implode(' ', self::$whereRaw);
        }

        if (count(self::$order)) {
            $query[] = 'ORDER BY';
            $query[] = implode(', ', self::$order);
        }

        if (!empty(self::$limit)) {
            $query[] = self::$limit;
        }

        $query_final = implode(PHP_EOL, $query);

//        echo '<pre>' . print_r($query, true) . '</pre>';
//        echo '<pre>$query: ' . print_r($query_final, true) . '</pre>';
//        echo '<pre>$values: ' . print_r($values, true) . '</pre>';

        return self::query($query_final, $values)->fetchAll();
    }

    public static function query($query, $args = array())
    {
        try {

            if (!$args) {

                return self::instance()->query($query);
            }

            $stmt = self::instance()->prepare($query);
            $stmt->execute($args);
//            $stmt->debugDumpParams();

            return $stmt;

        } catch (PDOException $exception) {

            echo $exception->getMessage();

        }
    }
}

class myPDOStatement extends PDOStatement
{
    private $_debugValues;

    function execute($data = array())
    {
        if (is_array($data)) {
            $i = 1;
            foreach ($data as $k => $p) {
                // default to string datatype
                $parameterType = PDO::PARAM_STR;
                // now let's see if there is something more appropriate
                if (is_bool($p)) {
                    $parameterType = PDO::PARAM_BOOL;
                } elseif (is_null($p)) {
                    $parameterType = PDO::PARAM_NULL;
                } elseif (is_int($p)) {
                    $parameterType = PDO::PARAM_INT;
                }
                $this->_debugValues[$k] = $p;
                $this->bindParam($i, $p, $parameterType);
                $i++;
            }
        }
        parent::execute($data);
        return $this;
    }

    public function _debugQuery($replaced = true)
    {
        $q = $this->queryString;
        if (!$replaced) {
            return $q;
        }
        return preg_replace_callback('/:([0-9a-z_]+)/i', 'self::_debugReplace', $q);
    }

    private function _debugReplace($m)
    {
        $v = $this->_debugValues[$m[0]];
        if ($v === null) {
            return "NULL";
        }
        if (!is_numeric($v)) {
            $v = str_replace("'", "''", $v);
        }
        return "'" . $v . "'";
    }
}
