<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Uuid;

use App\Traits\ModelTransform;
use App\Traits\DisplayTransform;

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
class Gene extends Model
{
    //
    use ModelTransform;
    use DisplayTransform;

    /**
     * Map the json attributes to associative arrays.
     *
     * @var array
     */
	protected $casts = [
        'prev_symbol' => 'array',
        'alias_symbol' => 'array',
        'omim_id' => 'array'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'curie', 'type', 'title', 'description', 'status', 'date_modified', 'uuid', 'is_morbid',
        'hgnc_uuid', 'hgnc_id', 'symbol', 'name', 'location', 'locus_group', 'locus_type',
        'date_symbol_changed', 'hi', 'plof', 'pli', 'lsdb', 'haplo', 'triplo', 'curation_status',
        'entrez_id', 'uniprot_id', 'function',
        'chr', 'start37', 'stop37', 'seqid37', 'stop38', 'start38', 'seqid38', 'history',
        'notes', 'date_last_curated', 'nstatus', 'mane_select', 'mane_plus', 'acmg59',
        'prev_symbol', 'alias_symbol', 'omim_id', 'ucsc_id', 'ensembl_gene_id', 'date_approved_reserved'
    ];

    public const STATUS_INITIALIZED = 0;

    /**
     * Type identifiers
     */
    public const TYPE_NAME = 1;
    public const TYPE_PREV = 2;
    public const TYPE_ALIAS = 3;


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
     * Get all the published submissions associated with this gene.
     *
     */
    public function submissions()
    {
        return $this->hasMany('App\Submission')->where('status', '=', 1);
    }


    /**
     * Scope a query by curie.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurie($query, $id)
    {
        return $query->where('curie', '=', $id)->orderBy('updated_at', 'asc');
    }


    /**
     * Scope a query by uuid.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUuid($query, $id)
    {
        return $query->where('uuid', '=', $id)->orderBy('updated_at', 'asc');
    }


    /**
     * Scope a query by symbol.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSymbol($query, $id)
    {
        return $query->where('title', '=', $id)->orderBy('updated_at', 'asc');
    }


    /**
     * Query scope by ident
     *
     * @@param	string	$ident
     * @return Illuminate\Database\Eloquent\Collection
     */
	public function scopeIdent($query, $ident)
    {
       return $query->where('ident', $ident);
    }


    /**
    * Query scope by symbol name
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
    public function scopeName($query, $name)
    {
       return $query->where('name', $name);
    }


    /**
    * Query scope by hgncid name
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
     public function scopeHgnc($query, $id)
    {
       return $query->where('hgnc_id', $id);
    }


    /**
    * Query scope by ensemble id
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
     public function scopeEnsembl($query, $id)
    {
      return $query->where('ensembl_gene_id', $id);
    }


    /**
    * Query scope by omim value
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
    public function scopeOmim($query, $value)
    {
       // strip out the prefix if present
       if (strpos($value, 'OMIM:') === 0)
           $value = substr($value, 5);

       // should be left with just a numeric string
       if (!is_numeric($value))
           return $query;

       return $query->whereJsonContains('omim_id', $value);
    }


    /**
    * Query scope by uniprot id
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
    public function scopeUniprot($query, $id)
    {
        return $query->where('uniprot_id', $id);
    }


    /**
    * Query scope by entrez id
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
    public function scopeEntrez($query, $id)
    {
        return $query->where('entrez_id', $id);
    }


    /**
    * Query scope by ucsc id
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
    public function scopeUcsc($query, $id)
    {
        return $query->where('ucsc_id', $id);
    }


    /**
    * Query scope by cytoband
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
    public function scopeCytoband($query, $name)
    {
       return $query->where('location', $name);
    }


    /**
    * Query scope by gene previous symbol
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
     public function scopePrevious($query, $symbol)
    {
        return $query->whereJsonContains('prev_symbol', $symbol);
    }


   /**
    * Query scope by gene alias symbol
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
    public function scopeAlias($query, $symbol)
    {
       return $query->whereJsonContains('alias_symbol', $symbol);
    }


    /**
    * Query scope by ACMG SF 3 flag
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
    public function scopeAcmg($query)
    {
       return $query->where('acmg59', 1);
    }


    /**
    * Get a display formatted form of aliases
    *
    * @@param
    * @return
    */
    public function getDisplayAliasesAttribute()
    {
       if (empty($this->alias_symbol))
           return 'No aliases found';

       return implode(', ', $this->alias_symbol);
    }


   /**
    * Get a display formatted form of previous names
    *
    * @@param
    * @return
    */
    public function getDisplayPreviousAttribute()
    {
       if (empty($this->prev_symbol))
           return 'No previous names found';

       return implode(', ', $this->prev_symbol);
    }


    /**
    * Get a display formatted form of omim ids
    *
    * @@param
    * @return
    */
    public function getDisplayOmimAttribute()
    {
        if (empty($this->omim_id))
             return null;

        return implode(', ', $this->omim_id);
    }


   /**
    * Get a display formatted form of grch37
    *
    * @@param
    * @return
    */
    public function getGrch37Attribute()
    {
         if ($this->chr === null || $this->start37 === null || $this->stop37 === null)
              return null;

         switch ($this->chr)
         {
              case '23':
                   $chr = 'X';
                   break;
              case '24':
                   $chr = 'Y';
                   break;
              default:
                   $chr = $this->chr;
         }

         return 'chr' . $chr . ':' . $this->start37 . '-' . $this->stop37;
    }


