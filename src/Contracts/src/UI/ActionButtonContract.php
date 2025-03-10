<?php

declare(strict_types=1);

namespace MoonShine\Contracts\UI;

use Closure;
use Illuminate\Support\Traits\Conditionable;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Support\DTOs\AsyncCallback;
use MoonShine\Support\Enums\HttpMethod;

/**
 * @template TModal of  ComponentContract
 * @template TOffCanvas of  ComponentContract
 *
 * @extends HasModalContract<TModal>
 * @extends HasOffCanvasContract<TOffCanvas>
 *
 * @mixin Conditionable
 */
interface ActionButtonContract extends
    ComponentContract,
    HasLabelContract,
    HasOffCanvasContract,
    HasModalContract,
    HasIconContract
{
    public function getUrl(mixed $data = null): string;

    /**
     * @param  (Closure(mixed $original, ?DataWrapperContract $casted, static $ctx): string)|string  $url
     */
    public function setUrl(Closure|string $url): static;

    /**
     * @param  Closure(ActionButtonContract $ctx): string  $onClick
     */
    public function onClick(Closure $onClick, ?string $modifier = null): static;

    public function bulk(?string $forComponent = null): static;

    public function isBulk(): bool;

    public function getBulkForComponent(): ?string;

    public function getData(): ?DataWrapperContract;

    public function setData(?DataWrapperContract $data = null): static;

    /**
     * @param  Closure(?DataWrapperContract $data, ActionButtonContract $ctx): ?DataWrapperContract  $onBeforeSet
     */
    public function onBeforeSet(Closure $onBeforeSet): static;

    /**
     * @param  Closure(?DataWrapperContract $data, ActionButtonContract $ctx): void  $onAfterSet
     */
    public function onAfterSet(Closure $onAfterSet): static;

    public function isInDropdown(): bool;

    public function showInDropdown(): static;

    public function showInLine(): static;

    /**
     * @param array|(Closure(mixed $original): array) $params = []
     */
    public function method(
        string $method,
        array|Closure $params = [],
        ?string $message = null,
        null|string|array $selector = null,
        array $events = [],
        ?AsyncCallback $callback = null,
        ?PageContract $page = null,
        ?ResourceContract $resource = null
    ): static;

    public function withSelectorsParams(array $selectors): static;

    public function dispatchEvent(array|string $events): static;

    public function async(
        HttpMethod $method = HttpMethod::GET,
        null|string|array $selector = null,
        array $events = [],
        ?AsyncCallback $callback = null
    ): static;

    public function disableAsync(): static;

    public function getAsyncMethod(): ?string;

    public function isAsyncMethod(): bool;

    public function isAsync(): bool;

    public function badge(Closure|string|int|float|null $value): static;

    public function primary(Closure|bool|null $condition = null): static;

    public function secondary(Closure|bool|null $condition = null): static;

    public function success(Closure|bool|null $condition = null): static;

    public function warning(Closure|bool|null $condition = null): static;

    public function info(Closure|bool|null $condition = null): static;

    public function error(Closure|bool|null $condition = null): static;
}
