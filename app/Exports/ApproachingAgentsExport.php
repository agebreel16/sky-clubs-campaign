<?php

namespace App\Exports;

use App\Models\Club;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApproachingAgentsExport
{
    public function download(Collection $agents, ?Club $antelaaq): StreamedResponse
    {
        $requiredIncrease = (int) ($antelaaq?->required_increase ?? 25);
        $requiredTransfer = (int) ($antelaaq?->required_transfer_count ?? 15);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('قريبون من الانطلاق');

        $headers = [
            'اسم الوكيل',
            'رقم الهاتف',
            'الموزع',
            'خطوط الحملة',
            'خطوط التحويل',
            'المتبقي (خطوط)',
            'المتبقي (تحويل)',
            'نسبة الإنجاز',
        ];

        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue($columns[$index] . '1', $header);
        }

        $sheet->getStyle('A1:H1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 2;
        foreach ($agents as $agent) {
            $campaignLines  = $agent->new_line_count + $agent->transfer_count;
            $remainingLines = max($requiredIncrease - $campaignLines, 0);
            $remainingXfer  = max($requiredTransfer - $agent->transfer_count, 0);
            $linePct        = min($campaignLines / max($requiredIncrease, 1), 1.0);
            $xferPct        = min($agent->transfer_count / max($requiredTransfer, 1), 1.0);
            $progressPct    = round(($linePct + $xferPct) / 2 * 100, 1) . '%';

            $sheet->setCellValue('A' . $row, $agent->agent_name);
            $sheet->setCellValue('B' . $row, $agent->phone ?? '—');
            $sheet->setCellValue('C' . $row, $agent->distributor?->name ?? '—');
            $sheet->setCellValue('D' . $row, $campaignLines);
            $sheet->setCellValue('E' . $row, $agent->transfer_count);
            $sheet->setCellValue('F' . $row, $remainingLines);
            $sheet->setCellValue('G' . $row, $remainingXfer);
            $sheet->setCellValue('H' . $row, $progressPct);

            $row++;
        }

        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'تقرير_الانطلاق_' . now()->format('Y-m-d') . '.xlsx';

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
