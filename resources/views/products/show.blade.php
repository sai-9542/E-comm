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

            <p><strong>Total Price:</strong> ₹<span id="totalPrice">{{ $product->price }}</span></p>


        <button type="submit" class="btn btn-success">Add to Cart</button>
    </form>
</div>

{{-- Dependency Logic --}}
<script>
function handleChange(el, fieldId) {
    const value = el.value;
    document.querySelectorAll(`.dependent-field[data-parent="${fieldId}"]`).forEach(child => {
        const shouldShow = child.dataset.showIf === value;
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
        const shouldShow = child.dataset.showIf === selectedVal;
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

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.form-select').forEach(select => {
        const id = select.closest('.dependent-field')?.id?.replace('field_', '');
        if (id) handleChange(select, id);
    });

    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        const fieldId = radio.name.replace('custom[', '').replace(']', '');
        handleRadioChange(fieldId, radio.value);
    });
});


</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const basePrice = {{ $product->price }};
    const totalEl = document.getElementById('totalPrice');

    function updateTotal() {
        let total = basePrice;

        // Loop through each field wrapper
        document.querySelectorAll('.dependent-field').forEach(wrapper => {
            const hidden = wrapper.querySelector('input.custom-hidden');
            if (!hidden) return;

            const selects = wrapper.querySelectorAll('select.custom-option');
            const radios = wrapper.querySelectorAll('input[type="radio"].custom-option');
            const checkboxes = wrapper.querySelectorAll('input[type="checkbox"].custom-option');
            let fieldData = null;

            if (selects.length) {
                const select = selects[0];
                const selectedOption = select.options[select.selectedIndex];
                const value = select.value;
                const price = parseFloat(selectedOption?.dataset.price || 0);

                if (value) {
                    total += price;
                    fieldData = { value, price };
                }
            } else if (radios.length) {
                radios.forEach(radio => {
                    if (radio.checked) {
                        const value = radio.value;
                        const price = parseFloat(radio.dataset.price || 0);
                        total += price;
                        fieldData = { value, price };
                    }
                });
            } else if (checkboxes.length) {
                const values = [];
                let checkboxTotal = 0;

                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        const value = cb.value;
                        const price = parseFloat(cb.dataset.price || 0);
                        values.push({ value, price });
                        checkboxTotal += price;
                    }
                });

                if (values.length) {
                    total += checkboxTotal;
                    fieldData = values;
                }
            }

            hidden.value = fieldData ? JSON.stringify(fieldData) : '';
        });

        totalEl.innerText = total.toFixed(2);
    }

    // Attach listener
    document.querySelectorAll('.custom-option').forEach(input => {
        input.addEventListener('change', updateTotal);
    });

    updateTotal(); // Initial run
});
</script>




@endsection
