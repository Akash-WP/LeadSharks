<?php
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Define headers for your Excel file:
$headers = [
    'Company Name',
    'Company Type',
    'Website',
    'Address',
    'Country',
    'State',
    'City',
    'Pincode',
    'Other Info',
    'Interested In',
    'Lead Source',
    'Remarks',
    'Assigned To',
    'Status',
    'Contact Person Name 1',
    'Contact Number 1',
    'Email 1',
    'Designation 1',
    'Contact Person Name 2',
    'Contact Number 2',
    'Email 2',
    'Designation 2',
];

// Write the headers to the first row
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '2', $header);
    $col++;
}

// Write instructions/rules in the first row (merged cells for better visibility)
$sheet->mergeCells('A1:V1');
$sheet->setCellValue('A1', 
    "Instructions: 
- You can add up to 2 contact persons per lead.
- Leave additional contact columns blank if unused.
- Status must be one of: New/Prospect, Open, Working, Not a Target, Disqualified, Nurture, Opportunity Created, Opportunity Lost, Inactive.
- Assigned To and Lead Source must match existing system entries.
- Do NOT change header row (row 2).
- Data starts from row 3."
);

// Style the instruction row (bold, yellow fill)
$styleArray = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
               'startColor' => ['rgb' => 'FFFF99']],
    'alignment' => ['wrapText' => true, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP],
];
$sheet->getStyle('A1:V1')->applyFromArray($styleArray);

// Set column widths for readability
foreach (range('A', 'V') as $col) {
    $sheet->getColumnDimension($col)->setWidth(20);
}

// Send the file to the browser as download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="lead_import_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
