<?php

namespace Larasang;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Larasang\Events\IndexBefore;
use Larasang\Events\IndexAfter;
use Larasang\Events\StoreBefore;
use Larasang\Events\StoreAfter;
use Larasang\Events\UpdateBefore;
use Larasang\Events\UpdateAfter;
use Larasang\Events\ShowBefore;
use Larasang\Events\ShowAfter;
use Larasang\Events\DeleteBefore;
use Larasang\Events\DeleteAfter;
use Larasang\Resources\DefaultResource;

class ApiController extends Controller
{
    /**
     * Current class module config
     *
     * @var string
     */
    protected $config;
    /**
     * Eloquent model
     *
     * @var Illuminate\Database\Eloquent\Model
     */
    protected $model;
    /**
     * Resource 
     *
     * @var Illuminate\Http\Resources\Json\Resource
     */
    protected $resource;
    

    public function __construct()
    {
        $this->setup();
    }

    /**
     * List multiple resource
     * 
     * @return json
     */
    public function index()
    {
        $this->fireEvent(new IndexBefore());

        if($this->getConfig('pagination.limit'))
            $objects = $this->model->paginate( $this->getConfig('pagination.limit') );
        else
            $objects = $this->model->get();

        $this->fireEvent(new IndexAfter($objects));
        
        return $this->resourceResponse($objects, true);
    }

    /**
     * Show a single resource
     * 
     * @param Integer $id  Resource ID
     * 
     * @return json
     */
    public function show($id)
    {
        $object = $this->model->find($id);
        
        $this->fireEvent(new ShowBefore());

        if (!$object)
            return response()->errorNotFound();
        
        $this->fireEvent(new ShowAfter($object));
    
        return $this->resourceResponse($object);
    }

    /**
     * Store a single resource
     * 
     * @param \Illuminate\Http\Request $request
     * 
     * @return json
     */
    public function store(Request $request)
    {
        $data = $request -> all();
        if(!count($data))
            return response()->errorBadRequest();

        $validator = $this->validateRequest( $this->getConfig('request.store'), $data );
        if ($validator !== true)
        {
            return response()->errorValidation(['error'=>$validator->errors()]);
        }

        $this->fireEvent(new StoreBefore());
    
        $object = $this->model->create($data);    

        $this->fireEvent(new StoreAfter($object));

        return $this->resourceResponse($object);
    }

    /**
     * Store multiple resource
     *
     * @param Request $request
     * @return json
     */
    public function storeMany(Request $request)
    {
        $rows = collect($request -> all());

        if($rows->count() <= 0)
            return response()->errorBadRequest('No entry to be insert.');

        $objects = collect([]);
        $rows->each(function ($data, $key) use (&$objects) {
            if( $result = $this->model->create($data) )
                $objects->push($result);
        });

        if($objects->isEmpty())
            return response()->errorBadRequest('Bad request - something is went wrong');

        return $this->resourceResponse($objects, true);
    }

    /**
     * Update a single resource
     * 
     * @param \Illuminate\Http\Request $request
     * @param Integer $id model primary key ID
     * 
     * @return json
     */
    public function update(Request $request, $id)
    {
        $data = $request -> all();
        
        if (!count($data))
            return response()->errorBadRequest();

        $validator = $this->validateRequest( $this->getConfig('request.update'), $data );
        if ($validator !== true)
        {
            return response()->errorValidation(['error'=>$validator->errors()]);
        }

        $object = $this -> performUpdate($id, $data);

        if (!$object)
            return response()->errorBadRequest();

        return $this->resourceResponse($object);
    }

