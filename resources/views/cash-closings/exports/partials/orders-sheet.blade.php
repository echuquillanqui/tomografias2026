<Worksheet ss:Name="{{ $sheetName }}">
    <Table>
        <Row><Cell><Data ss:Type="String">Fecha</Data></Cell><Cell><Data ss:Type="String">Orden</Data></Cell><Cell><Data ss:Type="String">Paciente</Data></Cell><Cell><Data ss:Type="String">Convenio</Data></Cell><Cell><Data ss:Type="String">Pago</Data></Cell><Cell><Data ss:Type="String">Total</Data></Cell></Row>
        @foreach($sheetOrders as $order)
            <Row><Cell><Data ss:Type="String">{{ $order->fecha_orden->format('d/m/Y H:i') }}</Data></Cell><Cell><Data ss:Type="String">{{ htmlspecialchars($order->codigo_orden ?? '#'.$order->id, ENT_QUOTES | ENT_XML1, 'UTF-8') }}</Data></Cell><Cell><Data ss:Type="String">{{ htmlspecialchars(($order->patient->nombres ?? '').' '.($order->patient->apellidos ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8') }}</Data></Cell><Cell><Data ss:Type="String">{{ htmlspecialchars($order->agreement->nombre_institucion ?? '—', ENT_QUOTES | ENT_XML1, 'UTF-8') }}</Data></Cell><Cell><Data ss:Type="String">{{ htmlspecialchars($order->tipo_pago ?? '—', ENT_QUOTES | ENT_XML1, 'UTF-8') }}</Data></Cell><Cell><Data ss:Type="Number">{{ number_format((float) $order->total, 2, '.', '') }}</Data></Cell></Row>
        @endforeach
    </Table>
</Worksheet>
