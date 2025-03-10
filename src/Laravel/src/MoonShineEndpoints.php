<?php

declare(strict_types=1);

namespace MoonShine\Laravel;

use MoonShine\Contracts\Core\CrudResourceContract;
use MoonShine\Contracts\Core\DependencyInjection\EndpointsContract;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\Core\Exceptions\EndpointException;
use MoonShine\Core\Pages\Pages;
use MoonShine\Laravel\DependencyInjection\MoonShineRouter;
use MoonShine\Support\UriKey;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

/**
 * @implements EndpointsContract<CrudResourceContract>
 */
final readonly class MoonShineEndpoints implements EndpointsContract
{
    public function __construct(
        private MoonShineRouter $router
    ) {
    }

    public function method(
        string $method,
        ?string $message = null,
        array $params = [],
        ?PageContract $page = null,
        ?ResourceContract $resource = null
    ): string {
        return $this->router->to('method', [
            'method' => $method,
            'message' => $message,
            ...$params,
            ...[
                'pageUri' => $this->router->getParam('pageUri', $this->router->extractPageUri($page)),
                'resourceUri' => $this->router->getParam('resourceUri', $this->router->extractResourceUri($resource)),
            ],
        ]);
    }

    public function reactive(
        ?PageContract $page = null,
        ?ResourceContract $resource = null,
        array $extra = []
    ): string {
        $key = $extra['key'] ?? $resource?->getItem()?->getKey();

        return $this->router->to('reactive', [
            'resourceItem' => $key,
            'pageUri' => $this->router->getParam('pageUri', $this->router->extractPageUri($page)),
            'resourceUri' => $this->router->getParam('resourceUri', $this->router->extractResourceUri($resource)),
        ]);
    }

    public function component(
        string $name,
        array $additionally = []
    ): string {
        return $this->router->to('component', [
            '_component_name' => $name,
            '_parentId' => moonshineRequest()->getParentResourceId(),
            ...$additionally,
            ...[
                'pageUri' => $this->router->extractPageUri(),
                'resourceUri' => $this->router->extractResourceUri(),
            ],
        ]);
    }

    public function updateField(
        ?ResourceContract $resource = null,
        ?PageContract $page = null,
        array $extra = [],
    ): string {
        $relation = $extra['relation'] ?? null;
        $resourceItem = $extra['resourceItem'] ?? null;
        $through = $relation ? 'relation' : 'column';

        return $this->withRelation(
            "update-field.through-$through",
            resourceItem: $this->router->extractResourceItem($resourceItem),
            relation: $relation,
            resourceUri: $resource ? $resource->getUriKey() : $this->router->extractResourceUri(),
            pageUri: $page ? $page->getUriKey() : $this->router->extractPageUri()
        );
    }

    /**
     * @param  class-string<PageContract>|PageContract|null  $page
     * @param  class-string<ResourceContract>|ResourceContract|null  $resource
     * @throws Throwable
     */
    public function toPage(
        string|PageContract|null $page = null,
        string|ResourceContract|null $resource = null,
        array $params = [],
        array $extra = [],
    ): string|RedirectResponse {
        $targetPage = null;

        $redirect = $extra['redirect'] ?? false;
        $fragment = $extra['fragment'] ?? null;

        if (\is_array($fragment)) {
            $fragment = implode(',', array_map(
                static fn ($key, $value): string => "$key:$value",
                array_keys($fragment),
                $fragment
            ));
        }

        if ($fragment !== null && $fragment !== '') {
            $params += ['_fragment-load' => $fragment];
        }

        if (\is_null($page) && \is_null($resource)) {
            throw EndpointException::pageOrResourceRequired();
        }

        if (! \is_null($resource)) {
            $targetResource = $resource instanceof ResourceContract
                ? $resource
                : moonshine()->getResources()->findByClass($resource);

            $targetPage = $targetResource?->getPages()->when(
                \is_null($page),
                static fn (Pages $pages) => $pages->first(),
                static fn (Pages $pages): ?PageContract => $pages->findByUri(
                    $page instanceof PageContract
                        ? $page->getUriKey()
                        : (new UriKey($page))->generate()
                ),
            );
        }

        if (\is_null($resource)) {
            $targetPage = $page instanceof PageContract
                ? $page
                : moonshine()->getPages()->findByClass($page);
        }

        if (\is_null($targetPage)) {
            throw EndpointException::pageRequired();
        }

        return $redirect
            ? redirect($targetPage->getRoute($params))
            : $targetPage->getRoute($params);
    }

    public function home(): string
    {
        if ($url = moonshineConfig()->getHomeUrl()) {
            return $url;
        }

        return route(
            moonshineConfig()->getHomeRoute()
        );
    }

    public function withRelation(
        string $action,
        int|string|null $resourceItem = null,
        ?string $relation = null,
        ?string $resourceUri = null,
        ?string $pageUri = null,
        ?string $parentField = null,
    ): string {
        return $this->router->to($action, [
            'pageUri' => $pageUri ?? moonshineRequest()->getPageUri(),
            'resourceUri' => $resourceUri ?? moonshineRequest()->getResourceUri(),
            'resourceItem' => $resourceItem,
            '_parent_field' => $parentField,
            '_relation' => $relation,
        ]);
    }
}
