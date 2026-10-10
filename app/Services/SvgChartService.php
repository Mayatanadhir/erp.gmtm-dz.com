<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

final class SvgChartService
{
    // ── أبعاد اللوحة المحسوبة بدقة ──
    private float $width;

    private float $height;

    // ── هوامش واسعة تمنع أي قص للنصوص أو تداخل في المحاور ──
    private float $paddingTop = 52.0;

    private float $paddingBottom = 96.0;  // مساحة كافية لتدرجات X، عنوان المحور، ومفتاح الرسم (Legend)

    private float $paddingLeft = 120.0; // مساحة كافية للعنوان الرأسي وأرقام المحور Y

    private float $paddingRight = 45.0;  // مساحة كافية لشارات EMT يميناً

    private float $xInnerPad = 28.0;  // مسافة أمان داخلية لتدرجات البداية والنهاية

    // ── حدود البيانات ──
    private float $minY;

    private float $maxY;

    /**
     * @var array<int, array{index: int, val: float, point: mixed, phase: string}>
     */
    private array $sequenceSteps = [];

    private bool $hasDescending = false;

    /**
     * توليد كود SVG لمنحنى الخطأ المترولوجي بتسلسل كامل (0 -> 25 -> ... -> 100 ثم 100 -> 75 -> ... -> 0)
     *
     * @param  Collection  $points  نقاط القياس
     * @param  float  $emt  حد الخطأ الأقصى المسموح به (EMT)
     * @param  float  $rangeMin  بداية المجال
     * @param  float  $rangeMax  نهاية المجال
     * @param  float  $width  عرض اللوحة
     * @param  float  $height  ارتفاع اللوحة
     * @return string كود SVG جاهز للطباعة والعرض
     */
    public function generateErrorCurve(
        Collection $points,
        float $emt,
        float $rangeMin,
        float $rangeMax,
        float $width = 960.0,
        float $height = 530.0
    ): string {

        if ($points->isEmpty()) {
            return $this->emptyMessage($width, $height);
        }

        $this->width = $width;
        $this->height = $height;

        // ── 1. تنظيم تسلسل الخطوات العشر (أو الخمس) بالتتابع الكامل ──
        $this->hasDescending = $points->contains(fn ($p) => ($p->cycle_phase ?? '') === 'Descending');

        $ascending = $points->filter(fn ($p) => ($p->cycle_phase ?? 'Ascending') === 'Ascending')
            ->sortBy(fn ($p) => $this->getRefValue($p))->values();

        $descending = $this->hasDescending
            ? $points->filter(fn ($p) => ($p->cycle_phase ?? '') === 'Descending')
                ->sortByDesc(fn ($p) => $this->getRefValue($p))->values()
            : collect();

        $this->sequenceSteps = [];
        $stepIdx = 0;

        // نقاط الصعود: 0 -> 25 -> 50 -> 75 -> 100
        foreach ($ascending as $pt) {
            $this->sequenceSteps[] = [
                'index' => $stepIdx++,
                'val' => $this->getRefValue($pt),
                'point' => $pt,
                'phase' => 'ascending',
            ];
        }

        // نقاط النزول: 100 -> 75 -> 50 -> 25 -> 0
        if ($this->hasDescending) {
            foreach ($descending as $pt) {
                $this->sequenceSteps[] = [
                    'index' => $stepIdx++,
                    'val' => $this->getRefValue($pt),
                    'point' => $pt,
                    'phase' => 'descending',
                ];
            }
        }

        // ── 2. حدود محور Y (±yLimit) ──
        $maxAbsError = $points->max(fn ($p) => abs((float) ($p->absolute_error ?? 0.0))) ?? 0.0;
        $yLimit = max($maxAbsError, abs($emt)) * 1.60;
        if ($yLimit == 0.0) {
            $yLimit = 1.0;
        }
        $this->minY = -$yLimit;
        $this->maxY = $yLimit;

        // ── 3. بناء SVG مع عزل اتجاه النصوص LTR ──
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$this->width.' '.$this->height.'" width="100%" height="100%"'
            .' class="gmtm-svg-chart" dir="ltr" style="direction:ltr; unicode-bidi:isolate; font-family:\'Inter\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background:#f8fafc;">';

        $svg .= $this->drawDefs();
        $svg .= $this->drawBackground();
        $svg .= $this->drawGrid();
        $svg .= $this->drawEmtBand($emt);
        $svg .= $this->drawEmtLines($emt);
        $svg .= $this->drawZeroLine();
        $svg .= $this->drawAxes();

        // ── 4. رسم مسارات الصعود والنزول المتسلسلة ──
        $ascSteps = array_values(array_filter($this->sequenceSteps, fn ($s) => $s['phase'] === 'ascending'));
        $descSteps = array_values(array_filter($this->sequenceSteps, fn ($s) => $s['phase'] === 'descending'));

        if (! empty($ascSteps)) {
            $svg .= $this->drawStepPath($ascSteps, '#1d4ed8', 'none', 2.8, 'svg-path-ascending');
        }

        if (! empty($descSteps)) {
            // خط وصل اختياري ناعم بين نهاية الصعود (100) وبداية النزول (100)
            if (! empty($ascSteps)) {
                $lastAsc = end($ascSteps);
                $firstDesc = reset($descSteps);
                $x1 = $this->mapStepToX($lastAsc['index']);
                $y1 = $this->mapY((float) ($lastAsc['point']->absolute_error ?? 0.0));
                $x2 = $this->mapStepToX($firstDesc['index']);
                $y2 = $this->mapY((float) ($firstDesc['point']->absolute_error ?? 0.0));
                $svg .= '<line class="svg-cycle-connector" x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="#94a3b8" stroke-width="1.5" stroke-dasharray="2,2"/>';
            }

            $svg .= $this->drawStepPath($descSteps, '#047857', '7,4', 2.8, 'svg-path-descending');
        }

        // ── 5. رسم نقاط وبيانات الـ 10 خطوات ──
        $svg .= $this->drawStepPointsWithLabels($ascSteps, '#1d4ed8', '#1e3a8a', 'ascending');
        if (! empty($descSteps)) {
            $svg .= $this->drawStepPointsWithLabels($descSteps, '#047857', '#064e3b', 'descending');
        }

        // Legend أسفل منطقة الرسم بمساحة منفصلة
        $svg .= $this->drawLegend($this->hasDescending);

        $svg .= '</svg>';

        return $svg;
    }

    // ═══════════════════════════════════════
    //  مساعدات: الاستخراج والإسقاط
    // ═══════════════════════════════════════

    private function getRefValue(mixed $point): float
    {
        if (is_array($point)) {
            return (float) ($point['corrected_reference_value'] ?? $point['reference_value'] ?? $point['reference_temperature'] ?? $point['equivalent_value'] ?? 0.0);
        }

        return (float) ($point->corrected_reference_value ?? $point->reference_value ?? $point->reference_temperature ?? $point->equivalent_value ?? 0.0);
    }

