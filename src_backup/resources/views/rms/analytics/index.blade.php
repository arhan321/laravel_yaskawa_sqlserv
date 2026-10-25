@extends('rms.layouts.app')
@section('heading', 'Data quality & analytics')
@section('subtitle', 'Telemetry reading quality dan trend monitoring — bukan perhitungan machine utilization.')
@section('content')
<div class="toolbar"><form method="GET" class="rms-filter-form"><label class="field">Periode <select name="period" class="input"><option value="24h" @selected($period === '24h')>24 jam</option><option value="7d" @selected($period === '7d')>7 hari</option></select></label><button type="submit" class="btn primary">Terapkan</button></form><span class="rms-muted-small">Sejak {{ $since->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</span></div>
<div class="stats rms-three-stats">
@include('rms.partials.metric',['label'=>'Total telemetry samples','value'=>number_format($metrics['total_samples'],0,',','.'),'description'=>'Semua robot pada periode ini'])
@include('rms.partials.metric',['label'=>'Valid position reads','value'=>number_format($metrics['valid_position_samples'],0,',','.'),'description'=>'Position read_ok'])
@include('rms.partials.metric',['label'=>'Valid torque reads','value'=>number_format($metrics['valid_torque_samples'],0,',','.'),'description'=>'Torque read_ok'])
</div>
<div class="two-col"><section class="panel"><h2>Per-robot data quality</h2><div class="table-wrap"><table class="data-table"><thead><tr><th>Robot</th><th>Samples</th><th>Position OK</th><th>Torque OK</th></tr></thead><tbody>@forelse($robots as $r) @php($v=$perRobot->get($r->id)) <tr><td>{{ $r->robot_code }}</td><td>{{ $v?->total_samples ?? 0 }}</td><td>{{ $v?->valid_position ?? 0 }}</td><td>{{ $v?->valid_torque ?? 0 }}</td></tr>@empty<tr><td colspan="4">Belum ada robot di registry</td></tr>@endforelse</tbody></table></div></section>
<section class="panel"><h2>Daily telemetry count</h2><p class="rms-muted-small">Agregasi kalender Asia/Jakarta</p>
@if($daily->isEmpty()) @include('rms.partials.no-data') @else @php($maxDay=max(1,(int)$daily->max('total_samples')))<div class="rms-bar-chart">@foreach($daily as $d)<div class="rms-bar-row"><span>{{ $d->local_day }}</span><div class="progress"><span style="width:{{ (int)round(100*(int)$d->total_samples/$maxDay) }}%"></span></div><strong>{{ number_format($d->total_samples,0,',','.') }}</strong></div>@endforeach</div>@endif
</section></div>
<div class="rms-note"><strong>Utilization dan downtime:</strong> {{ $unavailableReason }} Belum bisa dihitung secara sah dari sampel torque/position saja.</div>
@endsection
