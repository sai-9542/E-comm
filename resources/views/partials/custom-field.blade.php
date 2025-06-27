@foreach($customFields as $field)
    <div class="mb-3">
        <label>{{ $field->label }}</label>
        @if($field->type == 'text')
            <input type="text" name="custom[{{ $field->id }}]" class="form-control">
        @elseif($field->type == 'select')
            <select name="custom[{{ $field->id }}]" class="form-select">
                @foreach(explode(',', $field->options) as $option)
                    <option value="{{ trim($option) }}">{{ trim($option) }}</option>
                @endforeach
            </select>
        @endif

        {{-- Check and render children --}}
        @foreach($field->children as $child)
            <div class="mt-2 ms-3 border-start ps-3">
                <label>{{ $child->label }}</label>
                @if($child->type == 'text')
                    <input type="text" name="custom[{{ $child->id }}]" class="form-control">
                @elseif($child->type == 'select')
                    <select name="custom[{{ $child->id }}]" class="form-select">
                        @foreach(explode(',', $child->options) as $option)
                            <option value="{{ trim($option) }}">{{ trim($option) }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        @endforeach
    </div>
@endforeach
