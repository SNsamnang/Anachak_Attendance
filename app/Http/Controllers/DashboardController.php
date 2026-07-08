<?php
// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Location;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $cid   = $this->authCompanyId();

        $totalEmployees = Employee::when($cid, fn($q) => $q->where('company_id', $cid))->count();
        $totalLocations = Location::where('is_active', true)
            ->when($cid, fn($q) => $q->where('company_id', $cid))->count();

        $checkedInToday = Attendance::whereDate('scanned_at', $today)
            ->where('type', 'in')->where('location_verified', true)
            ->when($cid, fn($q) => $q->whereHas('employee', fn($e) => $e->where('company_id', $cid)))
            ->distinct('employee_id')->count('employee_id');

        $rejectedToday = Attendance::whereDate('scanned_at', $today)
            ->where('location_verified', false)
            ->when($cid, fn($q) => $q->whereHas('employee', fn($e) => $e->where('company_id', $cid)))
            ->count();

        // Per-location stats today
        $locationStats = Location::when($cid, fn($q) => $q->where('company_id', $cid))
            ->withCount([
                'attendances as checkins_today' => fn($q) =>
                    $q->whereDate('scanned_at', $today)->where('type', 'in')->where('location_verified', true),
                'attendances as rejected_today' => fn($q) =>
                    $q->whereDate('scanned_at', $today)->where('location_verified', false),
            ])->get();

        // Currently inside each location
        $currentlyInside = Employee::when($cid, fn($q) => $q->where('company_id', $cid))
            ->whereHas('lastAttendance', fn($q) => $q->where('type', 'in')->where('location_verified', true))
            ->with(['lastAttendance.location'])->get();

        // Recent activity
        $recentActivity = Attendance::with(['employee', 'location'])
            ->when($cid, fn($q) => $q->whereHas('employee', fn($e) => $e->where('company_id', $cid)))
            ->orderByDesc('scanned_at')->limit(15)->get();

        // Weekly chart
        $weekly = Attendance::where('scanned_at', '>=', now()->subDays(6)->startOfDay())
            ->where('type', 'in')->where('location_verified', true)
            ->when($cid, fn($q) => $q->whereHas('employee', fn($e) => $e->where('company_id', $cid)))
            ->select(DB::raw('DATE(scanned_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')->orderBy('date')
            ->pluck('total', 'date')->toArray();

        $weeklyLabels = [];
        $weeklyData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $date           = now()->subDays($i)->toDateString();
            $weeklyLabels[] = now()->subDays($i)->format('D d');
            $weeklyData[]   = $weekly[$date] ?? 0;
        }

        return view('dashboard.index', compact(
            'totalEmployees',
            'totalLocations',
            'checkedInToday',
            'rejectedToday',
            'locationStats',
            'currentlyInside',
            'recentActivity',
            'weeklyLabels',
            'weeklyData'
        ));
    }

    public function live()
    {
        $cid = $this->authCompanyId();
        return response()->json(
            Attendance::with(['employee', 'location'])
                ->when($cid, fn($q) => $q->whereHas('employee', fn($e) => $e->where('company_id', $cid)))
                ->orderByDesc('scanned_at')->limit(10)->get()
                ->map(fn($a) => [
                    'name'     => $a->employee->name,
                    'emp_id'   => $a->employee->employee_id,
                    'location' => $a->location->name,
                    'type'     => $a->type,
                    'verified' => $a->location_verified,
                    'distance' => $a->distance_meters,
                    'time'     => $a->scanned_at->format('H:i'),
                    'time_ago' => $a->scanned_at->diffForHumans(),
                ])
        );
    }

    public function exportCsv()
    {
        $today = now()->toDateString();
        $rows  = Attendance::with(['employee', 'location'])
            ->whereDate('scanned_at', $today)->orderBy('scanned_at')->get();

        return response()->stream(function () use ($rows) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Name', 'Employee ID', 'Department', 'Location', 'Type', 'Distance(m)', 'Verified', 'Time']);
            foreach ($rows as $r) {
                fputcsv($f, [
                    $r->employee->name,
                    $r->employee->employee_id,
                    $r->employee->department ?? '',
                    $r->location->name,
                    $r->type,
                    $r->distance_meters,
                    $r->location_verified ? 'Yes' : 'No',
                    $r->scanned_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($f);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance_' . $today . '.csv"',
        ]);
    }
}
