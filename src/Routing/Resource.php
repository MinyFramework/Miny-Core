<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

use BadMethodCallException;

class Resource extends Resources
{
    /**
     * @var string[]
     */
    protected static $memberActions = array();

    /**
     * @var string[]
     */
    protected static $collectionActions = array(
        'show'    => 'GET',
        'destroy' => 'DELETE',
        'edit'    => 'GET',
        'update'  => 'PUT',
        'new'     => 'GET',
        'create'  => 'POST'
    );

    /**
     * @param string $name
     * @param array  $parameters
     */
    public function __construct($name, array $parameters = array())
    {
        parent::__construct($name, $parameters);
        $this->singular($name);
    }

    /**
     * @param string $method
     * @param string $name
     *
     * @throws BadMethodCallException
     */
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
        $this->generateActions($this->collection_actions, $unnamed, $this->getName(), $this->getPathBase());
    }
}
