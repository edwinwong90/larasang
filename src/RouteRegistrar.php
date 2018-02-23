<?php

namespace Larasang;

use Illuminate\Contracts\Routing\Registrar as Router;

class RouteRegistrar
{
    /**
     * The router implementation.
     *
     * @var \Illuminate\Contracts\Routing\Registrar
     */
    protected $router;

    /**
     * larasang api config
     *
     * @var array
     */
    protected $config;

    /**
     * set Current Module
     *
     * @var string
     */
    protected $module;

    const RESOURCE_INDEX = 'index';
    const RESOURCE_SHOW = 'show';
    const RESOURCE_STORE = 'store';
    const RESOURCE_STORE_MANY = 'storeMany';
    const RESOURCE_UPDATE = 'update';
    const RESOURCE_UPDATE_MANY = 'updateMany';
    const RESOURCE_DESTROY = 'destroy';
    const RESOURCE_DESTROY_MANY = 'destroyMany';

    /**
     * Create a new route registrar instance.
     *
     * @param  \Illuminate\Contracts\Routing\Registrar  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->config = config('larasangapi', []);
    }

    public function all()
    {
        $this->forModuleRoutes();
    }

    public function forModuleRoutes()
    {
        foreach($this->config as $module => $config)
        {
            $options = [];
            $this->setModule($module);

            if ($this->getModuleConfig('prefix')) 
            {
                $options['prefix'] = $this->getModuleConfig('prefix');
            }

            if ($this->getModuleConfig('middleware'))
            {
                // if not multidimensional array mean apply for all
                if (!$this->is_multi_array($this->getModuleConfig('middleware')))
                {
                    $options['middleware'] = $this->getModuleConfig('middleware');
                }   
            }
        
            $this->router->group($options, function () {
                $this->addResourceRoutes();
            });
        }
    }

    /**
     * Check is multidimentional array or not
     *
     * @param array $arr
     * @return boolean
     */
    public function is_multi_array( $arr ) 
    {
        rsort( $arr );
        return isset( $arr[0] ) && is_array( $arr[0] );
    }

    /**
     * module name
     *
     * @param string $module
     * @return void
     */
    public function setModule($module)
    {
        return $this->module = $module;
    }

    /**
     * add resources routes
     *
     * @return void
     */
    public function addResourceRoutes() 
    {
        $controller = '\\'.$this->getModuleConfig('controller');

        $methods = [
            self::RESOURCE_INDEX,
            self::RESOURCE_SHOW,
            self::RESOURCE_STORE,
            self::RESOURCE_STORE_MANY,
            self::RESOURCE_UPDATE,
            self::RESOURCE_UPDATE_MANY,
            self::RESOURCE_DESTROY,
            self::RESOURCE_DESTROY_MANY,
        ];

        foreach ($methods as $method) 
        {
            $action = $controller.'@'.$method;
            $controller_class = new $controller;

            // check method exist only create route.
            if (method_exists($controller_class, $method))
            {
                // check the controller has method defined or not
                if (in_array($method, get_class_methods($controller_class)))
                    $this->addRoute($method, $action);
            }
        }
    }

    /**
     * add single Route
     *
     * @param [string] $method  e.g INDEX, STORE, SHOW and more.
     * @param [string] $action e.g "TestController@index"
     * @return void
     */
    protected function addRoute($method, $action)
    {
        $controller = '\\'.$this->getModuleConfig('controller');
        $route_name = $this->module.'.'.$method;
        
        switch ($method) 
        {
            case self::RESOURCE_INDEX:
                $path = $this->module;
                $this->router->get($path, $action)->name($route_name);
            break;

            case self::RESOURCE_SHOW:
                $path = $this->module.'/{id}';
                $this->router->get($path, $action)->name($route_name);
            break;

            case self::RESOURCE_STORE:
            case self::RESOURCE_STORE_MANY:
                $path = $this->module;
                $path = ($method == 'storeMany') ? $path.'/many' : $path;
                $this->router->post($path, $action)->name($route_name);
            break;

            case self::RESOURCE_UPDATE:
            case self::RESOURCE_UPDATE_MANY:
                $path = $this->module.'/{id}';
                $path = ($method == self::RESOURCE_UPDATE_MANY) ? $path.'/many' : $path;
                $this->router->put($path, $action)->name($route_name.'.put');
                $this->router->post($path, $action)->name($route_name.'.post');
            break;

            case self::RESOURCE_DESTROY:
            case self::RESOURCE_DESTROY_MANY:
                $path = $this->module.'/{id}';
                $path = ($method == self::RESOURCE_DESTROY_MANY) ? $path.'/many' : $path;
                $this->router->delete($path, $action)->name($route_name);
            break;
        }
    }
    
    /**
     * get module config
     *
     * @param string $name
     * @return void
     */
    protected function getModuleConfig($name = null)
    {   
        $this->validateConfig();

        if ($name)
        {
            return config('larasangapi.'.$this->module.'.'.$name);
        }
            
        return config('larasangapi.'.$this->module);
    }

    protected function validateConfig()
    {
        if (!$this->module) 
        {
            abort('Route module is not set');
        }

        if (!config('larasangapi.'.$this->module.'.controller'))
        {
            abort('Route module controller is not set');
        }

        if (!config('larasangapi.'.$this->module.'.model'))
        {
            abort('Route module model is not set');
        }
    }
}