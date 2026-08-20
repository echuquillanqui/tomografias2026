<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderReportAttachment;
use App\Models\User;
use App\Services\ReportAttachmentStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $allDates = $search !== '' && $request->boolean('all_dates');
        $date = (string) $request->query('date', now()->toDateString());
        if (! Carbon::hasFormat($date, 'Y-m-d')) {
            $date = now()->toDateString();
        }

        $orders = Order::with(['patient', 'agreement', 'medicoInforme', 'orderExams.exam', 'report.medicoFirmante', 'report.attachments'])
            ->when(! $allDates, fn ($query) => $query->whereDate('fecha_orden', $date))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('codigo_orden', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($patient) => $patient->where('dni', 'like', "%{$search}%")
                        ->orWhere('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%"));
            }))
            ->latest('fecha_orden')
            ->paginate(10)
            ->withQueryString();

        return view('reports.index', compact('orders', 'search', 'date', 'allDates'));
    }

    public function edit(Order $order): View
    {
        $this->ensureOrderRequiresReport($order);
        $order->load(['patient', 'agreement', 'medicoSolicitante', 'medicoInforme', 'admissionForm', 'orderExams.exam', 'report.attachments']);
        $this->ensureReport($order);

        return view('reports.edit', [
            'order' => $order->fresh(['patient', 'agreement', 'medicoSolicitante', 'medicoInforme', 'orderExams.exam', 'report.attachments']),
            'medicosInformantes' => $this->medicosInformantes(),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->ensureOrderRequiresReport($order);
        $data = $request->validate([
            'medico_firmante_id' => ['nullable', 'exists:users,id'],
            'adjuntos' => ['nullable', 'array'],
            'adjuntos.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'],
        ]);

        $order->update(['medico_informe_id' => $data['medico_firmante_id']]);
        $admissionData = $order->admissionForm?->data ?? [];
        $admissionData['informed_by'] = User::find($data['medico_firmante_id'])?->nombre_completo ?? '';
        $order->admissionForm()->updateOrCreate([], ['data' => $admissionData]);
        $report = $order->report()->updateOrCreate(
            ['order_id' => $order->id],
            collect($data)->except('adjuntos')->all()
        );

        $storage = app(ReportAttachmentStorage::class);
        foreach ($request->file('adjuntos', []) as $file) {
            $storage->store($report, $file);
        }

        if ($data['medico_firmante_id'] && $report->attachments()->exists()) {
            $order->update(['estado' => 'Informado']);
        }

        return redirect()->route('reports.edit', $order)->with('success', 'Informe actualizado correctamente.');
    }

    public function pdf(Order $order)
    {
        $this->ensureOrderRequiresReport($order);
        $order->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam', 'report.medicoFirmante']);
        $this->ensureReport($order);
        $order->load('report.medicoFirmante');

        return Pdf::loadView('reports.pdf', ['order' => $order, 'setting' => \App\Models\SystemSetting::current()])->stream('informe-orden-'.$order->id.'.pdf');
    }

    public function downloadAttachment(Order $order, OrderReportAttachment $attachment)
    {
        $this->ensureOrderRequiresReport($order);
        abort_unless($attachment->report()->where('order_id', $order->id)->exists(), 404);
        abort_unless(Storage::disk('local')->exists($attachment->stored_name), 404);

        $path = Storage::disk('local')->path($attachment->stored_name);

        if (str_ends_with($attachment->stored_name, '.gz')) {
            return response()->streamDownload(function () use ($path) {
                $handle = gzopen($path, 'rb');
                while ($handle !== false && ! gzeof($handle)) {
                    echo gzread($handle, 8192);
                }
                if ($handle !== false) {
                    gzclose($handle);
                }
            }, $attachment->original_name, ['Content-Type' => $attachment->mime_type]);
        }

        return response()->download($path, $attachment->original_name, ['Content-Type' => $attachment->mime_type]);
    }

    public function viewAttachment(Order $order, OrderReportAttachment $attachment)
    {
        $this->ensureOrderRequiresReport($order);
        $this->authorizeAttachment($order, $attachment);
        abort_unless(Storage::disk('local')->exists($attachment->stored_name), 404);

        $path = Storage::disk('local')->path($attachment->stored_name);
        $headers = [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $attachment->original_name,
                $this->asciiFilename($attachment->original_name)
            ),
        ];

        if (str_ends_with($attachment->stored_name, '.gz')) {
            return response()->stream(function () use ($path) {
                $handle = gzopen($path, 'rb');
                while ($handle !== false && ! gzeof($handle)) {
                    echo gzread($handle, 8192);
                }
                if ($handle !== false) {
                    gzclose($handle);
                }
            }, 200, $headers);
        }

        return response()->file($path, $headers);
    }

    public function updateAttachment(Request $request, Order $order, OrderReportAttachment $attachment): RedirectResponse
    {
        $this->ensureOrderRequiresReport($order);
        $this->authorizeAttachment($order, $attachment);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'archivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'],
        ]);

        if ($request->hasFile('archivo')) {
            app(ReportAttachmentStorage::class)->replace($attachment, $request->file('archivo'));
        }

        $attachment->update(['original_name' => basename(str_replace('\\', '/', trim($data['nombre'])))]);

        return redirect()->route('reports.edit', $order)->with('success', 'Archivo adjunto actualizado correctamente.');
    }

    public function destroyAttachment(Order $order, OrderReportAttachment $attachment): RedirectResponse
    {
        $this->ensureOrderRequiresReport($order);
        $this->authorizeAttachment($order, $attachment);
        Storage::disk('local')->delete($attachment->stored_name);
        $attachment->delete();

        return redirect()->route('reports.edit', $order)->with('success', 'Archivo adjunto eliminado correctamente.');
    }

    private function authorizeAttachment(Order $order, OrderReportAttachment $attachment): void
    {
        abort_unless($attachment->report()->where('order_id', $order->id)->exists(), 404);
    }

    private function asciiFilename(string $filename): string
    {
        $fallback = Str::ascii($filename);
        $fallback = preg_replace('/[^A-Za-z0-9._ -]/', '_', $fallback) ?? '';

        return $fallback !== '' ? $fallback : 'archivo';
    }

    private function composeReportContent(array $data): string
    {
        $sections = [
            '### **TÉCNICA**' => $data['tecnica'],
            '### **INFORME**' => $data['informe'],
            '### **IMPRESIÓN DIAGNÓSTICA**' => $data['impresion'],
        ];

        if (! empty($data['recomendaciones'])) {
            $sections['### **RECOMENDACIONES / NOTAS**'] = $data['recomendaciones'];
        }

        return collect($sections)
            ->map(fn ($content, $heading) => $heading."\n\n".trim($content))
            ->implode("\n\n---\n\n");
    }

    private function ensureReport(Order $order): void
    {
        if ($order->report) {
            return;
        }

        app(OrderController::class)->createInitialReport($order);
    }

    private function ensureOrderRequiresReport(Order $order): void
    {
        abort_if($order->tipo_informe === 'SIN INFORME', 404);
    }

    private function medicosInformantes()
    {
        return User::select(['id', 'nombre_completo'])
            ->where('rol', 'Médico')
            ->where('activo', true)
            ->whereIn('tipo_medico', ['De Informe', 'Ambos'])
            ->orderBy('nombre_completo')
            ->get();
    }
}
