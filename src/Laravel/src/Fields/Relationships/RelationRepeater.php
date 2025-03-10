<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Fields\Relationships;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\HasFieldsContract;
use MoonShine\Contracts\UI\TableBuilderContract;
use MoonShine\Laravel\Collections\Fields;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Contracts\DefaultValueTypes\CanBeArray;
use MoonShine\UI\Contracts\DefaultValueTypes\CanBeObject;
use MoonShine\UI\Contracts\HasDefaultValueContract;
use MoonShine\UI\Contracts\RemovableContract;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Traits\Fields\HasVerticalMode;
use MoonShine\UI\Traits\Fields\WithDefaultValue;
use MoonShine\UI\Traits\Removable;
use MoonShine\UI\Traits\WithFields;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

/**
 * @implements HasFieldsContract<Fields|FieldsContract>
 */
class RelationRepeater extends ModelRelationField implements
    HasFieldsContract,
    RemovableContract,
    HasDefaultValueContract,
    CanBeArray,
    CanBeObject
{
    use WithFields;
    use Removable;
    use WithDefaultValue;
    use HasVerticalMode;

    protected string $view = 'moonshine::fields.json';

    protected bool $isGroup = true;

    protected bool $hasOld = false;

    protected bool $resolveValueOnce = true;

    protected bool $isCreatable = true;

    protected ?int $creatableLimit = null;

    protected ?ActionButtonContract $creatableButton = null;

    protected array $buttons = [];

    protected ?Closure $modifyTable = null;

    protected ?Closure $modifyRemoveButton = null;

    public function __construct(
        string|Closure $label,
        ?string $relationName = null,
        string|Closure|null $formatted = null,
        ModelResource|string|null $resource = null
    ) {
        parent::__construct($label, $relationName, $formatted, $resource);

        $this->fields(
            $this->getResource()?->getFormFields()?->onlyFields() ?? []
        );
    }

    public function creatable(
        Closure|bool|null $condition = null,
        ?int $limit = null,
        ?ActionButtonContract $button = null
    ): static {
        $this->isCreatable = value($condition, $this) ?? true;

        if ($this->isCreatable()) {
            $this->creatableLimit = $limit;
            $this->creatableButton = $button?->customAttributes([
                '@click.prevent' => 'add()',
            ]);
        }

        return $this;
    }

    public function getCreateButton(): ?ActionButtonContract
    {
        return $this->creatableButton;
    }

    public function isCreatable(): bool
    {
        return $this->isCreatable;
    }

    public function getCreateLimit(): ?int
    {
        return $this->creatableLimit;
    }

    /**
     * @param  Closure(TableBuilder $table, bool $preview): TableBuilder  $callback
     */
    public function modifyTable(Closure $callback): static
    {
        $this->modifyTable = $callback;

        return $this;
    }

    /**
     * @param  Closure(ActionButton $button, self $field): ActionButton  $callback
     */
    public function modifyRemoveButton(Closure $callback): self
    {
        $this->modifyRemoveButton = $callback;

        return $this;
    }

    public function buttons(array $buttons): static
    {
        $this->buttons = $buttons;

        return $this;
    }

    public function getButtons(): array
    {
        if (array_filter($this->buttons) !== []) {
            return $this->buttons;
        }

        $buttons = [];

        if ($this->isRemovable()) {
            $button = ActionButton::make('', '#')
                ->icon('trash')
                ->onClick(static fn ($action): string => 'remove', 'prevent')
                ->customAttributes($this->removableAttributes ?: ['class' => 'btn-error'])
                ->showInLine();

            if (! \is_null($this->modifyRemoveButton)) {
                $button = value($this->modifyRemoveButton, $button, $this);
            }

            $buttons[] = $button;
        }

        return $buttons;
    }

    /**
     * @throws Throwable
     */
    protected function prepareFields(): FieldsContract
    {
        return $this->getFields()->prepareAttributes()->prepareReindexNames(parent: $this, before: static function (self $parent, Field $field): void {
            $field
                ->disableSortable()
                ->withoutWrapper()
                ->setRequestKeyPrefix($parent->getRequestKeyPrefix())
            ;
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Throwable
     * @throws NotFoundExceptionInterface
     */
    protected function resolvePreview(): string|Renderable
    {
        return $this
            ->getComponent()
            ->simple()
            ->preview()
            ->render();
    }

    protected function isBlankValue(): bool
    {
        if ($this->isPreviewMode()) {
            return parent::isBlankValue();
        }

        return blank($this->value);
    }

    /**
     * @throws Throwable
     */
    protected function resolveValue(): mixed
    {
        $value = $this->isPreviewMode()
            ? $this->toFormattedValue()
            : $this->toValue();

        $values = Collection::make(
            is_iterable($value)
                ? $value
                : []
        );

        return $values->when(
            ! $this->isPreviewMode() && ! $this->isCreatable() && blank($values),
            static fn ($values): Collection => $values->push([null])
        );
    }

    /**
     * @throws Throwable
     */
    protected function getComponent(): TableBuilder
    {
        $fields = $this->getPreparedFields();

        return TableBuilder::make($fields, $this->getValue())
            ->name("relation_repeater_{$this->getIdentity()}")
            ->inside('field')
            ->customAttributes(
                $this->getAttributes()
                    ->except(['class', 'data-name', 'data-column'])
                    ->jsonSerialize()
            )
            ->cast($this->getResource()?->getCaster())
            ->when(
                $this->isVertical(),
                fn (TableBuilderContract $table): TableBuilderContract => $table->vertical(
                    title: fn (FieldContract $field, ComponentContract $default): Column => Column::make([
                        Div::make([
                            $field->getLabel(),
                        ]),
                    ])->columnSpan($this->verticalTitleSpan),
                )
            )
            ->when(
                ! \is_null($this->modifyTable),
                fn (TableBuilder $tableBuilder) => value($this->modifyTable, $tableBuilder, $this->isPreviewMode())
            );
    }

    /**
     * @throws Throwable
     */
    protected function resolveAppliesCallback(
        mixed $data,
        Closure $callback,
        ?Closure $response = null,
        bool $fill = false
    ): mixed {
        $requestValues = array_filter($this->getRequestValue() ?: []);

        $applyValues = [];

        foreach ($requestValues as $index => $values) {
            $values = $this->getResource()
                ?->getDataInstance()
                ?->forceFill($values) ?? $values;

            $requestValues[$index] = $values;

            foreach ($this->resetPreparedFields()->getPreparedFields() as $field) {
                $field->setNameIndex($index);

                $field->when($fill, fn (FieldContract $f): FieldContract => $f->fillCast(
                    $values,
                    $this->getResource()->getCaster()
                ));

                $apply = $callback($field, $values, $data);

                data_set(
                    /** @phpstan-ignore-next-line  */
                    $applyValues[$index],
                    $field->getColumn(),
                    data_get($apply, $field->getColumn())
                );
            }
        }

        $values = array_values($applyValues);

        return \is_null($response) ? data_set(
            $data,
            str_replace('.', '->', $this->getColumn()),
            $values
        ) : $response($values, $data);
    }

    protected function resolveOnApply(): ?Closure
    {
        return fn ($item): mixed => $this->resolveAppliesCallback(
            data: $item,
            callback: static fn (FieldContract $field, mixed $values): mixed => $field->apply(
                static fn ($data): mixed => data_set($data, $field->getColumn(), $values[$field->getColumn()] ?? ''),
                $values
            ),
            response: static fn (array $values, mixed $data): mixed => $data
        );
    }

    /**
     * @throws Throwable
     */
    protected function resolveBeforeApply(mixed $data): mixed
    {
        return $this->resolveAppliesCallback(
            data: $data,
            callback: static fn (FieldContract $field, mixed $values): mixed => $field->beforeApply($values),
            response:  static fn (array $values, mixed $data): mixed => $data
        );
    }

    /**
     * @throws Throwable
     */
    protected function resolveAfterApply(mixed $data): mixed
    {
        return $this->resolveAppliesCallback(
            data: $data,
            callback: static fn (FieldContract $field, mixed $values): mixed => $field->apply(
                static fn ($data): mixed => data_set($data, $field->getColumn(), $values[$field->getColumn()] ?? ''),
                $values
            ),
            response: fn (array $values, mixed $data) => $this->saveRelation($values, $data),
            fill: true,
        );
    }

    private function saveRelation(array $items, mixed $model)
    {
        $items = collect($items);

        $relationName = $this->getColumn();

        $related = $model->{$relationName}()->getRelated();

        $relatedKeyName = $related->getKeyName();
        $relatedQualifiedKeyName = $related->getQualifiedKeyName();

        $ids = $items
            ->pluck($relatedKeyName)
            ->filter()
            ->toArray();

        $model->{$relationName}()->when(
            ! empty($ids),
            static fn (Builder $q) => $q->whereNotIn(
                $relatedQualifiedKeyName,
                $ids
            )->delete(),
            static fn (Builder $q) => $q->delete()
        );

        foreach ($items as $item) {
            if (empty($item[$relatedKeyName])) {
                unset($item[$relatedKeyName]);
                $model->{$relationName}()->create($item);
            } else {
                $model->{$relationName}()->where($relatedKeyName, $item[$relatedKeyName])->update($item);
            }
        }

        return $model;
    }

    /**
     * @throws Throwable
     */
    protected function resolveAfterDestroy(mixed $data): mixed
    {
        if (! $this->getResource()?->isDeleteRelationships()) {
            return $data;
        }

        $values = $this->toValue(withDefault: false);

        if (filled($values)) {
            foreach ($values as $value) {
                $this->getFields()
                    ->onlyFields()
                    ->each(
                        static fn (Field $field): mixed => $field
                            ->fillData($value)
                            ->afterDestroy($value)
                    );
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     * @throws Throwable
     */
    protected function viewData(): array
    {
        return [
            'component' => $this->getComponent()
                ->editable()
                ->reindex(prepared: true)
                ->when(
                    $this->isCreatable(),
                    fn (TableBuilderContract $table): TableBuilderContract => $table->creatable(
                        limit: $this->getCreateLimit(),
                        button: $this->getCreateButton()
                    )->removeAfterClone()
                )
                ->buttons($this->getButtons())
                ->simple(),
        ];
    }
}
