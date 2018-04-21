<?php

namespace Larasang;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Register the application's response macros.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro('success', function (array $value = [], $status_code = 200) {
            $response = array_merge(['success'=>1], $value);
            return response()->json($response, $status_code);
        });

        Response::macro('error', function (array $value = [], $status_code = 200) {
            $response = array_merge(['success'=>0], $value);
            return response()->json($response, $status_code);
        });

        Response::macro('errorNotFound', function ($value = 'Resource Not Found') {
            if(is_array($value))
                $response = array_merge(['success'=>0, 'message'=>'Resource Not Found'], $value);
            else
                $response = ['success'=>0, 'message'=>$value];
            return response()->json($response, 404);
        });

        Response::macro('errorBadRequest', function ($value = []) {
            if(is_array($value))
                $response = array_merge(['success'=>0, 'message'=>'Bad Request'], $value);
            else
                $response = ['success'=>0, 'message'=>$value];
            return response()->json($response, 400);
        });

        Response::macro('errorValidation', function ($value) {
            if(is_array($value))
                $response = array_merge(['success'=>0, 'errors'=>'Validation Failed'], $value);
            else
                $response = ['success'=>0, 'errors'=>$value];
            return response()->json($response, 422);
        });
    }
}