   /**
    * Get a display formatted form of grch38
    *
    * @@param
    * @return
    */
    public function getGrch38Attribute()
    {
         if ($this->chr == null || $this->start38 == null || $this->stop38 == null)
              return null;

         switch ($this->chr)
         {
              case '23':
                   $chr = 'X';
                   break;
              case '24':
                   $chr = 'Y';
                   break;
              default:
                   $chr = $this->chr;
         }

         return 'chr' . $chr . ':' . $this->start38 . '-' . $this->stop38;
    }


    /**
    * Search for all contained or overlapped genes and regions
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
    public static function searchList($args, $page = 0, $pagesize = 20)
    {
         // break out the args
         foreach ($args as $key => $value)
              $$key = $value;

         // initialize the collection
         $collection = collect();
         $gene_count = 0;
         $region_count = 0;

         // check the required input
         if (!isset($type) || !isset($region))
              return (object) ['count' => $collection->count(), 'collection' => $collection,
                        'gene_count' => $gene_count, 'region_count' => $region_count];

         // only recognize 37 and 38 at this time
         if ($type != 'GRCh37' && $type != 'GRCh38')
              return (object) ['count' => $collection->count(), 'collection' => $collection,
                        'gene_count' => $gene_count, 'region_count' => $region_count];

         // break out the location and clean it up
         $location = preg_split('/[:-]/', trim($region), 3);

         $chr = strtoupper($location[0]);

         if (strpos($chr, 'CHR') === 0 )   // strip out the chr
              $chr = substr($chr, 3);



         //vet the search terms
         $start = str_replace(',', '', empty($location[1]) ? '0' : $location[1]);  // strip out commas
         $stop = str_replace(',', '', empty($location[2]) ? '9999999999' : $location[2]);

         if ($chr == 'X')
              $chr = 23;

         if ($chr == 'Y')
              $chr = 24;

         if ($start == '' || $stop == '')
              return (object) ['count' => $collection->count(), 'collection' => $collection,
                        'gene_count' => $gene_count, 'region_count' => $region_count];

         if (!is_numeric($start) || !is_numeric($stop))
              return (object) ['count' => $collection->count(), 'collection' => $collection,
                        'gene_count' => $gene_count, 'region_count' => $region_count];

         if ((int) $start >= (int) $stop)
              return (object) ['count' => $collection->count(), 'collection' => $collection,
                        'gene_count' => $gene_count, 'region_count' => $region_count];

           if (isset($option) && $option == 1)  // only return contained
           {
           if ($type == 'GRCh37')
               $regions = self::where('chr', (int) $chr)
                           ->where('start37', '>=', (int) $start)
                           ->where('stop37', '<=', (int) $stop)->get();
           else if ($type == 'GRCh38')
               $regions = self::where('chr', (int) $chr)
                           ->where('start38', '>=', (int) $start)
                           ->where('stop38', '<=', (int) $stop)->get();
           }
           else
           {
               if ($type == 'GRCh37')
                   $regions = self::where('chr', (int) $chr)
                           ->where('start37', '<=', (int) $stop)
                           ->where('stop37', '>=', (int) $start)->get();
               else if ($type == 'GRCh38')
                   $regions = self::where('chr', (int) $chr)
                           ->where('start38', '<=', (int) $stop)
                           ->where('stop38', '>=', (int) $start)->get();
           }

         foreach ($regions as $region)
         {
              $region->type = $type;
              $gene_count++;

              if ($type == 'GRCh37')
              {
                   $region->start = $region->start37;
                   $region->stop = $region->stop37;
              }
              else if ($type == 'GRCh38')
              {
                   $region->start = $region->start38;
                   $region->stop = $region->stop38;
              }

              $region->relationship = ($region->start >= (int) $start && $region->stop <= (int) $stop ? 'Contained' : 'Overlap');

              $collection->push($region);
         }

         return (object) ['count' => $collection->count(), 'collection' => $collection,
                     'gene_count' => $gene_count, 'region_count' => $region_count];
   }


   /**
    * Map various gene references gene record
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
   public static function rosetta($id)
   {
       if (empty($id))
           return null;

       // do some cleanup
       $id = basename(trim($id));

       $parts = explode(':', $id);

       if (!isset($parts[1]))
       {
           if (is_numeric($id))
               $check = Gene::omim($id)->first();
           else
               $check = Gene::name($id)->first();
       }
       else
       {
           $id = $parts[1];

           switch (strtoupper($parts[0]))
           {
               case 'MIM':
               case 'OMIM':
                   $check = Gene::omim($id)->first();
                   break;
               case 'ENSEMBL':
               case 'NCBI':
                   $check = Gene::ensembl($id)->first();
                   break;
               case 'ENTREZ':
                   $check = Gene::entrez($id)->first();
                   break;
               case 'HGNC':
                   $check = Gene::hgnc('HGNC:' . $id)->first();
                   break;
               case 'UCSC':
                   $check = Gene::ucsc($id)->first();
                   break;
               case 'UNIPROT':
                   $check = Gene::uniprot($id)->first();
                   break;
               default:
                   $check = null;

           }

       }

       return $check;
   }
}
