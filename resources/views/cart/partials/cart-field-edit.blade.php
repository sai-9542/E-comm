@php
    $value = $fieldValues[$field->id] ?? null;
    $options = explode(',', $field->options ?? '');
    $prices = explode(',', str_replace(['[', ']'], '', $field->option_prices ?? ''));
    //print_r($fieldValues);
@endphp

<div class="mb-3 border-start ps-3 dependent-field"
     id="field_{{ $field->id }}"
     data-parent="{{ $field->parent_id }}"
     data-show-if="{{ $field->dependency_value }}"
     style="{{ $field->parent_id ? 'display:none;' : '' }}">

    <strong>{{ $field->label }}</strong><br>
    
    @php
    	$cartVal = $fieldValues[$field->label];
    	//print_r($cartVal);
    @endphp
    <!-- <li>{{ $field->label }}: {{ $cartVal['value'] ?? '' }} (₹{{ $cartVal['price'] ?? 0 }})</li> -->


    {{-- Text Field --}}
    @if($field->type === 'text')
        <input type="text"
               name="custom[{{ $field->id }}]"
               class="form-control"
               value="{{ is_array($value) ? $value['value'] ?? '' : $value }}"
               data-required="{{ $field->required ? 'true' : 'false' }}"
               {{ $field->required ? 'required' : '' }}>

    {{-- Select Field --}}
    @elseif($field->type === 'select')
        <select name="select_temp_{{ $field->id }}"
                class="form-select custom-option"
                data-field-id="{{ $field->id }}"
                data-type="select"
                onchange="handleChange(this, '{{ $field->id }}')"
                data-required="{{ $field->required ? 'true' : 'false' }}"
                {{ $field->required ? 'required' : '' }}>
            <option value="">Select</option>
            @foreach($options as $i => $opt)
                @php
                    echo $opt = trim($opt);
                    $price = $prices[$i] ?? 0;
                    $selected = isset($cartVal['value']) && $cartVal['value'] == $opt ? 'selected' : '';
                @endphp
                <option value="{{ $opt }}"
                        data-price="{{ $price }}"
                        {{ $selected ? 'selected' : '' }}>
                    {{ $opt }} {{ $price > 0 ? '+₹' . $price : '' }}
                </option>
            @endforeach
        </select>
        <input type="hidden" name="custom[{{ $field->id }}]" class="custom-hidden" />


    {{-- Radio Field --}}
    @elseif($field->type === 'radio')
        @foreach($options as $i => $opt)
            @php
                $opt = trim($opt);
                $price = $prices[$i] ?? 0;
                //$checked = is_array($value) && isset($value['value']) ? $value['value'] == $opt : $value == $opt;
                $checked = isset($cartVal['value']) && $cartVal['value'] == $opt ? 'checked' : '';
            @endphp
            <div class="form-check form-check-inline">
                <input type="radio"
                       name="custom[{{ $field->id }}]"
                       class="form-check-input custom-option"
                       value="{{ $opt }}"
                       data-type="radio"
                       data-price="{{ $price }}"
                       onchange="handleRadioChange('{{ $field->id }}', '{{ $opt }}')"
                       {{ $checked ? 'checked' : '' }}
                       {{ $field->required ? 'required' : '' }}>
                <label class="form-check-label">{{ $opt }} {{ $price > 0 ? '+₹' . $price : '' }}</label>
            </div>
        @endforeach
        <input type="hidden" name="custom[{{ $field->id }}]" class="custom-hidden" />

    {{-- Checkbox Field --}}
    @elseif($field->type === 'checkbox')
       @php
			    $fieldLabel = $field->label;

			    // Get saved values for this field from session/cart
			    $cartVal = $fieldValues[$fieldLabel] ?? [];

			    // Normalize cart values: may be 1D (['value'=>'A',...]) or 2D ([[], []])
			    $selectedValues = collect(is_array($cartVal[0] ?? null) ? $cartVal : [$cartVal])
			        ->pluck('value')
			        ->map(fn($v) => trim($v))
			        ->all();

			    // Field options and prices
			    $options = array_map('trim', explode(',', $field->options ?? ''));
			    //$prices = array_map('trim', explode(',', $field->prices ?? '')); print_r($prices);
			@endphp

			@foreach($options as $i => $opt)
			    @php
			        $price = $prices[$i] ?? 0;
			        $isChecked = in_array($opt, $selectedValues);
			    @endphp

			    <div class="form-check form-check-inline">
			        <input type="checkbox"
			               name="custom[{{ $field->id }}][]"
			               class="form-check-input custom-option"
			               value="{{ $opt }}"
			               data-type="checkbox"
			               data-price="{{ $price }}"
			               {{ $isChecked ? 'checked' : '' }}>
			        <label class="form-check-label">
			            {{ $opt }} {{ $price > 0 ? '+₹' . $price : '' }}
			        </label>
			    </div>
			@endforeach


        <input type="hidden" name="custom[{{ $field->id }}]" class="custom-hidden" />
    @endif
</div>

{{-- Recursively render children --}}
@if($field->children && $field->children->count())
    @foreach($field->children as $child)
        @include('cart.partials.cart-field-edit', [
            'field' => $child,
            'fieldValues' => $fieldValues
        ])
    @endforeach
@endif
