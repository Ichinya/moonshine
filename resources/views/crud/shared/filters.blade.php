@if(count($filters))
    <x-moonshine::offcanvas
        title="{{ trans('moonshine::ui.filters') }}"
        :left="false"
    >
        <x-slot:toggler class="btn-pink">
            <x-moonshine::icon
                icon="heroicons.adjustments-horizontal"
                size="6"
            />
            @lang('moonshine::ui.filters')

            @if(request('filters'))
                ({{ count(array_filter(Arr::map(request('filters'), fn($filter) => is_array($filter) ? Arr::whereNotNull($filter) : $filter))) }})
            @endif
        </x-slot:toggler>
        <x-moonshine::form action="{{ $resource->currentRoute() }}" method="get">
            <div class="form-flex-col">
                @foreach($filters as $filter)
                    @if($filter->isSee($resource->getModel()))
                        <x-moonshine::filter-container :filter="$filter" :resource="$resource">
                            {{ $resource->renderComponent($filter, $resource->getModel()) }}
                        </x-moonshine::filter-container>
                    @endif
                @endforeach
            </div>

            <x-slot:button type="submit">
                {{ trans('moonshine::ui.search') }}
            </x-slot:button>

            <x-slot:buttons>
                @if(request('filters'))
                    <x-moonshine::link href="{{ $resource->currentRoute(query: ['reset' => true]) }}">
                        {{ trans('moonshine::ui.reset') }}
                    </x-moonshine::link>
                @endif
            </x-slot:buttons>
        </x-moonshine::form>
    </x-moonshine::offcanvas>
@endif
