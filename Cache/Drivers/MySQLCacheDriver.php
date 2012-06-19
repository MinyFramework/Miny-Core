<?php

namespace Miny\Cache\Drivers;

class MySQLCacheDriver implements \Miny\Cache\iCacheDriver {

    private $keys = array();
    private $data = array();
    private $driver;

    public function __construct(\Miny\Database\Driver $driver) {
        register_shutdown_function(array($this, 'close'));
        $this->driver = $driver;
        //GC
        $this->driver->exec('DELETE FROM `{prefix}cache` WHERE `expiration` < NOW()');

        $result = $this->driver->query('SELECT `key` FROM `{prefix}cache` WHERE `expiration` >= NOW()');
        foreach ($result as $row) {
            $this->keys[$row['key']] = 1;
        }
    }

    public function exists($key) {
        return array_key_exists($key, $this->keys);
    }

    public function get($key) {
        if (!$this->exists($key)) {
            throw new \OutOfBoundsException('Key not found: ' . $key);
        }
        if (!array_key_exists($key, $this->data)) {
            $statement = $this->driver->prepare('SELECT `data` FROM `{prefix}cache` WHERE `key` = :key');
            $statement->bindValue('key', $key);
            $statement->execute();
            $temp = $statement->fetch();
            $this->data[$key] = unserialize($temp['data']);
        }
        return $this->data[$key];
    }

    public function store($key, $data, $ttl) {
        if (!$this->exists($key)) {
            $this->keys[$key] = 'a';
        } else {
            $this->keys[$key] = 'm';
        }
        $this->data[$key] = array($data, $ttl);
    }

    public function remove($key) {
        if ($index !== false) {
            $this->keys[$key] = 'r';
            unset($this->data[$key]);
        }
    }

    public function close() {
        $save = false;
        $db = $this->driver;
        if (in_array('a', $this->keys)) {
            $save = true;
            $new_statement = $db->prepare('INSERT INTO `{prefix}cache` (`key`, `data`, `expiration`) VALUES(:key, :value, :expiration)');
        }
        if (in_array('r', $this->keys)) {
            $save = true;
            $delete_statement = $db->prepare('DELETE FROM `{prefix}cache` WHERE `key` = :key');
        }
        if (in_array('m', $this->keys)) {
            $save = true;
            $modify_statement = $db->prepare('REPLACE INTO `{prefix}cache` (`key`, `data`, `expiration`) VALUES(:key, :value, :expiration)');
        }
        if (!$save) {
            return;
        }
        $db->beginTransaction();
        foreach ($this->keys as $key => $state) {
            if ($state === 1)
                continue;
            $expiration = date('Y-m-d H:i:s', time() + $this->data[$key][1]);
            switch ($state) {
                case 'a':
                    $new_statement->bindValue(':key', $key);
                    $new_statement->bindValue(':value', serialize($this->data[$key][0]));
                    $new_statement->bindValue(':expiration', $expiration);
                    $new_statement->execute();
                    break;
                case 'r':
                    $delete_statement->bindValue(':key', $key);
                    $delete_statement->execute();
                    break;
                case 'm':
                    $modify_statement->bindValue(':key', $key);
                    $modify_statement->bindValue(':value', serialize($this->data[$key][0]));
                    $modify_statement->bindValue(':expiration', $expiration);
                    $modify_statement->execute();
                    break;
            }
        }
        $db->commit();
    }

}