    private function mapStepToX(int $stepIndex): float
    {
        $totalSteps = count($this->sequenceSteps);
        if ($totalSteps <= 1) {
            return $this->paddingLeft + ($this->width - $this->paddingLeft - $this->paddingRight) / 2.0;
        }
        $usable = $this->width - $this->paddingLeft - $this->paddingRight - (2 * $this->xInnerPad);

        return $this->paddingLeft + $this->xInnerPad + ($stepIndex / (float) ($totalSteps - 1)) * $usable;
    }

    private function mapY(float $val): float
    {
        $usable = $this->height - $this->paddingTop - $this->paddingBottom;

        return $this->height - $this->paddingBottom - (($val - $this->minY) / ($this->maxY - $this->minY)) * $usable;
    }

    private function fmtNum(float $val, int $dec = 4): string
    {
        return number_format($val, $dec, '.', '');
    }

    /**
     * تنسيق أرقام تدريجات المحور بشكل أنيق (0، 25، 50 بدلاً من 0.00، 25.00)
     */
    private function fmtAxisVal(float $val): string
    {
        if (abs($val - round($val)) < 0.0001) {
            return (string) (int) round($val);
        }
        $formatted = number_format($val, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    // ═══════════════════════════════════════
    //  مكونات SVG
    // ═══════════════════════════════════════

    private function emptyMessage(float $w, float $h): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'" width="100%" height="100%">'
            .'<rect width="100%" height="100%" fill="#f8fafc" rx="10"/>'
            .'<text x="'.($w / 2).'" y="'.($h / 2).'" text-anchor="middle" fill="#94a3b8" font-size="14" font-family="sans-serif">'
            .'Aucune donn&#233;e disponible pour le graphique'
            .'</text>'
            .'</svg>';
    }

    private function drawDefs(): string
    {
        return '<defs>'
            .'<filter id="ch-shadow" x="-5%" y="-5%" width="110%" height="110%">'
            .'<feDropShadow dx="0" dy="2" stdDeviation="4" flood-color="#00000012"/>'
            .'</filter>'
            .'</defs>';
    }

    private function drawBackground(): string
    {
        // خلفية اللوحة الكاملة
        $svg = '<rect class="svg-bg-full" width="'.$this->width.'" height="'.$this->height.'" fill="#f8fafc"/>';
        // منطقة الرسم ذات الخلفية البيضاء
        $svg .= '<rect class="svg-plot-area" '
            .'x="'.$this->paddingLeft.'" '
            .'y="'.$this->paddingTop.'" '
            .'width="'.($this->width - $this->paddingLeft - $this->paddingRight).'" '
            .'height="'.($this->height - $this->paddingTop - $this->paddingBottom).'" '
            .'fill="#ffffff" stroke="#cbd5e1" stroke-width="1" rx="4" filter="url(#ch-shadow)"/>';

        return $svg;
    }

    private function drawGrid(): string
    {
        $svg = '<g class="svg-grid-group" stroke="#1415155d" stroke-width="1">';
        $xRight = $this->width - $this->paddingRight;
        $yTop = $this->paddingTop;
        $yBottom = $this->height - $this->paddingBottom;

        // ── 1. خطوط عمودية وتدريجات X لجميع الخطوات (0 ➔ 25 ... 100 ثم 100 ➔ 75 ... 0) ──
        foreach ($this->sequenceSteps as $step) {
            $x = $this->mapStepToX($step['index']);

            // خط الشبكة العمودي
            $svg .= '<line class="svg-grid-line svg-grid-line-x" x1="'.$x.'" y1="'.$yTop.'" x2="'.$x.'" y2="'.$yBottom.'" stroke-dasharray="3,3"/>';
            // علامة تدريج سفلية
            $svg .= '<line x1="'.$x.'" y1="'.$yBottom.'" x2="'.$x.'" y2="'.($yBottom + 5).'" stroke="#475569" stroke-width="1.2"/>';

            // أرقام المحور X باللون الأسود الكامل (#000000)
            $color = '#000000';
            $label = $this->fmtAxisVal((float) $step['val']);

            $svg .= '<text class="svg-axis-label-x svg-axis-label-'.$step['phase'].'" x="'.$x.'" y="'.($yBottom + 20).'" '
                .'text-anchor="middle" font-size="12" font-weight="700" fill="'.$color.'" style="direction:ltr;">'.$label.'</text>';
        }

        // ── فاصل دورة الصعود عن دورة النزول في منتصف الـ 100 والـ 100 ──
        if ($this->hasDescending && count($this->sequenceSteps) >= 10) {
            $xMid = ($this->mapStepToX(4) + $this->mapStepToX(5)) / 2.0;
            $svg .= '<line class="svg-cycle-divider" x1="'.$xMid.'" y1="'.$yTop.'" x2="'.$xMid.'" y2="'.$yBottom.'" stroke="#94a3b8" stroke-dasharray="4,4" stroke-width="1.2"/>';
        }

        // ── 2. خطوط أفقية (8 تقسيمات على Y) ──
        $steps = 8;
        $range = $this->maxY - $this->minY;
        for ($i = 0; $i <= $steps; $i++) {
            $val = $this->minY + ($i / (float) $steps) * $range;
            $y = $this->mapY($val);
            if (abs($val) < $range * 0.015) {
                continue;
            }
            $svg .= '<line class="svg-grid-line svg-grid-line-y" x1="'.$this->paddingLeft.'" y1="'.$y.'" x2="'.$xRight.'" y2="'.$y.'" stroke-dasharray="3,3"/>';
            $svg .= '<line x1="'.($this->paddingLeft - 5).'" y1="'.$y.'" x2="'.$this->paddingLeft.'" y2="'.$y.'" stroke="#64748b" stroke-width="1.2"/>';
            $label = $this->fmtNum($val, 4);
            $svg .= '<text class="svg-axis-label-y" x="'.($this->paddingLeft - 9).'" y="'.($y + 4).'" '
                .'text-anchor="end" font-size="11" font-weight="600" fill="#334155" style="direction:ltr;">'.$label.'</text>';
        }

        $svg .= '</g>';

        return $svg;
    }

    private function drawZeroLine(): string
    {
        $y = $this->mapY(0.0);
        $xLeft = $this->paddingLeft;
        $xRight = $this->width - $this->paddingRight;

        $svg = '<line class="svg-zero-line" x1="'.$xLeft.'" y1="'.$y.'" x2="'.$xRight.'" y2="'.$y.'" '
            .'stroke="#000000" stroke-width="1.6"/>';
        $svg .= '<text class="svg-zero-label" x="'.($xLeft - 9).'" y="'.($y + 4).'" '
            .'text-anchor="end" font-size="11.5" font-weight="700" fill="#000000" style="direction:ltr;">0</text>';

        return $svg;
    }

    private function drawEmtBand(float $emt): string
    {
        $yPlus = $this->mapY(abs($emt));
        $yMinus = $this->mapY(-abs($emt));
        $xLeft = $this->paddingLeft;
        $xRight = $this->width - $this->paddingRight;

        return '<rect class="svg-emt-band" x="'.$xLeft.'" y="'.$yPlus.'" '
            .'width="'.($xRight - $xLeft).'" height="'.($yMinus - $yPlus).'" '
            .'fill="#f0fdf4" fill-opacity="0.75"/>';
    }

    private function drawEmtLines(float $emt): string
    {
        $absEmt = abs($emt);
        $yPlus = $this->mapY($absEmt);
        $yMinus = $this->mapY(-$absEmt);
        $xLeft = $this->paddingLeft;
        $xRight = $this->width - $this->paddingRight;
        $emtStr = $this->fmtNum($absEmt, 4);

        $svg = '<g class="svg-emt-lines" stroke="#ef4444" stroke-width="1.5" stroke-dasharray="6,4">';
        $svg .= '<line x1="'.$xLeft.'" y1="'.$yPlus.'" x2="'.$xRight.'" y2="'.$yPlus.'"/>';
        $svg .= '<line x1="'.$xLeft.'" y1="'.$yMinus.'" x2="'.$xRight.'" y2="'.$yMinus.'"/>';
        $svg .= '</g>';

        $plotTop = $this->paddingTop;
        $plotBottom = $this->height - $this->paddingBottom;

        $plusY = ($yPlus - $plotTop < 22) ? ($yPlus + 15) : ($yPlus - 6);
        $minusY = ($plotBottom - $yMinus < 22) ? ($yMinus - 6) : ($yMinus + 15);

        // شارة +EMT
        $plusText = '+EMT = '.$emtStr;
        $plusW = strlen($plusText) * 7.0 + 16;
        $plusX = $xRight - 10;

        $svg .= '<rect class="svg-emt-badge-bg" x="'.($plusX - $plusW).'" y="'.($plusY - 12).'" width="'.$plusW.'" height="17" fill="#fee2e2" fill-opacity="0.95" stroke="#ef4444" stroke-width="0.9" rx="4"/>';
        $svg .= '<text class="svg-emt-label svg-emt-label-plus" x="'.($plusX - $plusW / 2).'" y="'.($plusY).'" text-anchor="middle" font-size="10.5" font-weight="bold" fill="#b91c1c" style="direction:ltr; unicode-bidi:isolate;">&#x200E;+EMT = '.$emtStr.'</text>';

        // شارة -EMT
        $minusText = '-EMT = -'.$emtStr;
        $minusW = strlen($minusText) * 7.0 + 16;
        $minusX = $xRight - 10;

        $svg .= '<rect class="svg-emt-badge-bg" x="'.($minusX - $minusW).'" y="'.($minusY - 12).'" width="'.$minusW.'" height="17" fill="#fee2e2" fill-opacity="0.95" stroke="#ef4444" stroke-width="0.9" rx="4"/>';
        $svg .= '<text class="svg-emt-label svg-emt-label-minus" x="'.($minusX - $minusW / 2).'" y="'.($minusY).'" text-anchor="middle" font-size="10.5" font-weight="bold" fill="#b91c1c" style="direction:ltr; unicode-bidi:isolate;">&#x200E;-EMT = -'.$emtStr.'</text>';

        return $svg;
    }

    private function drawAxes(): string
    {
        $xLeft = $this->paddingLeft;
        $xRight = $this->width - $this->paddingRight;
        $yTop = $this->paddingTop;
        $yBottom = $this->height - $this->paddingBottom;

        $svg = '<g class="svg-axis-lines" stroke="#000000" stroke-width="1.8">';
        $svg .= '<line x1="'.$xLeft.'" y1="'.$yTop.'" x2="'.$xLeft.'" y2="'.$yBottom.'"/>';
        $svg .= '<line x1="'.$xLeft.'" y1="'.$yBottom.'" x2="'.$xRight.'" y2="'.$yBottom.'"/>';
        $svg .= '</g>';

        // عنوان X في المنتصف ومفصول بمسافة مريحة
        $svg .= '<text class="svg-axis-title svg-axis-title-x" x="'.(($xLeft + $xRight) / 2).'" y="'.($yBottom + 46).'" '
            .'text-anchor="middle" font-size="12.5" font-weight="700" fill="#000000">Points de Contr&#244;le (%)</text>';

        // عنوان Y (دوران 90°) على أقصى اليسار
        $cx = 24;
        $cy = ($yTop + $yBottom) / 2;
        $svg .= '<text class="svg-axis-title svg-axis-title-y" x="'.$cx.'" y="'.$cy.'" text-anchor="middle" '
            .'transform="rotate(-90,'.$cx.','.$cy.')" '
            .'font-size="12.5" font-weight="700" fill="#000000">Erreur Absolue</text>';

        return $svg;
    }

    /**
     * رسم مسار متصل للخطوات المتسلسلة
     *
     * @param  array<int, array{index: int, val: float, point: mixed, phase: string}>  $steps
     */
    private function drawStepPath(array $steps, string $color, string $dashArray, float $sw, string $extraClass = ''): string
    {
        if (count($steps) < 2) {
            return '';
        }

        $d = '';
        foreach ($steps as $i => $step) {
            $x = round($this->mapStepToX($step['index']), 2);
            $y = round($this->mapY((float) ($step['point']->absolute_error ?? 0.0)), 2);
            $d .= ($i === 0 ? 'M' : 'L').$x.','.$y.' ';
        }

        return '<path class="svg-curve-path '.$extraClass.'" d="'.trim($d).'" fill="none" stroke="'.$color.'" '
            .'stroke-width="'.$sw.'" stroke-linejoin="round" stroke-linecap="round" '
            .'stroke-dasharray="'.$dashArray.'"/>';
    }

    /**
     * رسم نقاط وشارات الخطوات المتسلسلة
     *
     * @param  array<int, array{index: int, val: float, point: mixed, phase: string}>  $steps
     */
    private function drawStepPointsWithLabels(array $steps, string $strokeColor, string $fillColor, string $phaseClass = 'ascending'): string
    {
        if (empty($steps)) {
            return '';
        }

        $svg = '';
        $plotTop = $this->paddingTop + 14;
        $plotBottom = $this->height - $this->paddingBottom - 14;
        $xLeft = $this->paddingLeft;
        $xRight = $this->width - $this->paddingRight;

        // رسم النقاط
        foreach ($steps as $step) {
            $x = round($this->mapStepToX($step['index']), 2);
            $y = round($this->mapY((float) ($step['point']->absolute_error ?? 0.0)), 2);
            $err = (float) ($step['point']->absolute_error ?? 0.0);

            // نقطة القياس
            $svg .= '<circle class="svg-point-circle svg-point-'.$phaseClass.'" cx="'.$x.'" cy="'.$y.'" r="5.2" '
                .'fill="'.$fillColor.'" stroke="#ffffff" stroke-width="2"/>';

            // شارة القيمة
            $label = $this->fmtNum($err, 4);

            // موضع الشارة فوق النقطة دائماً بما أن النقاط مفصولة على المحور X
            $labelY = $y - 13;
            if ($labelY < $plotTop) {
                $labelY = $y + 20;
            }

            $labelW = strlen($label) * 7.2 + 14;
            $badgeCenterX = max($x, $xLeft + ($labelW / 2) + 2);
            $badgeCenterX = min($badgeCenterX, $xRight - ($labelW / 2) - 2);

            $svg .= '<rect class="svg-label-bg svg-label-bg-'.$phaseClass.'" x="'.($badgeCenterX - $labelW / 2).'" y="'.($labelY - 12).'" '
                .'width="'.$labelW.'" height="16" fill="#ffffff" stroke="'.$strokeColor.'" stroke-width="1" rx="4"/>';

            $svg .= '<text class="svg-label-text svg-label-text-'.$phaseClass.'" x="'.$badgeCenterX.'" y="'.($labelY + 0.5).'" '
                .'text-anchor="middle" font-size="10.5" font-weight="700" fill="'.$fillColor.'" style="direction:ltr; unicode-bidi:isolate;">'
                .$label.'</text>';
        }

        return $svg;
    }

    /**
     * Legend أسفل منطقة الرسم في سطر منفصل وبدون أي تداخل
     */
    private function drawLegend(bool $showDescending): string
    {
        $yBottom = $this->height - $this->paddingBottom;
        $yLegend = $yBottom + 76;

        $items = [
            [
                'color' => '#1d4ed8',
                'dash' => false,
                'dot' => true,
                'label' => $showDescending ? 'Cycle Montant (0 &#10140; 100)' : 'Mesures d\'&#201;talonnage',
                'class' => 'svg-legend-ascending',
                'width' => 190,
            ],
        ];

        if ($showDescending) {
            $items[] = [
                'color' => '#047857',
                'dash' => true,
                'dot' => true,
                'label' => 'Cycle Descendant (100 &#10140; 0)',
                'class' => 'svg-legend-descending',
                'width' => 200,
            ];
        }

        $items[] = [
            'color' => '#ef4444',
            'dash' => true,
            'dot' => false,
            'label' => 'Limites EMT',
            'class' => 'svg-legend-emt',
            'width' => 140,
        ];

        $totalW = array_sum(array_column($items, 'width'));
        $currentX = ($this->width / 2) - ($totalW / 2);

        $svg = '<g class="svg-legend">';
        foreach ($items as $item) {
            $dashAttr = $item['dash'] ? ' stroke-dasharray="6,4"' : '';

            $svg .= '<line class="svg-legend-line '.$item['class'].'" x1="'.$currentX.'" y1="'.$yLegend.'" x2="'.($currentX + 24).'" y2="'.$yLegend.'" '
                .'stroke="'.$item['color'].'" stroke-width="2.8"'.$dashAttr.'/>';

            if ($item['dot']) {
                $svg .= '<circle class="svg-legend-dot" cx="'.($currentX + 12).'" cy="'.$yLegend.'" r="4.5" '
                    .'fill="'.$item['color'].'" stroke="#ffffff" stroke-width="1.5"/>';
            }

            $svg .= '<text class="svg-legend-text" x="'.($currentX + 34).'" y="'.($yLegend + 4).'" '
                .'font-size="11.5" font-weight="600" fill="#000000">'.$item['label'].'</text>';

            $currentX += $item['width'];
        }
        $svg .= '</g>';

        return $svg;
    }
    // ═══════════════════════════════════════════════════════════════
    //  PDF-SAFE SVG: inline styles only — compatible with DomPDF
    //  aucune CSS class, aucun filter, aucun clipPath
    // ═══════════════════════════════════════════════════════════════

    /**
     * Génère un PNG via GD et retourne une string base64 (data:image/png;base64,...).
     * Méthode 100% compatible DomPDF — ne dépend d'aucune lib SVG externe.
     */
    public function generateErrorCurvePng(
        Collection $points,
        float $emt,
        float $rangeMin = 0.0,
        float $rangeMax = 100.0,
        int $width = 900,
        int $height = 380
    ): string {
        if ($points->isEmpty() || ! function_exists('imagecreatetruecolor')) {
            // Fallback SVG si GD non disponible
            return 'data:image/svg+xml;base64,'.base64_encode(
                $this->generateErrorCurvePdf($points, $emt, $rangeMin, $rangeMax, $width, $height)
            );
        }

        // ── Layout ────────────────────────────────────────────────
        $pTop = 44;
        $pBottom = 72;
        $pLeft = 92;
        $pRight = 42;
        $plotW = $width - $pLeft - $pRight;
        $plotH = $height - $pTop - $pBottom;
        $xL = $pLeft;
        $xR = $width - $pRight;
        $yT = $pTop;
        $yB = $pTop + $plotH;

        // ── Données ───────────────────────────────────────────────
        $hasDesc = $points->contains(fn ($p) => ($p->cycle_phase ?? '') === 'Descending');

        $asc = $points->filter(fn ($p) => ($p->cycle_phase ?? 'Ascending') === 'Ascending')
            ->sortBy(fn ($p) => (float) ($p->reference_value ?? $p->reference_temperature ?? 0.0))
            ->values();
        $desc = $hasDesc
            ? $points->filter(fn ($p) => ($p->cycle_phase ?? '') === 'Descending')
                ->sortByDesc(fn ($p) => (float) ($p->reference_value ?? $p->reference_temperature ?? 0.0))
                ->values()
            : collect();

        $seq = [];
        $idx = 0;
        foreach ($asc as $pt) {
            $seq[] = ['i' => $idx++, 'pt' => $pt, 'ph' => 'asc'];
        }
        foreach ($desc as $pt) {
            $seq[] = ['i' => $idx++, 'pt' => $pt, 'ph' => 'desc'];
        }
        $n = count($seq);

        $maxAbsErr = $points->max(fn ($p) => abs((float) ($p->absolute_error ?? 0.0))) ?? 0.0;
        $yLimit = max($maxAbsErr, abs($emt)) * 1.6;
        if ($yLimit == 0.0) {
            $yLimit = 1.0;
        }
        $minY = -$yLimit;
        $maxY = $yLimit;

        // ── Helpers ───────────────────────────────────────────────
        $xInner = 24;
        $mapX = function (int $i) use ($n, $xL, $plotW, $xInner): int {
            if ($n <= 1) {
                return (int) ($xL + $plotW / 2);
            }

            return (int) round($xL + $xInner + ($i / (float) ($n - 1)) * ($plotW - 2 * $xInner));
        };
        $mapY = function (float $val) use ($yT, $plotH, $minY, $maxY): int {
            return (int) round($yT + $plotH - (($val - $minY) / ($maxY - $minY)) * $plotH);
        };
        $fmt = fn (float $v, int $d = 4): string => number_format($v, $d, '.', '');
        $fmtAx = function (float $v): string {
            if (abs($v - round($v)) < 0.0001) {
                return (string) (int) round($v);
            }

            return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        };

        // ── Création image GD ─────────────────────────────────────
        $img = imagecreatetruecolor($width, $height);
        imageantialias($img, true);

        // Couleurs
        $cBg = imagecolorallocate($img, 248, 250, 252);
        $cWhite = imagecolorallocate($img, 255, 255, 255);
        $cBorder = imagecolorallocate($img, 203, 213, 225);
        $cGrid = imagecolorallocate($img, 226, 232, 240);
        $cEmtFill = imagecolorallocate($img, 236, 253, 245);
        $cEmtRed = imagecolorallocate($img, 239, 68, 68);
        $cEmtBadge = imagecolorallocate($img, 254, 226, 226);
        $cZero = imagecolorallocate($img, 30, 41, 59);
        $cBlack = imagecolorallocate($img, 15, 23, 42);
        $cGray = imagecolorallocate($img, 100, 116, 139);
        $cLabelY = imagecolorallocate($img, 51, 65, 85);
        $cBlue = imagecolorallocate($img, 29, 78, 216);
        $cGreen = imagecolorallocate($img, 4, 120, 87);
        $cBlueD = imagecolorallocate($img, 30, 58, 138);
        $cGreenD = imagecolorallocate($img, 6, 78, 59);
        $cNok = imagecolorallocate($img, 185, 28, 28);

        // ── Fond ─────────────────────────────────────────────────
        imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $cBg);
        imagefilledrectangle($img, $xL, $yT, $xR, $yB, $cWhite);
        imagerectangle($img, $xL, $yT, $xR, $yB, $cBorder);

        // ── Bande EMT (fond vert clair) ───────────────────────────
        $yEp = $mapY(abs($emt));
        $yEm = $mapY(-abs($emt));
        imagefilledrectangle($img, $xL + 1, $yEp, $xR - 1, $yEm, $cEmtFill);

        // ── Grille Y ─────────────────────────────────────────────
        $steps = 8;
        $range = $maxY - $minY;
        for ($gi = 0; $gi <= $steps; $gi++) {
            $val = $minY + ($gi / (float) $steps) * $range;
            if (abs($val) < $range * 0.015) {
                continue;
            }
            $gy = $mapY($val);
            // Ligne pointillée (simulation)
            for ($gx = $xL; $gx <= $xR; $gx += 6) {
                imageline($img, $gx, $gy, min($gx + 3, $xR), $gy, $cGrid);
            }
            // Tick + label
            imageline($img, $xL - 4, $gy, $xL, $gy, $cGray);
            $lbl = $fmt($val, 4);
            // Décale label à gauche (longueur * ~6px/char, font 1 = 6px wide)
            $lx = $xL - 8 - (strlen($lbl) * 6);
            imagestring($img, 1, max(0, $lx), $gy - 3, $lbl, $cLabelY);
        }

        // ── Grille X et labels X ──────────────────────────────────
        foreach ($seq as $step) {
            $gx = $mapX($step['i']);
            $refV = (float) ($step['pt']->reference_value
                ?? $step['pt']->reference_temperature
                ?? $step['pt']->equivalent_value ?? 0.0);
            $lbl = $fmtAx($refV);
            $lColor = ($step['ph'] === 'asc') ? $cBlue : $cGreen;

            for ($gy2 = $yT; $gy2 <= $yB; $gy2 += 6) {
                imageline($img, $gx, $gy2, $gx, min($gy2 + 3, $yB), $cGrid);
            }
            imageline($img, $gx, $yB, $gx, $yB + 4, $cGray);
            $lx2 = $gx - (int) (strlen($lbl) * 3);
            imagestring($img, 2, $lx2, $yB + 7, $lbl, $lColor);
        }

        // ── Ligne zéro ────────────────────────────────────────────
        $y0 = $mapY(0.0);
        imageline($img, $xL, $y0, $xR, $y0, $cZero);
        imagestring($img, 2, $xL - 14, $y0 - 4, '0', $cBlack);

        // ── Lignes EMT (rouge, pointillées) ──────────────────────
        for ($gx = $xL; $gx <= $xR; $gx += 8) {
            imageline($img, $gx, $yEp, min($gx + 5, $xR), $yEp, $cEmtRed);
            imageline($img, $gx, $yEm, min($gx + 5, $xR), $yEm, $cEmtRed);
        }
        // Badges EMT
        $emtStr = '+EMT='.$fmt(abs($emt), 4);
        $emtW = strlen($emtStr) * 6 + 6;
        imagefilledrectangle($img, $xR - $emtW - 4, $yEp - 11, $xR - 2, $yEp + 2, $cEmtBadge);
        imagerectangle($img, $xR - $emtW - 4, $yEp - 11, $xR - 2, $yEp + 2, $cEmtRed);
        imagestring($img, 1, $xR - $emtW - 1, $yEp - 9, $emtStr, $cNok);

        $emtStr2 = '-EMT=-'.$fmt(abs($emt), 4);
        $emtW2 = strlen($emtStr2) * 6 + 6;
        imagefilledrectangle($img, $xR - $emtW2 - 4, $yEm - 1, $xR - 2, $yEm + 12, $cEmtBadge);
        imagerectangle($img, $xR - $emtW2 - 4, $yEm - 1, $xR - 2, $yEm + 12, $cEmtRed);
        imagestring($img, 1, $xR - $emtW2 - 1, $yEm + 2, $emtStr2, $cNok);

        // ── Axes ─────────────────────────────────────────────────
        // Épaisseur 2: tracer 2 fois décalé
        foreach ([0, 1] as $off) {
            imageline($img, $xL + $off, $yT, $xL + $off, $yB, $cBlack); // axe Y
            imageline($img, $xL, $yB + $off, $xR, $yB + $off, $cBlack); // axe X
        }

        // Titre X (centré)
        $tXlbl = 'Points de Controle (%)';
        $tXx = (int) (($xL + $xR) / 2 - strlen($tXlbl) * 3);
        imagestring($img, 2, $tXx, $yB + 36, $tXlbl, $cBlack);

        // Titre Y (vertical - utiliser imagecopy + rotation)
        $tYlbl = 'Erreur Absolue';
        $tYlen = strlen($tYlbl);
        $tYcx = 14;
        $tYcy = (int) (($yT + $yB) / 2);
        // Simuler rotation: écrire char par char à la verticale
        for ($ci = 0; $ci < $tYlen; $ci++) {
            $charY = $tYcy - (int) (($tYlen / 2 - $ci) * 8);
            imagestring($img, 1, $tYcx - 2, $charY, $tYlbl[$ci], $cBlack);
        }

        // ── Courbes ───────────────────────────────────────────────
        // Montant (bleu, trait plein)
        $ascSteps = array_values(array_filter($seq, fn ($s) => $s['ph'] === 'asc'));
        for ($si = 0; $si < count($ascSteps) - 1; $si++) {
            $x1 = $mapX($ascSteps[$si]['i']);
            $y1 = $mapY((float) ($ascSteps[$si]['pt']->absolute_error ?? 0.0));
            $x2 = $mapX($ascSteps[$si + 1]['i']);
            $y2 = $mapY((float) ($ascSteps[$si + 1]['pt']->absolute_error ?? 0.0));
            foreach ([0, 1] as $t) {
                imageline($img, $x1, $y1 + $t, $x2, $y2 + $t, $cBlue);
            }
        }

        // Descendant (vert, pointillé)
        $descSteps = array_values(array_filter($seq, fn ($s) => $s['ph'] === 'desc'));
        for ($si = 0; $si < count($descSteps) - 1; $si++) {
            $x1 = $mapX($descSteps[$si]['i']);
            $y1 = $mapY((float) ($descSteps[$si]['pt']->absolute_error ?? 0.0));
            $x2 = $mapX($descSteps[$si + 1]['i']);
            $y2 = $mapY((float) ($descSteps[$si + 1]['pt']->absolute_error ?? 0.0));
            // trait pointillé: segments de 8 px
            $dist = (int) sqrt(($x2 - $x1) ** 2 + ($y2 - $y1) ** 2);
            if ($dist < 1) {
                continue;
            }
            for ($d = 0; $d < $dist; $d += 10) {
                $r = min($d + 6, $dist);
                $dx1 = (int) ($x1 + ($d / $dist) * ($x2 - $x1));
                $dy1 = (int) ($y1 + ($d / $dist) * ($y2 - $y1));
                $dx2 = (int) ($x1 + ($r / $dist) * ($x2 - $x1));
                $dy2 = (int) ($y1 + ($r / $dist) * ($y2 - $y1));
                imageline($img, $dx1, $dy1, $dx2, $dy2, $cGreen);
            }
        }

        // ── Points + Labels de valeur ─────────────────────────────
        foreach ($seq as $step) {
            $px = $mapX($step['i']);
            $py = $mapY((float) ($step['pt']->absolute_error ?? 0.0));
            $err = (float) ($step['pt']->absolute_error ?? 0.0);
            $ok = (bool) ($step['pt']->is_conforme ?? true);
            $dotC = ($step['ph'] === 'asc') ? $cBlue : $cGreen;
            $lblC = ($step['ph'] === 'asc') ? $cBlueD : $cGreenD;

            // Cercle (rempli + contour)
            imagefilledellipse($img, $px, $py, 9, 9, $dotC);
            imageellipse($img, $px, $py, 9, 9, $ok ? $cWhite : $cNok);

            // Badge valeur
            $lbl = $fmt($err, 4);
            $lblW = strlen($lbl) * 6 + 4;
            $lbY = $py - 18;
            if ($lbY < $yT + 10) {
                $lbY = $py + 12;
            }
            $lbX = max($px - (int) ($lblW / 2), $xL + 2);
            $lbX = min($lbX, $xR - $lblW - 2);
            imagefilledrectangle($img, $lbX - 1, $lbY - 1, $lbX + $lblW, $lbY + 8, $cWhite);
            imagerectangle($img, $lbX - 1, $lbY - 1, $lbX + $lblW, $lbY + 8, $dotC);
            imagestring($img, 1, $lbX + 1, $lbY, $lbl, $lblC);
        }

        // ── Légende ───────────────────────────────────────────────
        $yLeg = $yB + 52;
        $legItems = [['color' => $cBlue, 'dash' => false, 'label' => $hasDesc ? 'Cycle Montant (0->100)' : 'Mesures']];
        if ($hasDesc) {
            $legItems[] = ['color' => $cGreen, 'dash' => true, 'label' => 'Cycle Descendant (100->0)'];
        }
        $legItems[] = ['color' => $cEmtRed, 'dash' => true, 'label' => 'Limites EMT'];

        $legItemW = 200;
        $legTotalW = count($legItems) * $legItemW;
        $legStartX = (int) (($width - $legTotalW) / 2);

        foreach ($legItems as $item) {
            if ($item['dash']) {
                for ($d = 0; $d < 22; $d += 8) {
                    imageline($img, $legStartX + $d, $yLeg, $legStartX + min($d + 5, 22), $yLeg, $item['color']);
                }
            } else {
                imageline($img, $legStartX, $yLeg, $legStartX + 22, $yLeg, $item['color']);
                imagefilledellipse($img, $legStartX + 11, $yLeg, 7, 7, $item['color']);
            }
            imagestring($img, 2, $legStartX + 26, $yLeg - 4, $item['label'], $cBlack);
            $legStartX += $legItemW;
        }

        // ── Export PNG base64 ─────────────────────────────────────
        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,'.base64_encode($pngData);
    }

