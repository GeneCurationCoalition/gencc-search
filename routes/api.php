<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Release endpoint (publish/unpublish submissions)
Route::post('/release', 'ReleaseController@process');

// Export endpoints
Route::get('/export/submissions', 'DownloadController@export_XLSX');
Route::get('/export/submissions-with-rowid', 'DownloadController@export_rowid_XLSX');
