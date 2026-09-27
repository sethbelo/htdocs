<?php

namespace Tests\Feature;

use App\Enums\DesignJobStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PrintJobStatus;
use App\Enums\ProofValidatorType;
use App\Exceptions\BusinessRuleException;
use App\Models\Machine;
use App\Models\Order;
use App\Services\Cash\OrderCheckoutService;
use App\Services\Cash\PaymentData;
use App\Services\Design\DesignService;
use App\Services\Design\ProofService;
use App\Services\Production\PrintJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class DesignAndProductionTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->setUpErp();
    }

    private function paidOrder(): Order
    {
        $order = $this->createOrder(unitPrice: 50);
        $cashier = $this->userWithRole('caissier');
        app(OrderCheckoutService::class)->checkout($order, [new PaymentData(PaymentMethod::Cash, 50)], $this->openSession($cashier), $cashier);

        return $order->fresh();
    }

    public function test_full_design_proof_and_print_workflow(): void
    {
        $order = $this->paidOrder();
        $designer = $this->userWithRole('graphiste');
        $design = app(DesignService::class);
        $job = $order->designJob;

        $design->assign($job, $designer, $this->userWithRole('responsable-graphisme'));
        $design->start($job->fresh(), $designer);
        $v1 = $design->uploadVersion($job->fresh(), UploadedFile::fake()->image('maquette.png'), $designer, 'Première proposition');
        $v2 = $design->uploadVersion($job->fresh(), UploadedFile::fake()->image('maquette-v2.png'), $designer);
        $this->assertSame([1, 2], [$v1->version, $v2->version]);

        $proof = $design->generateProof($job->fresh(), $v2, $designer);
        $this->assertSame(OrderStatus::ProofPending, $order->fresh()->status);

        $salesRep = $order->salesRep;
        app(ProofService::class)->approve($proof, ProofValidatorType::Client, $salesRep, 'Jean Client', 'OK pour impression');

        $proof->refresh();
        $this->assertTrue($proof->is_locked);
        $this->assertNotNull($proof->locked_checksum);
        Storage::disk('local')->assertExists($proof->locked_path);
        $this->assertSame(OrderStatus::PrintQueue, $order->fresh()->status);
        $this->assertSame(DesignJobStatus::SentToPrint, $job->fresh()->status);

        $printJob = $order->fresh()->printJobs->first();
        $this->assertSame($proof->id, $printJob->proof_id);

        $operator = $this->userWithRole('imprimeur');
        $service = app(PrintJobService::class);
        $service->start($printJob, $operator, Machine::query()->create(['code' => 'M1', 'name' => 'Numérique', 'type' => 'digital_printer', 'status' => 'operational', 'site_id' => $this->headquarters->id]));
        $this->assertSame(OrderStatus::Printing, $order->fresh()->status);

        $service->advance($printJob->fresh(), $operator);
        $service->advance($printJob->fresh(), $operator);
        $service->advance($printJob->fresh(), $operator);
        $this->assertSame(PrintJobStatus::QualityCheck, $printJob->fresh()->status);
        $this->assertSame(OrderStatus::QualityCheck, $order->fresh()->status);

        $service->qualityCheck($printJob->fresh(), $operator, false, 'Couleurs ternes');
        $this->assertSame(OrderStatus::Rework, $order->fresh()->status);

        $service->start($printJob->fresh(), $operator, Machine::query()->firstOrFail());
        foreach (range(1, 3) as $step) {
            $service->advance($printJob->fresh(), $operator);
        }
        $service->qualityCheck($printJob->fresh(), $operator, true);

        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);
    }

    public function test_rejected_proof_returns_to_correction_and_locked_proof_cannot_be_reprocessed(): void
    {
        $order = $this->paidOrder();
        $designer = $this->userWithRole('graphiste');
        $design = app(DesignService::class);
        $job = $order->designJob;
        $design->start($job, $designer);
        $version = $design->uploadVersion($job->fresh(), UploadedFile::fake()->create('bat.pdf', 100, 'application/pdf'), $designer);
        $proof = $design->generateProof($job->fresh(), $version, $designer);

        app(ProofService::class)->reject($proof, $order->salesRep, 'Changer la couleur du logo');
        $this->assertSame(DesignJobStatus::Correction, $job->fresh()->status);
        $this->assertSame(OrderStatus::InDesign, $order->fresh()->status);

        $this->expectException(BusinessRuleException::class);
        app(ProofService::class)->approve($proof->fresh(), ProofValidatorType::Client, $order->salesRep, 'Client');
    }

    public function test_designer_must_give_reason_and_report_to_block(): void
    {
        $order = $this->paidOrder();

        $this->expectException(BusinessRuleException::class);
        app(DesignService::class)->block($order->designJob, $this->userWithRole('graphiste'), 'Fichier illisible', '');
    }
}
