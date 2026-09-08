<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Parcel Label {{ $order->order_number }}</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#eef2f7;color:#000;font-family:Arial,sans-serif}.toolbar{display:flex;justify-content:center;gap:10px;padding:16px}.toolbar button,.toolbar a{border:0;border-radius:8px;padding:10px 18px;background:#111827;color:#fff;font-size:14px;font-weight:700;text-decoration:none;cursor:pointer}.toolbar a{background:#64748b}.label{width:4in;min-height:6in;margin:0 auto 24px;background:#fff;border:1px solid #111;padding:.16in}.row{display:flex;justify-content:space-between;gap:12px}.sender{padding-bottom:10px;border-bottom:2px solid #000}.sender h1{margin:0 0 4px;font-size:18px}.small{font-size:10px;line-height:1.35}.section{padding:10px 0;border-bottom:1px solid #000}.heading{margin:0 0 5px;font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.customer{font-size:17px;font-weight:800}.phone{margin-top:4px;font-size:20px;font-weight:900}.address{margin-top:5px;font-size:13px;line-height:1.35}.badge{display:inline-block;border:2px solid #000;padding:4px 7px;font-size:11px;font-weight:800}.cod{font-size:22px;font-weight:900}.items{margin:0;padding-left:16px;font-size:10px;line-height:1.4}.barcode{padding-top:10px;text-align:center}.barcode svg{display:block;width:100%;height:82px}.note{margin-top:7px;border:1px dashed #000;padding:5px;font-size:9px}.muted{color:#444}@page{size:4in 6in;margin:0}@media print{body{background:#fff}.toolbar{display:none}.label{margin:0;border:0;width:4in;height:6in;overflow:hidden;page-break-after:always}}
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print label</button><a href="{{ route('orders.show', $order) }}">Back to order</a></div>
    <article class="label">
        <header class="sender row">
            <div>
                <h1>{{ $settings?->site_name ?? config('app.name') }}</h1>
                <div class="small">{{ $settings?->business_phone ?: 'Sender phone not set' }}</div>
                <div class="small">{{ $settings?->business_address ?: 'Sender address not set' }}</div>
                <div class="small">{{ $settings?->website_url ?: url('/') }}</div>
            </div>
            <div style="text-align:right"><div class="heading">Order</div><strong>{{ $order->order_number }}</strong><div class="small">{{ $order->created_at->format('d M Y') }}</div></div>
        </header>

        <section class="section">
            <p class="heading">Deliver to</p>
            <div class="customer">{{ $order->customer_name }}</div>
            <div class="phone">{{ $order->customer_phone }}</div>
            <div class="address">{{ $order->shipping_address }}</div>
            @if($order->customer_email)<div class="small muted" style="margin-top:4px">{{ $order->customer_email }}</div>@endif
        </section>

        <section class="section row" style="align-items:center">
            <div><p class="heading">Payment</p><span class="badge">{{ $order->payment_method === 'cash_on_delivery' ? 'CASH ON DELIVERY' : strtoupper(str_replace('_',' ',$order->payment_method)) }}</span></div>
            <div style="text-align:right"><p class="heading">Collect amount</p><div class="cod">৳{{ number_format((float)$order->total,2) }}</div></div>
        </section>

        <section class="section row">
            <div><p class="heading">Items ({{ $order->items->sum('quantity') }})</p><ul class="items">@foreach($order->items as $item)<li>{{ $item->product_title }} × {{ $item->quantity }}</li>@endforeach</ul></div>
            @if($order->shipment)<div style="min-width:105px;text-align:right"><p class="heading">{{ $order->shipment->provider }}</p><div class="small"><strong>ID:</strong> {{ $order->shipment->external_id }}</div>@if($order->shipment->tracking_code)<div class="small"><strong>Tracking:</strong> {{ $order->shipment->tracking_code }}</div>@endif</div>@endif
        </section>

        @if($order->customer_note)<div class="note"><strong>Note:</strong> {{ $order->customer_note }}</div>@endif
        <div class="barcode">{!! $barcodeSvg !!}</div>
    </article>
</body>
</html>
