@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Your Cart</h2>

    @if(count($cart) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Custom Fields</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cart as $key => $item)  
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['qty'] }}</td>
                        <td>
                            @foreach($item['custom_fields'] as $fieldId => $value)
                                <strong>{{ $fieldMap[$fieldId] ?? 'Unknown' }}</strong>: {{ $value }}<br>
                            @endforeach

                        </td>
                        <td>
<a href="{{ route('cart.edit', $item['id']) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('cart.destroy', $item['id']) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Your cart is empty.</p>
    @endif
</div>
@endsection
