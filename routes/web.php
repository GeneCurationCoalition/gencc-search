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


Route::group(['prefix' => 'submissions'], function () {
  Route::get('/', 'SubmissionController@index')->name('submissions');
  Route::get('/{id?}', 'SubmissionController@show')->name('submission-show');
});

Route::group(['prefix' => 'members'], function () {
  Route::get('/', 'SubmitterController@index')->name('members');
  Route::get('/{id?}', 'SubmitterController@show')->name('member-show');
});

Route::group(['prefix' => 'disease'], function () {
  Route::get('/', 'DiseaseController@index')->name('diseases');
  Route::get('/{id?}', 'DiseaseController@show')->name('disease-show');
  Route::get('/{id}/diseases', 'DiseaseController@disease')->name('disease-show-disease');
  Route::get('/{id}/submitters', 'DiseaseController@submitter')->name('disease-show-submitter');
});


Route::get('/', 'GeneController@index')->name('home');
Route::get('/home', 'GeneController@index');
Route::get('/statistics', 'StatController@index')->name('statistics');
Route::get('/download', 'DownloadController@index')->name('download');
Route::get('/download/action/submissions-export-xlsx', 'DownloadController@export_XLSX')->name('submissions-export-xlsx');
Route::get('/download/action/submissions-export-xls', 'DownloadController@export_XLS')->name('submissions-export-xls');
Route::get('/download/action/submissions-export-tsv', 'DownloadController@export_TSV')->name('submissions-export-tsv');
Route::get('/download/action/submissions-export-csv', 'DownloadController@export_CSV')->name('submissions-export-csv');

// Logo serving from database
Route::get('/brand/submitters/{identifier}', 'LogoController@show')->name('submitter-logo');

Route::get('/about', 'AboutController@index')->name('about');
Route::get('/privacy', 'PrivacyController@index')->name('privacy');
Route::get('/terms', 'TermsController@index')->name('terms');
Route::get('/faq', 'FaqController@index')->name('faq');

Route::get('/reset/modal', function (Request $request) {
  $request->session()->forget('modal.welcome.dismiss');
  return redirect('/');
})->name('reset-modal');