    /**
     * Génère un SVG simplifié avec styles inline uniquement, compatible DomPDF.
     */
    public function generateErrorCurvePdf(
        Collection $points,
        float $emt,
        float $rangeMin,
        float $rangeMax,
        float $width = 900.0,
        float $height = 380.0
    ): string {
        if ($points->isEmpty()) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'">'
                .'<rect width="100%" height="100%" fill="#f8fafc"/>'
                .'<text x="'.($width / 2).'" y="'.($height / 2).'" text-anchor="middle" '
                .'font-family="DejaVu Sans,Arial,sans-serif" font-size="13" fill="#94a3b8">'
                .'Aucune donn&#233;e disponible'
                .'</text></svg>';
        }

        // ── Layout ─────────────────────────────────────────────────
        $pTop = 40.0;
        $pBottom = 70.0;
        $pLeft = 90.0;
        $pRight = 40.0;
        $plotW = $width - $pLeft - $pRight;
        $plotH = $height - $pTop - $pBottom;

        // ── Séparer montant / descendant ───────────────────────────
        $hasDesc = $points->contains(fn ($p) => ($p->cycle_phase ?? '') === 'Descending');

        $asc = $points->filter(fn ($p) => ($p->cycle_phase ?? 'Ascending') === 'Ascending')
            ->sortBy(fn ($p) => (float) ($p->reference_value ?? $p->reference_temperature ?? $p->equivalent_value ?? 0.0))
            ->values();

