<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'start' => ['nullable', 'date'], 'end' => ['nullable', 'date', 'after_or_equal:start'],
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'], 'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $appointments = Appointment::query()->with(['customer', 'employee', 'service'])
            ->when($data['start'] ?? null, fn (Builder $query, string $date) => $query->where('date_time', '>=', $date))
            ->when($data['end'] ?? null, fn (Builder $query, string $date) => $query->where('date_time', '<=', $date))
            ->when($data['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($data['employee_id'] ?? null, fn (Builder $query, int $id) => $query->where('employee_id', $id))
            ->orderBy('date_time')->cursorPaginate($data['per_page'] ?? 50);

        return AppointmentResource::collection($appointments);
    }

    public function store(Request $request)
    {
        $appointment = Appointment::create($this->validated($request));

        return (new AppointmentResource($appointment->load(['customer', 'employee', 'service'])))->response()->setStatusCode(201);
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        return new AppointmentResource($appointment->load(['customer', 'employee', 'service']));
    }

    public function update(Request $request, Appointment $appointment): AppointmentResource
    {
        $appointment->update($this->validated($request, true));

        return new AppointmentResource($appointment->refresh()->load(['customer', 'employee', 'service']));
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'customer_id' => [$required, 'integer', 'exists:customers,id'],
            'employee_id' => [$required, 'integer', 'exists:users,id'],
            'service_id' => [$required, 'integer', Rule::exists('products', 'id')->where(fn ($query) => $query->where('type', 'service')->where('is_active', true))],
            'date_time' => [$required, 'date'],
            'status' => [$required, Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
