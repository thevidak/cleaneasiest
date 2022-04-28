<div class="bg-white rounded shadow-sm p-4 py-4 d-flex flex-column">
    <div class="row form-group align-items-baseline">
        <div class="col pe-md-0">
            <span>Status narudzbine:</span>
        </div>
        <div class="col pe-md-0">
            <span>{{ $status }}</span>
        </div>
    </div>
    
    <div class="form-label">Status narudzbine: {{ $status }}</div>
    <div>Klijent <a href= "{{ route('client.edit', $client['id']) }}">{{ $client['name'] }}</div>
</div>