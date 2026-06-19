<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentsExport
{
    public function download(Collection $agents): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('الوكلاء');

        $headers = [
            'اسم الوكيل',
            'الموزع',
            'إجمالي الخطوط قبل الحملة',
            'إجمالي الخطوط حتى اليوم',
            'الخطوط داخل الحملة',
            'الخطوط الجديدة',
            'خطوط التحويل',
            'النادي الحالي',
            'نسبة التحويل',
        ];

        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue($columns[$index] . '1', $header);
        }

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '374151']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 2;
        foreach ($agents as $agent) {
            $campaignLines    = $agent->new_line_count + $agent->transfer_count;
            $requiredIncrease = (int) ($agent->club?->required_increase ?? 0);
            if ($requiredIncrease > 0) {
                $transferPct = round($agent->transfer_count / $requiredIncrease * 100, 2) . '%';
            } elseif ($campaignLines > 0) {
                $transferPct = round($agent->transfer_count / $campaignLines * 100, 2) . '%';
            } else {
                $transferPct = '0%';
            }

            $sheet->setCellValue('A' . $row, $agent->agent_name);
            $sheet->setCellValue('B' . $row, $agent->distributor?->name ?? '—');
            $sheet->setCellValue('C' . $row, $agent->baseline_count);
            $sheet->setCellValue('D' . $row, $agent->current_total);
            $sheet->setCellValue('E' . $row, $campaignLines);
            $sheet->setCellValue('F' . $row, $agent->new_line_count);
            $sheet->setCellValue('G' . $row, $agent->transfer_count);
            $sheet->setCellValue('H' . $row, $agent->club?->club_name ?? 'خارج الأندية');
            $sheet->setCellValue('I' . $row, $transferPct);

            $row++;
        }

        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'الوكلاء_' . now()->format('Y-m-d') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
