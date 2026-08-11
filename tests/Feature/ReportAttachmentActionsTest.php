<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Order;
use App\Models\OrderReport;
use App\Models\OrderReportAttachment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_an_attachment_with_a_non_ascii_name_can_be_viewed_inline(): void
    {
        Storage::fake('local');
        [$user, $order, $attachment] = $this->records('reportes/1/scan.pdf', 'application/pdf', '%PDF-test');
        $attachment->update(['original_name' => 'TOMOGRAFÍA CEREBRAL.pdf']);

        $this->actingAs($user)->get(route('reports.attachments.view', [$order, $attachment]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="TOMOGRAFIA CEREBRAL.pdf"; filename*=utf-8\'\'TOMOGRAF%C3%8DA%20CEREBRAL.pdf');
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

    public function test_report_becomes_informed_when_it_has_a_doctor_and_attachment(): void
    {
        Storage::fake('local');
        [$user, $order] = $this->records('reportes/1/scan.pdf', 'application/pdf', '%PDF-test');
        $order->report->attachments()->delete();
        Storage::disk('local')->delete('reportes/1/scan.pdf');

        $doctor = User::create([
            'username' => 'doctor',
            'email' => 'doctor@example.com',
            'password' => 'password',
            'nombre_completo' => 'Dra. María Pérez',
            'rol' => 'Médico',
            'tipo_medico' => 'De Informe',
            'activo' => true,
        ]);

        $this->actingAs($user)->put(route('reports.update', $order), [
            'medico_firmante_id' => $doctor->id,
            'adjuntos' => [UploadedFile::fake()->create('resultado.pdf', 10, 'application/pdf')],
        ])->assertRedirect(route('reports.edit', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'medico_informe_id' => $doctor->id,
            'estado' => 'Informado',
        ]);
        $this->assertDatabaseHas('order_report_attachments', [
            'order_report_id' => $order->report->id,
            'original_name' => 'resultado.pdf',
        ]);
    }

    public function test_report_index_hides_uploaded_files_column_and_highlights_pdf_button(): void
    {
        Storage::fake('local');
        [$user, $order] = $this->records('reportes/1/scan.pdf', 'application/pdf', '%PDF-test');

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('resultado.pdf')
            ->assertDontSee('<th>Archivos subidos</th>', false)
            ->assertSee(route('reports.attachments.view', [$order, $order->report->attachments->first()]), false)
            ->assertSee('btn-success', false);
    }

    public function test_report_index_pdf_button_opens_attachment_modal_with_inline_preview(): void
    {
        Storage::fake('local');
        [$user, $order] = $this->records('reportes/1/scan.pdf', 'application/pdf', '%PDF-test');
        $attachment = $order->report->attachments->first();
        $previewUrl = route('reports.attachments.view', [$order, $attachment]);

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('data-bs-target="#reportFilesModal'.$order->id.'"', false)
            ->assertSee('Archivos del informe')
            ->assertSee('Adjuntos (1)')
            ->assertSee('data-preview-url="'.$previewUrl.'"', false)
            ->assertSee('class="report-file-preview-frame', false);
    }

    public function test_doctor_select_only_displays_the_doctor_name(): void
    {
        Storage::fake('local');
        [$user, $order] = $this->records('reportes/1/scan.pdf', 'application/pdf', '%PDF-test');
        User::create([
            'username' => 'doctor',
            'email' => 'doctor@example.com',
            'password' => 'password',
            'nombre_completo' => 'Dr. Juan Torres',
            'rol' => 'Médico',
            'tipo_medico' => 'De Informe',
            'firma_path' => 'firmas/doctor.png',
            'activo' => true,
        ]);

        $this->actingAs($user)->get(route('reports.edit', $order))
            ->assertOk()
            ->assertSee('Dr. Juan Torres')
            ->assertDontSee('con firma')
            ->assertDontSee('sin firma');
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
