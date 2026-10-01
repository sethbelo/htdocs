<?php

namespace Tests\Feature;

use App\Livewire\Personal as Screens;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PersonalNote;
use App\Models\PersonalSheet;
use App\Models\PersonalTask;
use App\Models\User;
use App\Models\Voucher;
use App\Support\Modules;
use App\Support\SheetFormula;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Espace personnel : accès pour tous les rôles, isolation des données
 * par propriétaire, congés, bons, notes assainies et formules de tableur.
 */
class PersonalSpaceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function as(string $login): User
    {
        $user = User::where('email', "{$login}@sogeref.cd")->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    private function routes(): array
    {
        return ['personal', 'personal.notes', 'personal.sheets', 'personal.vouchers', 'personal.leaves', 'personal.tasks'];
    }

    public function test_every_screen_renders_for_master_and_a_basic_role(): void
    {
        foreach (['master', 'accueil', 'magasin'] as $login) {
            $user = $this->as($login);
            $this->assertTrue($user->canAccessModule('personal'), "{$login} doit accéder à l'Espace personnel");
            foreach ($this->routes() as $route) {
                $this->get(route($route))->assertOk();
            }
            $this->get(route('personal.tasks.print'))->assertOk();
        }
    }

    public function test_module_is_registered_first_with_its_menu(): void
    {
        $this->assertSame('personnel', array_key_first(config('erp.groups')));
        $module = config('erp.modules.personal');
        $this->assertTrue($module['core']);
        $this->assertEqualsCanonicalizing($this->routes(), collect($module['menu'])->pluck('route')->all());
        foreach ($module['menu'] as $item) {
            $this->assertNotEmpty(route($item['route']));
        }
        $this->assertSame('personal', Modules::navigation(User::where('email', 'accueil@sogeref.cd')->first())->flatten(1)->first()['key']);
    }

    public function test_demo_items_are_seeded_for_master(): void
    {
        $master = User::where('email', 'master@sogeref.cd')->first();
        $this->assertGreaterThanOrEqual(2, PersonalNote::where('user_id', $master->id)->count());
        $sheet = PersonalSheet::where('user_id', $master->id)->firstOrFail();
        $this->assertEquals(1580.0, $sheet->values['D7']); // 840 + 340 + 280 + 120
        $this->assertGreaterThan(0, PersonalTask::where('user_id', $master->id)->whereDate('task_date', today())->count());
    }

    public function test_personal_items_are_private_to_their_owner(): void
    {
        $master = User::where('email', 'master@sogeref.cd')->first();
        $note = PersonalNote::where('user_id', $master->id)->firstOrFail();
        $sheet = PersonalSheet::where('user_id', $master->id)->firstOrFail();
        $task = PersonalTask::where('user_id', $master->id)->firstOrFail();

        $this->as('accueil');
        $this->assertSame(0, PersonalNote::mine()->count());
        $this->get(route('personal.notes', ['note' => $note->id]))->assertOk()->assertDontSee($note->title);
        $this->get(route('personal.notes.pdf', $note))->assertNotFound();
        $this->get(route('personal.notes.print', $note))->assertNotFound();
        $this->get(route('personal.sheets.print', $sheet))->assertNotFound();
        $this->get(route('personal.sheets.xlsx', $sheet))->assertNotFound();

        $this->expectsModelNotFound(fn () => Livewire::test(Screens\Notes::class)->call('saveNote', $note->id, 'Piratage', '<p>x</p>'));
        $this->expectsModelNotFound(fn () => Livewire::test(Screens\Sheets::class)->call('delete', $sheet->id));
        $this->expectsModelNotFound(fn () => Livewire::test(Screens\Tasks::class)->call('toggle', $task->id));

        $this->assertSame($note->title, $note->fresh()->title);
        $this->assertNotNull($sheet->fresh());
        $this->assertSame($task->is_done, $task->fresh()->is_done);
        $this->assertFalse(auth()->user()->can('view', $note));
        $this->actingAs($master);
        $this->assertTrue($master->can('update', $sheet->fresh()), 'Le propriétaire garde ses droits');
    }

    private function expectsModelNotFound(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Accès attendu refusé (404).');
        } catch (ModelNotFoundException|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_note_html_is_sanitized_and_exports_work(): void
    {
        $this->as('accueil');
        $component = Livewire::test(Screens\Notes::class)->call('create');
        $note = PersonalNote::mine()->latest('id')->firstOrFail();

        $component->call('saveNote', $note->id, 'Compte rendu', '<h1 onclick="alert(1)">Titre</h1><script>alert("x")</script><p style="color:red" onmouseover="steal()">Texte <b>gras</b> <a href="javascript:alert(1)">lien</a> <a href="https://sogeref.cd">site</a></p><ul data-type="checklist"><li data-checked="true" onclick="x()">Fait</li></ul><img src="x" onerror="alert(1)"><iframe src="https://evil.test"></iframe>')
            ->assertHasNoErrors();

        $html = $note->fresh()->content;
        $this->assertSame('Compte rendu', $note->fresh()->title);
        $this->assertStringNotContainsStringIgnoringCase('<script', $html);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $html);
        $this->assertStringNotContainsStringIgnoringCase('onmouseover', $html);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $html);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $html);
        $this->assertStringNotContainsStringIgnoringCase('<iframe', $html);
        $this->assertStringNotContainsStringIgnoringCase('style=', $html);
        $this->assertStringContainsString('<h1>Titre</h1>', $html);
        $this->assertStringContainsString('<strong>gras</strong>', $html);
        $this->assertStringContainsString('href="https://sogeref.cd"', $html);
        $this->assertStringContainsString('<ul data-type="checklist"><li data-checked="true">Fait</li></ul>', $html);

        $component->call('togglePin', $note->id)->call('duplicate', $note->id);
        $this->assertTrue($note->fresh()->is_pinned);
        $this->assertSame(2, PersonalNote::mine()->count());

        $this->get(route('personal.notes.print', $note))->assertOk()->assertSee('Compte rendu');
        $this->get(route('personal.notes.pdf', $note))->assertOk()->assertHeader('content-type', 'application/pdf');

        $component->call('delete', $note->id);
        $this->assertNull($note->fresh());
    }

    public function test_sheet_formulas_are_evaluated_on_save_and_exported(): void
    {
        $this->as('magasin');
        $component = Livewire::test(Screens\Sheets::class)->call('create');
        $sheet = PersonalSheet::mine()->latest('id')->firstOrFail();

        $component->call('saveSheet', $sheet->id, 'Inventaire', 10, 4, [
            'A1' => '10', 'A2' => '20', 'A3' => '30,5', 'A4' => '=SUM(A1:A3)',
            'B1' => '=AVERAGE(A1:A3)', 'B2' => '=MIN(A1:A3)', 'B3' => '=MAX(A1:A3)', 'B4' => '=(A1+A2)*2-A3/2',
            'C1' => '=A1/0', 'C2' => '=C3', 'C3' => '=C2', 'C4' => 'Texte libre',
            'Z99' => 'hors grille',
        ])->assertHasNoErrors();

        $sheet->refresh();
        $this->assertSame('Inventaire', $sheet->title);
        $this->assertEquals(60.5, $sheet->values['A4']);
        $this->assertEqualsWithDelta(20.1666666667, $sheet->values['B1'], 1e-6);
        $this->assertEquals(10, $sheet->values['B2']);
        $this->assertEquals(30.5, $sheet->values['B3']);
        $this->assertEquals(44.75, $sheet->values['B4']);
        $this->assertSame('#DIV/0!', $sheet->values['C1']);
        $this->assertSame('#REF!', $sheet->values['C2']);
        $this->assertArrayNotHasKey('Z99', $sheet->cells, 'Les cellules hors grille sont ignorées');
        $this->assertSame('=SUM(A1:A3)', $sheet->cells['A4'], 'La formule brute est conservée');
        $this->assertSame('60.5', $sheet->display('A4'));
        $this->assertSame('#ERR!', SheetFormula::evaluate('=SUM(A1:'));

        $this->get(route('personal.sheets.print', $sheet))->assertOk()->assertSee('Inventaire');
        $this->get(route('personal.sheets.pdf', $sheet))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('personal.sheets.xlsx', $sheet))->assertOk()->assertDownload('inventaire.xlsx');
    }

    public function test_employee_requests_and_cancels_a_leave(): void
    {
        $user = $this->as('accueil');
        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        $type = LeaveType::where('code', 'CA')->firstOrFail();
        $from = now()->addDays(30)->next('Monday');

        Livewire::test(Screens\Leaves::class)
            ->call('create')
            ->set('leaveTypeId', $type->id)
            ->set('from', $from->toDateString())
            ->set('to', $from->copy()->addDays(5)->toDateString())
            ->set('reason', 'Congé familial')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $leave = LeaveRequest::where('employee_id', $employee->id)->where('reason', 'Congé familial')->firstOrFail();
        $this->assertSame('pending', $leave->status);
        $this->assertEquals(6, (float) $leave->days); // lundi → samedi : 6 jours ouvrables (dimanche exclu)
        $this->assertSame($user->id, $leave->requested_by);

        Livewire::test(Screens\Leaves::class)->call('cancel', $leave->id);
        $this->assertSame('cancelled', $leave->fresh()->status);

        // Dates passées refusées
        Livewire::test(Screens\Leaves::class)->call('create')->set('from', now()->subDays(3)->toDateString())->set('to', now()->toDateString())->call('save')->assertHasErrors('from');
    }

    public function test_user_without_employee_record_is_handled_gracefully(): void
    {
        $this->as('master');
        $this->get(route('personal.leaves'))->assertOk()->assertSee('Aucune fiche employé liée à votre compte');
        Livewire::test(Screens\Leaves::class)->call('create')->assertForbidden();
    }

    public function test_employee_issues_a_voucher_and_submits_it_for_signature(): void
    {
        $user = $this->as('accueil');
        Livewire::test(Screens\Vouchers::class)
            ->call('create', 'decaissement')
            ->set('purpose', 'Achat de fournitures pour l’accueil')
            ->set('lines.0.description', 'Ramettes de papier A4')
            ->set('lines.0.quantity', 5)
            ->set('lines.0.unit_price', 6.5)
            ->call('saveAndSubmit')
            ->assertHasNoErrors();

        $voucher = Voucher::where('requested_by', $user->id)->latest('id')->firstOrFail();
        $this->assertSame('submitted', $voucher->status);
        $this->assertSame('decaissement', $voucher->type);
        $this->assertStringStartsWith('BD', $voucher->number);
        $this->assertEquals(32.5, (float) $voucher->amount);
        $this->assertSame(Employee::where('user_id', $user->id)->value('id'), $voucher->employee_id);
        $this->get(route('personal.vouchers.pdf', $voucher))->assertOk()->assertHeader('content-type', 'application/pdf');

        // Un autre employé ne voit ni n'imprime ce bon.
        $this->as('magasin');
        $this->get(route('personal.vouchers.pdf', $voucher))->assertNotFound();
        $this->expectsModelNotFound(fn () => Livewire::test(Screens\Vouchers::class)->call('cancel', $voucher->id));

        // La direction le voit en attente de signature sur sa journée.
        $this->as('direction');
        $this->get(route('personal'))->assertOk()->assertSee($voucher->number);
    }

    public function test_daily_tasks_add_toggle_reorder_and_carry_over(): void
    {
        $this->as('accueil');
        $screen = Livewire::test(Screens\Tasks::class)
            ->set('title', 'Accueillir le client de 10 h')->set('priority', 'haute')->set('startTime', '10:00')->set('endTime', '10:30')->call('add')
            ->set('title', 'Classer le courrier')->call('add')
            ->assertHasNoErrors();
        [$first, $second] = PersonalTask::mine()->whereDate('task_date', today())->orderBy('position')->get()->all();
        $this->assertSame('10:00 – 10:30', $first->timeSlot());

        $screen->call('toggle', $first->id)->call('reorder', [$second->id, $first->id]);
        $this->assertTrue($first->fresh()->is_done);
        $this->assertSame(1, $second->fresh()->position);

        $late = PersonalTask::create(['company_id' => company_id(), 'user_id' => auth()->id(), 'task_date' => today()->subDays(2), 'title' => 'Tâche oubliée']);
        $screen->call('carryOver');
        $this->assertTrue($late->fresh()->task_date->isToday());
        $this->assertTrue($late->fresh()->carried_from->isSameDay(today()->subDays(2)));

        $this->get(route('personal.tasks.print', ['jour' => today()->toDateString()]))->assertOk()->assertSee('Classer le courrier')->assertSee('Tâche oubliée');
        $screen->call('delete', $second->id);
        $this->assertNull($second->fresh());
    }
}
