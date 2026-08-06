<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
    @php
        $money = fn ($value) => number_format((float) $value, 2, '.', '');
        $text = fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    @endphp
    <Styles>
        <Style ss:ID="Default" ss:Name="Normal">
            <Alignment ss:Vertical="Center" ss:WrapText="1"/>
            <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders>
            <Font ss:FontName="Calibri" ss:Size="10" ss:Color="#1E293B"/>
        </Style>
        <Style ss:ID="Title"><Alignment ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="18" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0F766E" ss:Pattern="Solid"/></Style>
        <Style ss:ID="Header"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0F766E" ss:Pattern="Solid"/></Style>
        <Style ss:ID="Label"><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#0F172A"/><Interior ss:Color="#CCFBF1" ss:Pattern="Solid"/></Style>
        <Style ss:ID="Money"><NumberFormat ss:Format="&quot;S/ &quot;#,##0.00"/></Style>
        <Style ss:ID="Integer"><Alignment ss:Horizontal="Center"/><NumberFormat ss:Format="0"/></Style>
        <Style ss:ID="Centered"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/></Style>
    </Styles>
    <Worksheet ss:Name="Resumen">
        <Table>
            <Column ss:Width="150"/><Column ss:Width="180"/>
            <Row ss:Height="30"><Cell ss:StyleID="Title" ss:MergeAcross="1"><Data ss:Type="String">REPORTE DE ATENCIONES - CUADRE DE CAJA</Data></Cell></Row>
            <Row><Cell ss:StyleID="Label"><Data ss:Type="String">Periodo</Data></Cell><Cell><Data ss:Type="String">{{ $periods[$period] }}: {{ $from }} al {{ $to }}</Data></Cell></Row>
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
            <Column ss:Width="35"/><Column ss:Width="70"/><Column ss:Width="150"/><Column ss:Width="75"/><Column ss:Width="170"/><Column ss:Width="65"/><Column ss:Width="125"/><Column ss:Width="75" ss:Span="3"/><Column ss:Width="75" ss:Span="1"/><Column ss:Width="130" ss:Span="1"/><Column ss:Width="140" ss:Span="1"/><Column ss:Width="130"/><Column ss:Width="85"/>
            <Row ss:Height="30"><Cell ss:StyleID="Title" ss:MergeAcross="16"><Data ss:Type="String">DETALLE DE ATENCIONES</Data></Cell></Row>
            <Row ss:Height="32"><Cell ss:StyleID="Header"><Data ss:Type="String">N°</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Fecha</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Paciente</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">DNI</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Tipo de tomografía</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">S/C C/C</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Convenio / Institución</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Total cobrado</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Yape</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Transferencia</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Por cobrar</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Placas usadas</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Saldo placas</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Médico solicitante</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Doctor informe</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Gasto</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Monto gasto</Data></Cell></Row>
            @php $plateRunning = $plateSummary['initial']; $maxRows = max($orders->count(), $expenses->count()); @endphp
            @for($i = 0; $i < $maxRows; $i++)
                @php $order = $orders->values()->get($i); $expense = $expenses->values()->get($i); $plates = $order ? (int) (($order->admissionForm?->data['delivery_quantities']['PLACAS'] ?? $order->admissionForm?->data['plates_count'] ?? 0)) : 0; $plateRunning -= $plates; @endphp
                <Row><Cell ss:StyleID="Integer"><Data ss:Type="Number">{{ $i + 1 }}</Data></Cell><Cell ss:StyleID="Centered"><Data ss:Type="String">{{ $order?->fecha_orden?->format('d/m/Y') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($order ? trim(($order->patient->nombres ?? '').' '.($order->patient->apellidos ?? '')) : '') }}</Data></Cell><Cell ss:StyleID="Centered"><Data ss:Type="String">{{ $text($order->patient->dni ?? '') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($order?->orderExams?->pluck('exam.nombre_examen')->filter()->join(' + ') ?? '') }}</Data></Cell><Cell ss:StyleID="Centered"><Data ss:Type="String">{{ $text($order?->orderExams?->pluck('tipo_contraste')->filter()->join(', ') ?? '') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($order?->agreement?->nombre_institucion ?? '') }}</Data></Cell><Cell ss:StyleID="Money"><Data ss:Type="Number">{{ $order ? $money($order->total) : 0 }}</Data></Cell><Cell ss:StyleID="Money"><Data ss:Type="Number">{{ $order?->tipo_pago === 'Yape/Plin' ? $money($order->total) : 0 }}</Data></Cell><Cell ss:StyleID="Money"><Data ss:Type="Number">{{ $order?->tipo_pago === 'Transferencia' ? $money($order->total) : 0 }}</Data></Cell><Cell ss:StyleID="Money"><Data ss:Type="Number">{{ $order?->tipo_pago === 'Convenio' ? $money($order->total) : 0 }}</Data></Cell><Cell ss:StyleID="Integer"><Data ss:Type="Number">{{ $plates }}</Data></Cell><Cell ss:StyleID="Integer"><Data ss:Type="Number">{{ $order ? $plateRunning : 0 }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($order->medicoSolicitante->nombre ?? '') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($order->medicoInforme->nombre_completo ?? 'SIN INFORME') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($expense->descripcion ?? '') }}</Data></Cell><Cell ss:StyleID="Money"><Data ss:Type="Number">{{ $expense ? $money($expense->monto) : 0 }}</Data></Cell></Row>
            @endfor
        </Table>
    </Worksheet>

    @include('cash-closings.exports.partials.orders-sheet', ['sheetName' => 'Ingresos efectivo', 'sheetOrders' => $cashOrders])

    <Worksheet ss:Name="Egresos efectivo">
        <Table>
            <Column ss:Width="90"/><Column ss:Width="220"/><Column ss:Width="90"/><Column ss:Width="120"/>
            <Row ss:Height="30"><Cell ss:StyleID="Title" ss:MergeAcross="3"><Data ss:Type="String">EGRESOS EN EFECTIVO</Data></Cell></Row>
            <Row ss:Height="28"><Cell ss:StyleID="Header"><Data ss:Type="String">Fecha</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Descripción</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Monto</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Usuario</Data></Cell></Row>
            @foreach($expenses as $expense)
                <Row><Cell ss:StyleID="Centered"><Data ss:Type="String">{{ $expense->fecha_egreso->format('d/m/Y') }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($expense->descripcion) }}</Data></Cell><Cell ss:StyleID="Money"><Data ss:Type="Number">{{ $money($expense->monto) }}</Data></Cell><Cell><Data ss:Type="String">{{ $text($expense->creator->username ?? '—') }}</Data></Cell></Row>
            @endforeach
        </Table>
    </Worksheet>

    @include('cash-closings.exports.partials.orders-sheet', ['sheetName' => 'Yape Plin', 'sheetOrders' => $yapePlinOrders])
    @include('cash-closings.exports.partials.orders-sheet', ['sheetName' => 'Transferencias', 'sheetOrders' => $transferOrders])
</Workbook>
