<?php

namespace App\Http\Controllers\Api\V2\Master;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\SaasProvisioner;
use App\Support\DisplayCurrency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'suspended', 'archived'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $companies = Company::query()
            ->withCount(['establishments', 'pointsOfSale', 'users'])
            ->when($data['search'] ?? null, fn ($query, string $search) => $query
                ->where(fn ($nested) => $nested
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('rccm', 'like', "%{$search}%")))
            ->when($data['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate($data['per_page'] ?? 20);

        return response()->json($companies);
    }

    public function store(Request $request, SaasProvisioner $provisioner): JsonResponse
    {
        $data = $request->validate($this->rules());
        $result = $provisioner->createCompany($data);

        return response()->json([
            'message' => 'Entreprise, établissement, point de vente et administrateur créés.',
            'data' => $result,
        ], 201);
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json(['data' => $company->load([
            'establishments.pointsOfSale.subscriptions',
            'users:id,company_id,name,email,is_company_admin,is_active',
        ])]);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:190'],
            'legal_name' => ['nullable', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'rccm' => ['nullable', 'string', 'max:100'],
            'nif' => ['nullable', 'string', 'max:100'],
            'tva_number' => ['nullable', 'string', 'max:100'],
            'exchange_rate' => ['sometimes', 'numeric', 'gt:0'],
            'status' => ['sometimes', Rule::in(['active', 'suspended', 'archived'])],
            'timezone' => ['sometimes', 'timezone'],
        ]);
        $company->update($data);

        return response()->json(['message' => 'Entreprise mise à jour.', 'data' => $company->refresh()]);
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'alpha_dash', 'max:190', 'unique:companies,slug'],
            'legal_name' => ['nullable', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'rccm' => ['nullable', 'string', 'max:100'],
            'nif' => ['nullable', 'string', 'max:100'],
            'tva_number' => ['nullable', 'string', 'max:100'],
            'base_currency' => ['nullable', Rule::in(DisplayCurrency::SUPPORTED)],
            'secondary_currency' => ['nullable', Rule::in(DisplayCurrency::SUPPORTED), 'different:base_currency'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'timezone' => ['nullable', 'timezone'],
            'establishment_name' => ['nullable', 'string', 'max:190'],
            'point_of_sale_name' => ['nullable', 'string', 'max:190'],
            'administrator_name' => ['required', 'string', 'max:190'],
            'administrator_email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'administrator_password' => ['required', 'string', 'min:12', 'confirmed'],
            'plan_code' => ['nullable', Rule::in(['starter', 'professional', 'enterprise'])],
            'subscription_days' => ['nullable', 'integer', 'between:1,3650'],
            'subscription_amount' => ['nullable', 'numeric', 'min:0'],
            'subscription_currency' => ['nullable', Rule::in(DisplayCurrency::SUPPORTED)],
            'billing_period' => ['nullable', Rule::in(['monthly', 'quarterly', 'semiannual', 'annual'])],
            'max_users' => ['nullable', 'integer', 'between:1,10000'],
        ];
    }
}
