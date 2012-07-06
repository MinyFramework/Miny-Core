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
 * @package   Miny/Entity
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Entity;

abstract class EntityProvider
{
    private $entity_class;
    private $entities = array();

    public function __construct($entity_class, array $entities = array())
    {
        $this->entity_class = $entity_class;
        foreach($entities as $entity) {
            $this->add($this->create($entity));
        }
    }

    public function create(array $entity_data = array())
    {
        $class = $this->entity_class;
        $entity = new $class($entity_data);
        $entity->setProvider($this);
        return $entity;
    }

    public function add(Entity $ent)
    {
        $this->entities[$ent->getKey()] = $ent;
    }

    public function get($key)
    {
        if (!$this->has($key)) {
            throw new \OutOfBoundsException('Entity not set: ' . $key);
        }
        return $this->entities[$key];
    }

    public function remove($key)
    {
        if (isset($this->entities[$key])) {
            unset($this->entities[$key]);
            return true;
        }
    }

    public function has($key)
    {
        return isset($this->entities[$key]);
    }

}