        $desc = $hasDesc
            ? $points->filter(fn ($p) => ($p->cycle_phase ?? '') === 'Descending')
                ->sortByDesc(fn ($p) => (float) ($p->reference_value ?? $p->reference_temperature ?? $p->equivalent_value ?? 0.0))
                ->values()
            : collect();

        // Séquence complète (montant puis descendant)
        $seq = [];
        $idx = 0;
        foreach ($asc as $pt) {
            $seq[] = ['i' => $idx++, 'pt' => $pt, 'phase' => 'asc'];
        }
        foreach ($desc as $pt) {
            $seq[] = ['i' => $idx++, 'pt' => $pt, 'phase' => 'desc'];
        }
        $n = count($seq);

        // ── Bornes Y ───────────────────────────────────────────────
        $maxAbsErr = $points->max(fn ($p) => abs((float) ($p->absolute_error ?? 0.0))) ?? 0.0;
        $yLimit = max($maxAbsErr, abs($emt)) * 1.6;
        if ($yLimit == 0.0) {
            $yLimit = 1.0;
        }
        $minY = -$yLimit;
        $maxY = $yLimit;

        // ── Helpers ─────────────────────────────────────────────────
        $xInner = 24.0;
        $mapX = function (int $i) use ($n, $pLeft, $plotW, $xInner): float {
            if ($n <= 1) {
                return $pLeft + $plotW / 2;
            }

            return $pLeft + $xInner + ($i / (float) ($n - 1)) * ($plotW - 2 * $xInner);
        };
        $mapY = function (float $val) use ($pTop, $plotH, $minY, $maxY): float {
            return $pTop + $plotH - (($val - $minY) / ($maxY - $minY)) * $plotH;
        };
        $fmt = fn (float $v, int $d = 4): string => number_format($v, $d, '.', '');
        $fmtAx = function (float $v): string {
            if (abs($v - round($v)) < 0.0001) {
                return (string) (int) round($v);
            }

            return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        };

