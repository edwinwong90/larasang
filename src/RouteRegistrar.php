<?php

namespace Edwinwong90\Larasang;

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
        $options = [];

        foreach($this->config as $module => $config)
        {
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
        $config = $this->getModuleConfig();
        $controller = '\\'.$config['controller'];
        $resource_config = isset($config['route_resource']) ? $config['route_resource'] : [];
        $module_many_path = $this->module.'/many';

        if (empty($resource_config)) 
        {
            $this->router->post($module_many_path, $controller.'@storeMany')->name($this->module.'.store.many');
            $this->router->match(['put', 'patch'], $module_many_path, $controller.'@updateMany')->name($this->module.'.update.many');
            $this->router->delete($module_many_path, $controller.'@destroyMany')->name($this->module.'.destroy.many');
        }
        else 
        {
            if (isset($resource_config['only'])) 
            {
                if (in_array('storeMany', $resource_config['only'])) 
                {
                    $this->router->post($module_many_path, $controller.'@storeMany')->name($this->module.'.store.many');
                }

                if (in_array('updateMany', $resource_config['only'])) 
                {
                    $this->router->match(['put', 'patch'], $module_many_path, $controller.'@updateMany')->name($this->module.'.update.many');
                }

                if (in_array('destroyMany', $resource_config['only'])) 
                {
                    $this->router->delete($module_many_path, $controller.'@destroyMany')->name($this->module.'.destroy.many');
                }             
            }     
        }  

        $this->router->apiResource($this->module, $controller, $resource_config);
    }

    /**
     * get module config
     *
     * @param string $name
     * @return void
     */
    protected function getModuleConfig($name = null)
    {   
        if (!$this->module) 
        {
            abort('Route module is not set');
        }

        if ($name)
        {
            return config('larasangapi.'.$this->module.'.'.$name);
        }
            
        return config('larasangapi.'.$this->module);
    }
}