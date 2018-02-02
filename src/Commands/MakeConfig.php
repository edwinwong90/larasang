<?php

namespace Larasang\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeConfig extends Command
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larasang:make-config {name}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create api config with Api namespaces';
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Config';
    
    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {   
        $path = $this->getConfigPath($this->getNameInput());
        $this->files->put($path, $this->buildConfig($this->getNameInput()));
        $this->info('Module config created successfully.');
    }

    /** 
     * get the config path for module api configuration file
     * 
     * @param string $name 
     * @return string
     */
    protected function getConfigPath($name)
    {
        $nameArr = explode('\\',$name);
        $name = strtolower(end($nameArr));
        return $this->getLarasangApiPath().'/'.$name.'.php';
    }

    /**
     * get the larasangapi folder path
     *
     * @return string
     */
    protected function getLarasangApiPath()
    {
        $path = $this->laravel['path.base'].'/config/larasangapi';
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0777, true, true);
        }
        return $path;
    }

    protected function buildConfig($name)
    {
        $stub = $this->files->get($this->getStub());
        return $this->replaceController($stub, $name)->replaceModel($stub, $name);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/module.config.stub';
    }

    /**
     * Replace the controller class for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceController(&$stub, $name)
    {
        $stub = str_replace(
            ['DummyControllerClass'],
            [$this->getControllerClass($name.'Controller')],
            $stub
        );
        return $this;
    }

    /**
     * Replace and return the model class for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return mixed
     */
    protected function replaceModel(&$stub, $name)
    {
        return str_replace('DummyModelClass', $this->getModelClass($name), $stub);
    }

    protected function getNameInput()
    {
        return trim($this->argument('name'));
    }

    /**
     * Get the controller namespace for the class.
     *
     * @return string
     */
    protected function getControllerClass($name)
    {
        return trim(str_replace('{CONTROLLER}', ucfirst($name), '\App\Http\Controllers\{CONTROLLER}::class'));
    }

    /**
     * Get the model namespace for the class.
     *
     * @return string
     */
    protected function getModelClass($name)
    {
        return trim(str_replace('{MODEL}', ucfirst($name), '\App\Models\{MODEL}::class'));
    }
}