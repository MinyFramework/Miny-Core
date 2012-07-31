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
 * @package   Miny/Routing
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0-dev
 */

namespace Miny\Routing;

use BadMethodCallException;

class Resource extends Resources
{
    protected static $memberActions = array();
    protected static $collectionActions = array(
        'show'    => 'GET',
        'destroy' => 'DELETE',
        'edit'    => 'GET',
        'update'  => 'PUT',
        'new'     => 'GET',
        'create'  => 'POST'
    );

    public function __construct($name, array $parameters = array())
    {
        parent::__construct($name, $parameters);
        $this->singular($name);
    }

    public function member($method, $name)
    {
        throw new BadMethodCallException('Single resource can\'t have member action.');
    }

    protected function generateMemberActions()
    {

    }

    protected function generateCollectionActions()
    {
        $unnamed = array('create', 'show', 'destroy', 'update');
        $this->generateActions($this->collection_actions, $unnamed, $this->getName(), $this->getPathBase() . $this->name);
    }

}