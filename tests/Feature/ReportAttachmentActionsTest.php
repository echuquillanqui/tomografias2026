<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Order;
use App\Models\OrderReport;
use App\Models\OrderReportAttachment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportAttachmentActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_an_attachment_can_be_viewed_inline_and_renamed(): void
    {
        Storage::fake('local');
        [$user, $order, $attachment] = $this->records('reportes/1/scan.pdf', 'application/pdf', '%PDF-test');

        $this->actingAs($user)->get(route('reports.attachments.view', [$order, $attachment]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename=resultado.pdf');

        $this->actingAs($user)->put(route('reports.attachments.update', [$order, $attachment]), [
            'nombre' => 'resultado-final.pdf',
        ])->assertRedirect(route('reports.edit', $order));

        $this->assertDatabaseHas('order_report_attachments', [
            'id' => $attachment->id,
            'original_name' => 'resultado-final.pdf',
        ]);
        Storage::disk('local')->assertExists('reportes/1/scan.pdf');
    }

    public function test_attachment_actions_are_scoped_to_their_order(): void
    {
        Storage::fake('local');
        [$user, , $attachment] = $this->records('reportes/1/scan.pdf', 'application/pdf', '%PDF-test');

        $patient = Patient::create(['dni' => '87654321', 'nombres' => 'Otra', 'apellidos' => 'Persona']);
        $agreement = Agreement::first();
        $otherOrder = Order::create([
            'codigo_orden' => 'ORD-002',
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => now(),
        ]);

        $this->actingAs($user)->get(route('reports.attachments.view', [$otherOrder, $attachment]))->assertNotFound();
        $this->actingAs($user)->put(route('reports.attachments.update', [$otherOrder, $attachment]), [
            'nombre' => 'intruso.pdf',
        ])->assertNotFound();
    }

    /** @return array{User, Order, OrderReportAttachment} */
    private function records(string $path, string $mime, string $contents): array
    {
        $user = User::create([
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => 'password',
        ]);
        $patient = Patient::create(['dni' => '12345678', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $agreement = Agreement::create(['nombre_institucion' => 'Particular', 'activo' => true]);
        $order = Order::create([
            'codigo_orden' => 'ORD-001',
            'patient_id' => $patient->id,
            'agreement_id' => $agreement->id,
            'fecha_orden' => now(),
        ]);
        $report = OrderReport::create(['order_id' => $order->id, 'contenido' => 'Informe']);
        Storage::disk('local')->put($path, $contents);
        $attachment = $report->attachments()->create([
            'original_name' => 'resultado.pdf',
            'stored_name' => $path,
            'mime_type' => $mime,
            'original_size' => strlen($contents),
            'stored_size' => strlen($contents),
            'compressed' => false,
        ]);

        return [$user, $order, $attachment];
    }
}
