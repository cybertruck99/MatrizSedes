<?php

namespace App\Support;

use App\Models\TaskRecord;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReportPdfGenerator
{
    private const PAGE_WIDTH = 1275;
    private const PAGE_HEIGHT = 1650;
    private const PDF_WIDTH = 612;
    private const PDF_HEIGHT = 792;
    private const SCALE = 2.0833333333;

    // Carta a 150 dpi: superior 3 cm, izquierdo aprox. 3.7 cm, derecho/inferior 2.5 cm.
    private const MARGIN_TOP = 177;
    private const MARGIN_LEFT = 220;
    private const MARGIN_RIGHT = 148;
    private const MARGIN_BOTTOM = 148;
    private const CONTENT_WIDTH = 907;
    private const TABLE_X = 80;
    private const TABLE_TOTAL_WIDTH = 1115;
    private const TABLE_WIDTHS = [190, 170, 435, 100, 100, 120];

    private string $font;
    private string $fontBold;
    private array $pages = [];
    private $image;
    private int $cursorY = 0;
    private string $currentTableCite = '';
    private string $currentTableTitle = '';
    private int $tableFontSize = 18;

    public function __construct()
    {
        $this->allowPdfMemory();

        $windows = getenv('WINDIR') ?: 'C:\\Windows';
        $this->font = $this->firstExisting([
            $windows.'\\Fonts\\arial.ttf',
            $windows.'\\Fonts\\calibri.ttf',
        ]);
        $this->fontBold = $this->firstExisting([
            $windows.'\\Fonts\\arialbd.ttf',
            $windows.'\\Fonts\\calibrib.ttf',
            $this->font,
        ]);
    }

    public function task(TaskRecord $task, User $admin, ?string $cite = null): string
    {
        $this->allowPdfMemory();
        $task->loadMissing(['technician', 'submitter', 'taskFiles']);
        $now = now();
        $cite ??= $this->cite('TASK-'.$task->id, $now);
        $this->currentTableCite = $cite;
        $this->currentTableTitle = $task->assigned_task;

        $this->pages = [];
        $this->newPage();
        $this->drawReportHeader($cite, $now, $task->assigned_task);

        $this->cursorY = 500;
        $responsible = trim(($task->technician->name ?? 'Sin asignar').', '.($task->technician->cargo ?? 'Cargo no definido'), ' ,');
        $fields = [
            'Nombre del Responsable:' => $responsible,
            'Inicio:' => $task->start_date ? $this->dateOnly($task->start_date) : 'Sin fecha',
            'Vencimiento:' => $task->due_date ? $this->dateOnly($task->due_date) : 'Sin fecha',
            'Tiempo restante:' => $this->timeStatus($task),
            'Observación Inicial:' => $task->initial_observation ?: 'Sin observación inicial.',
            'Archivos enviados:' => $task->uploaded_files_summary,
            'Estado de revisión:' => $task->state_label,
            'Revisión de archivos:' => $task->files_reviewed_at ? $this->dateTimeText($task->files_reviewed_at) : 'Sin revisión',
        ];

        foreach ($fields as $label => $value) {
            $this->labelValue($label, $value);
        }

        if (filled($task->final_observations)) {
            $this->labelValue('Observaciones:', $task->final_observations);
        }

        $this->labelValue('Revisado por:', $admin->name);
        $this->drawSignature($admin);
        $this->finishPage();

        return $this->buildPdf($this->pages);
    }

    public function taskTable(Collection $tasks, User $admin, string $subtitle, string $cite, CarbonInterface $from, CarbonInterface $to): string
    {
        $this->allowPdfMemory();
        $now = now();
        $this->currentTableCite = $cite;
        $this->currentTableTitle = $subtitle;

        $this->pages = [];
        $this->tableFontSize = $this->fitTableFontSize($tasks);
        $this->newPage();
        $this->drawReportHeader($cite, $now, $subtitle);
        $this->cursorY = 465;
        $this->drawDateRange($from, $to, $admin);
        $this->cursorY += 16;
        $this->drawTableHeader();

        foreach ($tasks as $task) {
            $this->drawTaskRow($task);
        }

        if ($tasks->isEmpty()) {
            $this->text('No existen tareas para el rango seleccionado.', self::MARGIN_LEFT, $this->cursorY + 45, $this->pt(11));
        }

        $this->finishPage();

        return $this->buildPdf($this->pages);
    }

    public function timeStatus(TaskRecord $task): string
    {
        $task->loadMissing('taskFiles');
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

    private function newPage(): void
    {
        $this->image = imagecreatetruecolor(self::PAGE_WIDTH, self::PAGE_HEIGHT);
        imagealphablending($this->image, true);
        imagesavealpha($this->image, true);
        $white = imagecolorallocate($this->image, 255, 255, 255);
        imagefilledrectangle($this->image, 0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT, $white);
        $this->drawWatermark();
    }

    private function finishPage(): void
    {
        ob_start();
        imagejpeg($this->image, null, 92);
        $this->pages[] = ob_get_clean();
        imagedestroy($this->image);
    }

    private function drawReportHeader(string $cite, CarbonInterface $date, string $title): void
    {
        $this->drawLogo(public_path('assets/img/LOGO_circulo_SEDES.png'), 1070, 45, 103, 103);
        $this->drawLogo(public_path('assets/img/Logo_gobernacion_rectangulo.png'), 770, 54, 260, 80);

        $titleSize = $this->pt(12);
        $bodySize = $this->pt(11);
        $this->text('SERVICIO DEPARTAMENTAL DE SALUD - SEDES POTOSÍ', self::MARGIN_LEFT, 175, $titleSize, true);
        $this->text('UNIDAD DE PLANIFICACIÓN Y PROYECTOS', self::MARGIN_LEFT, 210, $bodySize, true);
        $this->text('INFORME DE REGISTRO', self::MARGIN_LEFT, 262, $titleSize, true);
        $this->text('CITE: '.$cite, self::MARGIN_LEFT, 308, $titleSize, true);
        $this->text($this->longDate($date), self::MARGIN_LEFT, 346, $bodySize);

        $refLines = $this->wrap('REF.: '.mb_strtoupper($title, 'UTF-8'), self::CONTENT_WIDTH, $bodySize, true);
        $this->drawParagraph($refLines, self::MARGIN_LEFT, 395, self::CONTENT_WIDTH, $bodySize, true, $this->lineHeight($bodySize, 1.15), false);
    }

    private function drawDateRange(CarbonInterface $from, CarbonInterface $to, User $admin): void
    {
        $this->labelValue('Periodo:', $this->dateOnly($from).' al '.$this->dateOnly($to), $this->pt(10), 24);
        $this->labelValue('Revisado por:', trim($admin->name.', '.($admin->cargo ?? 'Cargo no definido'), ' ,'), $this->pt(10), 24);
    }

    private function labelValue(string $label, ?string $value, ?int $size = null, ?int $lineHeight = null): void
    {
        $size ??= $this->pt(11);
        $lineHeight ??= $this->lineHeight($size);
        $blockWidth = 760;
        $blockX = (int) ((self::PAGE_WIDTH - $blockWidth) / 2);
        $labelWidth = 255;
        $valueX = $blockX + $labelWidth + 20;
        $valueWidth = $blockWidth - $labelWidth - 20;
        $lines = $this->wrap((string) ($value ?: '---'), $valueWidth, $size);
        $height = max(1, count($lines)) * $lineHeight + 12;
        $this->ensureSpace($height + 8);

        $this->text($label, $blockX, $this->cursorY, $size, true);
        $this->drawParagraph($lines, $valueX, $this->cursorY, $valueWidth, $size, false, $lineHeight, false);
        $this->cursorY += $height;
    }

    private function drawTableHeader(): void
    {
        $headers = ['TÉCNICO DESIGNADO', 'CARGO', 'TAREA ASIGNADA', 'INICIO', 'VENC.', 'ESTADO'];
        $x = self::TABLE_X;
        $y = $this->cursorY;
        $height = 58;
        $this->rect($x, $y, self::TABLE_TOTAL_WIDTH, $height, [255, 247, 247], [194, 65, 65]);

        foreach ($headers as $index => $header) {
            $width = self::TABLE_WIDTHS[$index];
            $this->drawCell($header, $x, $y, $width, $height, max($this->pt(8), $this->tableFontSize), true, 'center');
            $x += $width;
            $this->line($x, $y, $x, $y + $height, [194, 65, 65]);
        }

        $this->cursorY += $height;
    }

    private function drawTaskRow(TaskRecord $task): void
    {
        $task->loadMissing(['technician', 'taskFiles']);
        $values = [
            $task->technician->name ?? 'Sin asignar',
            $task->technician->cargo ?? 'Cargo no definido',
            $task->assigned_task,
            $task->start_date ? $this->dateOnly($task->start_date) : '---',
            $task->due_date ? $this->dateOnly($task->due_date) : '---',
            $task->state_label,
        ];

        $size = $this->tableFontSize;
        $lineHeight = $this->lineHeight($size, 1.2);
        $lineGroups = [];
        $height = 52;

        foreach ($values as $index => $value) {
            $lines = $this->wrap((string) $value, self::TABLE_WIDTHS[$index] - 18, $size);
            $lines = $this->limitLines($lines, 7);
            $lineGroups[] = $lines;
            $height = max($height, count($lines) * $lineHeight + 18);
        }

        if ($this->cursorY + $height > self::PAGE_HEIGHT - self::MARGIN_BOTTOM) {
            $this->finishPage();
            $this->newPage();
            $this->drawReportHeader($this->currentTableCite, now(), $this->currentTableTitle.' - CONTINUACIÓN');
            $this->cursorY = 465;
            $this->drawTableHeader();
        }

        $x = self::TABLE_X;
        $y = $this->cursorY;
        $this->rect($x, $y, self::TABLE_TOTAL_WIDTH, $height, [255, 255, 255], [235, 210, 210]);

        foreach ($lineGroups as $index => $lines) {
            $width = self::TABLE_WIDTHS[$index];
            $align = in_array($index, [3, 4, 5], true) ? 'center' : 'left';
            $this->drawCellLines($lines, $x, $y, $width, $height, $size, false, $align);
            $x += $width;
            $this->line($x, $y, $x, $y + $height, [235, 210, 210]);
        }

        $this->cursorY += $height;
    }

    private function drawSignature(User $admin): void
    {
        $y = self::PAGE_HEIGHT - self::MARGIN_BOTTOM - 95;
        $this->center('................', $y, $this->pt(11), true);
        $this->center($admin->name, $y + 42, $this->pt(11), true);
        $this->center($admin->cargo ?? 'Cargo no definido', $y + 78, $this->pt(11));
    }

    private function drawWatermark(): void
    {
        $path = public_path('assets/img/logo_cuadrado_sedes.jpg');
        if (! is_file($path)) {
            return;
        }
        $src = @imagecreatefromjpeg($path);
        if (! $src) {
            return;
        }

        $w = 470;
        $h = 470;
        $dst = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $w, $h, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
        imagefilter($dst, IMG_FILTER_BRIGHTNESS, 52);
        imagefilter($dst, IMG_FILTER_CONTRAST, -40);
        imagecopymerge($this->image, $dst, (int) ((self::PAGE_WIDTH - $w) / 2), 585, 0, 0, $w, $h, 9);
        imagedestroy($src);
        imagedestroy($dst);
    }

    private function drawLogo(string $path, int $x, int $y, int $w, int $h): void
    {
        if (! is_file($path)) {
            return;
        }

        $src = @imagecreatefromstring(file_get_contents($path));
        if (! $src) {
            return;
        }

        imagealphablending($this->image, true);
        imagecopyresampled($this->image, $src, $x, $y, 0, 0, $w, $h, imagesx($src), imagesy($src));
        imagedestroy($src);
    }

    private function drawCell(string $text, int $x, int $y, int $width, int $height, int $size, bool $bold = false, string $align = 'left'): void
    {
        $lines = $this->wrap($text, $width - 16, $size, $bold);
        $this->drawCellLines($lines, $x, $y, $width, $height, $size, $bold, $align);
    }

    private function drawCellLines(array $lines, int $x, int $y, int $width, int $height, int $size, bool $bold = false, string $align = 'left'): void
    {
        $lineHeight = $this->lineHeight($size, 1.2);
        $totalHeight = count($lines) * $lineHeight;
        $currentY = $y + (int) max($size + 10, (($height - $totalHeight) / 2) + $size);

        foreach ($lines as $line) {
            $lineX = $x + 8;
            if ($align === 'center') {
                $lineX = $x + (int) (($width - $this->textWidth($line, $size, $bold)) / 2);
            }
            $this->text($line, $lineX, $currentY, $size, $bold);
            $currentY += $lineHeight;
        }
    }

    private function drawParagraph(array $lines, int $x, int $y, int $width, int $size, bool $bold, int $lineHeight, bool $justify): void
    {
        $justify = false;

        foreach ($lines as $index => $line) {
            $currentY = $y + ($index * $lineHeight);
            if ($justify && $index < count($lines) - 1) {
                $this->justifiedLine($line, $x, $currentY, $width, $size, $bold);
            } else {
                $this->text($line, $x, $currentY, $size, $bold);
            }
        }
    }

    private function justifiedLine(string $line, int $x, int $y, int $width, int $size, bool $bold): void
    {
        $words = preg_split('/\s+/u', trim($line)) ?: [];
        if (count($words) < 2) {
            $this->text($line, $x, $y, $size, $bold);
            return;
        }

        $textWidth = array_sum(array_map(fn ($word) => $this->textWidth($word, $size, $bold), $words));
        $space = ($width - $textWidth) / (count($words) - 1);
        if ($space < 4 || $space > 45) {
            $this->text($line, $x, $y, $size, $bold);
            return;
        }

        $cursor = $x;
        foreach ($words as $word) {
            $this->text($word, (int) $cursor, $y, $size, $bold);
            $cursor += $this->textWidth($word, $size, $bold) + $space;
        }
    }

    private function text(string $text, int|float $x, int|float $y, int $size, bool $bold = false, ?array $color = null): void
    {
        $rgb = $color ?: [32, 33, 36];
        $allocated = imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]);
        imagettftext($this->image, $size, 0, (int) $x, (int) $y, $allocated, $bold ? $this->fontBold : $this->font, $text);
    }

    private function center(string $text, int $y, int $size, bool $bold = false, int $maxWidth = self::CONTENT_WIDTH): void
    {
        $lines = $this->wrap($text, $maxWidth, $size, $bold);
        foreach ($lines as $index => $line) {
            $width = $this->textWidth($line, $size, $bold);
            $this->text($line, (self::PAGE_WIDTH - $width) / 2, $y + ($index * $this->lineHeight($size, 1.0)), $size, $bold);
        }
    }

    private function rect(int $x, int $y, int $w, int $h, array $fill, array $border): void
    {
        $fillColor = imagecolorallocate($this->image, $fill[0], $fill[1], $fill[2]);
        $borderColor = imagecolorallocate($this->image, $border[0], $border[1], $border[2]);
        imagefilledrectangle($this->image, $x, $y, $x + $w, $y + $h, $fillColor);
        imagerectangle($this->image, $x, $y, $x + $w, $y + $h, $borderColor);
    }

    private function line(int $x1, int $y1, int $x2, int $y2, array $color): void
    {
        $allocated = imagecolorallocate($this->image, $color[0], $color[1], $color[2]);
        imageline($this->image, $x1, $y1, $x2, $y2, $allocated);
    }

    private function wrap(string $text, int $maxWidth, int $size, bool $bold = false): array
    {
        $text = trim(preg_replace('/[ \t]+/u', ' ', $text) ?? $text);
        if ($text === '') {
            return ['---'];
        }

        $lines = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $paragraph) {
            $line = '';
            foreach (preg_split('/\s+/u', trim($paragraph)) ?: [] as $word) {
                foreach ($this->splitLongWord($word, $maxWidth, $size, $bold) as $piece) {
                    $candidate = $line === '' ? $piece : $line.' '.$piece;
                    if ($this->textWidth($candidate, $size, $bold) <= $maxWidth) {
                        $line = $candidate;
                        continue;
                    }

                    if ($line !== '') {
                        $lines[] = $line;
                    }
                    $line = $piece;
                }
            }
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines ?: ['---'];
    }

    private function fitTableFontSize(Collection $tasks): int
    {
        foreach ([10, 9, 8, 7] as $points) {
            $size = $this->pt($points);
            $lineHeight = $this->lineHeight($size, 1.2);
            $tooTall = false;

            foreach ($tasks as $task) {
                $task->loadMissing('technician');
                $values = [
                    $task->technician->name ?? 'Sin asignar',
                    $task->technician->cargo ?? 'Cargo no definido',
                    $task->assigned_task,
                    optional($task->start_date)->format('d/m/Y') ?? '---',
                    optional($task->due_date)->format('d/m/Y') ?? '---',
                    $task->state_label,
                ];

                $maxLines = 1;
                foreach ($values as $index => $value) {
                    $lines = $this->wrap((string) $value, self::TABLE_WIDTHS[$index] - 18, $size);
                    $maxLines = max($maxLines, count($lines));
                }

                if (($maxLines * $lineHeight + 22) > 150) {
                    $tooTall = true;
                    break;
                }
            }

            if (! $tooTall) {
                return $size;
            }
        }

        return $this->pt(7);
    }

    private function splitLongWord(string $word, int $maxWidth, int $size, bool $bold): array
    {
        if ($this->textWidth($word, $size, $bold) <= $maxWidth) {
            return [$word];
        }

        $parts = [];
        $part = '';
        foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            $candidate = $part.$char;
            if ($part !== '' && $this->textWidth($candidate, $size, $bold) > $maxWidth) {
                $parts[] = $part;
                $part = $char;
                continue;
            }
            $part = $candidate;
        }

        if ($part !== '') {
            $parts[] = $part;
        }

        return $parts ?: [$word];
    }

    private function limitLines(array $lines, int $max): array
    {
        if (count($lines) <= $max) {
            return $lines;
        }

        $limited = array_slice($lines, 0, $max);
        $limited[$max - 1] = rtrim($limited[$max - 1], ' .'). '...';

        return $limited;
    }

    private function textWidth(string $text, int $size, bool $bold = false): int
    {
        $box = imagettfbbox($size, 0, $bold ? $this->fontBold : $this->font, $text);
        return (int) abs(($box[2] ?? 0) - ($box[0] ?? 0));
    }

    private function lineHeight(int $size, float $spacing = 1.15): int
    {
        return (int) ceil($size * $spacing + 7);
    }

    private function pt(float $points): int
    {
        return (int) round($points * self::SCALE);
    }

    private function ensureSpace(int $height): void
    {
        if ($this->cursorY + $height <= self::PAGE_HEIGHT - self::MARGIN_BOTTOM) {
            return;
        }

        $this->finishPage();
        $this->newPage();
        $this->drawReportHeader($this->currentTableCite ?: $this->cite('CONT', now()), now(), $this->currentTableTitle ?: 'CONTINUACIÓN');
        $this->cursorY = 430;
    }

    private function cite(string $seed, CarbonInterface $date): string
    {
        $number = (crc32($seed.'-'.$date->format('YmdHis')) % 900) + 100;
        return 'SEDES/POT/UPP/INF-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT).'/'.$date->format('Y');
    }

    private function longDate(CarbonInterface $date): string
    {
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return 'Potosí, '.$date->format('j').' de '.$months[(int) $date->format('n')].' de '.$date->format('Y');
    }

    private function dateOnly(CarbonInterface $date): string
    {
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return $date->format('j').' de '.$months[(int) $date->format('n')].' de '.$date->format('Y');
    }

    private function dateTimeText(CarbonInterface $date): string
    {
        $period = (int) $date->format('G') < 12 ? 'a.m.' : 'p.m.';

        return $this->dateOnly($date).' a las '.$date->format('g:i').' '.$period;
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

    private function firstExisting(array $paths): string
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return $paths[0];
    }

    private function allowPdfMemory(): void
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

    private function buildPdf(array $jpegPages): string
    {
        $objects = [];
        $pageIds = [];
        $nextId = 3;

        foreach ($jpegPages as $jpeg) {
            $imageId = $nextId++;
            $contentId = $nextId++;
            $pageId = $nextId++;
            $pageIds[] = $pageId;

            $objects[$imageId] = "<< /Type /XObject /Subtype /Image /Width ".self::PAGE_WIDTH." /Height ".self::PAGE_HEIGHT." /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($jpeg)." >>\nstream\n".$jpeg."\nendstream";
            $content = "q\n".self::PDF_WIDTH." 0 0 ".self::PDF_HEIGHT." 0 0 cm\n/Im0 Do\nQ";
            $objects[$contentId] = "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream";
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ".self::PDF_WIDTH." ".self::PDF_HEIGHT."] /Resources << /XObject << /Im0 {$imageId} 0 R >> >> /Contents {$contentId} 0 R >>";
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', array_map(fn ($id) => $id.' 0 R', $pageIds)).'] /Count '.count($pageIds).' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$object."\nendobj\n";
        }

        $xref = strlen($pdf);
        $count = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }
}
