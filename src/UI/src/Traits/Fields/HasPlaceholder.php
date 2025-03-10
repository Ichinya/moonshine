<?php

declare(strict_types=1);

namespace MoonShine\UI\Traits\Fields;

trait HasPlaceholder
{
    public function placeholder(string $value): static
    {
        return $this->customAttributes([
            'placeholder' => $value,
        ]);
    }
}
