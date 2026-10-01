<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\PersonalNote;
use App\Models\PersonalSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lanceur d'applications et barre latérale contextuelle (une application = un service).
 */
class AppLauncherTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function as(string $login): User
    {
        $user = User::where('email', "{$login}@sogeref.cd")->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    public function test_login_page_shows_no_credentials(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertDontSee('Sogeref@2026')
            ->assertDontSee('master@sogeref.cd')
            ->assertDontSee('Comptes de démonstration');
    }

    public function test_login_redirects_to_launcher(): void
    {
        $this->post(route('login.store'), ['email' => 'master@sogeref.cd', 'password' => 'Sogeref@2026'])
            ->assertRedirect(route('launcher'));
    }

    public function test_launcher_lists_only_authorised_applications(): void
    {
        $this->as('caisse');
        $this->get(route('launcher'))->assertOk()
            ->assertSee('Finance')->assertSee('Caisse</span>', false)
            ->assertDontSee('Paie</span>', false)
            ->assertDontSee('Comptabilité</span>', false);

        $this->as('rh');
        $this->get(route('launcher'))->assertOk()
            ->assertSee('Ressources humaines')->assertSee('Paie</span>', false)
            ->assertDontSee('Point de vente</span>', false);
    }

    /** Liens de la barre latérale uniquement (hors sélecteur d'application). */
    private function sidebarLinks(string $url): array
    {
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('/<nav class="flex-1.*?<\/nav>/s', $html, $nav);
        preg_match_all('/class="sidebar-link"[^>]*|href="([^"]+)"/', $nav[0] ?? '', $matches);

        return array_values(array_filter($matches[1]));
    }

    public function test_sidebar_only_shows_the_selected_application(): void
    {
        $this->as('master');

        $this->get(route('accounting'))->assertSee('Menu · Finance');
        $finance = $this->sidebarLinks(route('accounting'));
        $this->assertContains(route('banking'), $finance);
        $this->assertContains(route('cash'), $finance);
        $this->assertNotContains(route('employees'), $finance);
        $this->assertNotContains(route('pos'), $finance);

        $hr = $this->sidebarLinks(route('employees'));
        $this->assertContains(route('payroll'), $hr);
        $this->assertNotContains(route('banking'), $hr);
    }

    public function test_pages_without_module_keep_the_last_application(): void
    {
        $this->as('master');
        $this->get(route('invoices'))->assertOk()->assertSee('Menu · Ventes');
        $this->get(route('documents.show', Document::first()))->assertOk()->assertSee('Menu · Ventes');
    }

    public function test_only_sogeref_branding_is_shown(): void
    {
        $this->get(route('login'))->assertSee('SOGEREF');
        $this->as('master');
        foreach ([route('launcher'), route('accounting'), route('personal'), asset('site.webmanifest')] as $url) {
            $html = $url === asset('site.webmanifest') ? file_get_contents(public_path('site.webmanifest')) : $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('SOGEREF', $html);
            $this->assertDoesNotMatchRegularExpression('/yodoox|yeboox|yuki|surnaturel/i', $html, $url);
        }
    }

    public function test_personal_notes_and_sheets_links_carry_no_raw_ids(): void
    {
        $user = $this->as('master');
        $note = PersonalNote::where('user_id', $user->id)->firstOrFail();
        $sheet = PersonalSheet::where('user_id', $user->id)->firstOrFail();

        foreach ([route('personal.notes.pdf', $note), route('personal.sheets.xlsx', $sheet), route('personal.notes', ['note' => $note->getRouteKey()])] as $url) {
            $this->assertDoesNotMatchRegularExpression('#/(notes|tableurs)/\d+(/|$)|[?&](note|classeur)=\d+(&|$)#', $url);
        }
        $this->get(route('personal.notes.print', $note))->assertOk();
        $this->get('/espace-personnel/notes/'.$note->id.'/imprimer')->assertNotFound();
        $this->get(route('personal.notes', ['note' => $note->getRouteKey()]))->assertOk()->assertSee($note->title);
        $this->get(route('personal'))->assertOk()->assertDontSee('?note='.$note->id.'"', false);
    }
}
