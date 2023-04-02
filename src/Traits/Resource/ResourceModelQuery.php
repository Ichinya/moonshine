<?php

declare(strict_types=1);

namespace Leeto\MoonShine\Traits\Resource;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

trait ResourceModelQuery
{
    public static array $with = [];

    public static string $orderField = 'id';

    public static string $orderType = 'DESC';

    public static int $itemsPerPage = 25;

    protected ?Builder $customBuilder = null;

    public function paginate(): LengthAwarePaginator
    {
        return $this->resolveQuery()
            ->paginate(static::$itemsPerPage)
            ->appends(request()->except('page'));
    }


    public function customBuilder(Builder $builder): void
    {
        $this->customBuilder = $builder;
    }

    public function hasWith(): bool
    {
        return count(static::$with) > 0;
    }

    public function getWith(): array
    {
        return static::$with;
    }

    public function query(): Builder
    {
        $query = $this->customBuilder ?? $this->getModel()->query();

        if ($this->hasWith()) {
            $query->with($this->getWith());
        }

        return $query;
    }

    public function resolveQuery(): Builder
    {
        $query = $this->query();

        if ($this->isRelatable()) {
            return $query
                ->where($this->relatedColumn(), $this->relatedKey())
                ->orderBy(static::$orderField, static::$orderType);
        }

        if ($this->scopes()) {
            foreach ($this->scopes() as $scope) {
                $query = $query->withGlobalScope($scope::class, $scope);
            }
        }

        if (request()->has('search') && count($this->search())) {
            foreach ($this->search() as $field) {
                $query = $query->orWhere(
                    $field,
                    'LIKE',
                    '%'.request('search').'%'
                );
            }
        }

        if (request()->has('filters') && count($this->filters())) {
            foreach ($this->filters() as $filter) {
                $query = $filter->getQuery($query);
            }
        }

        if (request()->has('order')) {
            $query = $query->orderBy(
                request('order.field'),
                request('order.type')
            );
        } else {
            $query = $query->orderBy(static::$orderField, static::$orderType);
        }

        Cache::forget($this->queryCacheKey());
        Cache::remember($this->queryCacheKey(), now()->addHours(2), static function () {
            return request()->getQueryString();
        });

        return $query;
    }

    protected function queryCacheKey(): string
    {
        return "moonshine_query_{$this->routeNameAlias()}";
    }
}
