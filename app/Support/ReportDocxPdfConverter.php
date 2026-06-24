<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class ReportDocxPdfConverter
{
    public function convert(string $docx, string $filename): ?string
    {
        $directory = storage_path('app/report-conversions');
        if (! is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        if (! is_dir($directory) || ! is_writable($directory)) {
            return null;
        }

        foreach ($this->candidates() as $binary) {
            $pdf = $this->attemptConversion($binary, $directory, $docx, $filename);
            if ($pdf !== null) {
                return $pdf;
            }
        }

        return null;
    }

    private function attemptConversion(string $binary, string $directory, string $docx, string $filename): ?string
    {
        $baseName = pathinfo($filename, PATHINFO_FILENAME) ?: 'Registro';
        $safeBaseName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $baseName) ?: 'Registro';
        $token = (string) Str::uuid();
        $docxPath = $directory.DIRECTORY_SEPARATOR.$safeBaseName.'_'.$token.'.docx';
        $pdfPath = $directory.DIRECTORY_SEPARATOR.$safeBaseName.'_'.$token.'.pdf';

        try {
            if (file_put_contents($docxPath, $docx) === false) {
                return null;
            }

            $process = new Process([
                $binary,
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $directory,
                $docxPath,
            ]);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($pdfPath)) {
                return null;
            }

            $pdf = file_get_contents($pdfPath);

            return is_string($pdf) && $pdf !== '' ? $pdf : null;
        } catch (Throwable) {
            return null;
        } finally {
            @unlink($docxPath);
            @unlink($pdfPath);
        }
    }

    private function candidates(): array
    {
        return array_values(array_filter(array_unique([
            env('SOFFICE_PATH'),
            env('LIBREOFFICE_PATH'),
            'soffice',
            'libreoffice',
            'C:\Program Files\LibreOffice\program\soffice.exe',
            'C:\Program Files (x86)\LibreOffice\program\soffice.exe',
        ])));
    }
}
