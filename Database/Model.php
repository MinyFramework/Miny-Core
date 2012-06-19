<?php

namespace Miny\Database;

class Model {

    private static $connections = array();

    public static function setConnection(\PDO $db, $name = 'default') {
        self::$connections[$name] = $db;
    }

    public static function getDBConnection($connection = 'default') {
        if (!isset(self::$connections[$connection])) {
            throw new \InvalidArgumentException('Connection not set: ' . $connection);
        }
        return self::$connections[$connection];
    }

    private $connection_name;
    private $connection;

    public function __construct($connection = 'default') {
        $this->connection_name = $connection;
        $this->connection = self::getDBConnection($connection);
    }

    public function getConnection() {
        return $this->connection;
    }

    public function getConnectionName() {
        return $this->connection_name;
    }

}