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
