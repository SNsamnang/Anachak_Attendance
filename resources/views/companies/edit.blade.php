@extends('layouts.app')
@section('page-title','Edit Company')
@section('content')

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary btn-sm">← Back</a>
    <h6 class="mb-0 fw-semibold">Edit Company — {{ $company->name }}</h6>
</div>

<div class="card" style="max-width:520px">
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('companies.update', $company) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $company->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Code <span class="text-muted fw-normal">(short identifier)</span></label>
                <input type="text" name="code" class="form-control font-monospace text-uppercase"
                    value="{{ old('code', $company->code) }}" placeholder="e.g. ACME" maxlength="20">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Address</label>
                <input type="text" name="address" class="form-control" value="{{ old('address', $company->address) }}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $company->phone) }}">
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                        @checked(old('is_active', $company->is_active))>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
            </div>
            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="salary_enabled" value="1" id="salaryEnabled"
                        @checked(old('salary_enabled', $company->salary_enabled))>
                    <label class="form-check-label" for="salaryEnabled">Enable Salary Summaries</label>
                </div>
                <div class="form-text">Uncheck to hide salary features for this company's admin.</div>
            </div>

            <hr>
            <div class="mb-2 fw-semibold" style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">
                <i class="bi bi-telegram me-1"></i> Telegram Alerts (optional)
            </div>
            <div class="form-text mb-3">Leave blank to use the global Telegram settings from .env</div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Bot Token</label>
                <input type="text" name="telegram_bot_token" class="form-control font-monospace"
                    value="{{ old('telegram_bot_token', $company->telegram_bot_token) }}"
                    placeholder="123456:ABC-DEF...">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Group Chat ID</label>
                <input type="text" name="telegram_chat_id" class="form-control font-monospace"
                    value="{{ old('telegram_chat_id', $company->telegram_chat_id) }}"
                    placeholder="-1001234567890">
            </div>
            <div class="row g-2 mb-4">
                <div class="col-4">
                    <label class="form-label fw-semibold">Attendance Topic</label>
                    <input type="number" name="telegram_topic_attendance" class="form-control"
                        value="{{ old('telegram_topic_attendance', $company->telegram_topic_attendance) }}" placeholder="2">
                </div>
                <div class="col-4">
                    <label class="form-label fw-semibold">Leave Topic</label>
                    <input type="number" name="telegram_topic_leave" class="form-control"
                        value="{{ old('telegram_topic_leave', $company->telegram_topic_leave) }}" placeholder="4">
                </div>
                <div class="col-4">
                    <label class="form-label fw-semibold">Device Topic</label>
                    <input type="number" name="telegram_topic_device" class="form-control"
                        value="{{ old('telegram_topic_device', $company->telegram_topic_device) }}" placeholder="6">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>
@endsection
