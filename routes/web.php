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

Route::group(['prefix' => 'dashboard'], function () {
  Route::get('/', 'DashboardController@index')->name('dashboard');

  Route::group(['prefix' => 'admin'], function () {
    Route::get('/', 'AdministratorController@index')->name('manage-submitters');
    Route::get('/{id?}', 'AdministratorController@show')->name('manage-submitters-show');
  });

});


Route::get('/', 'GeneController@index')->name('home');
Route::get('/home', 'GeneController@index');
Route::get('/statistics', 'StatController@index')->name('statistics');

Route::get('/reset/modal', function (Request $request) {
  $request->session()->forget('modal.welcome.dismiss');
  return redirect('/');
})->name('reset-modal');