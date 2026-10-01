<?php

namespace Tests\Feature;

use App\Models\AttendanceSheet;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Employee;
use App\Models\ManufacturingOrder;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\PosOrder;
use App\Models\Project;
use App\Models\SalesOrder;
use App\Models\Site;
use App\Models\User;
use App\Models\Voucher;
use App\Support\RouteKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cryptage des URL : aucun identifiant numérique n'apparaît dans les liens,
 * les jetons se résolvent et un jeton falsifié ou d'un autre modèle donne 404.
 */
class EncryptedRouteKeyTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function master(): User
    {
        return User::where('email', 'master@sogeref.cd')->firstOrFail();
    }

    /** @return array<string, array{0: string, 1: string, 2: class-string}> nom de route => [paramètre, modèle] */
    private function boundRoutes(): array
    {
        return [
            'orders.show' => ['order', SalesOrder::class], 'pdf.order' => ['order', SalesOrder::class],
            'contacts.show' => ['contact', Contact::class],
            'documents.show' => ['document', Document::class], 'documents.edit' => ['document', Document::class], 'pdf.document' => ['document', Document::class],
            'manufacturing.show' => ['mo', ManufacturingOrder::class], 'pdf.manufacturing' => ['mo', ManufacturingOrder::class],
            'sites.show' => ['site', Site::class], 'pdf.site' => ['site', Site::class],
            'projects.show' => ['project', Project::class],
            'employees.show' => ['employee', Employee::class],
            'payroll.show' => ['period', PayrollPeriod::class], 'pdf.payroll' => ['period', PayrollPeriod::class],
            'pdf.voucher' => ['voucher', Voucher::class], 'pdf.payslip' => ['payslip', Payslip::class],
            'pdf.attendance' => ['sheet', AttendanceSheet::class], 'pdf.ticket' => ['posOrder', PosOrder::class],
        ];
    }

    public function test_token_is_compact_stable_and_round_trips(): void
    {
        $token = RouteKey::encode(SalesOrder::class, 12);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{22}$/', $token);
        $this->assertSame($token, RouteKey::encode(SalesOrder::class, 12));
        $this->assertSame(12, RouteKey::decode(SalesOrder::class, $token));
        $this->assertNotSame($token, RouteKey::encode(SalesOrder::class, 13));
        $this->assertNotSame($token, RouteKey::encode(Document::class, 12));
        $this->assertNull(RouteKey::decode(Document::class, $token));
        $this->assertNull(RouteKey::decode(SalesOrder::class, '12'));
        $this->assertNull(RouteKey::decode(SalesOrder::class, null));
    }

    public function test_tampered_token_never_decodes(): void
    {
        $token = RouteKey::encode(Site::class, 4);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

        for ($i = 0; $i < strlen($token); $i++) {
            $char = $alphabet[(strpos($alphabet, $token[$i]) + 1) % 64];
            $this->assertNull(RouteKey::decode(Site::class, substr_replace($token, $char, $i, 1)), "Position {$i}");
        }
    }

    public function test_generated_urls_contain_no_numeric_id_and_resolve(): void
    {
        $this->actingAs($this->master());

        foreach ($this->boundRoutes() as $name => [$parameter, $class]) {
            $model = $name === 'documents.edit'
                ? Document::where('type', 'quote')->where('status', 'draft')->first()
                : $class::query()->first();
            if (! $model) {
                continue;
            }

            $url = route($name, [$parameter => $model]);
            $path = parse_url($url, PHP_URL_PATH);

            $this->assertDoesNotMatchRegularExpression('#/\d+(/|$)#', $path, "{$name} expose un identifiant : {$path}");
            $this->assertStringContainsString($model->getRouteKey(), $path);
            $this->assertSame($model->getKey(), $class::decodeRouteKey($model->getRouteKey()));
            $this->get($url)->assertOk();
        }
    }

    public function test_raw_tampered_and_foreign_tokens_return_404(): void
    {
        $this->actingAs($this->master());

        foreach ($this->boundRoutes() as $name => [$parameter, $class]) {
            $model = $class::query()->first();
            if (! $model) {
                continue;
            }
            $token = $model->getRouteKey();
            $tampered = substr_replace($token, $token[5] === 'A' ? 'B' : 'A', 5, 1);
            $foreign = RouteKey::encode($class === Site::class ? Project::class : Site::class, $model->getKey());

            $this->get(route($name, [$parameter => $model->getKey()]))->assertNotFound();
            $this->get(route($name, [$parameter => $tampered]))->assertNotFound();
            $this->get(route($name, [$parameter => $foreign]))->assertNotFound();
        }
    }

    public function test_rendered_pages_link_with_tokens_only(): void
    {
        $this->actingAs($this->master());

        foreach (['orders.index', 'contacts', 'quotes', 'employees', 'sites', 'projects', 'payroll', 'manufacturing', 'vouchers'] as $name) {
            $html = $this->get(route($name))->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression(
                '#/(commandes|contacts|documents|atelier|chantiers|projets|employes|paie|pdf/[a-z-]+)/\d+(["/?\#])#',
                $html,
                "La page {$name} contient un lien avec identifiant numérique."
            );
        }
    }

    public function test_query_string_filters_use_tokens(): void
    {
        $this->actingAs($this->master());
        $site = Site::first();
        $contact = Contact::first();

        $this->get(route('attendance', ['chantier' => $site]))->assertOk();
        $this->get(route('vouchers', ['chantier' => $site, 'nouveau' => 1]))->assertOk();
        $this->get(route('quotes.create', ['contact' => $contact]))->assertOk();
        $this->get(route('employees', ['modifier' => Employee::first()]))->assertOk();
        $this->get(route('attendance', ['chantier' => $site->id]))->assertOk(); // identifiant brut ignoré

        $this->assertStringNotContainsString('chantier='.$site->id, route('attendance', ['chantier' => $site]));
    }
}
