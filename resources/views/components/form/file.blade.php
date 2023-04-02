@props([
    'files' => [],
    'path' => '',
    'download' => false,
    'removable' => true,
    'imageable' => true
])
<div class="form-group form-group-dropzone">
    <x-moonshine::form.input
        type="file"
        {{ $attributes->merge(['class' => 'form-file-upload'])->except(['x-model', 'x-bind:id', 'id'])}}
    />

    @if($attributes->has('x-model') || array_filter((array) $files))
        <div class="dropzone" @if($attributes->has('x-model')) x-show="Object.keys({{ $attributes->get('x-model') }}).length" @endif>
            <div class="dropzone-items"
                 @if($attributes->has('x-model'))
                     x-data="{xValues: {{ $attributes->get('x-model', '') }}}"
                @endif
            >
                @if($attributes->has('x-model'))
                    <template x-for="(xValue, index) in xValues" :key="index">
                        <x-moonshine::form.file-item
                            :attributes="$attributes"
                            :path="$path"
                            :download="$download"
                            :removable="$removable"
                            :imageable="$imageable"
                        />
                    </template>
                @else
                    @foreach($files as $index => $file)
                        <x-moonshine::form.file-item
                            :attributes="$attributes"
                            :path="$path"
                            :file="$file"
                            :download="$download"
                            :removable="$removable"
                            :imageable="$imageable"
                        />
                    @endforeach
                @endif
            </div>
        </div>
    @endif
</div>
