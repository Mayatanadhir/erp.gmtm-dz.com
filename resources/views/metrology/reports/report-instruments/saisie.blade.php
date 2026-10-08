@php
    $typeVal = strtolower($instrument->instrument_type instanceof \BackedEnum ? $instrument->instrument_type->value : (string) $instrument->instrument_type);
@endphp

@if($typeVal === 'probe')
    @include('metrology.reports.report-instruments.probe')
@elseif(in_array($typeVal, ['flowcomputer', 'flow_computer']))
    @include('metrology.reports.report-instruments.flow_computer')
@else
    @include('metrology.reports.report-instruments.transmitter')
@endif