        $xLeft = $pLeft;
        $xRight = $width - $pRight;
        $yTop = $pTop;
        $yBottom = $pTop + $plotH;
        $font = "font-family='DejaVu Sans,Arial,sans-serif'";

        // ══════════════════════════════════════════════════════════
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"'
            .' width="'.$width.'" height="'.$height.'"'
            .' viewBox="0 0 '.$width.' '.$height.'"'
            .' style="direction:ltr;">';

        // ── Fond ──────────────────────────────────────────────────
        $svg .= '<rect x="0" y="0" width="'.$width.'" height="'.$height.'" fill="#f8fafc"/>';
        $svg .= '<rect x="'.$xLeft.'" y="'.$yTop.'"'
            .'width="'.$plotW.'" height="'.$plotH.'" '
            .'fill="#ffffff" stroke="#cbd5e1" stroke-width="1"/>';

        // ── Bande EMT ─────────────────────────────────────────────
        $yEp = $mapY(abs($emt));
        $yEm = $mapY(-abs($emt));
        $svg .= '<rect x="'.$xLeft.'" y="'.$yEp.'" '
            .'width="'.$plotW.'" height="'.($yEm - $yEp).'" '
            .'fill="#f0fdf4" fill-opacity="0.8"/>';

        // ── Grille Y (8 divisions) ────────────────────────────────
        $steps = 8;
        $range = $maxY - $minY;
        for ($i = 0; $i <= $steps; $i++) {
            $val = $minY + ($i / (float) $steps) * $range;
            if (abs($val) < $range * 0.015) {
                continue;
            }
            $gy = $mapY($val);
            $svg .= '<line x1="'.$xLeft.'" y1="'.$gy.'" x2="'.$xRight.'" y2="'.$gy.'" '
                .'stroke="#e2e8f0" stroke-width="1" stroke-dasharray="3,3"/>';
            // Tick Y
            $svg .= '<line x1="'.($xLeft - 5).'" y1="'.$gy.'" x2="'.$xLeft.'" y2="'.$gy.'" '
                .'stroke="#64748b" stroke-width="1"/>';
            // Label Y
            $svg .= '<text x="'.($xLeft - 8).'" y="'.($gy + 3.5).'" '
                .'text-anchor="end" '.$font.' font-size="9" fill="#334155">'
                .$fmt($val, 4).'</text>';
        }

