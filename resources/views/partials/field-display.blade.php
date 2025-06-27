@php
    $fieldValue = $values->where('custom_field_id', $field->id)->first()->value ?? null;
    $fieldId = 'field_' . $field->id;
@endphp



<div class="mb-3 ps-3 border-start dependent-field" 
     id="{{ $fieldId }}" 
     data-parent="{{ $field->parent_id }}" 
     data-show-if="{{ $field->dependency_value }}" 
     style="{{ $field->parent_id ? 'display:none;' : '' }}">

    <strong>{{ $field->label }}</strong><br>

    @if($field->type === 'text')
        <input type="text" class="form-control" value="{{ $fieldValue }}" readonly>

    @elseif($field->type === 'select')
        <select class="form-select readonly-select" onchange="handleChange(this, '{{ $field->id }}')">
            @foreach(explode(',', $field->options ?? '') as $option)
                <option value="{{ trim($option) }}" {{ trim($option) == $fieldValue ? 'selected' : '' }}>
                    {{ trim($option) }}
                </option>
            @endforeach
        </select>

    @elseif($field->type === 'radio')
        @foreach(explode(',', $field->options ?? '') as $option)
    <div class="form-check form-check-inline">
        <input type="radio"
               class="form-check-input readonly-radio"
               name="radio_{{ $field->id }}"
               value="{{ trim($option) }}"
               {{ trim($option) == $fieldValue ? 'checked' : '' }}
               onclick="handleRadioChange({{ $field->id }}, '{{ trim($option) }}')">
        <label class="form-check-label">{{ trim($option) }}</label>
    </div>
@endforeach

    @elseif($field->type === 'checkbox')
        @php $selected = explode(',', $fieldValue); @endphp
        @foreach(explode(',', $field->options ?? '') as $option)
            <div class="form-check form-check-inline">
                <input type="checkbox" class="form-check-input" disabled {{ in_array(trim($option), $selected) ? 'checked' : '' }}>
                <label class="form-check-label">{{ trim($option) }}</label>
            </div>
        @endforeach
    @endif
</div>

{{-- Render children recursively --}}
@if($field->children && $field->children->count())
    @foreach($field->children as $child)
        @include('partials.field-display', ['field' => $child, 'values' => $values])
    @endforeach
@endif
