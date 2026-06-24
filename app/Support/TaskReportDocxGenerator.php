<?php

namespace App\Support;

use App\Models\TaskRecord;
use App\Models\User;
use Carbon\CarbonInterface;
use RuntimeException;
use ZipArchive;

class TaskReportDocxGenerator
{
    private const LOGO_WIDTH_EMU = 2331720;
    private const LOGO_HEIGHT_EMU = 593529;

    public function make(TaskRecord $task, User $admin, string $cite, ?string $requestedObservations = null): string
    {
        $this->allowDocxMemory();
        $task->loadMissing(['technician', 'submitter', 'taskFiles']);

        $now = now();
        $observations = $this->resolveObservations($requestedObservations, $task->final_observations);
        $rows = $this->taskRows($task, $admin);
        $logo = $this->combinedLogo();
        $watermark = $this->watermark();

        return $this->buildDocx([
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels' => $this->rootRelationships(),
            'word/document.xml' => $this->documentXml($task, $admin, $now, $cite, $rows, $observations),
            'word/_rels/document.xml.rels' => $this->documentRelationships(),
            'word/header1.xml' => $this->headerXml($logo !== null, $watermark !== null),
            'word/_rels/header1.xml.rels' => $this->headerRelationships($logo !== null, $watermark !== null),
            'word/styles.xml' => $this->stylesXml(),
            'word/settings.xml' => $this->settingsXml(),
        ], $logo, $watermark);
    }

    private function taskRows(TaskRecord $task, User $admin): array
    {
        $responsible = trim(($task->technician->name ?? 'Sin asignar').', '.($task->technician->cargo ?? 'Cargo no definido'), ' ,');

        $rows = [
            ['Responsable', ':', $responsible],
            ['Tarea Asignada', ':', $task->assigned_task ?: 'Sin tarea asignada'],
        ];

        if ($this->isFilled($task->initial_observation)) {
            $rows[] = ['Observación Inicial', ':', $task->initial_observation];
        }

        return [
            ...$rows,
            ['Fecha de Inicio', ':', $task->start_date ? $this->dateOnly($task->start_date) : 'Sin fecha'],
            ['Fecha de Vencimiento', ':', $task->due_date ? $this->dateOnly($task->due_date) : 'Sin fecha'],
            ['Tiempo restante', ':', $this->timeStatus($task)],
            ['Archivos enviados', ':', $task->uploaded_files_summary],
            ['Estado de revisión', ':', $this->stateLabel($task)],
            ['Revisión de archivos', ':', $task->files_reviewed_at ? $this->dateTimeText($task->files_reviewed_at) : 'Sin revisión'],
            ['Supervisado por', ':', $admin->name],
        ];
    }

    private function documentXml(TaskRecord $task, User $admin, CarbonInterface $date, string $cite, array $rows, ?string $observations): string
    {
        $body = [];
        $body[] = $this->paragraph([$this->run($this->longDate($date), false, 24)], 'left', 0, 240);
        $body[] = $this->paragraph([$this->run('SERVICIO DEPARTAMENTAL DE SALUD - SEDES POTOSÍ', true, 24)], 'left', 0, 0);
        $body[] = $this->paragraph([$this->run('UNIDAD DE PLANIFICACIÓN Y PROYECTOS', true, 24)], 'left', 0, 160);
        $body[] = $this->paragraph([
            $this->run('CITE: ', true, 24),
            $this->run($cite, false, 24),
        ], 'left', 0, 320);
        $body[] = $this->paragraph([$this->run('INFORME DE REGISTRO', true, 28)], 'center', 80, 300);
        $body[] = $this->infoTable($rows);

        if ($observations !== null) {
            $body[] = $this->paragraph([
                $this->run('Observaciones:  ', true, 24),
                $this->run($observations, false, 24),
            ], 'left', 260, 240);
        }

        $body[] = $this->paragraph([
            $this->run('Es cuanto se informa y certifica para los fines correspondientes.', false, 24),
        ], 'left', 220, 0);
        $body[] = $this->signatureTable($admin);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" '
            .'xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" '
            .'xmlns:o="urn:schemas-microsoft-com:office:office" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            .'xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" '
            .'xmlns:v="urn:schemas-microsoft-com:vml" '
            .'xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" '
            .'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
            .'xmlns:w10="urn:schemas-microsoft-com:office:word" '
            .'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            .'xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" '
            .'xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" '
            .'xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" '
            .'xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" '
            .'xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" '
            .'xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" '
            .'mc:Ignorable="w14 w15 wp14"><w:body>'
            .implode('', $body)
            .'<w:sectPr><w:headerReference w:type="default" r:id="rIdHeader"/>'
            .'<w:pgSz w:w="12240" w:h="15840"/>'
            .'<w:pgMar w:top="1037" w:right="1728" w:bottom="792" w:left="1728" w:header="360" w:footer="360" w:gutter="0"/>'
            .'<w:cols w:space="720"/><w:docGrid w:linePitch="360"/></w:sectPr>'
            .'</w:body></w:document>';
    }