    /**
     * Update multiple resource
     * 
     * @param \Illuminate\Http\Request $request
     * @param Integer $id model primary key ID
     * 
     * @return json
     */
    public function updateMany(Request $request)
    {
        $rows = collect($request -> all());

        // check each row has ID column.
        $is_valid = $rows->every(function ($data, $key) {
            return isset($data['id']) && $this->model->find($data['id']);
        });

        if (!$is_valid)
            return response()->errorBadRequest('Invalid resource update, and make sure ID is provided.');

        $validator = null;
        // loop through validation for each row
        $validation = $rows->every(function ($data, $key) use (&$validator) {
            $validator = $this->validateRequest( $this->getConfig('request.update'), $data );
            if ($validator !== true)
            {
                return false;
            }
            return true;
        });

        if (!$validation && !is_null($validator)) {
            return response()->errorValidation(['error'=>$validator->errors()]);
        }

        $objects = collect([]);
        $rows->each(function ($data, $key) use (&$objects) {
            $id = $data['id'];
            unset($data['id']); // should not be update id in table

            if( $result = $this -> performUpdate($id, $data) )
                $objects->push($result);
        });

        if($objects->isEmpty())
            return response()->errorBadRequest('Bad request - something is went wrong');

        return $this->resourceResponse($objects, true);
    }

    /**
     * Delete a single resource
     * @param Integer $id model primary key ID
     * 
     * @return json
     */
    public function destroy($id)
    {
        $this->fireEvent(new DeleteBefore());

        $success = $this -> model -> destroy($id);

        if (!$success) 
        {
            response()->errorNotFound();
        }

        $this->fireEvent(new DeleteAfter());

        return response()->success(['id'=>$id]);
    }

    /**
     * Delete multiple resource
     * 
     * @param \Illuminate\Http\Request $request
     * 
     * @return json
     */
    public function destroyMany(Request $request)
    {
        if(!$request->has('ids'))
            return response()->errorBadRequest();

        $this->fireEvent(new DeleteBefore());

        $success = $this -> model -> destroy( $request->input('ids') );

        if (!$success) 
        {
            response()->errorNotFound();
        }

        $this->fireEvent(new DeleteAfter());

        return response()->success(['id'=>$id]);
    }

    /**
     * perform update query
     *
     * @param int   $id
     * @param array $data
     * @return Illuminate\Database\Eloquent\Model
     */
    private function performUpdate($id, $data)
    {
        $object = $this->model->find($id);
        
        if(!$object)
            return FALSE;

        $this->fireEvent(new UpdateBefore());

        $success = $object->update($data);
 
        if(!$success)
            return FALSE;

        $this->fireEvent(new UpdateAfter($object));

        return $object;
    }

    /**
     * @param Illuminate\Database\Eloquent\Model $model
     * @param Boolean $collection true for collection, false for single result
     * 
     * @return json
     */
    private function resourceResponse($model, $collection = false)
    { 
        if ($collection)
        {
            return $this->resource::collection($model)->additional(['success'=>1]);
        }

        return (new $this->resource($model))->additional(['success'=>1]);
    }

    /**
     * Core setup the larasang config file to register module
     * 
     * @return void
     */
    private function setup()
    {
        $this->setConfig();
        $config = $this->getConfig();
    
        try {
            if (!isset($config['model']))
            {
                throw new \Exception('Model not found in config');
            }

            $this->model      = new $config['model']();
            $this->resource   = isset($config['resource']) ? $config['resource'] : DefaultResource::class;

        }
        catch (\Exception $e)
        {
            abort(400, $e->getMessage());
        }  
    }

    /**
     * get configuration for larasang api module, it works the same like config()
     *
     * @return object
     */
    protected function getConfig($param = '', $default = null)
    {
        if ($param != '')
            return config($this->config.'.'.$param, $default);

        return config($this->config, $default);
    }
    /**
     * set current module config
     *
     * @return void
     */
    private function setConfig()
    {
        $class = get_class($this);
        $classArray = explode('\\', $class);
        $className = end($classArray);
        
        $module = strtolower(str_replace('Controller', '', $className));
        $this->config = 'larasangapi.'.$module;
    }
    /**
     * fire an pre-defined event
     *
     * @param Edwinwong90\Larasang\Events $event
     * @return void
     */
    protected function fireEvent($event)
    {
        // Event is not register will be return null/empty
        event($event);
    }

    protected function validateRequest($validation, $data)
    {
        if ($validation) 
        {
            $validator = validator($data, (new $validation)->rules());
            
            if ($validator->fails()) 
            {
                return $validator;
            }
        }
        return TRUE;
    }
}