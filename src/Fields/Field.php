<?php

declare(strict_types=1);

namespace Leeto\MoonShine\Fields;

use Illuminate\Database\Eloquent\Model;
use Leeto\MoonShine\Contracts\Fields\HasAssets;
use Leeto\MoonShine\Contracts\Fields\HasExportViewValue;
use Leeto\MoonShine\Contracts\Fields\HasFields;
use Leeto\MoonShine\Contracts\Fields\HasFormViewValue;
use Leeto\MoonShine\Contracts\Fields\HasIndexViewValue;
use Leeto\MoonShine\Contracts\ResourceRenderable;

use Leeto\MoonShine\Helpers\Condition;
use Leeto\MoonShine\Traits\Fields\FormElement;
use Leeto\MoonShine\Traits\Fields\HasCanSee;
use Leeto\MoonShine\Traits\Fields\LinkTrait;
use Leeto\MoonShine\Traits\Fields\ShowOrHide;
use Leeto\MoonShine\Traits\Fields\ShowWhen;
use Leeto\MoonShine\Traits\Fields\WithHtmlAttributes;
use Leeto\MoonShine\Traits\Fields\XModel;
use Leeto\MoonShine\Traits\Makeable;
use Leeto\MoonShine\Traits\WithAssets;
use Leeto\MoonShine\Traits\WithView;
use Leeto\MoonShine\Utilities\AssetManager;

abstract class Field implements ResourceRenderable, HasAssets, HasExportViewValue, HasIndexViewValue, HasFormViewValue
{
    use Makeable;
    use FormElement;
    use ShowOrHide;
    use WithHtmlAttributes;
    use WithView;
    use WithAssets;
    use LinkTrait;
    use ShowWhen;
    use XModel;
    use HasCanSee;

    protected ?Field $parent = null;

    protected string $hint = '';

    protected bool $sortable = false;

    protected bool $removable = false;

    protected bool $canSave = true;

    protected bool $fieldContainer = true;

    /**
     * @deprecated Will be deleted
     */
    protected bool $fullWidth = false;

    protected ?string $ext = null;

    protected function afterMake(): void
    {
        if ($this->getAssets()) {
            app(AssetManager::class)->add($this->getAssets());
        }
    }

    /**
     * Set field label block view on forms, based on condition
     *
     * @deprecated Will be deleted
     * @param  mixed  $condition
     * @return $this
     */
    public function fullWidth(mixed $condition = null): static
    {
        $this->fullWidth = Condition::boolean($condition, true);

        return $this;
    }

    /**
     * @deprecated Will be deleted
     */
    public function isFullWidth(): bool
    {
        return $this->fullWidth;
    }

    public function fieldContainer(mixed $condition = null): static
    {
        $this->fieldContainer = Condition::boolean($condition, true);

        return $this;
    }

    public function hasFieldContainer(): bool
    {
        return $this->fieldContainer;
    }

    public function canSave(mixed $condition = null): static
    {
        $this->canSave = Condition::boolean($condition, true);

        return $this;
    }

    public function isCanSave(): bool
    {
        return $this->canSave;
    }

    public function parent(): ?Field
    {
        return $this->parent;
    }

    public function hasParent(): bool
    {
        return $this->parent instanceof self;
    }

    protected function setParent(Field $field): static
    {
        $this->parent = $field;

        return $this;
    }

    public function setParents(): static
    {
        if ($this instanceof HasFields) {
            $fields = [];

            foreach ($this->getFields() as $field) {
                $field = $field->setParents();

                $fields[] = $field->setParent($this);
            }

            $this->fields($fields);
        }

        return $this;
    }

    /**
     * Define a field description(hint), which will be displayed on create/edit page
     *
     * @param  string  $hint
     * @return $this
     */
    public function hint(string $hint): static
    {
        $this->hint = $hint;

        return $this;
    }

    public function getHint(): string
    {
        return $this->hint;
    }

    /**
     * Set field as removable
     *
     * @return $this
     */
    public function removable(): static
    {
        $this->removable = true;

        return $this;
    }

    public function isRemovable(): bool
    {
        return $this->removable;
    }

    /**
     * Define whether if index page can be sorted by this field
     *
     * @return $this
     */
    public function sortable(): static
    {
        $this->sortable = true;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function expansion(string $expansion): static
    {
        $this->ext = $expansion;

        return $this;
    }

    public function hasExt(): bool
    {
        return ! is_null($this->ext);
    }

    public function ext(): ?string
    {
        return $this->ext;
    }

    public function formViewValue(Model $item): mixed
    {
        if ($this->hasRelationship() && ! $item->relationLoaded($this->relation())) {
            $item->load($this->relation());
        }

        if ($this->belongToOne()) {
            return $item->{$this->relation()}?->getKey() ?? $this->getDefault();
        }

        if ($this->hasRelationship()) {
            return $item->{$this->relation()} ?? $this->getDefault();
        }

        if (is_callable($this->valueCallback())) {
            return $this->valueCallback()($item) ?? $this->getDefault();
        }

        return $item->{$this->field()} ?? $this->getDefault();
    }

    public function indexViewValue(Model $item, bool $container = true): mixed
    {
        if ($this->hasRelationship() && ! $item->relationLoaded($this->relation())) {
            $item->load($this->relation());
        }

        if ($this->hasRelationship()) {
            $item = $item->{$this->relation()};
        }

        if (is_callable($this->valueCallback())) {
            return $this->valueCallback()($item);
        }

        if ($this->hasRelationship()) {
            return $container ? view('moonshine::ui.badge', [
                'color' => 'purple',
                'value' => $item->{$this->resourceTitleField()} ?? false,
            ]) : $item->{$this->resourceTitleField()} ?? false;
        }

        return $item->{$this->field()} ?? '';
    }

    public function exportViewValue(Model $item): mixed
    {
        return $this->indexViewValue($item, false);
    }

    public function save(Model $item): Model
    {
        $item->{$this->field()} = $this->requestValue() !== false
            ? $this->requestValue()
            : ($this->isNullable() ? null : '');

        return $item;
    }

    public function beforeSave(Model $item): void
    {
        //
    }

    public function afterSave(Model $item): void
    {
        //
    }
}
