@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Cart Item</h2>

    <form action="{{ route('cart.update', $cart->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="product_id" value="{{ $product->id }}">

        @foreach($product->customFields->whereNull('parent_id') as $field) 
            @include('cart.partials.field-edit', [
                'field' => $field,
                'fieldValues' => $fieldValues
            ])
        @endforeach

        <button type="submit" class="btn btn-primary mt-3">Update Cart</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.dependent-field').forEach(function (childField) {
        const parentId = childField.dataset.parent;
        const expectedValue = childField.dataset.showIf;

        if (!parentId || !expectedValue) return;

        const parentField = document.querySelector(`#field_${parentId}`);
        if (!parentField) return;

        let parentValue = '';

        // Select
        const select = parentField.querySelector('select');
        if (select) parentValue = select.value;

        // Radio
        const radio = parentField.querySelector('input[type="radio"]:checked');
        if (radio) parentValue = radio.value;

        // Optional text
        const text = parentField.querySelector('input[type="text"]');
        if (text && !parentValue) parentValue = text.value;

        const shouldShow = parentValue === expectedValue;
        childField.style.display = shouldShow ? 'block' : 'none';

        childField.querySelectorAll('[data-required="true"]').forEach(input => {
            if (shouldShow) input.setAttribute('required', 'required');
            else input.removeAttribute('required');
        });
    });
});
</script>


<script>
function handleChange(el, fieldId) {
    const value = el.value;

    document.querySelectorAll('.dependent-field[data-parent="' + fieldId + '"]').forEach(child => {
        const expected = child.getAttribute('data-show-if');
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

</script>



@endsection
