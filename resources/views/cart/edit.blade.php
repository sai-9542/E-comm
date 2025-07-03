@extends('layouts.app')

@section('content')
@php
    $cart = session()->get('cart', []);
    $matchedItems = collect($cart)->where('product_id', $product->id);
    $existedInCart = $matchedItems->first();
    $cartKey = $matchedItems->keys()->first();
@endphp

<div class="container">
    <form action="{{ route('cart.store') }}" method="post">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        @if($cartKey)
            <input type="hidden" name="cart_key" value="{{ $cartKey }}">
        @endif

        <h2>{{ $product->name }}</h2>
        <p>{{ $product->description }}</p>

        <h4>Custom Fields</h4>
        @foreach($product->customFields->whereNull('parent_id') as $field)
            @include('cart.partials.cart-field-edit', [
                'field' => $field,
                'fieldValues' => $existedInCart['custom_fields'] ?? []
            ])
        @endforeach

        <p><strong>Total Price:</strong> ₹<span id="totalPrice">{{ $existedInCart['total_price'] ?? $product->price }}</span></p>

        <button type="submit" class="btn btn-success">
            {{ $existedInCart ? 'Update Cart' : 'Add to Cart' }}
        </button>
    </form>
</div>

<!-- <script>
function handleChange(el, fieldId) {
    const value = el.value;
    document.querySelectorAll(`.dependent-field[data-parent="${fieldId}"]`).forEach(child => {
        const shouldShow = child.dataset.showIf === value;
        child.style.display = shouldShow ? 'block' : 'none';
        child.querySelectorAll('[data-required="true"]').forEach(input => {
            if (shouldShow) input.setAttribute('required', 'required');
            else input.removeAttribute('required');
        });
    });
}

function handleRadioChange(fieldId, selectedVal) {
    document.querySelectorAll(`.dependent-field[data-parent="${fieldId}"]`).forEach(child => {
        const shouldShow = child.dataset.showIf === selectedVal;
        child.style.display = shouldShow ? 'block' : 'none';
        child.querySelectorAll('[data-required="true"]').forEach(input => {
            if (shouldShow) input.setAttribute('required', 'required');
            else input.removeAttribute('required');
        });
    });
}

function updateTotal() {
    const basePrice = parseFloat({{ $product->price }});
    const totalEl = document.getElementById('totalPrice');
    let total = basePrice;

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

// On DOM load
window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.form-select').forEach(select => {
        const id = select.closest('.dependent-field')?.id?.replace('field_', '');
        if (id) handleChange(select, id);
    });
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        const fieldId = radio.name.replace('custom[', '').replace(']', '');
        handleRadioChange(fieldId, radio.value);
    });
    document.querySelectorAll('.custom-option').forEach(input => {
        input.addEventListener('change', updateTotal);
    });
    updateTotal();
});
</script> -->

<script>
function handleChange(el, fieldId) {
    const value = el.value;
    document.querySelectorAll(`.dependent-field[data-parent="${fieldId}"]`).forEach(child => {
        const shouldShow = child.dataset.showIf === value;
        child.style.display = shouldShow ? 'block' : 'none';
        child.querySelectorAll('[data-required="true"]').forEach(input => {
            if (shouldShow) input.setAttribute('required', 'required');
            else input.removeAttribute('required');
        });
    });
}

function handleRadioChange(fieldId, selectedVal) {
    document.querySelectorAll(`.dependent-field[data-parent="${fieldId}"]`).forEach(child => {
        const shouldShow = child.dataset.showIf === selectedVal;
        child.style.display = shouldShow ? 'block' : 'none';
        child.querySelectorAll('[data-required="true"]').forEach(input => {
            if (shouldShow) input.setAttribute('required', 'required');
            else input.removeAttribute('required');
        });
    });
}

function updateTotal() {
    const basePrice = parseFloat({{ $product->price }});
    const totalEl = document.getElementById('totalPrice');
    let total = basePrice;

    document.querySelectorAll('.dependent-field').forEach(wrapper => {
            const hidden = wrapper.querySelector('input.custom-hidden');
            if (!hidden) return;

            const selects = wrapper.querySelectorAll('select.custom-option');
            const radios = wrapper.querySelectorAll('input[type="radio"].custom-option');
            const checkboxes = wrapper.querySelectorAll('input[type="checkbox"].custom-option');
            console.log(radios);
            console.log(checkboxes);
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

window.addEventListener('DOMContentLoaded', () => {
    // 👇 Load dependencies visibility on load
    document.querySelectorAll('.form-select').forEach(select => {
        const id = select.closest('.dependent-field')?.id?.replace('field_', '');
        if (id) handleChange(select, id);
    });

    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        if (radio.checked) {
            const fieldId = radio.name.replace('custom[', '').replace(']', '');
            handleRadioChange(fieldId, radio.value);
        }
    });

    // 👇 Add event listeners
    document.querySelectorAll('.custom-option').forEach(input => {
        input.addEventListener('change', () => {
            const fieldId = input.closest('.dependent-field')?.id?.replace('field_', '');
            if (input.type === 'radio') {
                handleRadioChange(fieldId, input.value);
            } else if (input.tagName === 'SELECT') {
                handleChange(input, fieldId);
            }
            updateTotal();
        });
    });

    document.querySelectorAll('.custom-option').forEach(input => {
        input.addEventListener('change', updateTotal);
    });

    // ✅ This is the key fix: it now calculates selected values on load (including radios/checkboxes)
    updateTotal();
});
</script>


@endsection
