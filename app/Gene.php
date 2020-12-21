<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelTransform;
use App\Traits\DisplayTransform;

class Gene extends Model
{
    //
    use ModelTransform;
    use DisplayTransform;

    public function submissions()
    {
        return $this->hasMany('App\Submission')->where('status', '=', 1);
    }

    public function scopeCurie($query, $id)
    {
        return $query->where('curie', '=', $id)->orderBy('updated_at', 'asc');
    }

    public function scopeUuid($query, $id)
    {
        return $query->where('uuid', '=', $id)->orderBy('updated_at', 'asc');
    }

    public function scopeSymbol($query, $id)
    {
        return $query->where('title', '=', $id)->orderBy('updated_at', 'asc');
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    // public function getFullNameAttribute()
    // {
    //     //$collection = collect([1, 2, 3]);
    //     //dd($collection);
    //     //dd($this->submissions);
    //     //dd($data);
    //     //$mondo =  array();
    //     foreach ($this->submissions as $item) {
    //         //dd($item);
    //         foreach ($item->diseases->where("type", "MONDO") as $element) {
    //             //  dd($element);
    //             $mondo[$element->id]["title"] =  $element->title;
    //             $mondo[$element->id]["curie"] =  $element->curie;
    //             $mondo[$element->id]["submissions"][$item->id] = $item;
    //         }
    //     }
    //     //dd($mondo);
    //     //$mondo = json_decode(json_encode($mondo));
    //     //dd($mondo);
    //     //dd($data);
    //     return $data;
    //     $return = "{$this->curie} {$this->title}";
    //     return $return;
    // }



    protected $fillable = [
        'curie',
        'type',
        'title',
        'description',
        'status',
        'date_modified',
        'uuid',
        'hgnc_uuid',
        'hgnc_id',
        'symbol',
        'name',
        'location',
        'locus_group',
        'locus_type',
        'alias_symbol',
        'omim_id',
        'ucsc_id',
        'ensembl_gene_id',
        'date_approved_reserved'
    ];
}
