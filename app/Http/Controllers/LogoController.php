<?php

namespace App\Http\Controllers;

use App\Submitter;

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

            // Match the named placeholder the logo accessor renders inline.
            if ($submitter) {
                return response($submitter->logoPlaceholderSvg())
                    ->header('Content-Type', 'image/svg+xml')
                    ->header('Cache-Control', 'public, max-age=86400');
            }

            // Return a 404 if no default logo exists
            abort(404);
        }

        // Determine content type
        $contentType = $submitter->logo_mime_type ?? 'image/png';

        // logo_contents holds base64-encoded image data; older rows may hold raw
        // binary, which strict decoding rejects and we then pass through as-is.
        $contents = base64_decode($submitter->logo_contents, true);
        if ($contents === false) {
            $contents = $submitter->logo_contents;
        }

        return response($contents)
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