        // ── Grille X et labels X ──────────────────────────────────
        foreach ($seq as $step) {
            $gx = $mapX($step['i']);
            $refV = (float) ($step['pt']->reference_value
                ?? $step['pt']->reference_temperature
                ?? $step['pt']->equivalent_value
                ?? 0.0);
            $label = $fmtAx($refV);
            $lColor = ($step['phase'] === 'asc') ? '#1d4ed8' : '#047857';

            $svg .= '<line x1="'.$gx.'" y1="'.$yTop.'" x2="'.$gx.'" y2="'.$yBottom.'" '
                .'stroke="#e2e8f0" stroke-width="1" stroke-dasharray="3,3"/>';
            $svg .= '<line x1="'.$gx.'" y1="'.$yBottom.'" x2="'.$gx.'" y2="'.($yBottom + 5).'" '
                .'stroke="#475569" stroke-width="1"/>';
            $svg .= '<text x="'.$gx.'" y="'.($yBottom + 16).'" '
                .'text-anchor="middle" '.$font.' font-size="9" font-weight="bold" fill="'.$lColor.'">'
                .$label.'</text>';
        }

        // ── Ligne zéro ────────────────────────────────────────────
        $y0 = $mapY(0.0);
        $svg .= '<line x1="'.$xLeft.'" y1="'.$y0.'" x2="'.$xRight.'" y2="'.$y0.'" '
            .'stroke="#1e293b" stroke-width="1.5"/>';
        $svg .= '<text x="'.($xLeft - 8).'" y="'.($y0 + 3.5).'" '
            .'text-anchor="end" '.$font.' font-size="9.5" font-weight="bold" fill="#0f172a">0</text>';

