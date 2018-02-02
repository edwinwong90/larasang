<?php

namespace Larasang;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Filesystem\Filesystem;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [];


    /**
     * Register events for Larasang.
     *
     * @return void
     */
    public function boot()
    {
        $this->files = new Filesystem;
        $modules = config('larasangapi');

        foreach ($modules as $module)
        {
            if (isset($module['listener']))
            {
                $listeners = $module['listener'];

                foreach ($listeners as $event => $listener)
                {
                    if ($this->isEventExist($event) && !empty($listener)) 
                    {
                        $eventClass = $this->getEventNamespaceClass($event);
                        $this->addListener($eventClass, $listener);
                    }
                }
            }    
        }
        parent::boot();
    }
    /**
     * get full namespace class name
     *
     * @param string $event
     * @return string
     */
    private function getEventNamespaceClass($event) 
    {
        return 'Edwinwong90\\Larasang\\Events\\'.$event;
    }
    /**
     * check event is existed
     *
     * @param string $eventName
     * @return boolean
     */
    private function isEventExist($eventName)
    {
        return $this->files->exists($this->getEventPath($eventName));
    }
    /**
     * get events folder path
     *
     * @param string $eventName
     * @return string
     */
    private function getEventPath($eventName)
    {
        return __DIR__.'//Events//'.$eventName.'.php';
    }
    /**
     * register laravel event listener
     *
     * @param Edwinwong\Larasang\Events $event
     * @param App\Listeners $listener
     * @return void
     */
    private function addListener($event, $listener) 
    {
        if (is_array($listener))
        {
            return $this->listen[$event] = $listener;
        }
        
        return $this->listen[$event] = [$listener];
    }
}
