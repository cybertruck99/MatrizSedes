<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class ReportTableDocxGenerator
{
    private const LOGO_WIDTH_EMU = 2331720;
    private const LOGO_HEIGHT_EMU = 593529;
    private const TABLE_WIDTHS = [1600, 1701, 3686, 1364, 1192, 1343];
    private const TABLE_TOTAL_WIDTH = 10886;

    public function make(
        Collection $tasks,
        User $admin,
        string $title,
        string $cite,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
        ?string $observations = null,
        bool $showPeriod = false,
    ): string {
        $this->allowDocxMemory();

        $now = now();
        $logo = $this->combinedLogo();
        $watermark = $this->watermark();
        $observations = $this->cleanText($observations);

        return $this->buildDocx([
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels' => $this->rootRelationships(),
            'word/document.xml' => $this->documentXml($tasks, $admin, $title, $cite, $now, $from, $to, $observations, $showPeriod),
            'word/_rels/document.xml.rels' => $this->documentRelationships(),
            'word/header1.xml' => $this->headerXml($logo !== null, $watermark !== null),
            'word/_rels/header1.xml.rels' => $this->headerRelationships($logo !== null, $watermark !== null),
            'word/styles.xml' => $this->stylesXml(),
            'word/settings.xml' => $this->settingsXml(),
        ], $logo, $watermark);
    }

    private function documentXml(
        Collection $tasks,
        User $admin,
        string $title,
        string $cite,
        CarbonInterface $date,
        ?CarbonInterface $from,
        ?CarbonInterface $to,
        ?string $observations,
        bool $showPeriod,
    ): string {
        $body = [];
        $body[] = $this->paragraph([$this->run($this->longDate($date), false, 24)], 'left', 0, 240);
        $body[] = $this->paragraph([$this->run('SERVICIO DEPARTAMENTAL DE SALUD - SEDES POTOSÍ', true, 24)], 'left', 0, 0);
        $body[] = $this->paragraph([$this->run('UNIDAD DE PLANIFICACIÓN Y PROYECTOS', true, 24)], 'left', 0, 160);
        $body[] = $this->paragraph([
            $this->run('CITE: ', true, 24),
            $this->run($cite, false, 24),
        ], 'left', 0, 300);
        $body[] = $this->paragraph([$this->run($title, true, 28)], 'center', 60, 220);

        if ($showPeriod && $from && $to) {
            $body[] = $this->keyValueTable([
                ['Periodo', ':', 'Del '.$this->dateOnly($from, true).', al '.$this->dateOnly($to)],
            ], [2189, 180, 5751], 8120, false, 80);
            $body[] = $this->blankParagraph(120);
            $body[] = $this->blankParagraph(80);
            $body[] = $this->blankParagraph(80);
        }

        $body[] = $this->taskTable($tasks);
        $body[] = $this->blankParagraph(0);
        $body[] = $this->keyValueTable([
            ['Supervisado por', ':', $admin->name],
        ], [3941, 253, 4351], 8545, true, 0);

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
            .'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
            .'xmlns:w10="urn:schemas-microsoft-com:office:word" '
            .'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            .'xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" '
            .'xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" '
            .'xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" '
            .'xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" '
            .'xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" '
            .'xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" '
            .'mc:Ignorable="w14 w15"><w:body>'
            .implode('', $body)
            .'<w:sectPr><w:headerReference w:type="default" r:id="rIdHeader"/>'
            .'<w:pgSz w:w="12240" w:h="15840"/>'
            .'<w:pgMar w:top="1037" w:right="1728" w:bottom="792" w:left="1728" w:header="360" w:footer="360" w:gutter="0"/>'
            .'<w:cols w:space="720"/><w:docGrid w:linePitch="360"/></w:sectPr>'
            .'</w:body></w:document>';
    }

    private function taskTable(Collection $tasks): string
    {
        $headers = ['TÉCNICO DESIGNADO', 'CARGO', 'TAREA ASIGNADA', 'INICIO', 'VENC.', 'ESTADO'];
        $xml = '<w:tbl><w:tblPr>'
            .'<w:tblW w:w="'.self::TABLE_TOTAL_WIDTH.'" w:type="dxa"/><w:jc w:val="center"/>'
            .'<w:tblLayout w:type="fixed"/>'
            .$this->tableBorders('A6A6A6', '4')
            .'<w:tblCellMar><w:top w:w="90" w:type="dxa"/><w:left w:w="80" w:type="dxa"/><w:bottom w:w="90" w:type="dxa"/><w:right w:w="80" w:type="dxa"/></w:tblCellMar>'
            .'</w:tblPr><w:tblGrid>';

        foreach (self::TABLE_WIDTHS as $width) {
            $xml .= '<w:gridCol w:w="'.$width.'"/>';
        }

        $xml .= '</w:tblGrid><w:tr><w:trPr><w:tblHeader/><w:trHeight w:val="837" w:hRule="atLeast"/></w:trPr>';
        foreach ($headers as $index => $header) {
            $xml .= $this->visibleCell($header, self::TABLE_WIDTHS[$index], true, 'F5ECEC');
        }
        $xml .= '</w:tr>';

        if ($tasks->isEmpty()) {
            $xml .= '<w:tr><w:trPr><w:trHeight w:val="617" w:hRule="atLeast"/></w:trPr>'
                .$this->visibleCell('No existen tareas para el rango seleccionado.', self::TABLE_TOTAL_WIDTH, false, null, 6)
                .'</w:tr>';
        }

        foreach ($tasks as $task) {
            $task->loadMissing('technician');
            $values = [
                $task->technician->name ?? 'Sin asignar',
                $task->technician->cargo ?? 'Cargo no definido',
                $task->assigned_task,
                $task->start_date?->format('d/m/Y') ?? '---',
                $task->due_date?->format('d/m/Y') ?? '---',
                $task->state_label,
            ];

            $xml .= '<w:tr><w:trPr><w:trHeight w:val="617" w:hRule="atLeast"/></w:trPr>';
            foreach ($values as $index => $value) {
                $xml .= $this->visibleCell((string) $value, self::TABLE_WIDTHS[$index]);
            }
            $xml .= '</w:tr>';
        }

        return $xml.'</w:tbl>';
    }

    private function visibleCell(string $text, int $width, bool $bold = false, ?string $fill = null, int $gridSpan = 1): string
    {
        $grid = $gridSpan > 1 ? '<w:gridSpan w:val="'.$gridSpan.'"/>' : '';
        $shade = $fill ? '<w:shd w:val="clear" w:color="auto" w:fill="'.$fill.'"/>' : '';

        return '<w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/>'.$grid.$shade.'<w:vAlign w:val="center"/></w:tcPr>'
            .$this->paragraph([$this->run($text, $bold, 20)], 'center', 0, 0, ['line' => 240])
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
            .'</w:tcPr>'.$this->paragraph([$this->run($text, $bold, $size)], 'center', 0, 0, ['line' => 240]).'</w:tc></w:tr>';
    }

    private function keyValueTable(array $rows, array $widths, int $totalWidth, bool $center, int $before): string
    {
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="'.$totalWidth.'" w:type="dxa"/>'
            .($center ? '<w:jc w:val="center"/>' : '')
            .'<w:tblLayout w:type="fixed"/>'
            .'<w:tblBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/><w:insideH w:val="nil"/><w:insideV w:val="nil"/></w:tblBorders>'
            .'<w:tblCellMar><w:top w:w="55" w:type="dxa"/><w:left w:w="70" w:type="dxa"/><w:bottom w:w="55" w:type="dxa"/><w:right w:w="70" w:type="dxa"/></w:tblCellMar>'
            .'</w:tblPr><w:tblGrid>';

        foreach ($widths as $width) {
            $xml .= '<w:gridCol w:w="'.$width.'"/>';
        }

        $xml .= '</w:tblGrid>';

        foreach ($rows as $rowIndex => [$label, $colon, $value]) {
            $xml .= '<w:tr><w:trPr><w:trHeight w:val="417" w:hRule="atLeast"/></w:trPr>'
                .$this->invisibleCell((string) $label, $widths[0], true, $rowIndex === 0 ? $before : 0)
                .$this->invisibleCell((string) $colon, $widths[1], true, $rowIndex === 0 ? $before : 0, 'center')
                .$this->invisibleCell((string) $value, $widths[2], false, $rowIndex === 0 ? $before : 0)
                .'</w:tr>';
        }

        return $xml.'</w:tbl>';
    }

    private function invisibleCell(string $text, int $width, bool $bold = false, int $before = 0, string $align = 'left'): string
    {
        return '<w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/>'
            .'<w:tcBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/></w:tcBorders>'
            .'</w:tcPr>'.$this->paragraph([$this->run($text, $bold, 24)], $align, $before, 0).'</w:tc>';
    }

    private function tableBorders(string $color, string $size): string
    {
        $border = ' w:val="single" w:sz="'.$size.'" w:space="0" w:color="'.$color.'"';

        return '<w:tblBorders><w:top'.$border.'/><w:left'.$border.'/><w:bottom'.$border.'/><w:right'.$border.'/><w:insideH'.$border.'/><w:insideV'.$border.'/></w:tblBorders>';
    }

    private function paragraph(array $runs, string $align = 'left', int $before = 0, int $after = 0, array $options = []): string
    {
        $keepNext = ($options['keepNext'] ?? false) ? '<w:keepNext/>' : '';
        $line = (int) ($options['line'] ?? 276);

        return '<w:p><w:pPr>'
            .$keepNext
            .'<w:spacing w:before="'.$before.'" w:after="'.$after.'" w:line="'.$line.'" w:lineRule="auto"/>'
            .'<w:jc w:val="'.$align.'"/></w:pPr>'
            .implode('', $runs)
            .'</w:p>';
    }

    private function blankParagraph(int $after = 0, int $before = 0): string
    {
        return '<w:p><w:pPr><w:spacing w:before="'.$before.'" w:after="'.$after.'" w:line="240" w:lineRule="auto"/></w:pPr></w:p>';
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
        $path = tempnam(sys_get_temp_dir(), 'sedes_table_report_');
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

    private function longDate(CarbonInterface $date): string
    {
        return 'Potosí, '.$this->dateOnly($date);
    }

    private function dateOnly(CarbonInterface $date, bool $padDay = false): string
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

        $day = $padDay ? $date->format('d') : $date->format('j');

        return $day.' de '.$months[(int) $date->format('n')].' de '.$date->format('Y');
    }

    private function cleanText(?string $text): ?string
    {
        $text = trim((string) $text);
        return $text !== '' ? $text : null;
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
