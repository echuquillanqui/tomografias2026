<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
    @php
        $money = fn ($value) => number_format((float) $value, 2, '.', '');
        $text = fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    @endphp
    <Worksheet ss:Name="Resumen">
        <Table>
            <Row><Cell><Data ss:Type="String">Cuadre de caja</Data></Cell></Row>
            <Row><Cell><Data ss:Type="String">Periodo</Data></Cell><Cell><Data ss:Type="String">{{ $periods[$period] }}: {{ $from }} al {{ $to }}</Data></Cell></Row>
            <Row><Cell><Data ss:Type="String">Ingresos efectivo</Data></Cell><Cell><Data ss:Type="Number">{{ $money($cashIncome) }}</Data></Cell></Row>
            <Row><Cell><Data ss:Type="String">Egresos efectivo</Data></Cell><Cell><Data ss:Type="Number">{{ $money($expenseTotal) }}</Data></Cell></Row>
            <Row><Cell><Data ss:Type="String">Saldo efectivo</Data></Cell><Cell><Data ss:Type="Number">{{ $money($cashBalance) }}</Data></Cell></Row>
            <Row><Cell><Data ss:Type="String">Yape/Plin</Data></Cell><Cell><Data ss:Type="Number">{{ $money($yapePlinIncome) }}</Data></Cell></Row>
            <Row><Cell><Data ss:Type="String">Transferencias</Data></Cell><Cell><Data ss:Type="Number">{{ $money($transferIncome) }}</Data></Cell></Row>
            <Row><Cell><Data ss:Type="String">Total ingresos</Data></Cell><Cell><Data ss:Type="Number">{{ $money($incomeTotal) }}</Data></Cell></Row>
            <Row><Cell><Data ss:Type="String">Placas inicial</Data></Cell><Cell><Data ss:Type="Number">{{ $plateSummary['initial'] }}</Data></Cell></Row>
            <Row><Cell><Data ss:Type="String">Placas utilizadas</Data></Cell><Cell><Data ss:Type="Number">{{ $plateSummary['delivered'] }}</Data></Cell></Row>
            <Row><Cell><Data ss:Type="String">Placas final</Data></Cell><Cell><Data ss:Type="Number">{{ $plateSummary['final'] }}</Data></Cell></Row>
        </Table>
    </Worksheet>


    <Worksheet ss:Name="Cuadre diario">
        <Table>
            <Row><Cell><Data ss:Type="String">N°</Data></Cell><Cell><Data ss:Type="String">Fecha</Data></Cell><Cell><Data ss:Type="String">Paciente</Data></Cell><Cell><Data ss:Type="String">DNI</Data></Cell><Cell><Data ss:Type="String">Tipo de tomografía</Data></Cell><Cell><Data ss:Type="String">S/C C/C</Data></Cell><Cell><Data ss:Type="String">Total cobrado</Data></Cell><Cell><Data ss:Type="String">Yape</Data></Cell><Cell><Data ss:Type="String">Transferencia</Data></Cell><Cell><Data ss:Type="String">Por cobrar</Data></Cell><Cell><Data ss:Type="String">Placas usadas</Data></Cell><Cell><Data ss:Type="String">Saldo placas</Data></Cell><Cell><Data ss:Type="String">Médico solicitante</Data></Cell><Cell><Data ss:Type="String">Doctor informe</Data></Cell><Cell><Data ss:Type="String">Gasto</Data></Cell><Cell><Data ss:Type="String">Monto gasto</Data></Cell></Row>
            @php $plateRunning = $plateSummary['initial']; $maxRows = max($orders->count(), $expenses->count()); @endphp
            @for($i = 0; $i < $maxRows; $i++)
                @php $order = $orders->values()->get($i); $expense = $expenses->values()->get($i); $plates = $order ? (int) (($order->admissionForm?->data['delivery_quantities']['PLACAS'] ?? $order->admissionForm?->data['plates_count'] ?? 0)) : 0; $plateRunning -= $plates; @endphp
                <Row><Cell><Data ss:Type="Number">{{ $i + 1 }}</Data></Cell><Cell><Data ss:Type="String">{{ $order?->fecha_orden?->format('d/m/Y') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($order ? trim(($order->patient->nombres ?? '').' '.($order->patient->apellidos ?? '')) : '') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($order->patient->dni ?? '') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($order?->orderExams?->pluck('exam.nombre_examen')->filter()->join(' + ') ?? '') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($order?->orderExams?->pluck('tipo_contraste')->filter()->join(', ') ?? '') }}</Data></Cell><Cell><Data ss:Type="Number">{{ $order ? $money($order->total) : 0 }}</Data></Cell><Cell><Data ss:Type="Number">{{ $order?->tipo_pago === 'Yape/Plin' ? $money($order->total) : 0 }}</Data></Cell><Cell><Data ss:Type="Number">{{ $order?->tipo_pago === 'Transferencia' ? $money($order->total) : 0 }}</Data></Cell><Cell><Data ss:Type="Number">{{ $order?->tipo_pago === 'Convenio' ? $money($order->total) : 0 }}</Data></Cell><Cell><Data ss:Type="Number">{{ $plates }}</Data></Cell><Cell><Data ss:Type="Number">{{ $order ? $plateRunning : 0 }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($order->medicoSolicitante->nombre ?? '') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($order->medicoInforme->nombre_completo ?? 'SIN INFORME') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($expense->descripcion ?? '') }}</Data></Cell><Cell><Data ss:Type="Number">{{ $expense ? $money($expense->monto) : 0 }}</Data></Cell></Row>
            @endfor
        </Table>
    </Worksheet>

    @include('cash-closings.exports.partials.orders-sheet', ['sheetName' => 'Ingresos efectivo', 'sheetOrders' => $cashOrders])

    <Worksheet ss:Name="Egresos efectivo">
        <Table>
            <Row><Cell><Data ss:Type="String">Fecha</Data></Cell><Cell><Data ss:Type="String">Descripción</Data></Cell><Cell><Data ss:Type="String">Monto</Data></Cell><Cell><Data ss:Type="String">Usuario</Data></Cell></Row>
            @foreach($expenses as $expense)
                <Row><Cell><Data ss:Type="String">{{ $expense->fecha_egreso->format('d/m/Y') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($expense->descripcion) }}</Data></Cell><Cell><Data ss:Type="Number">{{ $money($expense->monto) }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($expense->creator->username ?? '—') }}</Data></Cell></Row>
            @endforeach
        </Table>
    </Worksheet>

    @include('cash-closings.exports.partials.orders-sheet', ['sheetName' => 'Yape Plin', 'sheetOrders' => $yapePlinOrders])
    @include('cash-closings.exports.partials.orders-sheet', ['sheetName' => 'Transferencias', 'sheetOrders' => $transferOrders])
</Workbook>
