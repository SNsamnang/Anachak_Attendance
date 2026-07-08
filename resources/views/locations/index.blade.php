@extends('layouts.app')
@section('page-title','Locations')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">All Locations</h5>
    <a href="{{ route('locations.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus"></i> Add Location
    </a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Coordinates</th>
                    <th>Radius</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($locations as $loc)
                <tr>
                    <td class="fw-semibold">{{ $loc->name }}</td>
                    <td class="text-muted small">{{ $loc->address ?? '—' }}</td>
                    <td><code style="font-size:.8rem">{{ $loc->latitude }}, {{ $loc->longitude }}</code></td>
                    <td><span class="badge bg-info text-dark">{{ $loc->radius_meters }}m</span></td>
                    <td>
                        @if($loc->is_active)
                        <span class="badge bg-success">Active</span>
                        @else
                        <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('locations.qr', $loc) }}"
                                class="btn btn-sm btn-outline-primary" title="View QR">
                                <i class="bi bi-qr-code"></i>
                            </a>
                            <a href="{{ route('locations.download-qr', $loc) }}"
                                class="btn btn-sm btn-outline-secondary" title="Download QR">
                                <i class="bi bi-download"></i>
                            </a>
                            <a href="{{ route('locations.edit', $loc) }}"
                                class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('locations.destroy', $loc) }}"
                                onsubmit="return confirm('Delete {{ $loc->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No locations yet. <a href="{{ route('locations.create') }}">Add one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection