<?php

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
    $mpdf = new \Mpdf\Mpdf(['tempDir' => public_path("tmp")]);
    $html = view('users.report')->render();
    $mpdf->WriteHTML($html);
    return $mpdf->Output('document.pdf', 'I');
//    \Barryvdh\DomPDF\Facade\Pdf::setOption(['dpi' => 150, 'defaultFont' => 'sans-serif']);
//    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('users.report');
//    return $pdf->stream('document.pdf');
});
