<?php

use App\Http\Controllers\AgreementController;
use App\Http\Controllers\AgreementPriceController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReagentController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);
    Route::resource('patients', PatientController::class)->except(['create', 'show', 'edit']);
    Route::resource('agreements', AgreementController::class)->except(['create', 'show', 'edit']);
    Route::resource('exams', ExamController::class)->except(['create', 'show', 'edit']);
    Route::resource('reagents', ReagentController::class)->except(['create', 'show', 'edit']);
    Route::resource('agreement-prices', AgreementPriceController::class)->except(['create', 'show', 'edit']);
    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'store', 'destroy']);
    Route::resource('orders', OrderController::class);
});
