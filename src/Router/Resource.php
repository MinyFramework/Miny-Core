<?php

namespace Miny\Router;

class Resource
{
    /**
     * @var \Miny\Router\Resource
     */
    private $parent;

    /**
     * @var bool
     */
    private $isPlural;

    /**
     * @var string
     */
    private $singularName;
    private $pluralName;

    private $collectionRoutes;
    private $memberRoutes;
    private $unnamedRoutes = array(
        'create'  => true,
        'show'    => true,
        'update'  => true,
        'destroy' => true
    );
    private $idPattern = '\d+';
    private $isParent = false;
    private $parameters = array();
    private $shallow = false;

    public function __construct($singularName, $pluralName = null)
    {
        $this->singularName = $singularName;
        $this->pluralName   = $pluralName;
        $this->isPlural     = $pluralName !== null;

        if ($this->isPlural) {
            $this->collectionRoutes       = array(
                'index'  => Route::METHOD_GET,
                'new'    => Route::METHOD_GET,
                'create' => Route::METHOD_POST
            );
            $this->memberRoutes           = array(
                'show'    => Route::METHOD_GET,
                'edit'    => Route::METHOD_GET,
                'update'  => Route::METHOD_PUT,
                'destroy' => Route::METHOD_DELETE
            );
            $this->unnamedRoutes['index'] = true;

            $this->parameters['controller'] = $this->camelize($pluralName);
        } else {
            $this->collectionRoutes = array(
                'new'     => Route::METHOD_GET,
                'create'  => Route::METHOD_POST,
                'show'    => Route::METHOD_GET,
                'edit'    => Route::METHOD_GET,
                'update'  => Route::METHOD_PUT,
                'destroy' => Route::METHOD_DELETE
            );
            $this->memberRoutes     = array();

            $this->parameters['controller'] = $this->camelize($singularName);
        }
    }

    private function camelize($str)
    {
        $str = strtolower($str);
        $str = ucwords($str);

        return strtr($str, '_', '');
    }

    public function shallow($shallow = true)
    {
        $this->shallow = $shallow;

        return $this;
    }

    public function idPattern($pattern)
    {
        $this->idPattern = $pattern;

        return $this;
    }

    public function resource(Resource $resource)
    {
        $resource->setParent($this);

        return $this;
    }

    public function setParent(Resource $parent)
    {
        $this->parent     = $parent;
        $parent->isParent = true;

        return $this;
    }

    public function except($name)
    {
        $only                   = array_flip(func_get_args());
        $this->collectionRoutes = array_diff_key($this->collectionRoutes, $only);
        $this->memberRoutes     = array_diff_key($this->memberRoutes, $only);

        return $this;
    }

    public function only($name)
    {
        $only                   = array_flip(func_get_args());
        $this->collectionRoutes = array_intersect_key($this->collectionRoutes, $only);
        $this->memberRoutes     = array_intersect_key($this->memberRoutes, $only);

        return $this;
    }

    public function member($name, $method)
    {
        if ($this->isPlural) {
            unset($this->unnamedRoutes[$name]);
            $this->memberRoutes[$name] = $method;
        }

        return $this;
    }

    public function collection($name, $method)
    {
        unset($this->unnamedRoutes[$name]);
        $this->collectionRoutes[$name] = $method;

        return $this;
    }

    public function getIdToken()
    {
        if ($this->isParent) {
            $name = $this->singularName . '_id:' . $this->idPattern;
        } else {
            $name = 'id:' . $this->idPattern;
        }

        return '{' . $name . '}';
    }

    public function register(Router $router)
    {
        $pathBase   = '';
        $nameBase   = '';
        $idPatterns = array();
        $parent     = $this->parent;
        while ($parent) {
            if ($parent->isPlural) {
                $pathBase .= $parent->pluralName . '/' . $parent->getIdToken() . '/';
                $idPatterns[$parent->singularName . '_id'] = $parent->idPattern;
            } else {
                $pathBase .= $parent->singularName . '/';
            }
            $nameBase = $parent->singularName . '_' . $nameBase;
            $parent   = $parent->parent;
        }

        if ($this->pluralName) {
            if ($this->shallow) {
                $pluralNameBase = '';
                $pluralPathBase = '';
            } else {
                $pluralPathBase = $pathBase;
                $pluralNameBase = $nameBase;
            }
            $this->addRoutes(
                $this->memberRoutes,
                $router,
                $pluralPathBase . $this->pluralName . '/' . $this->getIdToken(),
                $pluralNameBase,
                $pluralNameBase . $this->singularName,
                $idPatterns
            );
            $firstUnnamedRouteName = $this->pluralName;
        } else {
            $firstUnnamedRouteName = $this->singularName;
        }

        $this->addRoutes(
            $this->collectionRoutes,
            $router,
            $pathBase . $firstUnnamedRouteName,
            $nameBase,
            $nameBase . $firstUnnamedRouteName,
            $idPatterns
        );

        return $this;
    }

    /**
     * @param array  $routes
     * @param Router $router
     * @param        $basePath
     * @param        $namePrefix
     * @param        $firstUnnamedRouteName
     * @param        $idPatterns
     */
    private function addRoutes(
        array $routes,
        Router $router,
        $basePath,
        $namePrefix,
        $firstUnnamedRouteName,
        $idPatterns
    ) {
        foreach ($routes as $name => $method) {
            $path = $basePath;

            if (isset($this->unnamedRoutes[$name])) {
                $routeName = $firstUnnamedRouteName;

                $firstUnnamedRouteName = null;
            } else {
                $path .= '/' . $name;
                $routeName = $name . '_' . $namePrefix . $this->singularName;
            }

            $route = $router->add($path, $method, $routeName, true);
            foreach ($idPatterns as $id => $pattern) {
                $route->specify($id, $pattern);
            }
            $route->set($this->parameters);
            $route->set('action', $name);
        }
    }

    /**
     * @param $key
     * @param $value
     *
     * @return Route $this
     */
    public function set($key, $value = null)
    {
        if ($value === null) {
            $this->parameters = $key + $this->parameters;
        } else {
            $this->parameters[$key] = $value;
        }

        return $this;
    }
}
