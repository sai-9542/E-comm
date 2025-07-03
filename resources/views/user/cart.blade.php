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

    @foreach(session('cart', []) as $item)
                <div class="mb-3 border p-2">
                    <strong>{{ $item['name'] }}</strong><br>
                    Base Price: ₹{{ $item['price'] }}<br>

                    @if(!empty($item['custom_fields']))
                        <ul>
                            @foreach($item['custom_fields'] as $label => $data)
                                @if(is_array($data) && isset($data[0]['value']))
                                    @foreach($data as $opt)
                                        <li>{{ $label }}: {{ $opt['value'] }} (₹{{ $opt['price'] }})</li>
                                    @endforeach
                                @else
                                    <li>{{ $label }}: {{ $data['value'] }} (₹{{ $data['price'] }})</li>
                                @endif
                            @endforeach
                        </ul>
                    @endif

                    <strong>Total: ₹{{ $item['total_price'] }}</strong>
                </div>
            @endforeach

</div>
@endsection
