@extends('rms.layouts.app')
@section('heading', 'Robot Cockpit')
@section('subtitle', 'Motor Torque, Position, Threshold, dan Speed Reducer monitoring untuk kebutuhan teknisi.')
@section('auto_refresh', 'off')
@push('styles')
<link rel="stylesheet" href="{{ asset('rms/css/cockpit.css') }}?v=20261015-2255">
<link rel="stylesheet" href="{{ asset('rms/css/technician-guide.css') }}?v=20261015-2255">
@endpush
@section('content')
@php
$axes = ['S','L','U','R','B','T'];
$latestTime = $latest?->sampled_at?->timezone('Asia/Jakarta')->format('d M Y H:i:s');
$isDemo = \Illuminate\Support\Str::startsWith($robot->robot_code, 'DEMO_') || \Illuminate\Support\Str::contains($robot->name, '[DEMO]');
$axisStateLabels = [
  'normal' => 'NORMAL',
  'warning' => 'WARNING',
  'critical' => 'CRITICAL',
  'not_configured' => 'NOT CONFIGURED',
  'unavailable' => 'UNAVAILABLE',
];
@endphp
<div class="cp-root" data-cockpit>
  <div class="cp-toolbar">
    <div class="cp-links"><a href="{{ route('rms.robots.show',$robot) }}">← Robot detail</a><a href="{{ route('rms.robots.index') }}">Robot monitor</a><a href="{{ route('rms.robots.configuration', $robot) }}">⚙ Konfigurasi</a>@if(Route::has('rms.logs.index'))<a href="{{ route('rms.logs.index', ['robot_id'=>$robot->id]) }}">Log robot</a>@endif</div>
    <div class="cp-tags">
      @if($isDemo)<span class="cp-pill cp-demo">DATA DEMO</span>@endif
      <span class="cp-pill {{ $fresh?'cp-ok':'cp-muted' }}">{{ $fresh?'TELEMETRY FRESH':'TELEMETRY STALE / UNKNOWN' }}</span>
    </div>
  </div>
  <div class="cp-head">
    <div><div class="cp-eyebrow">YASKAWA EQUIPMENT HEALTH / READ-ONLY</div><h2>{{ $robot->name }}</h2><p>{{ $robot->robot_code }} · {{ $robot->model }} · {{ $robot->control_group }} · {{ $robot->station_name ?: 'Station belum diatur' }}</p></div>
    <div class="cp-controller"><div>CONTROLLER</div><strong>{{ $robot->controller?->model ?? '—' }}</strong><small>{{ $robot->controller?->ip_address ?? 'IP belum didaftarkan' }}</small></div>
  </div>
  <div class="cp-metrics">
    <div><span>Last Telemetry</span><strong>{{ $latestTime ?? 'Belum tersedia' }}</strong><small>Waktu Indonesia Barat (WIB)</small></div>
    <div><span>Servo Operating Hours</span><strong data-rms-operating-status="{{ $operatingDisplay['status'] }}" title="{{ $operatingDisplay['caption'] }}">{{ $operatingDisplay['text'] }}</strong><small data-rms-operating-caption>{{ $operatingDisplay['caption'] }}</small></div>
    <div><span>Open Threshold Events</span><strong>{{ $openEvents->count() }}</strong><small>RMS monitoring event; bukan Controller Alarm</small></div>
    <div><span>Identified Active Alarms</span><strong>{{ $alarms->count() }}</strong><small>Alarm controller yang kode dan statusnya berhasil diidentifikasi</small></div>
  </div>

  @include('rms.partials.technician-guide', ['guideId' => 'cockpit-technical-guide', 'guideOpen' => false])

  <div class="cp-columns">
    <section class="cp-panel cp-axis-panel">
      <header><div><span class="cp-kicker">CONTROL GROUP {{ $robot->control_group }}</span><h3>Axis Monitoring</h3></div><span class="cp-panel-sub">S · L · U · R · B · T</span></header>
      <div class="cp-robot-illustration" aria-hidden="true">
        <svg viewBox="0 0 260 156" width="100%" height="145" role="img">
          <defs><linearGradient id="cpRobotBlue" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#75b0d7"/><stop offset="1" stop-color="#1e5f95"/></linearGradient></defs>
          <ellipse cx="130" cy="143" rx="94" ry="9" fill="#e4ecf1"/>
          <path d="M56 141h66l-12-22H70z" fill="#1c537e"/><path d="M72 120V94h33v26" fill="url(#cpRobotBlue)" stroke="#1d5d92" stroke-width="3"/>
          <path d="m88 98-22-43 23-12 24 47z" fill="url(#cpRobotBlue)" stroke="#1d5d92" stroke-width="3"/><path d="m76 48 78-23 12 24-77 23z" fill="url(#cpRobotBlue)" stroke="#1d5d92" stroke-width="3"/>
          <path d="m157 33 41 35-12 15-45-36z" fill="url(#cpRobotBlue)" stroke="#1d5d92" stroke-width="3"/><path d="m195 75 29 12-7 19-30-13z" fill="#276b9b" stroke="#1d5d92" stroke-width="3"/>
          <circle cx="88" cy="97" r="17" fill="#d4ecfc" stroke="#216596" stroke-width="6"/><circle cx="76" cy="51" r="14" fill="#d4ecfc" stroke="#216596" stroke-width="6"/>
          <circle cx="156" cy="39" r="13" fill="#d4ecfc" stroke="#216596" stroke-width="6"/><path d="m218 102-8 21m5-19 13 17" stroke="#245e8c" stroke-width="5" stroke-linecap="round"/>
        </svg>
        <div class="cp-robot-caption">Skema ilustrasi robot · bukan digital twin</div>
      </div>
      <div class="cp-axis-list">
        @foreach($axes as $axis)
          @php($d=$axisStatus[$axis])
          <div class="cp-axis-item">
            <span class="cp-axis-letter">{{ $axis }}</span>
            <div class="cp-axis-measure"><strong>{{ $d['value'] !== null ? number_format($d['value'],4,',','.') : '—' }}</strong><small>Motor Torque · Position {{ is_numeric($d['pulse']) ? number_format((float)$d['pulse'],0,',','.') : '—' }} pulse</small></div>
            <span class="cp-state cp-{{ $d['status'] }}">{{ $axisStateLabels[$d['status']] ?? 'STATUS TIDAK DIKETAHUI' }}</span>
          </div>
        @endforeach
      </div>
      <div class="cp-smallnote"><strong>How to read:</strong> angka besar adalah Motor Torque Value dari controller. Instant Axis Status memakai satu pembacaan terbaru, sedangkan Threshold Event memerlukan 3 valid samples berturut-turut. Tanpa approved threshold, status ditampilkan sebagai <strong>NOT CONFIGURED</strong> dan tidak dianggap Normal.</div>
    </section>

    <section class="cp-panel cp-torque-panel">
      <header><div><span class="cp-kicker">MEASUREMENT HISTORY</span><h3>Motor Torque — All Axes</h3></div><span class="cp-panel-sub">Controller monitor value · unit pending verification</span></header>
      <div class="cp-chart-controls">
        <div class="cp-segment" role="group" aria-label="Mode grafik"><button type="button" data-mode="measurement" class="selected">Measurement</button><button type="button" data-mode="variation">Variation Δ</button></div>
        <div class="cp-periods" aria-label="Rentang historis">
          @foreach(['1h','8h','24h'] as $p)<a href="{{ route('rms.robots.cockpit', ['robot'=>$robot->id,'period'=>$p]) }}" class="{{ $period===$p?'selected':'' }}">{{ $p }}</a>@endforeach
        </div>
      </div>
      <div class="cp-chart-frame"><canvas id="cp-torque-chart" width="940" height="420" role="img" aria-label="Motor Torque history for six robot axes"></canvas><div id="cp-torque-empty" class="cp-empty" hidden>Belum tersedia sampel Motor Torque yang valid pada rentang ini.</div></div>
      <div class="cp-chart-legend" id="cp-torque-legend">@foreach($axes as $axis)<button type="button" data-axis="{{ $axis }}" class="is-selected"><span class="cp-dot cp-dot-{{ $axis }}"></span>{{ $axis }}</button>@endforeach</div>
      <div class="cp-chart-bottom"><div>Pilih axis untuk show/hide garis. Threshold line hanya muncul untuk axis dengan approved threshold pada mode Measurement.</div><div><label for="cp-focus-axis">Threshold Axis</label><select id="cp-focus-axis">@foreach($axes as $axis)<option value="{{ $axis }}">{{ $axis }}</option>@endforeach</select></div></div>
      <div class="cp-smallnote"><strong>Variation Δ</strong> adalah selisih terhadap valid sample pertama pada time range yang dipilih. Grafik ini menunjukkan perubahan sederhana dan <strong>bukan algoritma OEM Yaskawa Cockpit</strong>.</div>
    </section>
  </div>

  <div class="cp-columns cp-second-row">
    <section class="cp-panel" id="threshold-config"><header><div><span class="cp-kicker">CONFIGURED LIMITS</span><h3>Torque Threshold per Axis</h3></div><div class="cp-panel-tools"><span class="cp-panel-sub">Approved thresholds only</span><a class="cp-action" href="{{ route('rms.robots.configuration', $robot) }}#threshold-config">⚙ Threshold Configuration</a></div></header>
      <div class="cp-table-wrap"><table class="cp-table"><thead><tr><th>Axis</th><th>Warning Threshold</th><th>Critical Threshold</th><th>Hysteresis</th><th>Status</th></tr></thead><tbody>
      @foreach($axes as $axis)
        @php($rule=$rules->get($axis))
        <tr><td><strong>{{ $axis }}</strong></td><td>{{ $rule && $rule->isApproved()?number_format((float)$rule->warning_magnitude,3,',','.'):'—' }}</td><td>{{ $rule && $rule->isApproved()?number_format((float)$rule->critical_magnitude,3,',','.'):'—' }}</td><td>{{ $rule && $rule->isApproved()?$rule->clear_hysteresis_percent.'%':'—' }}</td><td><span class="cp-state {{ $rule && $rule->isApproved()?'cp-normal':'cp-not_configured' }}">{{ $rule && $rule->isApproved()?'APPROVED':'NOT CONFIGURED' }}</span></td></tr>
      @endforeach
      </tbody></table></div>
      <div class="cp-smallnote">Threshold membandingkan <strong>Torque Magnitude |value|</strong>. Threshold hanya berstatus APPROVED setelah nilainya dicocokkan, Source Reference dicatat, dan pemeriksa diisi. Pengaturan ini tidak mengubah Controller Alarm.</div>
    </section>
    <section class="cp-panel" id="reducer-config"><header><div><span class="cp-kicker">COMPONENT CONDITION</span><h3>Speed Reducer Life</h3></div><div class="cp-panel-tools"><span class="cp-panel-sub">Verified OEM / inspection evidence</span><a class="cp-action" href="{{ route('rms.robots.configuration', $robot) }}#reducer-config">＋ Input Evidence</a></div></header>
      <div class="cp-chart-controls"><label for="cp-reducer-axis">Reducer / Axis</label><select id="cp-reducer-axis">@foreach($axes as $axis)<option value="{{ $axis }}">Axis {{ $axis }}</option>@endforeach</select></div>
      <div class="cp-chart-frame cp-reducer-chart"><canvas id="cp-reducer-chart" width="940" height="360" role="img" aria-label="Histori hasil inspeksi speed reducer"></canvas><div id="cp-reducer-empty" class="cp-empty" hidden>Belum ada pengukuran speed reducer terverifikasi dari sumber OEM.</div></div>
      <div class="cp-reducer-meta" id="cp-reducer-meta"></div>
      <div class="cp-smallnote"><strong>Remaining Life</strong> hanya ditampilkan jika nilainya tersedia dan terverifikasi pada OEM/inspection evidence. RMS tidak mengubah Motor Torque menjadi prediksi umur reducer.</div>
    </section>
  </div>

  <div class="cp-columns cp-last-row">
    <section class="cp-panel"><header><div><span class="cp-kicker">THRESHOLD EVENT HISTORY</span><h3>Warning & Critical Events</h3></div><span class="cp-panel-sub">{{ $eventHistory->count() }} latest records</span></header>
      <div class="cp-table-wrap"><table class="cp-table"><thead><tr><th>Time WIB</th><th>Axis</th><th>Severity</th><th>Event Status</th><th>Peak Magnitude</th></tr></thead><tbody>
        @forelse($eventHistory as $event)<tr><td>{{ $event->started_at?->timezone('Asia/Jakarta')->format('d M H:i:s') }}</td><td>{{ $event->axis }}</td><td><span class="cp-state cp-{{ $event->severity }}">{{ strtoupper($event->severity) }}</span></td><td>{{ strtoupper($event->status) }}</td><td>{{ number_format((float)$event->peak_magnitude,4,',','.') }}</td></tr>
        @empty<tr><td colspan="5">Belum ada Threshold Event yang tercatat. Kondisi ini tidak otomatis membuktikan robot sehat; pastikan threshold sudah dikonfigurasi dan telemetry masih Fresh.</td></tr>@endforelse
      </tbody></table></div>
    </section>
    <section class="cp-panel"><header><div><span class="cp-kicker">MAINTENANCE REVIEW</span><h3>Data Quality & Action</h3></div></header>
      <div class="cp-quality"><div><strong>Controller Telemetry</strong><span>{{ $fresh?'Fresh data':'Stale or unavailable' }}</span></div><div><strong>Approved Torque Thresholds</strong><span>{{ $rules->filter(fn($r)=>$r->isApproved())->count() }} / 6 axis</span></div><div><strong>Verified Reducer Measurements</strong><span>{{ $reducerByAxis->filter()->count() }} / 6 axis</span></div><div><strong>Controller RUN/IDLE</strong><span>Belum tersedia</span></div><div><strong>Job & I/O</strong><span>Belum tersedia</span></div></div>
      <div class="cp-smallnote">Saat telemetry <strong>STALE</strong>, kondisi robot harus diperlakukan <strong>UNKNOWN</strong>, bukan Normal dan bukan otomatis rusak. Keputusan maintenance tetap memerlukan pengecekan pendant, Controller Alarm, dan inspeksi teknisi.</div>
    </section>
  </div>
</div>
<script>
window.__RMS_COCKPIT__ = {{ \Illuminate\Support\Js::from($cockpitPayload) }};
</script>
<script src="{{ asset('rms/js/cockpit.js') }}?v=20261020-operating-hours" defer></script>
@endsection
