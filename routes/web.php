<?php

use App\Http\Controllers\Api;
//use App\Http\Controllers\Auth;
use App\Http\Controllers\BulkUploadController;
use App\Http\Controllers\CurationByGdmController;
use App\Http\Controllers\CurationExportController;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

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

Route::get('/org-chart', [Api\OrgChartController::class, 'index']);

Route::middleware('auth')->group(function () {
    Route::get('auth/timeout-test', [Api\TimeoutTestController::class, 'index']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', [MainController::class, 'index']);
    Route::get('/home', [MainController::class, 'index']);

    Route::get('/omim/entry', [Api\OmimController::class, 'entry']);
    Route::get('/omim/search', [Api\OmimController::class, 'search']);
    Route::get('/omim/gene', [Api\OmimController::class, 'gene']);

    Route::get('bulk-uploads', [BulkUploadController::class, 'show'])->name('bulk-uploads.show');
    Route::post('bulk-uploads', [BulkUploadController::class, 'upload'])->name('bulk-uploads.upload');

    Route::get('curations/export/form', [CurationExportController::class, 'getForm'])->name('curations.export');
    Route::get('curations/export', [CurationExportController::class, 'getCsv'])->name('curations.export.download');

    Route::redirect('logs', 'admin/logs');
    Route::redirect('curations/{id}', '/#/curations/{id}');

    /**
     * Route so GCI can easily link to pre-curation detail by GDM UUID
     */
    Route::get('gdm/{gdmUuid}', CurationByGdmController::class);

    Route::impersonate();
});

Auth::routes();

Route::get('admin/password/reset/{token}', [Auth\ResetPasswordController::class, 'showResetForm']);

Route::get('/admin/login', [Auth\LoginController::class, 'showLoginForm']);

Route::middleware(['auth:api-external'])->get('api-v1-docs', function () {
    return view('swagger');
});
