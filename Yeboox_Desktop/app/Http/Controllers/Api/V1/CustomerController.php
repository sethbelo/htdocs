<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'updated_after' => ['nullable', 'date'], 'per_page' => ['nullable', 'integer', 'between:1,100']]);
        $customers = Customer::query()
            ->when($data['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($data['updated_after'] ?? null, fn (Builder $query, string $date) => $query->where('updated_at', '>', $date))
            ->orderBy('id')->cursorPaginate($data['per_page'] ?? 50);

        return CustomerResource::collection($customers);
    }

    public function store(Request $request)
    {
        $customer = Customer::create($this->validated($request));

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer);
    }

    public function update(Request $request, Customer $customer): CustomerResource
    {
        $customer->update($this->validated($request, $customer));

        return new CustomerResource($customer->refresh());
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('customers', 'phone')->ignore($customer)],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('customers', 'email')->ignore($customer)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
