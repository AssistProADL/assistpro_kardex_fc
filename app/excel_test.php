<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'SKU');
$sheet->setCellValue('B1', 'Cantidad');

$sheet->setCellValue('A2', 'ABC123');
$sheet->setCellValue('B2', 10);

// Generar archivo temporal
$tempXlsx = tempnam(sys_get_temp_dir(), 'excel_') . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($tempXlsx);

// Descargar archivo al navegador
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="test.xlsx"');
readfile($tempXlsx);

// Borrar archivo temporal
unlink($tempXlsx);
exit;
