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
 * @package   Miny/ORM
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\ORM;

use \Miny\Cache\iCacheDriver;

class Manager
{
    public $table_format = '%s';
    public $foreign_key = '%s_id';
    public $connection;
    private $cache;
    private $tables = array();

    public function __construct(\PDO $connection, iCacheDriver $cache = NULL)
    {
        $this->connection = $connection;
        $this->cache = $cache;
        if (!is_null($cache) && $cache->has('orm.tables')) {
            $this->tables = $cache->get('orm.tables');
        }
    }

    public function __destruct()
    {
        if (!is_null($this->cache)) {
            $this->cache->store('orm.tables', $this->tables);
        }
    }

    public function discover()
    {
        $tables = $this->connection->query('SHOW TABLES')->fetchAll();
        $table_ids = array();
        foreach ($tables as $name) {
            $name = $name[0];
            list($id) = sscanf($name, $this->table_format);
            $td = new TableDescriptor;
            $td->name = $id;
            $this->addTable($td, $id);
            $table_ids[$id] = $name;
        }

        $foreign_pattern = '/' . str_replace('%s', '(.*)', $this->foreign_key) . '/';
        
        foreach ($table_ids as $name => $table_name) {
            //TODO: many-many relations
            $stmt = $this->connection->query('DESCRIBE ' . $table_name);
            $fields = $stmt->fetchAll();
            $td = $this->tables[$name]->descriptor;

            foreach ($fields as $field) {
                $td->fields[] = $field['Field'];
                if ($field['Key'] == 'PK') {
                    $td->primary_key = $field['Field'];
                }

                $matches = array();
                if (preg_match($foreign_pattern, $field['Field'], $matches)) {
                    $referenced_table = $matches[1];
                    $referencing_table = $name;

                    $referenced = $this->tables[$referenced_table];
                    $referencing = $this->tables[$referencing_table];

                    $referenced_relation = new Relation($referenced, Relation::HAS);
                    $referencing_relation = new Relation($referencing, Relation::BELONGS_TO);

                    $referencing->descriptor->relations[$referenced_table] = $referenced_relation;
                    $referenced->descriptor->relations[$referencing_table] = $referencing_relation;
                }
            }
        }
    }

    public function addTable(TableDescriptor $table, $name = NULL)
    {
        if (is_null($name)) {
            list($name) = sscanf($table->name, $this->table_format);
        }
        $this->tables[$name] = new Table($this, $table);
    }

    public function __get($table)
    {
        if (!isset($this->tables[$table])) {
            throw new \OutOfBoundsException('Table not exists: ' . $table);
        }
        return $this->tables[$table];
    }

}