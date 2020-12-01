<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class GeneCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    // {
    //     return parent::toArray($request);
    // }
    {
        return [
            'id' => $this->id,
            'curie' => $this->curie
        ];
    }
}

//         'curie',
        // 'title',
        // 'type',
        // 'description',
        // 'status',
        // 'location',
        // 'locus_group',
        // 'locus_type',
        // 'alias_symbol',
        // 'omim_id',
        // 'ucsc_id'
