<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** Tables dont les lignes appartiennent à un point de vente. */
    private const TENANT_TABLES = [
        'products',
        'product_categories',
        'unit_of_measures',
        'payment_methods',
        'settings',
        'customers',
        'appointments',
        'sales',
        'sale_items',
        'sale_payments',
        'register_sessions',
        'stock_movements',
        'accounting_records',
        'invoices',
        'invoice_items',
        'invoice_payments',
        'credit_notes',
        'expenses',
        'purchases',
        'disbursement_requests',
        'equity_movements',
        'activity_logs',
        'subscriptions',
        'product_combo_items',
    ];

    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('rccm')->nullable();
            $table->string('nif')->nullable();
            $table->string('tva_number')->nullable();
            $table->string('base_currency', 3)->default('USD');
            $table->string('secondary_currency', 3)->default('CDF');
            $table->decimal('exchange_rate', 18, 6)->default(2800);
            $table->string('timezone')->default('Africa/Kinshasa');
            $table->string('status')->default('active')->index();
            $table->string('logo_path')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('establishments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('timezone')->default('Africa/Kinshasa');
            $table->boolean('is_active')->default(true)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('points_of_sale', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('establishment_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('default_currency', 3)->default('USD');
            $table->string('timezone')->default('Africa/Kinshasa');
            $table->boolean('is_active')->default(true)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['establishment_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('pos_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale')->cascadeOnDelete();
            $table->string('plan_code')->default('professional');
            $table->string('status')->default('trial')->index();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('grace_ends_at')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_period')->default('monthly');
            $table->unsignedInteger('max_users')->default(10);
            $table->json('features')->nullable();
            $table->string('external_reference')->nullable();
            $table->timestamps();
            $table->index(['point_of_sale_id', 'status', 'ends_at'], 'pos_subscription_active_idx');
        });

        Schema::create('point_of_sale_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('access_level')->default('operator');
            $table->timestamps();
            $table->unique(['point_of_sale_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('current_establishment_id')->nullable()->after('company_id')->constrained('establishments')->nullOnDelete();
            $table->foreignId('current_point_of_sale_id')->nullable()->after('current_establishment_id')->constrained('points_of_sale')->nullOnDelete();
            $table->boolean('is_master_admin')->default(false)->after('current_point_of_sale_id')->index();
            $table->boolean('is_company_admin')->default(false)->after('is_master_admin')->index();
            $table->boolean('is_active')->default(true)->after('is_company_admin')->index();
            $table->index(['company_id', 'is_active']);
        });

        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('point_of_sale_id')->nullable()->constrained('points_of_sale')->nullOnDelete();
            $table->index(['company_id', 'point_of_sale_id'], 'token_tenant_idx');
        });

        foreach (self::TENANT_TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('establishment_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('point_of_sale_id')->nullable()->constrained('points_of_sale')->nullOnDelete();
                $table->index(['company_id', 'point_of_sale_id'], $this->tenantIndexName($tableName));
            });
        }

        $this->convertCurrentInstallation();
        $this->replaceLegacyUniqueIndexes();
    }

    private function convertCurrentInstallation(): void
    {
        $setting = fn (string $key, mixed $default = null): mixed => DB::table('settings')->where('key', $key)->value('value') ?? $default;

        $companyId = DB::table('companies')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => $setting('company_name', 'Yeboox Entreprise'),
            'slug' => 'entreprise-principale',
            'legal_name' => $setting('company_name', 'Yeboox Entreprise'),
            'email' => $setting('company_email'),
            'phone' => $setting('company_phone'),
            'address' => $setting('company_address'),
            'rccm' => $setting('company_rccm'),
            'nif' => $setting('company_nif'),
            'tva_number' => $setting('company_tva'),
            'base_currency' => 'USD',
            'secondary_currency' => 'CDF',
            'exchange_rate' => max(1, (float) $setting('exchange_rate', 2800)),
            'timezone' => 'Africa/Kinshasa',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $establishmentId = DB::table('establishments')->insertGetId([
            'company_id' => $companyId,
            'uuid' => (string) Str::uuid(),
            'name' => 'Établissement principal',
            'code' => 'ETAB-001',
            'email' => $setting('company_email'),
            'phone' => $setting('company_phone'),
            'address' => $setting('company_address'),
            'timezone' => 'Africa/Kinshasa',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pointOfSaleId = DB::table('points_of_sale')->insertGetId([
            'company_id' => $companyId,
            'establishment_id' => $establishmentId,
            'uuid' => (string) Str::uuid(),
            'name' => 'Point de vente principal',
            'code' => 'PDV-001',
            'email' => $setting('company_email'),
            'phone' => $setting('company_phone'),
            'address' => $setting('company_address'),
            'default_currency' => 'USD',
            'timezone' => 'Africa/Kinshasa',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pos_subscriptions')->insert([
            'company_id' => $companyId,
            'point_of_sale_id' => $pointOfSaleId,
            'plan_code' => 'legacy-professional',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYears(10),
            'amount' => 0,
            'currency' => 'USD',
            'billing_period' => 'legacy',
            'max_users' => 100,
            'features' => json_encode(['all' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->update([
            'company_id' => $companyId,
            'current_establishment_id' => $establishmentId,
            'current_point_of_sale_id' => $pointOfSaleId,
        ]);

        $firstUserId = DB::table('users')->orderBy('id')->value('id');
        if ($firstUserId) {
            DB::table('users')->where('id', $firstUserId)->update([
                'is_master_admin' => true,
                'is_company_admin' => true,
            ]);
        }

        DB::table('personal_access_tokens')->update([
            'company_id' => $companyId,
            'point_of_sale_id' => $pointOfSaleId,
        ]);

        $memberships = DB::table('users')->pluck('id')->map(fn (int $userId) => [
            'point_of_sale_id' => $pointOfSaleId,
            'user_id' => $userId,
            'access_level' => $userId === $firstUserId ? 'administrator' : 'operator',
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();
        DB::table('point_of_sale_user')->insert($memberships);

        foreach (self::TENANT_TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            DB::table($tableName)->update([
                'company_id' => $companyId,
                'establishment_id' => $establishmentId,
                'point_of_sale_id' => $pointOfSaleId,
            ]);
        }
    }

    private function replaceLegacyUniqueIndexes(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropUnique('settings_key_unique');
            $table->unique(['company_id', 'point_of_sale_id', 'key'], 'settings_tenant_key_unique');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_sku_unique');
            $table->unique(['point_of_sale_id', 'sku'], 'products_pos_sku_unique');
        });
        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropUnique('product_categories_name_unique');
            $table->unique(['point_of_sale_id', 'name'], 'categories_pos_name_unique');
        });
        Schema::table('unit_of_measures', function (Blueprint $table): void {
            $table->dropUnique('unit_of_measures_name_unique');
            $table->unique(['point_of_sale_id', 'name'], 'units_pos_name_unique');
        });

        foreach ([
            'product_categories' => ['code', 'product_categories_code_unique', 'categories_pos_code_unique'],
            'unit_of_measures' => ['symbol', 'unit_of_measures_symbol_unique', 'units_pos_symbol_unique'],
            'payment_methods' => ['code', 'payment_methods_code_unique', 'payments_pos_code_unique'],
            'invoices' => ['number', 'invoices_number_unique', 'invoices_pos_number_unique'],
            'invoice_payments' => ['number', 'invoice_payments_number_unique', 'invoice_payments_pos_number_unique'],
            'credit_notes' => ['number', 'credit_notes_number_unique', 'credit_notes_pos_number_unique'],
            'expenses' => ['number', 'expenses_number_unique', 'expenses_pos_number_unique'],
            'purchases' => ['number', 'purchases_number_unique', 'purchases_pos_number_unique'],
            'disbursement_requests' => ['number', 'disbursement_requests_number_unique', 'disbursements_pos_number_unique'],
            'equity_movements' => ['number', 'equity_movements_number_unique', 'equity_pos_number_unique'],
            'sales' => ['client_reference', 'sales_client_reference_unique', 'sales_pos_client_reference_unique'],
        ] as $tableName => [$column, $legacyIndex, $tenantIndex]) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $legacyIndex, $tenantIndex): void {
                $table->dropUnique($legacyIndex);
                $table->unique(['point_of_sale_id', $column], $tenantIndex);
            });
        }
    }

    private function tenantIndexName(string $table): string
    {
        return 'tenant_'.substr(sha1($table), 0, 12).'_idx';
    }

    public function down(): void
    {
        Schema::table('unit_of_measures', function (Blueprint $table): void {
            $table->dropUnique('units_pos_name_unique');
            $table->unique('name');
        });
        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropUnique('categories_pos_name_unique');
            $table->unique('name');
        });

        foreach ([
            'product_categories' => ['code', 'categories_pos_code_unique'],
            'unit_of_measures' => ['symbol', 'units_pos_symbol_unique'],
            'payment_methods' => ['code', 'payments_pos_code_unique'],
            'invoices' => ['number', 'invoices_pos_number_unique'],
            'invoice_payments' => ['number', 'invoice_payments_pos_number_unique'],
            'credit_notes' => ['number', 'credit_notes_pos_number_unique'],
            'expenses' => ['number', 'expenses_pos_number_unique'],
            'purchases' => ['number', 'purchases_pos_number_unique'],
            'disbursement_requests' => ['number', 'disbursements_pos_number_unique'],
            'equity_movements' => ['number', 'equity_pos_number_unique'],
            'sales' => ['client_reference', 'sales_pos_client_reference_unique'],
        ] as $tableName => [$column, $tenantIndex]) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $tenantIndex): void {
                $table->dropUnique($tenantIndex);
                $table->unique($column);
            });
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_pos_sku_unique');
            $table->unique('sku');
        });
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropUnique('settings_tenant_key_unique');
            $table->unique('key');
        });

        foreach (array_reverse(self::TENANT_TABLES) as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex($this->tenantIndexName($tableName));
                $table->dropConstrainedForeignId('point_of_sale_id');
                $table->dropConstrainedForeignId('establishment_id');
                $table->dropConstrainedForeignId('company_id');
            });
        }

        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropIndex('token_tenant_idx');
            $table->dropConstrainedForeignId('point_of_sale_id');
            $table->dropConstrainedForeignId('company_id');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'is_active']);
            $table->dropIndex(['is_master_admin']);
            $table->dropIndex(['is_company_admin']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['is_master_admin', 'is_company_admin', 'is_active']);
            $table->dropConstrainedForeignId('current_point_of_sale_id');
            $table->dropConstrainedForeignId('current_establishment_id');
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::dropIfExists('pos_subscriptions');
        Schema::dropIfExists('point_of_sale_user');
        Schema::dropIfExists('points_of_sale');
        Schema::dropIfExists('establishments');
        Schema::dropIfExists('companies');
    }
};
