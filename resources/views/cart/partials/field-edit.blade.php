@php
    $value = $fieldValues[$field->id] ?? null;
@endphp

<div class="mb-3 ps-3 border-start dependent-field"
     id="field_{{ $field->id }}"
     data-parent="{{ $field->parent_id }}"
     data-show-if="{{ $field->dependency_value }}"
     style="{{ $field->parent_id ? 'display:none;' : '' }}">

    <strong>{{ $field->label }}</strong><br>

    @if($field->type === 'text')
        <input type="text"
               class="form-control"
               name="custom[{{ $field->id }}]"
               value="{{ $value }}"
               data-required="{{ $field->required ? 'true' : 'false' }}"
               {{ $field->required && !$field->parent_id ? 'required' : '' }}>

    @elseif($field->type === 'select')
        <select class="form-select"
                name="custom[{{ $field->id }}]"
                onchange="handleChange(this, '{{ $field->id }}')"
                data-required="{{ $field->required ? 'true' : 'false' }}"
                {{ $field->required && !$field->parent_id ? 'required' : '' }}>
            <option value="">Select</option>
            @foreach(explode(',', $field->options ?? '') as $option)
                <option value="{{ trim($option) }}" {{ trim($option) == $value ? 'selected' : '' }}>
                    {{ trim($option) }}
                </option>
            @endforeach
        </select>

    @elseif($field->type === 'radio')
        @php
    $isRequired = $field->required && !$field->parent_id ? 'required' : '';
@endphp

@foreach(explode(',', $field->options ?? '') as $option)
    <div class="form-check form-check-inline">
        <input type="radio"
               class="form-check-input"
               name="custom[{{ $field->id }}]"
               value="{{ trim($option) }}"
               {{ trim($option) == $value ? 'checked' : '' }}
               data-required="{{ $field->required ? 'true' : 'false' }}"
               {{ $isRequired }}
               onchange="handleRadioChange('{{ $field->id }}', '{{ trim($option) }}')">
        <label class="form-check-label">{{ trim($option) }}</label>
    </div>
@endforeach


    @elseif($field->type === 'checkbox')
        @php $selected = is_array($value) ? $value : explode(',', $value); @endphp
        @foreach(explode(',', $field->options ?? '') as $option)
            <div class="form-check form-check-inline">
                <input type="checkbox"
                       class="form-check-input"
                       name="custom[{{ $field->id }}][]"
                       value="{{ trim($option) }}"
                       {{ in_array(trim($option), $selected) ? 'checked' : '' }}
                       data-required="{{ $field->required ? 'true' : 'false' }}">
                <label class="form-check-label">{{ trim($option) }}</label>
            </div>
        @endforeach
    @endif
</div>

{{-- Recursive rendering for children --}}
@if($field->children && $field->children->count())
    @foreach($field->children as $child)
        @include('cart.partials.field-edit', [
            'field' => $child,
            'fieldValues' => $fieldValues
        ])
    @endforeach
@endif