    private function infoTable(array $rows): string
    {
        $widths = [2450, 220, 5930];
        $xml = '<w:tbl><w:tblPr>'
            .'<w:tblW w:w="8600" w:type="dxa"/><w:jc w:val="center"/>'
            .'<w:tblLayout w:type="fixed"/>'
            .'<w:tblBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/><w:insideH w:val="nil"/><w:insideV w:val="nil"/></w:tblBorders>'
            .'<w:tblCellMar><w:top w:w="55" w:type="dxa"/><w:left w:w="70" w:type="dxa"/><w:bottom w:w="55" w:type="dxa"/><w:right w:w="70" w:type="dxa"/></w:tblCellMar>'
            .'</w:tblPr><w:tblGrid>';

        foreach ($widths as $width) {
            $xml .= '<w:gridCol w:w="'.$width.'"/>';
        }

        $xml .= '</w:tblGrid>';

        foreach ($rows as [$label, $colon, $value]) {
            $xml .= '<w:tr>'
                .$this->tableCell((string) $label, $widths[0], true)
                .$this->tableCell((string) $colon, $widths[1], false, 'center')
                .$this->tableCell((string) $value, $widths[2])
                .'</w:tr>';
        }

        return $xml.'</w:tbl>';
    }

    private function tableCell(string $text, int $width, bool $bold = false, string $align = 'left'): string
    {
        return '<w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/>'
            .'<w:tcBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/></w:tcBorders>'
            .'<w:vAlign w:val="top"/></w:tcPr>'
            .$this->paragraph([$this->run($text, $bold, 24)], $align, 0, 0)
            .'</w:tc>';
    }

    private function signatureTable(User $admin): string
    {
        $width = 5600;

        return '<w:tbl><w:tblPr>'
            .'<w:tblpPr w:leftFromText="0" w:rightFromText="0" w:topFromText="0" w:bottomFromText="0" w:vertAnchor="page" w:horzAnchor="margin" w:tblpXSpec="center" w:tblpY="13680"/>'
            .'<w:tblW w:w="'.$width.'" w:type="dxa"/><w:jc w:val="center"/>'
            .'<w:tblLayout w:type="fixed"/>'
            .'<w:tblBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/><w:insideH w:val="nil"/><w:insideV w:val="nil"/></w:tblBorders>'
            .'<w:tblCellMar><w:top w:w="0" w:type="dxa"/><w:left w:w="0" w:type="dxa"/><w:bottom w:w="0" w:type="dxa"/><w:right w:w="0" w:type="dxa"/></w:tblCellMar>'
            .'</w:tblPr><w:tblGrid><w:gridCol w:w="'.$width.'"/></w:tblGrid>'
            .$this->signatureRow('…………………………………..', $width)
            .$this->signatureRow($admin->name, $width, true, 25)
            .$this->signatureRow($admin->cargo ?: 'Cargo no definido', $width)
            .'</w:tbl>';
    }

    private function signatureRow(string $text, int $width, bool $bold = false, int $size = 24): string
    {
        return '<w:tr><w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/>'
            .'<w:tcBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/></w:tcBorders>'
            .'</w:tcPr>'.$this->paragraph([$this->run($text, $bold, $size)], 'center', 0, 0).'</w:tc></w:tr>';
    }

