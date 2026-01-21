<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MonthlyRequestsService;
use App\Services\AirportRequestsService;
use App\Services\SpecialNeedsRequestsService;

class MonthlyRequestsController extends Controller
{
    public function employeeIndex(MonthlyRequestsService $service)
    {
        $pageTitle = 'المشاوير الموظفين';
        $rows = $service->listEmployee();
        return view('monthly.employee.index', compact('pageTitle', 'rows'));
    }

    public function employeeCreate()
    {
        $pageTitle = 'إضافة مشاوير الموظفين';
        return view('monthly.employee.create', compact('pageTitle'));
    }

    public function employeeStore(Request $request, MonthlyRequestsService $service)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'persons' => ['nullable', 'string'],
            'is_shift_work' => ['nullable'],
            'days_count' => ['nullable', 'string'],
            'home_address' => ['nullable', 'string'],
            'home_lat' => ['nullable', 'string'],
            'home_lng' => ['nullable', 'string'],
            'dest_address' => ['nullable', 'string'],
            'dest_lat' => ['nullable', 'string'],
            'dest_lng' => ['nullable', 'string'],
            'driver_arrival_time' => ['nullable', 'string'],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'shifts' => ['nullable', 'string'],
        ]);

        $payload = $validated;
        $payload['service_type'] = 'توصيل موظفين';
        $payload['status'] = 'pending';
        if (!empty($validated['shifts'])) {
            $payload['shifts'] = $this->decodeShifts($validated['shifts']);
        }

        $ok = $service->create($payload);
        if (!$ok) {
            return redirect()->back()->withErrors(['message' => 'تعذر الحفظ']);
        }

        return redirect()->route('monthly.employee.index')->withSuccess('تم الحفظ');
    }

    public function schoolsIndex(MonthlyRequestsService $service)
    {
        $pageTitle = 'المشاوير المدارس';
        $rows = $service->listSchools();
        return view('monthly.schools.index', compact('pageTitle', 'rows'));
    }

    public function schoolsCreate()
    {
        $pageTitle = 'إضافة مشاوير المدارس';
        return view('monthly.schools.create', compact('pageTitle'));
    }

    public function schoolsStore(Request $request, MonthlyRequestsService $service)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'persons' => ['nullable', 'string'],
            'is_shift_work' => ['nullable'],
            'days_count' => ['nullable', 'string'],
            'home_address' => ['nullable', 'string'],
            'home_lat' => ['nullable', 'string'],
            'home_lng' => ['nullable', 'string'],
            'dest_address' => ['nullable', 'string'],
            'dest_lat' => ['nullable', 'string'],
            'dest_lng' => ['nullable', 'string'],
            'driver_arrival_time' => ['nullable', 'string'],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'shifts' => ['nullable', 'string'],
        ]);

        $payload = $validated;
        $payload['service_type'] = 'توصيل مدارس';
        $payload['status'] = 'pending';
        if (!empty($validated['shifts'])) {
            $payload['shifts'] = $this->decodeShifts($validated['shifts']);
        }

        $ok = $service->create($payload);
        if (!$ok) {
            return redirect()->back()->withErrors(['message' => 'تعذر الحفظ']);
        }

        return redirect()->route('monthly.schools.index')->withSuccess('تم الحفظ');
    }

    public function airportsIndex(AirportRequestsService $service)
    {
        $pageTitle = 'المشاوير المطارات';
        $rows = $service->list();
        return view('monthly.airports.index', compact('pageTitle', 'rows'));
    }

    public function airportsCreate()
    {
        $pageTitle = 'إضافة مشاوير المطارات';
        return view('monthly.airports.create', compact('pageTitle'));
    }

    public function airportsStore(Request $request, AirportRequestsService $service)
    {
        $validated = $request->validate([
            'driver_time' => ['nullable', 'string'],
            'from_address' => ['nullable', 'string'],
            'from_lat' => ['nullable', 'string'],
            'from_lng' => ['nullable', 'string'],
            'to_address' => ['nullable', 'string'],
            'to_lat' => ['nullable', 'string'],
            'to_lng' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $payload = $validated;
        $payload['service_type'] = 'airport_transfer';
        $payload['status'] = $payload['status'] ?? 'pending';

        $ok = $service->create($payload);
        if (!$ok) {
            return redirect()->back()->withErrors(['message' => 'تعذر الحفظ']);
        }

        return redirect()->route('monthly.airports.index')->withSuccess('تم الحفظ');
    }

    public function specialNeedsIndex(SpecialNeedsRequestsService $service)
    {
        $pageTitle = 'ذو الاحتياجات الخاصة';
        $rows = $service->list();
        return view('monthly.special_needs.index', compact('pageTitle', 'rows'));
    }

    public function specialNeedsCreate()
    {
        $pageTitle = 'إضافة ذو الاحتياجات الخاصة';
        return view('monthly.special_needs.create', compact('pageTitle'));
    }

    public function specialNeedsStore(Request $request, SpecialNeedsRequestsService $service)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'from_address' => ['nullable', 'string'],
            'from_lat' => ['nullable', 'string'],
            'from_lng' => ['nullable', 'string'],
            'to_address' => ['nullable', 'string'],
            'to_lat' => ['nullable', 'string'],
            'to_lng' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'user_id' => ['nullable', 'string'],
        ]);

        $payload = $validated;
        $payload['service_type'] = 'special_needs_ride';
        $payload['status'] = $payload['status'] ?? 'pending';

        $ok = $service->create($payload);
        if (!$ok) {
            return redirect()->back()->withErrors(['message' => 'تعذر الحفظ']);
        }

        return redirect()->route('monthly.special_needs.index')->withSuccess('تم الحفظ');
    }

    private function decodeShifts(string $value): array
    {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return ['text' => $value];
    }
}
