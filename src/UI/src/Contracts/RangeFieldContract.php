<?php

declare(strict_types=1);

namespace MoonShine\UI\Contracts;

use MoonShine\Contracts\UI\FieldContract;

/**
 * @mixin FieldContract
 */
interface RangeFieldContract
{
    public function getFromField(): string;

    public function getToField(): string;
}