    private function paragraph(array $runs, string $align = 'left', int $before = 0, int $after = 0, array $options = []): string
    {
        $alignment = $align === 'both' ? 'left' : $align;
        $keepNext = ($options['keepNext'] ?? false) ? '<w:keepNext/>' : '';
        $keepLines = ($options['keepLines'] ?? false) ? '<w:keepLines/>' : '';

        return '<w:p><w:pPr>'
            .$keepNext.$keepLines
            .'<w:spacing w:before="'.$before.'" w:after="'.$after.'" w:line="276" w:lineRule="auto"/>'
            .'<w:jc w:val="'.$alignment.'"/></w:pPr>'
            .implode('', $runs)
            .'</w:p>';
    }

    private function run(string $text, bool $bold = false, int $size = 24): string
    {
        $properties = '<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:eastAsia="Arial" w:cs="Arial"/>'
            .($bold ? '<w:b/><w:bCs/>' : '')
            .'<w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/></w:rPr>';

        $parts = preg_split('/\R/u', $text) ?: [$text];
        $content = '';
        foreach ($parts as $index => $part) {
            if ($index > 0) {
                $content .= '<w:br/>';
            }
            $content .= '<w:t xml:space="preserve">'.$this->e($part).'</w:t>';
        }

        return '<w:r>'.$properties.$content.'</w:r>';
    }

    private function headerXml(bool $hasLogo, bool $hasWatermark): string
    {
        $watermark = $hasWatermark ? '<w:r><w:drawing><wp:anchor distT="0" distB="0" distL="0" distR="0" simplePos="0" relativeHeight="0" behindDoc="1" locked="0" layoutInCell="1" allowOverlap="1">'
            .'<wp:simplePos x="0" y="0"/><wp:positionH relativeFrom="page"><wp:posOffset>2135000</wp:posOffset></wp:positionH>'
            .'<wp:positionV relativeFrom="page"><wp:posOffset>3440000</wp:posOffset></wp:positionV><wp:extent cx="3500000" cy="3500000"/>'
            .'<wp:effectExtent l="0" t="0" r="0" b="0"/><wp:wrapNone/><wp:docPr id="2" name="Marca de agua SEDES"/>'
            .'<wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            .'<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="0" name="watermark.png"/><pic:cNvPicPr/></pic:nvPicPr>'
            .'<pic:blipFill><a:blip r:embed="rIdWatermark"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="3500000" cy="3500000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            .'</pic:pic></a:graphicData></a:graphic></wp:anchor></w:drawing></w:r>' : '';

        $logo = $hasLogo ? '<w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            .'<wp:extent cx="'.self::LOGO_WIDTH_EMU.'" cy="'.self::LOGO_HEIGHT_EMU.'"/>'
            .'<wp:effectExtent l="0" t="0" r="0" b="0"/><wp:docPr id="1" name="Logos institucionales"/>'
            .'<wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            .'<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="0" name="logos.png"/><pic:cNvPicPr/></pic:nvPicPr>'
            .'<pic:blipFill><a:blip r:embed="rIdLogo"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.self::LOGO_WIDTH_EMU.'" cy="'.self::LOGO_HEIGHT_EMU.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            .'</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>' : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            .'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">'
            .'<w:p><w:pPr><w:jc w:val="right"/></w:pPr>'.$watermark.$logo.'</w:p></w:hdr>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/>'
            .'<w:qFormat/><w:pPr><w:spacing w:line="276" w:lineRule="auto"/></w:pPr>'
            .'<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:eastAsia="Arial" w:cs="Arial"/>'
            .'<w:sz w:val="24"/><w:szCs w:val="24"/><w:lang w:val="es-BO"/></w:rPr></w:style>'
            .'</w:styles>';
    }

