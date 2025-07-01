@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Product</h2>

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row mb-3">
        <div class="col-sm-4">
            <label>Product Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>


          <div class="col-sm-4">
            <label>Price</label>
            <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $product->price ?? '') }}">
        </div>
        

        <div class="col-sm-4">
            <label>Post Image</label>
            <input type="file" name="post_image" class="form-control">
            @if(!empty($product->post_image))
            <img src="{{ asset('storage/' . $product->post_image) }}" height="60" class="mt-2">
            @endif
        </div>
    </div>

    <div class="mb-3">
        <label>Description</label>
        <textarea name="description" class="form-control"></textarea>
    </div>

    <h4>Add New Custom Fields (Dynamic)</h4>
    <div id="customFieldsContainer"></div>
    <button type="button" onclick="addField()" class="btn btn-outline-secondary mb-3">+ Add Field</button>

    <button type="submit" class="btn btn-primary">Save Product</button>
</form>
</div>

<script>
    const fieldTypes = ['text', 'select', 'radio', 'checkbox'];
    let fieldIndex = 0;
    let fieldRegistry = [];

    function addField(parentIndex = null, parentValue = null) {
        const index = fieldIndex++;
        const wrapperId = `field-wrapper-${index}`;

        const parentHidden = parentIndex !== null ? `
        <input type="hidden" name="fields[${index}][parent_temp]" value="${parentIndex}" />
        <input type="hidden" name="fields[${index}][dependency_value]" value="${parentValue}" />
        ` : '';

        const html = `
        <div class="border p-3 mb-3 row" id="${wrapperId}">
            ${parentHidden}
            <div class="col-sm-3">
                <select name="fields[${index}][type]" onchange="toggleOptions(this, ${index})" class="form-select mb-2">
            ${fieldTypes.map(t => `<option value="${t}">${t}</option>`).join('')}
                </select>
            </div>
            <div class="col-sm-7">
                <input type="text" name="fields[${index}][label]" placeholder="Label" class="form-control mb-2" required onblur="updateFieldRegistry(${index})">
            </div>
            <div class="col-sm-2">        
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="fields[${index}][required]" id="required-${index}" value="1">
              <label class="form-check-label" for="required-${index}">Required</label>
            </div>
                    </div>
            <div class="col-sm-12">
                <div id="options-${index}" style="display:none;" class="mb-2">
                    <textarea name="fields[${index}][options]" id="type-${index}" placeholder="Comma-separated options" class="form-control"></textarea>
                </div>
                <div class="col-sm-3" id="dependency-${index}" style="display:none;">
                    <label>Has Dependency?</label>
                    <select onchange="toggleDependency(${index}, this.value)" class="form-select mb-2" id="dependency-toggle-${index}">
                        <option value="">No</option>
                        <option value="yes">Yes</option>
                    </select>
                </div>
                <div class="col-sm-12" id="nested-${index}" style="display:none;"></div>
            </div>
        </div>
        `;

        const targetContainer = parentIndex === null
        ? document.getElementById('customFieldsContainer')
        : document.getElementById(`nested-option-${parentIndex}-${parentValue}`) || document.getElementById(`nested-${parentIndex}`);

        if (targetContainer) {
            const wrapper = document.createElement('div');
            wrapper.className = 'border mt-2';
            wrapper.innerHTML = html;
            targetContainer.appendChild(wrapper);
        }
    }

    function toggleOptions(select, index) {
        const typesWithOptions = ['select', 'radio', 'checkbox'];
        const optionsDiv = document.getElementById(`options-${index}`);
        const dependencyDiv = document.getElementById(`dependency-${index}`);
        optionsDiv.style.display = typesWithOptions.includes(select.value) ? 'block' : 'none';
        dependencyDiv.style.display = typesWithOptions.includes(select.value) ? 'block' : 'none';
    }

    function toggleDependency(index, value) {
        const nestedDiv = document.getElementById(`nested-${index}`);
        nestedDiv.style.display = value === 'yes' ? 'block' : 'none';
        nestedDiv.innerHTML = '';

        if (value === 'yes') {
            const optionsTextarea = document.getElementById(`type-${index}`);
            if (!optionsTextarea) return;
            const values = optionsTextarea.value.split(',').map(v => v.trim()).filter(Boolean);

            values.forEach((val, optIdx) => {
                const optionDivId = `nested-option-${index}-${val}`;
                const block = `
                <div class="border p-2 mb-2">
                    <strong>Option: ${val}</strong>
                    <div id="${optionDivId}" class="mt-2 mb-2"></div>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="addField(${index}, '${val}')">+ Add Field for "${val}"</button>
                </div>
                `;
                nestedDiv.insertAdjacentHTML('beforeend', block);
            });
        }
    }

    function updateFieldRegistry(index) {
        const input = document.querySelector(`input[name="fields[${index}][label]"]`);
        const label = input ? input.value.trim() : '';
        const exists = fieldRegistry.find(f => f.index === index);
        if (!exists && label) {
            fieldRegistry.push({ index, label });
        } else if (exists) {
            exists.label = label;
        }
    }
</script>
@endsection