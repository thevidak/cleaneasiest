{{ $weightClass }}
@foreach ($order->services as $service)
<div>
    Service Group : {{ json_encode($service) }}
    @foreach ($service['service_ids'] as $ids)
        {{ $ids }}
        @foreach ($weightClass as $wc)
            @if ()

            @endif
        @endforeach
    @endforeach
</div>
@endforeach