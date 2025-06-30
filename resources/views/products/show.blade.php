@extends('layouts.app')

@section('content')
<div class="container">
    <form action="{{ route('cart.store') }}" method="post">
        @csrf

        <input type="hidden" name="product_id" value="{{ $product->id }}">

        <h2>Product Details</h2>
        <div class="mb-3">
            <strong>Name:</strong> {{ $product->name }}
        </div>
        <div class="mb-3">
            <strong>Description:</strong> {{ $product->description }}
        </div>

        <h4>Custom Fields</h4>
        @foreach($product->customFields->whereNull('parent_id') as $field)
            @include('cart.partials.field-edit', [
                'field' => $field,
                'fieldValues' => []
            ])
        @endforeach

        <button type="submit" class="btn btn-success">Add to Cart</button>
    </form>
</div>

{{-- Dependency Logic --}}
<script>
function handleChange(el, fieldId) {
    const value = el.value;

    document.querySelectorAll(`.dependent-field[data-parent="${fieldId}"]`).forEach(child => {
        const expected = child.dataset.showIf;
        const shouldShow = value === expected;

        child.style.display = shouldShow ? 'block' : 'none';

        child.querySelectorAll('[data-required="true"]').forEach(input => {
            if (shouldShow) {
                input.setAttribute('required', 'required');
            } else {
                input.removeAttribute('required');
            }
        });
    });
}

function handleRadioChange(fieldId, selectedVal) {
    document.querySelectorAll(`.dependent-field[data-parent="${fieldId}"]`).forEach(child => {
        const expected = child.dataset.showIf;
        const isVisible = selectedVal === expected;

        child.style.display = isVisible ? 'block' : 'none';

        const radios = child.querySelectorAll('input[type="radio"]');
        if (radios.length > 0) {
            // Set required on first radio in the group
            if (isVisible) {
                radios[0].setAttribute('required', 'required');
            } else {
                radios[0].removeAttribute('required');
            }
        }

        const inputs = child.querySelectorAll('[data-required="true"]:not([type="radio"])');
        inputs.forEach(input => {
            if (isVisible) {
                input.setAttribute('required', 'required');
            } else {
                input.removeAttribute('required');
            }
        });
    });
}


document.addEventListener('DOMContentLoaded', function () {
    // Initialize selects
    document.querySelectorAll('.form-select').forEach(select => {
        const fieldId = select.closest('.dependent-field')?.id?.replace('field_', '');
        if (fieldId) handleChange(select, fieldId);
    });

    // Initialize radios
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        const fieldId = radio.name.replace('custom[', '').replace(']', '');
        handleRadioChange(fieldId, radio.value);
    });
});
</script>
@endsection
