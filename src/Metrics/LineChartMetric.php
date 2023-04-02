<?php

declare(strict_types=1);

namespace Leeto\MoonShine\Metrics;

class LineChartMetric extends Metric
{
    protected static string $view = 'moonshine::metrics.line-chart';

    protected array $lines = [];

    protected array $colors = [];

    protected array $assets = [
        'https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.37.1/apexcharts.min.js',
        '/vendor/moonshine/js/apexchart-config.js',
    ];

    public function lines(): array
    {
        return $this->lines;
    }

    public function line(array $line, string $color = '#7843E9'): static
    {
        $this->lines[] = $line;
        $this->colors[] = $color;

        return $this;
    }

    public function color(int $index): string
    {
        return $this->colors[$index];
    }

    public function colors(): array
    {
        return $this->colors;
    }

    public function labels(): array
    {
        return collect($this->lines())
            ->collapse()
            ->mapWithKeys(fn ($item) => $item)
            ->sortKeys()
            ->keys()
            ->toArray();
    }
}
