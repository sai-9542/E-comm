@php
    $value = $fieldValues[$field->id] ?? null;
    $options = explode(',', $field->options ?? '');
    $prices = explode(',', str_replace(['[', ']'], '', $field->option_prices ?? ''));
@endphp

<div class="mb-3 border-start ps-3 dependent-field"
     id="field_{{ $field->id }}"
     data-parent="{{ $field->parent_id }}"
     data-show-if="{{ $field->dependency_value }}"
     style="{{ $field->parent_id ? 'display:none;' : '' }}">



    <strong>{{ $field->label }}</strong><br>

    @if($field->type === 'text')
        <input type="text"
               class="form-control"
               name="custom[{{ $field->id }}]"
               data-required="{{ $field->required ? 'true' : 'false' }}"
               {{ $field->required && !$field->parent_id ? 'required' : '' }}>

    @elseif($field->type === 'select')
        <select class="form-select custom-option"
                name="custom1[{{ $field->id }}]"
                data-field-id="{{ $field->id }}"
                data-type="select"
                onchange="handleChange(this, '{{ $field->id }}')"
                data-required="{{ $field->required ? 'true' : 'false' }}"
                {{ $field->required && !$field->parent_id ? 'required' : '' }}>
            <option value="">Select</option>
            @foreach($options as $i => $opt)
                @php $label = trim($opt); $price = $prices[$i] ?? 0; @endphp
                <option value="{{ $label }}" data-price="{{ $price }}">
                    {{ $label }} {{ $price > 0 ? '+₹'.$price : '' }}
                </option>
            @endforeach
        </select>
             <input type="hidden" name="custom[{{ $field->id }}]" class="custom-hidden" />


    @elseif($field->type === 'radio')
        @foreach($options as $i => $opt)
            @php $label = trim($opt); $price = $prices[$i] ?? 0; @endphp
            <div class="form-check form-check-inline">
                <input type="radio"
                       class="form-check-input custom-option"
                       name="radio_temp[{{ $field->id }}]"
                       value="{{ $label }}"
                       data-price="{{ $price }}"
                       data-type="radio"
                       onchange="handleRadioChange('{{ $field->id }}', '{{ $label }}')">
                <label class="form-check-label">{{ $label }} {{ $price > 0 ? '+₹'.$price : '' }}</label>
            </div>
        @endforeach

        {{-- Hidden input to actually submit JSON --}}
        <input type="hidden" name="custom[{{ $field->id }}]" class="custom-hidden" />


    @elseif($field->type === 'checkbox')
       @foreach($options as $i => $opt)
        @php $label = trim($opt); $price = $prices[$i] ?? 0; @endphp
        <div class="form-check form-check-inline">
            <input type="checkbox"
                   class="form-check-input custom-option"
                   name="checkbox_temp[{{ $field->id }}][]"
                   value1='@json(["value" => $label, "price" => $price])'
                   value="{{ $opt }}"
                   data-price="{{ $price }}"
                   data-type="checkbox"
                   onchange="updateTotal()">
            <label class="form-check-label">{{ $label }} {{ $price > 0 ? '+₹'.$price : '' }}</label>
        </div>
    @endforeach

    {{-- Hidden input to submit as JSON array --}}
    <input type="hidden" name="custom[{{ $field->id }}]" class="custom-hidden" />

    @endif
</div>

@if($field->children && $field->children->count())
    @foreach($field->children as $child)
        @include('cart.partials.field-edit', [
            'field' => $child,
            'fieldValues' => [],
            'parentValue' => $field->dependency_value
        ])
    @endforeach
@endif
