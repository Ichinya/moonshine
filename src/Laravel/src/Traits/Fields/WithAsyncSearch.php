<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Traits\Fields;

use Closure;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Support\DTOs\Select\Option;
use MoonShine\Support\DTOs\Select\OptionProperty;

trait WithAsyncSearch
{
    protected bool $asyncSearch = false;

    protected ?string $asyncUrl = null;

    protected ?string $asyncSearchColumn = null;

    protected int $asyncSearchCount = 15;

    protected ?Closure $asyncSearchQuery = null;

    protected ?Closure $asyncSearchValueCallback = null;

    protected array $withImage = [];

    protected ?string $associatedWith = null;

    public function withImage(string $column, string $disk = 'public', string $dir = ''): static
    {
        $this->withImage = [
            'column' => $column,
            'disk' => $disk,
            'dir' => $dir,
        ];

        $this->relatedColumns([$column]);

        return $this;
    }

    protected function isWithImage(): bool
    {
        return ! empty($this->withImage['column']);
    }

    public function getImageUrl(Model $item): ?string
    {
        if (! $this->isWithImage()) {
            return null;
        }

        $value = data_get($item, $this->withImage['column']);

        if (empty($value)) {
            return null;
        }

        if (is_iterable($value)) {
            $value = Arr::first($value);
        }

        $value = str($value)
            ->replaceFirst($this->withImage['dir'], '')
            ->trim('/')
            ->prepend($this->withImage['dir'] . '/')
            ->value();

        return $this->getCore()->getStorage(disk: $this->withImage['disk'])->getUrl($value);
    }

    public function getValuesWithProperties(bool $onlyCustom = false): Collection
    {
        if (! $this->isWithImage()) {
            return collect();
        }

        return $this->getMemoizeValues()->mapWithKeys(function (Model $item) use ($onlyCustom): array {
            $option = $this->getAsyncSearchOption($item);

            return [
                $item->getKey() => $onlyCustom
                    ? $option->getProperties()?->toArray()
                    : $option->toArray(),
            ];
        });
    }

    public function isAsyncSearch(): bool
    {
        return $this->asyncSearch;
    }

    public function getAsyncSearchColumn(): ?string
    {
        return $this->asyncSearchColumn;
    }

    public function getAsyncSearchCount(): int
    {
        return $this->asyncSearchCount;
    }

    public function getAsyncSearchQuery(): ?Closure
    {
        return $this->asyncSearchQuery;
    }

    public function getAsyncSearchValueCallback(): ?Closure
    {
        return $this->asyncSearchValueCallback;
    }

    public function getAsyncSearchUrl(): string
    {
        if (! \is_null($this->asyncUrl)) {
            return $this->asyncUrl;
        }

        $parentName = null;

        if ($this->hasParent()) {
            $parentName = $this->getParent()?->getColumn();
        }

        $resourceUri = moonshineRequest()->getResourceUri();

        return moonshineRouter()->getEndpoints()->withRelation(
            'async-search',
            resourceItem: moonshineRequest()->getItemID(),
            relation: $this->getRelationName(),
            resourceUri: $resourceUri,
            parentField: $parentName
        );
    }

    public function getAsyncSearchOption(Model $model, ?string $searchColumn = null): Option
    {
        $searchColumn ??= $this->getAsyncSearchColumn();

        if (\is_null($searchColumn)) {
            $searchColumn = '';
        }

        return new Option(
            label: \is_null($this->getAsyncSearchValueCallback())
                ? data_get($model, $searchColumn, '')
                : value($this->getAsyncSearchValueCallback(), $model, $this),
            value: (string) $model->getKey(),
            properties: new OptionProperty($this->getImageUrl($model))
        );
    }

    /**
     * @param  string|null  $column
     * @param  ?Closure(Builder $query, MoonShineRequest $request, string $term, FieldContract $field): static  $searchQuery
     * @param  ?Closure(mixed $data, FieldContract $field): static  $formatted
     */
    public function asyncSearch(
        ?string $column = null,
        ?Closure $searchQuery = null,
        ?Closure $formatted = null,
        ?string $associatedWith = null,
        int $limit = 15,
        ?string $url = null,
    ): static {
        $this->asyncSearch = true;
        $this->searchable = true;
        $this->asyncSearchColumn = $column;
        $this->asyncSearchCount = $limit;
        $this->asyncSearchQuery = $searchQuery;
        $this->asyncSearchValueCallback = $formatted ?? $this->getFormattedValueCallback();
        $this->associatedWith = $associatedWith;
        $this->asyncUrl = $url;

        if ($this->associatedWith) {
            $this->customAttributes([
                'data-associated-with' => $this->getDotNestedToName($this->associatedWith),
            ]);
        }

        $this->valuesQuery = function (Builder $query) {
            if ($this->getRelatedModel()) {
                return $this->getRelation();
            }

            return $query->whereRaw('1=0');
        };

        return $this;
    }

    /**
     * @param  ?Closure(Builder $query, MoonShineRequest $request, string $term, FieldContract $field): static  $searchQuery
     */
    public function associatedWith(string $column, ?Closure $searchQuery = null): static
    {
        $defaultQuery = static fn (Builder $query, Request $request) => $query->where($column, $request->input($column));

        return $this->asyncSearch(
            searchQuery: \is_null($searchQuery) ? $defaultQuery : $searchQuery,
            associatedWith: $column
        );
    }

    public function asyncOnInit(): static
    {
        return $this->customAttributes(['data-async-on-init' => true]);
    }
}