    private function settingsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:zoom w:percent="100"/><w:defaultTabStop w:val="720"/>'
            .'</w:settings>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Default Extension="png" ContentType="image/png"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'<Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>'
            .'</Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>';
    }

    private function documentRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'<Relationship Id="rIdSettings" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>'
            .'<Relationship Id="rIdHeader" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header1.xml"/>'
            .'</Relationships>';
    }

    private function headerRelationships(bool $hasLogo, bool $hasWatermark): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .($hasLogo ? '<Relationship Id="rIdLogo" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/logos.png"/>' : '')
            .($hasWatermark ? '<Relationship Id="rIdWatermark" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/watermark.png"/>' : '')
            .'</Relationships>';
    }

    private function buildDocx(array $files, ?string $logo, ?string $watermark): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sedes_task_report_');
        if ($path === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del reporte.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            throw new RuntimeException('No se pudo iniciar el documento DOCX.');
        }

        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        if ($logo !== null) {
            $zip->addFromString('word/media/logos.png', $logo);
        }

        if ($watermark !== null) {
            $zip->addFromString('word/media/watermark.png', $watermark);
        }

        $zip->close();

        $binary = file_get_contents($path);
        @unlink($path);

        if ($binary === false) {
            throw new RuntimeException('No se pudo leer el reporte generado.');
        }

        return $binary;
    }

    private function combinedLogo(): ?string
    {
        $this->allowDocxMemory();

        if (! function_exists('imagecreatefrompng')) {
            return null;
        }

        $gobernacionPath = public_path('assets/img/Logo_gobernacion_rectangulo.png');
        $sedesPath = public_path('assets/img/LOGO_circulo_SEDES.png');

        if (! is_file($gobernacionPath) || ! is_file($sedesPath)) {
            return null;
        }

        $gobernacion = @imagecreatefrompng($gobernacionPath);
        $sedes = @imagecreatefrompng($sedesPath);
        if (! $gobernacion || ! $sedes) {
            return null;
        }

        $canvas = imagecreatetruecolor(760, 190);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, 760, 190, $white);
        imagealphablending($canvas, true);

        $this->copyFitted($canvas, $gobernacion, 0, 21, 560, 148);
        $this->copyFitted($canvas, $sedes, 590, 20, 150, 150);

        ob_start();
        imagepng($canvas);
        $binary = ob_get_clean();

        imagedestroy($gobernacion);
        imagedestroy($sedes);
        imagedestroy($canvas);

        return is_string($binary) ? $binary : null;
    }

    private function watermark(): ?string
    {
        if (! function_exists('imagecreatefromjpeg')) {
            return null;
        }

        $path = public_path('assets/img/logo_cuadrado_sedes.jpg');
        if (! is_file($path)) {
            return null;
        }

        $source = @imagecreatefromjpeg($path);
        if (! $source) {
            return null;
        }

        $size = 540;
        $resized = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($resized, 255, 255, 255);
        imagefilledrectangle($resized, 0, 0, $size, $size, $white);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $size, $size, imagesx($source), imagesy($source));
        imagefilter($resized, IMG_FILTER_BRIGHTNESS, 45);
        imagefilter($resized, IMG_FILTER_CONTRAST, -38);

        $watermark = imagecreatetruecolor($size, $size);
        imagealphablending($watermark, false);
        imagesavealpha($watermark, true);
        $transparent = imagecolorallocatealpha($watermark, 255, 255, 255, 127);
        imagefilledrectangle($watermark, 0, 0, $size, $size, $transparent);

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                $rgb = imagecolorat($resized, $x, $y);
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;
                $alpha = ($red > 246 && $green > 246 && $blue > 246) ? 127 : 112;
                imagesetpixel($watermark, $x, $y, imagecolorallocatealpha($watermark, $red, $green, $blue, $alpha));
            }
        }

        ob_start();
        imagepng($watermark);
        $binary = ob_get_clean();

        imagedestroy($source);
        imagedestroy($resized);
        imagedestroy($watermark);

        return is_string($binary) ? $binary : null;
    }

    private function copyFitted($canvas, $source, int $x, int $y, int $maxWidth, int $maxHeight): void
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            return;
        }

        $scale = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        $width = (int) round($sourceWidth * $scale);
        $height = (int) round($sourceHeight * $scale);
        $destX = $x + (int) floor(($maxWidth - $width) / 2);
        $destY = $y + (int) floor(($maxHeight - $height) / 2);

        imagecopyresampled($canvas, $source, $destX, $destY, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
    }

    private function resolveObservations(?string $requested, ?string $taskObservations): ?string
    {
        $requested = trim((string) $requested);
        if ($requested !== '') {
            return $requested;
        }

        $taskObservations = trim((string) $taskObservations);
        return $taskObservations !== '' ? $taskObservations : null;
    }

    private function timeStatus(TaskRecord $task): string
    {
        $due = $task->due_date?->copy()->endOfDay();
        if (! $due) {
            return 'Sin fecha de vencimiento';
        }

        $submittedAt = $task->submitted_at ?? $task->taskFiles->first()?->created_at;
        $reference = $submittedAt ?: now();
        $duration = $this->humanDuration($due, $reference);

        if ($submittedAt) {
            return $submittedAt->lessThanOrEqualTo($due)
                ? 'La Tarea fue enviada: '.$duration.' antes'
                : 'La Tarea fue enviada: '.$duration.' después';
        }

        return now()->lessThanOrEqualTo($due)
            ? $duration.' restante'
            : 'La Tarea está retrasada por: '.$duration;
    }

    private function humanDuration(CarbonInterface $a, CarbonInterface $b): string
    {
        $seconds = (int) abs($a->diffInSeconds($b, false));
        if ($seconds < 60) {
            return $seconds.' '.($seconds === 1 ? 'segundo' : 'segundos');
        }

        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            return $minutes.' '.($minutes === 1 ? 'minuto' : 'minutos');
        }

        $hours = intdiv($seconds, 3600);
        if ($hours < 24) {
            $remainingMinutes = intdiv($seconds % 3600, 60);
            return trim($hours.' '.($hours === 1 ? 'hora' : 'horas').' '.$remainingMinutes.' '.($remainingMinutes === 1 ? 'minuto' : 'minutos'));
        }

        $days = intdiv($seconds, 86400);
        if ($days < 31) {
            $remainingHours = intdiv($seconds % 86400, 3600);
            return trim($days.' '.($days === 1 ? 'día' : 'días').' '.$remainingHours.' '.($remainingHours === 1 ? 'hora' : 'horas'));
        }

        $months = intdiv($days, 30);
        if ($months <= 12) {
            $remainingDays = $days % 30;
            return trim($months.' '.($months === 1 ? 'mes' : 'meses').' '.$remainingDays.' '.($remainingDays === 1 ? 'día' : 'días'));
        }

        return 'hace tiempo';
    }

    private function stateLabel(TaskRecord $task): string
    {
        return match ($task->state) {
            'cumplido' => 'Sí cumplió',
            'no cumplido' => 'No cumplió',
            'retraso' => 'Retraso',
            default => 'Pendiente',
        };
    }

    private function cite(string $seed, CarbonInterface $date): string
    {
        $number = (crc32($seed.'-'.$date->format('YmdHis')) % 900) + 100;
        return 'SEDES/PT/UPP/INF-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT).'/'.$date->format('Y');
    }

    private function dateOnly(CarbonInterface $date): string
    {
        $months = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        return $date->format('j').' de '.$months[(int) $date->format('n')].' de '.$date->format('Y');
    }

    private function dateTimeText(CarbonInterface $date): string
    {
        $period = (int) $date->format('G') < 12 ? 'a.m.' : 'p.m.';

        return $this->dateOnly($date).' a las '.$date->format('g:i').' '.$period;
    }

    private function signatureGap(?string $observations, array $rows): int
    {
        $textLength = mb_strlen((string) $observations, 'UTF-8');
        $rowPenalty = max(0, count($rows) - 9) * 90;

        if ($textLength > 1300) {
            return max(360, 620 - $rowPenalty);
        }

        if ($textLength > 800) {
            return max(480, 760 - $rowPenalty);
        }

        return max(680, 980 - $rowPenalty);
    }

    private function longDate(CarbonInterface $date): string
    {
        $months = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        return 'Potosí, '.$date->format('j').' de '.$months[(int) $date->format('n')].' de '.$date->format('Y');
    }

    private function isFilled(?string $value): bool
    {
        return trim((string) $value) !== '';
    }

    private function allowDocxMemory(): void
    {
        $current = ini_get('memory_limit');
        if ($current === '-1') {
            return;
        }

        $bytes = $this->memoryToBytes($current);
        if ($bytes > 0 && $bytes < 536870912) {
            @ini_set('memory_limit', '512M');
        }
    }

    private function memoryToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
