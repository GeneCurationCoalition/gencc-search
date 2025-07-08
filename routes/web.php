<?php


use Illuminate\Http\Request;

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

/*
 * Gene display routes
 */
Route::group(['prefix' => 'genes'], function () {
  Route::get('/', 'GeneController@index')->name('genes');
  Route::get('/{id?}', 'GeneController@show')->name('gene-show');
  Route::get('/{id}/disease', 'GeneController@disease')->name('gene-show-disease');
  Route::get('/{id}/submitters', 'GeneController@submitter')->name('gene-show-submitter');

});

Route::group(['prefix' => 'reports'], function () {
  Route::get('/', 'ReportController@index')->name('reports');
});

Route::group(['prefix' => 'submissions'], function () {
  Route::get('/', 'SubmissionController@index')->name('submissions');
  Route::get('/{id?}', 'SubmissionController@show')->name('submission-show');
});

Route::group(['prefix' => 'submitters'], function () {
  Route::get('/', 'SubmitterController@index')->name('submitters');
  Route::get('/{id?}', 'SubmitterController@show')->name('submitter-show');
});

Route::group(['prefix' => 'disease'], function () {
  Route::get('/', 'DiseaseController@index')->name('diseases');
  Route::get('/{id?}', 'DiseaseController@show')->name('disease-show');
  Route::get('/{id}/diseases', 'DiseaseController@disease')->name('disease-show-disease');
  Route::get('/{id}/submitters', 'DiseaseController@submitter')->name('disease-show-submitter');
});

Route::group(['prefix' => 'dashboard', 'middleware' => ['auth']], function () {
  Route::get('/', 'DashboardController@index')->name('dashboard');

  Route::group(['prefix' => 'admin'], function () {
    Route::get('/', 'AdministratorController@index')->name('manage-submitters');
    Route::get('/submitter-create', 'AdministratorController@submitterCreate')->name('manage-submitter-create');
    Route::get('/{id?}', 'AdministratorController@show')->name('manage-submitter-show');
    Route::get('/{id?}/files', 'AdministratorController@files')->name('manage-submitter-show-files');
    Route::get('/{id?}/files/{file?}', 'AdministratorController@file')->name('manage-submitter-show-file');
    Route::get('/{id?}/submissions/{submission?}', 'AdministratorController@submission')->name('manage-submitter-show-submission');
    Route::get('/{id?}/profile', 'AdministratorController@profile')->name('manage-submitter-show-profile');
  });

});


Route::get('/', 'GeneController@index')->name('home');
Route::get('/home', 'GeneController@index');
Route::get('/statistics', 'StatController@index')->name('statistics');
Route::get('/download', 'DownloadController@index')->name('download');
Route::get('/download/action/submissions-export-xlsx', 'DownloadController@export_XLSX')->name('submissions-export-xlsx');
Route::get('/download/action/submissions-export-xls', 'DownloadController@export_XLS')->name('submissions-export-xls');
Route::get('/download/action/submissions-export-tsv', 'DownloadController@export_TSV')->name('submissions-export-tsv');
Route::get('/download/action/submissions-export-csv', 'DownloadController@export_CSV')->name('submissions-export-csv');

Route::get('/about', 'AboutController@index')->name('about');
Route::get('/privacy', 'PrivacyController@index')->name('privacy');
Route::get('/terms', 'TermsController@index')->name('terms');

Route::get('/reset/modal', function (Request $request) {
  $request->session()->forget('modal.welcome.dismiss');
  return redirect('/');
})->name('reset-modal');
