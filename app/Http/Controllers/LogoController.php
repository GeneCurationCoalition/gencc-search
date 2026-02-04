<?php

namespace App\Http\Controllers;

use App\Submitter;
use Illuminate\Http\Request;

class LogoController extends Controller
{
    /**
     * Display a submitter's logo from the database.
     *
     * Serves logos from the submitters.logo_contents column.
     * URL format: /brand/submitters/{identifier}.png
     * where identifier is the submitter's ident (e.g., GENCC_000101)
     *
     * @param string $identifier The submitter identifier (ident with underscores)
     * @return \Illuminate\Http\Response
     */
    public function show($identifier)
    {
        // Remove the .png extension if present
        $identifier = preg_replace('/\.png$/i', '', $identifier);

        // Try to find the submitter by ident first (underscore format like GENCC_000101)
        $submitter = Submitter::where('ident', $identifier)->first();

        // If not found, try with curie format (colon format like GENCC:000101)
        if (!$submitter) {
            $curieIdentifier = str_replace('_', ':', $identifier);
            $submitter = Submitter::where('curie', $curieIdentifier)->first();
        }

        // If still not found or no logo, return default logo
        if (!$submitter || !$submitter->logo_contents) {
            $defaultLogo = public_path('brand/submitters/default.png');
            if (file_exists($defaultLogo)) {
                return response()->file($defaultLogo);
            }
            // Return a 404 if no default logo exists
            abort(404);
        }

        // Determine content type
        $contentType = $submitter->logo_mime_type ?? 'image/png';

        return response($submitter->logo_contents)
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
