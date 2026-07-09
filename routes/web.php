<?php

use App\Http\Controllers\AgreementController;
use App\Http\Controllers\AgreementPriceController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReportController;
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
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::patch('orders/{order}/payment', [OrderController::class, 'updatePayment'])->name('orders.update-payment');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{order}/edit', [ReportController::class, 'edit'])->name('reports.edit');
    Route::put('reports/{order}', [ReportController::class, 'update'])->name('reports.update');
    Route::get('reports/{order}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    Route::resource('orders', OrderController::class);
});
