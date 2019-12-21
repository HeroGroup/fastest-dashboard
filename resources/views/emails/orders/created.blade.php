<div>
    <h3 style="color: #5cb85c">Your Order Placed Successfully!</h3>
    <span style="margin: 10px;">ID: <b>#{{ $uniqueNumber }}</b></span><br>
    <span style="margin: 10px;">Date: <b>{{ $date }}</b></span><br>
    <span style="margin: 10px;">Price: <b>{{ $totalPrice-$totalDiscount }}</b></span>
    <br><br>
    <div style="border: silver solid 1px; border-radius: 5px;">
        <h4 style="background-color: #f9f9f9; padding: 10px; margin: 0 0 10px 0;">Order Summary</h4>
        <table style="width: 100%">
            <thead>
                <tr>
                    <th style="padding: 15px; text-align: left">Restaurant</th>
                    <th style="padding: 15px; text-align: left">Food</th>
                    <th style="padding: 15px; text-align: center">Quantity</th>
                    <th style="padding: 15px; text-align: right">Price</th>
                    <th style="padding: 15px; text-align: right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td style="padding: 15px; text-align: left">{{ $item->food->restaurant->user->name }}</td>
                        <td style="padding: 15px; text-align: left">{{ $item->food->name_en }}</td>
                        <td style="padding: 15px; text-align: center">{{ $item->count }}</td>
                        <td style="padding: 15px; text-align: right">{{ $item->price }}</td>
                        <td style="padding: 15px; text-align: right">{{ $item->price*$item->count }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="4" style="padding: 15px; text-align: right;">Sum Price</td>
                    <td style="padding: 15px; text-align: right;">{{ $totalPrice }}</td>
                </tr>
                <tr>
                    <td colspan="4" style="padding: 15px; text-align: right;">Discount</td>
                    <td style="padding: 15px; text-align: right;">{{ $totalDiscount > 0 ? $totalDiscount : 0 }}</td>
                </tr>
                <tr>
                    <td colspan="4" style="padding: 15px; text-align: right;">Payable</td>
                    <td style="padding: 15px; text-align: right;">{{ $totalPrice - $totalDiscount }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
