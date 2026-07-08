@extends('layouts.app')
@section('page-title','Add Location')
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header p-3">Add New Location</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('locations.store') }}">
                    @csrf
                    @if($companies->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Company</label>
                        <select name="company_id" class="form-select">
                            <option value="">— No company —</option>
                            @foreach($companies as $co)
                            <option value="{{ $co->id }}" @selected(old('company_id') == $co->id)>{{ $co->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Location Name *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" placeholder="Main Office" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address</label>
                        <input type="text" name="address" class="form-control"
                            value="{{ old('address') }}" placeholder="123 Street, Phnom Penh">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label fw-semibold">Latitude *</label>
                            <input type="number" name="latitude" step="any"
                                class="form-control @error('latitude') is-invalid @enderror"
                                id="latInput" value="{{ old('latitude') }}"
                                placeholder="11.5564" required>
                            @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold">Longitude *</label>
                            <input type="number" name="longitude" step="any"
                                class="form-control @error('longitude') is-invalid @enderror"
                                id="lngInput" value="{{ old('longitude') }}"
                                placeholder="104.9282" required>
                            @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="getMyLocation()">
                            <i class="bi bi-crosshair"></i> Use my current GPS location
                        </button>
                        <div class="form-text">Or open Google Maps, right-click a point → copy coordinates</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Allowed Radius: <span id="radiusLabel">100</span>m
                        </label>
                        <input type="range" name="radius_meters" class="form-range"
                            min="10" max="500" step="10"
                            value="{{ old('radius_meters', 100) }}"
                            oninput="document.getElementById('radiusLabel').textContent=this.value">
                        <div class="d-flex justify-content-between" style="font-size:.75rem;color:#888">
                            <span>10m (very tight)</span>
                            <span>100m (recommended)</span>
                            <span>500m</span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Location</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header p-3">How to get coordinates</div>
            <div class="card-body">
                <ol class="small" style="line-height:2">
                    <li>Open <strong>Google Maps</strong> on your computer</li>
                    <li>Navigate to your location (office, warehouse, etc.)</li>
                    <li><strong>Right-click</strong> on the exact point</li>
                    <li>Click the coordinates at the top of the menu</li>
                    <li>Paste into Latitude and Longitude fields</li>
                </ol>
                <hr>
                <p class="small text-muted mb-0">
                    Or click <strong>"Use my current GPS location"</strong> if you are physically at the location right now.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function getMyLocation() {
        if (!navigator.geolocation) {
            alert('GPS not supported.');
            return;
        }
        navigator.geolocation.getCurrentPosition(pos => {
            document.getElementById('latInput').value = pos.coords.latitude.toFixed(7);
            document.getElementById('lngInput').value = pos.coords.longitude.toFixed(7);
        }, () => alert('Could not get location. Please enter manually.'), {
            enableHighAccuracy: true
        });
    }
</script>
@endpush