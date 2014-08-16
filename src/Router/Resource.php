<?php

namespace Miny\Router;

class Resource
{
    /**
     * @var \Miny\Router\Resource
     */
    private $parent;

    /**
     * @var string
     */
    private $singularName;

    /**
     * @var string
     */
    private $pluralName;

    /**
     * @var array
     */
    private $collectionRoutes;

    /**
     * @var array
     */
    private $memberRoutes;

    /**
     * @var array
     */
    private $unnamedRoutes = [
        'create'  => true,
        'show'    => true,
        'update'  => true,
        'destroy' => true
    ];

    /**
     * @var string
     */
    private $idPattern = '\d+';

    /**
     * @var bool
     */
    private $isParent = false;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var bool
     */
    private $shallow = false;

    public function __construct($singularName, $pluralName = null)
    {
        $this->singularName = $singularName;
        $this->pluralName   = $pluralName;

        if ($pluralName !== null) {
            $this->collectionRoutes       = [
                'index'  => Route::METHOD_GET,
                'new'    => Route::METHOD_GET,
                'create' => Route::METHOD_POST
            ];
            $this->memberRoutes           = [
                'show'    => Route::METHOD_GET,
                'edit'    => Route::METHOD_GET,
                'update'  => Route::METHOD_PUT,
                'destroy' => Route::METHOD_DELETE
            ];
            $this->unnamedRoutes['index'] = true;
            $controllerName               = $pluralName;
        } else {
            $this->collectionRoutes = [
                'new'     => Route::METHOD_GET,
                'create'  => Route::METHOD_POST,
                'show'    => Route::METHOD_GET,
                'edit'    => Route::METHOD_GET,
                'update'  => Route::METHOD_PUT,
                'destroy' => Route::METHOD_DELETE
            ];
            $this->memberRoutes     = [];
            $controllerName         = $singularName;
        }

        $this->parameters['controller'] = $this->camelize($controllerName);
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

    public function except($except)
    {
        $except                 = array_flip(func_get_args());
        $this->collectionRoutes = array_diff_key($this->collectionRoutes, $except);
        $this->memberRoutes     = array_diff_key($this->memberRoutes, $except);

        return $this;
    }

    public function only($only)
    {
        $only                   = array_flip(func_get_args());
        $this->collectionRoutes = array_intersect_key($this->collectionRoutes, $only);
        $this->memberRoutes     = array_intersect_key($this->memberRoutes, $only);

        return $this;
    }

    public function member($name, $method)
    {
        if ($this->pluralName !== null) {
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
        $idPatterns = [];
        $parent     = $this->parent;
        while ($parent) {
            if ($parent->pluralName !== null) {
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
