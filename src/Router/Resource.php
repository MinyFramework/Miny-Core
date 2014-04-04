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
    private $unnamedRoutes;
    private $idPattern = '\d+';
    private $isParent = false;

    public function __construct($singularName, $pluralName = null)
    {
        $this->singularName = $singularName;
        $this->pluralName   = $pluralName;
        $this->isPlural     = $pluralName !== null;

        if ($this->isPlural) {
            $this->collectionRoutes = array(
                'index'  => Route::METHOD_GET,
                'new'    => Route::METHOD_GET,
                'create' => Route::METHOD_POST
            );
            $this->memberRoutes     = array(
                'show'    => Route::METHOD_GET,
                'edit'    => Route::METHOD_GET,
                'update'  => Route::METHOD_PUT,
                'destroy' => Route::METHOD_DELETE
            );
            $this->unnamedRoutes    = array(
                'index',
                'create',
                'show',
                'update',
                'destroy'
            );
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
            $this->unnamedRoutes    = array(
                'create',
                'show',
                'update',
                'destroy'
            );
        }
    }

    public function idPattern($pattern)
    {
        $this->idPattern = $pattern;

        return $this;
    }

    public function setParent(Resource $parent)
    {
        $this->parent     = $parent;
        $parent->isParent = true;
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
            $this->memberRoutes[$name] = $method;
        }

        return $this;
    }

    public function collection($name, $method)
    {
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
        $idPatterns = array();
        $parent     = $this->parent;
        while ($parent) {
            $pathBase .= $parent->singularName . '/';
            if ($parent->isPlural) {
                $pathBase .= $parent->getIdToken() . '/';
                $idPatterns[$parent->singularName . '_id'] = $parent->idPattern;
            }
            $parent = $parent->parent;
        }

        if ($this->pluralName) {
            $this->addRoutes(
                $this->memberRoutes,
                $router,
                $pathBase . $this->singularName . '/' . $this->getIdToken(),
                $this->singularName,
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
            $firstUnnamedRouteName,
            $idPatterns
        );
    }

    /**
     * @param array  $routes
     * @param Router $router
     * @param        $basePath
     * @param        $firstUnnamedRouteName
     * @param        $idPatterns
     */
    private function addRoutes(
        array $routes,
        Router $router,
        $basePath,
        $firstUnnamedRouteName,
        $idPatterns
    ) {
        foreach ($routes as $name => $method) {
            $path = $basePath;

            if (in_array($name, $this->unnamedRoutes)) {
                $name = $firstUnnamedRouteName;

                $firstUnnamedRouteName = null;
            } else {
                $path .= '/' . $name;
            }

            $route = $router->add($path, $method, $name, true);
            foreach ($idPatterns as $id => $pattern) {
                $route->specify($id, $pattern);
            }
        }
    }
}
