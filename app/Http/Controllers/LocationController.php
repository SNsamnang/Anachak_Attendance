<?php
// app/Http/Controllers/LocationController.php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class LocationController extends Controller
{
    public function index()
    {
        $cid = $this->authCompanyId();
        $locations = Location::withCount('attendances')
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->orderBy('name')->get();
        return view('locations.index', compact('locations'));
    }

    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $companies = collect();
        if ($user->is_super_admin) {
            $companies = Company::all()->sortBy('name')->values();
        }
        return view('locations.create', compact('companies'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'name'          => 'required|string|max:100',
            'address'       => 'nullable|string|max:255',
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:5000',
            'company_id'    => 'nullable|exists:companies,id',
        ]);

        $companyId = $user->is_super_admin
            ? $request->company_id
            : $user->company_id;

        Location::create(array_merge(
            $request->only('name', 'address', 'latitude', 'longitude', 'radius_meters'),
            ['company_id' => $companyId]
        ));

        return redirect()->route('locations.index')
            ->with('success', "Location '{$request->name}' created.");
    }

    public function edit(Location $location)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $companies = $user->is_super_admin ? Company::query()->orderBy('name')->get() : collect();
        return view('locations.edit', compact('location', 'companies'));
    }

    public function update(Request $request, Location $location)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'name'          => 'required|string|max:100',
            'address'       => 'nullable|string|max:255',
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:5000',
            'is_active'     => 'boolean',
            'company_id'    => 'nullable|exists:companies,id',
        ]);

        $data = $request->only('name', 'address', 'latitude', 'longitude', 'radius_meters', 'is_active');

        if ($user->is_super_admin) {
            $data['company_id'] = $request->company_id ?: null;
        }

        $location->update($data);

        return redirect()->route('locations.index')
            ->with('success', 'Location updated.');
    }

    public function destroy(Location $location)
    {
        $location->delete();
        return redirect()->route('locations.index')
            ->with('success', 'Location deleted.');
    }

    // Show the QR scan page (this is what employee opens after scanning QR)
    public function scanPage(string $token)
    {
        $location = Location::where('qr_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        return view('locations.scan', compact('location', 'token'));
    }

    // Show QR code image for admin
    private function qrFormat(): string
    {
        return extension_loaded('imagick') ? 'png' : 'svg';
    }

    private function qrMimeType(string $format): string
    {
        return $format === 'svg' ? 'image/svg+xml' : 'image/png';
    }

    private function qrFilename(string $basename, string $format): string
    {
        return $basename . '-qr.' . ($format === 'svg' ? 'svg' : 'png');
    }

    public function showQr(Location $location)
    {
        $scanUrl    = route('locations.scan-page', $location->qr_token);
        $format     = $this->qrFormat();
        $qrCode     = QrCode::format($format)->size(300)->errorCorrection('H')->generate($scanUrl);
        $qrBase64   = base64_encode((string) $qrCode);
        $qrMimeType = $this->qrMimeType($format);

        return view('locations.qr', compact('location', 'qrBase64', 'scanUrl', 'qrMimeType'));
    }

    // Download QR as PNG or SVG depending on available backend
    public function downloadQr(Location $location)
    {
        $scanUrl  = route('locations.scan-page', $location->qr_token);
        $format   = $this->qrFormat();
        $qrCode   = QrCode::format($format)->size(500)->errorCorrection('H')->generate($scanUrl);
        $filename = $this->qrFilename('location-' . $location->id, $format);

        return response((string) $qrCode)
            ->header('Content-Type', $this->qrMimeType($format))
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