        // ── Lignes EMT ────────────────────────────────────────────
        $emtStr = $fmt(abs($emt), 4);
        $svg .= '<line x1="'.$xLeft.'" y1="'.$yEp.'" x2="'.$xRight.'" y2="'.$yEp.'" '
            .'stroke="#ef4444" stroke-width="1.4" stroke-dasharray="6,4"/>';
        $svg .= '<line x1="'.$xLeft.'" y1="'.$yEm.'" x2="'.$xRight.'" y2="'.$yEm.'" '
            .'stroke="#ef4444" stroke-width="1.4" stroke-dasharray="6,4"/>';

        // Badge +EMT (droite)
        $emtLabelP = '+EMT='.$emtStr;
        $emtWP = strlen($emtLabelP) * 6.5 + 10;
        $svg .= '<rect x="'.($xRight - $emtWP - 4).'" y="'.($yEp - 12).'" '
            .'width="'.($emtWP + 4).'" height="14" fill="#fee2e2" stroke="#ef4444" stroke-width="0.8" rx="3"/>';
        $svg .= '<text x="'.($xRight - $emtWP / 2 - 2).'" y="'.($yEp - 1).'" '
            .'text-anchor="middle" '.$font.' font-size="8.5" font-weight="bold" fill="#b91c1c">'
            .$emtLabelP.'</text>';

