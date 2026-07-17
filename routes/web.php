<?php

use App\Http\Controllers\AgreementController;
use App\Http\Controllers\AgreementPriceController;
use App\Http\Controllers\CashClosingController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReagentController;
use App\Http\Controllers\RequestingDoctorController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SystemSettingController;
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
    Route::get('patients/reniec/dni', [PatientController::class, 'reniec'])->name('patients.reniec');
    Route::resource('patients', PatientController::class)->except(['create', 'show', 'edit']);
    Route::resource('agreements', AgreementController::class)->except(['create', 'show', 'edit']);
    Route::resource('exams', ExamController::class)->except(['create', 'show', 'edit']);
    Route::resource('reagents', ReagentController::class)->except(['create', 'show', 'edit']);
    Route::resource('requesting-doctors', RequestingDoctorController::class)->except(['create', 'show', 'edit']);
    Route::resource('agreement-prices', AgreementPriceController::class)->except(['create', 'show', 'edit']);
    Route::get('stock-movements/report/download', [StockMovementController::class, 'downloadReport'])->name('stock-movements.report.download');
    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'store', 'destroy']);
    Route::get('cash-closings', [CashClosingController::class, 'index'])->name('cash-closings.index');
    Route::get('cash-closings/export/excel', [CashClosingController::class, 'exportExcel'])->name('cash-closings.export.excel');
    Route::get('cash-closings/export/pdf', [CashClosingController::class, 'exportPdf'])->name('cash-closings.export.pdf');
    Route::post('cash-closings/expenses', [CashClosingController::class, 'storeExpense'])->name('cash-closings.expenses.store');
    Route::post('cash-closings/fixed-expenses', [CashClosingController::class, 'storeFixedExpense'])->name('cash-closings.fixed-expenses.store');
    Route::put('cash-closings/fixed-expenses/{cashFixedExpense}', [CashClosingController::class, 'updateFixedExpense'])->name('cash-closings.fixed-expenses.update');
    Route::post('cash-closings/fixed-expenses/execute', [CashClosingController::class, 'executeFixedExpenses'])->name('cash-closings.fixed-expenses.execute');
    Route::delete('cash-closings/expenses/{cashExpense}', [CashClosingController::class, 'destroyExpense'])->name('cash-closings.expenses.destroy');
    Route::get('system-settings', [SystemSettingController::class, 'index'])->name('system-settings.index');
    Route::put('system-settings', [SystemSettingController::class, 'update'])->name('system-settings.update');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('orders/{order}/triaje', [OrderController::class, 'triaje'])->name('orders.triaje');
    Route::put('orders/{order}/triaje', [OrderController::class, 'updateTriaje'])->name('orders.triaje.update');
    Route::get('orders/{order}/ficha-ingreso/plantilla', [OrderController::class, 'fichaIngresoTemplate'])->name('orders.ficha-ingreso.template');
    Route::put('orders/{order}/ficha-ingreso/plantilla', [OrderController::class, 'updateFichaIngreso'])->name('orders.ficha-ingreso.update');
    Route::get('orders/{order}/ficha-ingreso', [OrderController::class, 'fichaIngresoPdf'])->name('orders.ficha-ingreso');
    Route::get('orders/{order}/declaracion-jurada/plantilla', [OrderController::class, 'declaracionJuradaTemplate'])->name('orders.declaracion-jurada.template');
    Route::put('orders/{order}/declaracion-jurada/plantilla', [OrderController::class, 'updateDeclaracionJurada'])->name('orders.declaracion-jurada.update');
    Route::get('orders/{order}/declaracion-jurada', [OrderController::class, 'declaracionJuradaPdf'])->name('orders.declaracion-jurada');
    Route::patch('orders/{order}/file', [OrderController::class, 'updateOrderFile'])->name('orders.update-file');
    Route::patch('orders/{order}/payment', [OrderController::class, 'updatePayment'])->name('orders.update-payment');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{order}/edit', [ReportController::class, 'edit'])->name('reports.edit');
    Route::put('reports/{order}', [ReportController::class, 'update'])->name('reports.update');
    Route::get('reports/{order}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    Route::resource('orders', OrderController::class);
});
