<?php

namespace Edwinwong90\Larasang\Resources;

use Illuminate\Http\Resources\Json\Resource;

class DefaultResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