        // Badge -EMT (droite)
        $emtLabelM = '-EMT=-'.$emtStr;
        $emtWM = strlen($emtLabelM) * 6.5 + 10;
        $svg .= '<rect x="'.($xRight - $emtWM - 4).'" y="'.($yEm - 2).'" '
            .'width="'.($emtWM + 4).'" height="14" fill="#fee2e2" stroke="#ef4444" stroke-width="0.8" rx="3"/>';
        $svg .= '<text x="'.($xRight - $emtWM / 2 - 2).'" y="'.($yEm + 9).'" '
            .'text-anchor="middle" '.$font.' font-size="8.5" font-weight="bold" fill="#b91c1c">'
            .$emtLabelM.'</text>';

        // ── Axes ─────────────────────────────────────────────────
        // Axe Y
        $svg .= '<line x1="'.$xLeft.'" y1="'.$yTop.'" x2="'.$xLeft.'" y2="'.$yBottom.'" '
            .'stroke="#1e293b" stroke-width="1.8"/>';
        // Axe X
        $svg .= '<line x1="'.$xLeft.'" y1="'.$yBottom.'" x2="'.$xRight.'" y2="'.$yBottom.'" '
            .'stroke="#1e293b" stroke-width="1.8"/>';

        // Titre X
        $svg .= '<text x="'.(($xLeft + $xRight) / 2).'" y="'.($yBottom + 38).'" '
            .'text-anchor="middle" '.$font.' font-size="10" font-weight="bold" fill="#0f172a">'
            .'Points de Contr&#244;le (%)</text>';

        // Titre Y (rotation)
        $cyTitle = ($yTop + $yBottom) / 2;
        $svg .= '<text x="18" y="'.$cyTitle.'" text-anchor="middle" '
            .'transform="rotate(-90,18,'.$cyTitle.')" '
            .$font.' font-size="10" font-weight="bold" fill="#0f172a">'
            .'Erreur Absolue</text>';

        // ── Courbes (polyline) ────────────────────────────────────
        // Montant
        $ascSteps = array_filter($seq, fn ($s) => $s['phase'] === 'asc');
        if (count($ascSteps) >= 2) {
            $pts = implode(' ', array_map(
                fn ($s) => round($mapX($s['i']), 2).','.round($mapY((float) ($s['pt']->absolute_error ?? 0.0)), 2),
                $ascSteps
            ));
            $svg .= '<polyline points="'.$pts.'" fill="none" stroke="#1d4ed8" stroke-width="2.5" '
                .'stroke-linejoin="round" stroke-linecap="round"/>';
        }

        // Descendant
        $descSteps = array_filter($seq, fn ($s) => $s['phase'] === 'desc');
        if (count($descSteps) >= 2) {
            $pts = implode(' ', array_map(
                fn ($s) => round($mapX($s['i']), 2).','.round($mapY((float) ($s['pt']->absolute_error ?? 0.0)), 2),
                $descSteps
            ));
            $svg .= '<polyline points="'.$pts.'" fill="none" stroke="#047857" stroke-width="2.5" '
                .'stroke-linejoin="round" stroke-linecap="round" stroke-dasharray="7,4"/>';
        }

        // ── Points + Labels de valeur ─────────────────────────────
        foreach ($seq as $step) {
            $px = round($mapX($step['i']), 2);
            $py = round($mapY((float) ($step['pt']->absolute_error ?? 0.0)), 2);
            $err = (float) ($step['pt']->absolute_error ?? 0.0);
            $ok = (bool) ($step['pt']->is_conforme ?? true);

            $dotColor = ($step['phase'] === 'asc') ? '#1d4ed8' : '#047857';
            $dotStroke = $ok ? '#ffffff' : '#b91c1c';
            $dotSW = $ok ? '2' : '2.5';

            // Point
            $svg .= '<circle cx="'.$px.'" cy="'.$py.'" r="4.5" '
                .'fill="'.$dotColor.'" stroke="'.$dotStroke.'" stroke-width="'.$dotSW.'"/>';

            // Label valeur (au-dessus si possible)
            $lbl = $fmt($err, 4);
            $lblW = strlen($lbl) * 6.5 + 10;
            $lblY = $py - 14;
            if ($lblY < $yTop + 12) {
                $lblY = $py + 20;
            }
            $lblX = max($px, $xLeft + $lblW / 2 + 2);
            $lblX = min($lblX, $xRight - $lblW / 2 - 2);

            $lblColor = ($step['phase'] === 'asc') ? '#1e3a8a' : '#064e3b';
            $svg .= '<rect x="'.($lblX - $lblW / 2).'" y="'.($lblY - 11).'" '
                .'width="'.$lblW.'" height="13" fill="#ffffff" stroke="'.$dotColor.'" stroke-width="0.8" rx="3"/>';
            $svg .= '<text x="'.$lblX.'" y="'.($lblY - 1).'" '
                .'text-anchor="middle" '.$font.' font-size="8.5" font-weight="bold" fill="'.$lblColor.'">'
                .$lbl.'</text>';
        }

        // ── Légende ───────────────────────────────────────────────
        $yLeg = $yBottom + 54;
        $items = [
            ['color' => '#1d4ed8', 'dash' => false, 'label' => $hasDesc ? 'Cycle Montant (0&#8594;100)' : 'Mesures'],
        ];
        if ($hasDesc) {
            $items[] = ['color' => '#047857', 'dash' => true, 'label' => 'Cycle Descendant (100&#8594;0)'];
        }
        $items[] = ['color' => '#ef4444', 'dash' => true, 'label' => 'Limites EMT'];

        $legItemW = $hasDesc ? 210 : 180;
        $legTotalW = count($items) * $legItemW;
        $legX = ($width / 2) - ($legTotalW / 2);

        foreach ($items as $item) {
            $dash = $item['dash'] ? ' stroke-dasharray="6,4"' : '';
            $svg .= '<line x1="'.$legX.'" y1="'.$yLeg.'" x2="'.($legX + 22).'" y2="'.$yLeg.'" '
                .'stroke="'.$item['color'].'" stroke-width="2.5"'.$dash.'/>';
            if (! $item['dash']) {
                $svg .= '<circle cx="'.($legX + 11).'" cy="'.$yLeg.'" r="3.5" '
                    .'fill="'.$item['color'].'" stroke="#ffffff" stroke-width="1.5"/>';
            }
            $svg .= '<text x="'.($legX + 28).'" y="'.($yLeg + 4).'" '
                .$font.' font-size="9.5" fill="#1e293b">'.$item['label'].'</text>';
            $legX += $legItemW;
        }

        $svg .= '</svg>';

        return $svg;
    }
}
