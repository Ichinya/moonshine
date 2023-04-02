<?php

declare(strict_types=1);

namespace Leeto\MoonShine\Fields;

use Illuminate\Database\Eloquent\Model;
use Leeto\MoonShine\Contracts\Fields\HasFields;
use Leeto\MoonShine\Contracts\Fields\HasFullPageMode;
use Leeto\MoonShine\Contracts\Fields\HasJsonValues;
use Leeto\MoonShine\Contracts\Fields\Relationships\HasRelationship;
use Leeto\MoonShine\Contracts\Fields\Relationships\HasResourceMode;
use Leeto\MoonShine\Contracts\Fields\Relationships\OneToManyRelation;
use Leeto\MoonShine\Traits\Fields\HasOneOrMany;
use Leeto\MoonShine\Traits\Fields\WithFullPageMode;
use Leeto\MoonShine\Traits\Fields\WithJsonValues;
use Leeto\MoonShine\Traits\Fields\WithRelationship;
use Leeto\MoonShine\Traits\Fields\WithResourceMode;
use Leeto\MoonShine\Traits\WithFields;

class HasMany extends Field implements
    HasRelationship,
    HasFields,
    HasJsonValues,
    HasResourceMode,
    HasFullPageMode,
    OneToManyRelation
{
    use WithResourceMode;
    use WithFullPageMode;
    use WithFields;
    use WithJsonValues;
    use WithRelationship;
    use HasOneOrMany;

    protected static string $view = 'moonshine::fields.has-many';

    protected bool $group = true;

    protected bool $onlyCount = false;

    public function onlyCount(): static
    {
        $this->onlyCount = true;

        return $this;
    }

    public function indexViewValue(Model $item, bool $container = false): mixed
    {
        if ($this->onlyCount) {
            return (string)$item->{$this->relation()}->count();
        }

        $columns = [];
        $values = [];

        foreach ($this->getFields() as $field) {
            $columns[$field->field()] = $field->label();
        }

        foreach ($item->{$this->field()} as $index => $data) {
            foreach ($this->getFields() as $field) {
                $values[$index][$field->field()] = $field->indexViewValue($data, false);
            }
        }

        return view('moonshine::ui.table', [
            'columns' => $columns,
            'values' => $values,
        ]);
    }

    public function exportViewValue(Model $item): string
    {
        return '';
    }
}
