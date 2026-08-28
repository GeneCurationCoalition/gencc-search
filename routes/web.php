<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::group(['prefix' => 'gencc-members'], function () {
  Auth::routes();
});

// Load balancer / container health check (public - no auth required)
Route::get('/-/healthz', function () {
  return response('ok', 200);
});

/*
 * Gene display routes
 */
Route::group(['prefix' => 'genes'], function () {
  Route::get('/', 'GeneController@index')->name('genes');
  Route::get('/{id?}', 'GeneController@show')->name('gene-show');
  Route::get('/{id}/disease', 'GeneController@disease')->name('gene-show-disease');
  Route::get('/{id}/submitters', 'GeneController@submitter')->name('gene-show-submitter');

});


Route::group(['prefix' => 'submissions'], function () {
  Route::get('/', 'SubmissionController@index')->name('submissions');
  Route::get('/{id?}', 'SubmissionController@show')->name('submission-show');
});

Route::group(['prefix' => 'members'], function () {
  Route::get('/', 'SubmitterController@index')->name('members');
  Route::get('/{id?}', 'SubmitterController@show')->name('member-show');
});

// Throwaway layout preview for issue #219, removed once a variant is chosen.
Route::get('/members-{variant}', 'SubmitterController@preview')
  ->where('variant', '[0-3]')
  ->name('members-preview');

Route::group(['prefix' => 'disease'], function () {
  Route::get('/', 'DiseaseController@index')->name('diseases');
  Route::get('/{id?}', 'DiseaseController@show')->name('disease-show');
});


Route::get('/', 'GeneController@index')->name('home');
Route::get('/home', 'GeneController@index');
Route::get('/statistics', 'StatController@index')->name('statistics');
Route::get('/conflict-viewer', 'ConflictViewerController@index')->name('conflict-viewer');
Route::get('/download', 'DownloadController@index')->name('download');
// File downloads are stateless and need no session, cookies, or CSRF middleware.
$downloadMiddlewareExclusions = [
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
];

Route::get('/conflict-viewer/download/{format}', 'ConflictViewerDownloadController')
    ->where('format', 'csv|tsv|xlsx')
    ->name('conflict-viewer-download')
    ->withoutMiddleware($downloadMiddlewareExclusions);
Route::get('/download/action/submissions-export-xlsx', 'DownloadController@export_XLSX')
    ->name('submissions-export-xlsx')
    ->withoutMiddleware($downloadMiddlewareExclusions);
Route::get('/download/action/submissions-export-tsv', 'DownloadController@export_TSV')
    ->name('submissions-export-tsv')
    ->withoutMiddleware($downloadMiddlewareExclusions);
Route::get('/download/action/submissions-export-csv', 'DownloadController@export_CSV')
    ->name('submissions-export-csv')
    ->withoutMiddleware($downloadMiddlewareExclusions);

// Logo serving from database
Route::get('/brand/submitters/{identifier}', 'LogoController@show')->name('submitter-logo');

Route::get('/about', 'AboutController@index')->name('about');
Route::get('/privacy', 'PrivacyController@index')->name('privacy');
Route::get('/terms', 'TermsController@index')->name('terms');
Route::get('/faq', 'FaqController@index')->name('faq');
