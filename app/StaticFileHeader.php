<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 * @category   Model
 * @package    GenCC
 * @author     P. Weller <pweller1@geisinger.edu>
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PackageName
 * @see        NetOther, Net_Sample::Net_Sample()
 * @since      Class available since Release 1.0.0
 *
 * The StaticFileHeaders table stores HTTP headers for external static files
 * to enable caching and avoid re-downloading files that haven't changed.
 *
 * This table operates as an append-only log, where new records are inserted
 * for each header check rather than updating existing records. This provides
 * a historical record of header changes over time.
 *
 * */
class StaticFileHeader extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'file_identifier',
        'content_length',
        'last_modified',
        'etag',
    ];

    /**
     * Get the latest header record for a file identifier
     *
     * @param string $identifier
     * @return static|null
     */
    public static function latest($identifier)
    {
        return static::where('file_identifier', $identifier)
                     ->orderBy('created_at', 'desc')
                     ->first();
    }

    /**
     * Check if cached headers match the provided headers
     *
     * Uses ETag as primary identifier if available (most reliable).
     * Falls back to Last-Modified if no ETag.
     * Content-Length is only used as a tiebreaker when both are present.
     *
     * @param array $headers Headers from HTTP HEAD request
     * @return bool
     */
    public function matchesHeaders(array $headers): bool
    {
        $etag = $headers['etag'] ?? null;
        $lastModified = $headers['last-modified'] ?? null;
        $contentLength = $headers['content-length'] ?? null;

        // If ETags are available and match, consider it a match
        if ($etag !== null && $this->etag !== null) {
            return $this->etag === $etag;
        }

        // If Last-Modified is available and matches, consider it a match
        if ($lastModified !== null && $this->last_modified !== null) {
            return $this->last_modified === $lastModified;
        }

        // Fall back to content-length comparison only if both present
        if ($contentLength !== null && $this->content_length !== null) {
            return $this->content_length === $contentLength;
        }

        // No comparable headers available - assume changed
        return false;
    }

    /**
     * Update headers from HTTP HEAD response
     *
     * @param array $headers Headers from HTTP HEAD request
     * @return void
     */
    public function updateFromHeaders(array $headers): void
    {
        $this->etag = $headers['etag'] ?? null;
        $this->last_modified = $headers['last-modified'] ?? null;
        $this->content_length = $headers['content-length'] ?? null;
        $this->save();
    }
}
