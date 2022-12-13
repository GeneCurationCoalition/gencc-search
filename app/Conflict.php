<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

use Uuid;

/**
 *
 * Complete representation of a Gene.
 *
 * @category   Model
 * @package    GenCC
 * @author     P. Weller <pweller1@geisinger.edu>
 * @copyright  2022 Geisinger
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PackageName
 * @see        NetOther, Net_Sample::Net_Sample()
 * @since      Class available since Release 1.0.0
 *
 * */
class Conflict extends Model
{
    use HasFactory;

    /**
     * Map the json attributes to associative arrays.
     *
     * @var array
     */
	protected $casts = [
        'submitters' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ident', 'hgnc_id', 'gene_symbol', 'mondo_id', 'disease', 'moi', 'weak', 'strong', 'submitters'
    ];

    public const STATUS_INITIALIZED = 0;

    /**
     * Type identifiers
     */
    public const TYPE_UNKNOWN = 0;

    /**
     * Automatically assign an ident on instantiation
     *
     * @param	array	$attributes
     * @return 	void
     */
    public function __construct(array $attributes = array())
    {
       $this->attributes['ident'] = (string) Uuid::generate(4);
       parent::__construct($attributes);
    }


    /**
     * Scope a query by curie.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTriple($query, $hgnc_id, $mondo_id, $moi)
    {
        return $query->where('hgnc_id', $hgnc_id)->where('mondo_id', $mondo_id)->where('moi', $moi)->orderBy('gene_symbol', 'asc');
    }


    /**
     * Scope a query by ident.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUuid($query, $id)
    {
        return $query->where('ident', $id);
    }

}
