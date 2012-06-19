<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Record
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 *
 */

namespace Miny\Record;

use Miny\Database\Driver;
use Miny\Database\Table;
use Miny\Database\Builder\Parts\Expression;
use Miny\Database\Builder\Parts\Parameter;
use Miny\Database\Builder\SelectQuery;

//TODO: sql mezőaliasok rendes kezelése!
class RecordManager {

    private $record;
    private $calls = array();
    private $connection;
    private $with = array();

    public function __construct($record, $connection) {
        $this->record = $record;
        $this->connection = $connection;
    }

    public function getConnectionName() {
        return $this->connection;
    }

    public function getConnection() {
        return Model::getDBConnection($this->connection);
    }

    public function __call($func, $args) {
        $this->calls[] = array($func, $args);
        return $this;
    }

    public function with() {
        $record = $this->record;
        foreach (func_get_args() as $relation) {
            if ($record::getRelationType($relation) !== Record::NOT_RELATED) {
                $this->with[] = $relation;
            }
        }
    }

    public function getRelated($key) {
        $record = $this->record;
        $class = ucfirst($record::getRelationClass($key)) . 'Model';
        return new $class($this->getConnectionName());
    }

    private function runStatement($sql, array $params) {
        $db = $this->getConnection();
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        return $stmt;
    }

    public function getFindQuery(array $fields, $alias = NULL) {
        $record = $this->record;
        $table_name = $record::getTableName();
        $table = Table::get($table_name);

        $pk = $table->getPrimaryKey();
        if (!isset($fields[$pk]) && isset($this->data[$pk])) {
            $fields = array($pk => $this->data[$pk]);
        }

        $query = new SelectQuery($table_name, $alias);
        $query->fields('*');
        //TODO: relációk
        /* foreach ($this->with as $relation) {

          } */

        foreach ($this->calls as $call) {
            list($func, $args) = $call;
            call_user_func_array(array($query, $func), $args);
        }

        if (!empty($fields)) {
            $where = $query->where;
            foreach (array_keys($fields) as $key) {
                $param = new Parameter($key);
                $expr = new Expression('=', $key, $param);
                if (is_null($where)) {
                    $where = $expr;
                } else {
                    $where = new Expression('and', $where, $expr);
                }
            }
            $query->where($where);
        }
        return $query;
    }

    public function find(array $fields = array()) {
        $query = $this->getFindQuery($fields)->get();
        return $this->findBySql($query, $fields);
    }

    public function findByPk($pk) {
        return $this->find(array($this->getPrimaryKey() => $pk));
    }

    private function getRecordObject($state) {
        $record = $this->record;
        return new $record($this->getConnectionName(), $state);
    }

    public function findBySql($sql, array $params = array(), $prefix = NULL) {
        //TODO: feldolgozás
        $db = $this->getConnection();
        if ($db instanceof Driver) {
            $prefix = $db->replacePrefix($prefix);
        }

        $result = $this->runStatement($sql, $params)->fetch();
        $model = $this->getRecordObject(Record::STATE_UNMODIFIED);
        $model->load($result, $prefix);
        return $model;
    }

    public function findAll(array $fields = array()) {
        $query = $this->getFindQuery($fields)->get();
        return $this->findAllBySql($query, $fields);
    }

    public function findAllByPk($pk) {
        return $this->findAll(array($this->getPrimaryKey() => $pk));
    }

    public function findAllBySql($sql, array $params = array()) {
        $stmt = $this->runStatement($sql, $params);
        $records = $stmt->fetchAll();
        //TODO: feldolgozás
        return $records;
    }

    public function save() {

    }

    public function delete() {

    }

}