@extends('layouts.app')

<!-- <style>
.readonly-select, .readonly-input {
    pointer-events: none;
    background-color: #e9ecef;
}
</style> -->


@section('content')
<div class="container">
    <h2>Product Details</h2>

    <div class="mb-3">
        <strong>Name:</strong> {{ $product->name }}
    </div>
    <div class="mb-3">
        <strong>Description:</strong> {{ $product->description }}
    </div>

    <h4>Custom Fields</h4>
    @foreach($product->customFields->whereNull('parent_id') as $field)
        @include('partials.field-display', ['field' => $field, 'values' => $product->customFieldValues])
    @endforeach
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fields = document.querySelectorAll('.dependent-field');

    // First pass: set up a map of field values
    const values = {};
    fields.forEach(field => {
        const id = field.id.split('_')[1];
        const input = field.querySelector('input:checked, select');
        values[id] = input ? input.value : '';
    });

    // Second pass: show/hide based on dependency
    fields.forEach(field => {
        const parentId = field.dataset.parent;
        const showIf = field.dataset.showIf;

        if (parentId && showIf && values[parentId] !== showIf) {
            field.style.display = 'none';
        } else if (parentId && showIf && values[parentId] === showIf) {
            field.style.display = 'block';
        }
    });
});


</script>
<script>
function handleChange(el, fieldId) {
    const value = el.value;

    document.querySelectorAll('.dependent-field[data-parent="' + fieldId + '"]').forEach(child => {
        const expected = child.getAttribute('data-show-if');
        child.style.display = (value === expected) ? 'block' : 'none';
    });
}
</script>
<script>
window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('select.form-select').forEach(el => {
        const fieldId = el.closest('.dependent-field')?.id?.replace('field_', '');
        if (fieldId) {
            handleChange(el, fieldId);
        }
    });
});
</script>

<script type="text/javascript">

function handleRadioChange(fieldId, value) {
    document.querySelectorAll('.dependent-field[data-parent="' + fieldId + '"]').forEach(child => {
        const expected = child.getAttribute('data-show-if');
        child.style.display = (value === expected) ? 'block' : 'none';
    });
}
</script>

@endsection


@push('scripts')

@endpush
