<?php

namespace App\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export tabel ke Excel tanpa paket eksternal & tanpa ext-zip.
 * Menghasilkan SpreadsheetML (Excel 2003 XML) yang dibuka langsung oleh
 * Excel, LibreOffice Calc, dan Google Sheets.
 *
 * Pemakaian:
 *   return (new TableExport(['Tanggal','Nama'], $rows, 'Laporan'))->download('laporan.xls');
 *
 * $rows = array of array (baris demi baris). Nilai numerik otomatis jadi angka.
 */
class TableExport
{
    public function __construct(
        private array $headings,
        private array $rows,
        private string $title = 'Sheet1',
    ) {}

    private function esc($v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function cell($v): string
    {
        if (is_int($v) || is_float($v)) {
            return '<Cell><Data ss:Type="Number">'.$v.'</Data></Cell>';
        }

        return '<Cell><Data ss:Type="String">'.$this->esc($v).'</Data></Cell>';
    }

    public function xml(): string
    {
        $sheet = $this->esc(substr($this->title, 0, 31));

        $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $out .= '<?mso-application progid="Excel.Sheet"?>'."\n";
        $out .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $out .= '<Styles><Style ss:ID="hdr"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#057D5D" ss:Pattern="Solid"/></Style></Styles>';
        $out .= '<Worksheet ss:Name="'.$sheet.'"><Table>';

        $out .= '<Row>';
        foreach ($this->headings as $h) {
            $out .= '<Cell ss:StyleID="hdr"><Data ss:Type="String">'.$this->esc($h).'</Data></Cell>';
        }
        $out .= '</Row>';

        foreach ($this->rows as $row) {
            $out .= '<Row>';
            foreach ($row as $v) {
                $out .= $this->cell($v);
            }
            $out .= '</Row>';
        }

        $out .= '</Table></Worksheet></Workbook>';

        return $out;
    }

    public function download(string $filename): StreamedResponse
    {
        if (! str_ends_with(strtolower($filename), '.xls')) {
            $filename .= '.xls';
        }

        $xml = $this->xml();

        return response()->streamDownload(
            fn () => print ($xml),
            $filename,
            ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8'],
        );
    }
}
