<?php

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

// Export endpoints
Route::get('/export/submissions', 'DownloadController@export_XLSX');
Route::get('/export/submissions-with-rowid', 'DownloadController@export_rowid_XLSX');
