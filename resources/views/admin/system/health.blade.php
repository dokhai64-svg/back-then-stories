@extends('admin.layout')

@section('heading','System Health')

@section('content')
<style>
.health-hero{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    flex-wrap:wrap;
    margin-bottom:16px;
}
.health-status{
    display:inline-flex;
    align-items:center;
    gap:8px;
    min-height:34px;
    padding:0 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:850;
}
.health-status.ok{
    background:#ecfdf5;
    color:#047857;
}
.health-status.warn{
    background:#fff7ed;
    color:#c2410c;
}
.health-dot{
    width:9px;
    height:9px;
    border-radius:999px;
    background:currentColor;
}
.health-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:12px;
}
.health-check{
    padding:14px;
}
.health-check-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
}
.health-check-name{
    color:#475569;
    font-size:11px;
    font-weight:800;
}
.health-check-value{
    margin-top:8px;
    color:#0f172a;
    font-size:15px;
    font-weight:850;
}
.health-pill{
    display:inline-flex;
    min-height:23px;
    align-items:center;
    padding:0 7px;
    border-radius:999px;
    font-size:9px;
    font-weight:850;
}
.health-pill.ok{
    background:#ecfdf5;
    color:#047857;
}
.health-pill.warn{
    background:#fff7ed;
    color:#c2410c;
}
.health-section{
    margin-top:18px;
}
.health-section h2{
    margin:0 0 10px;
    font-size:16px;
}
.health-stats{
    display:grid;
    grid-template-columns:repeat(6,minmax(0,1fr));
    gap:10px;
}
.health-stat{
    padding:12px;
    text-align:center;
}
.health-stat-value{
    color:#0f172a;
    font-size:21px;
    font-weight:900;
}
.health-stat-label{
    margin-top:4px;
    color:#64748b;
    font-size:9px;
    font-weight:800;
    text-transform:uppercase;
}
.health-command{
    padding:10px 12px;
    border:1px solid #dbe3ec;
    border-radius:9px;
    background:#0f172a;
    color:#e2e8f0;
    font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
    font-size:11px;
    overflow:auto;
}
.health-note{
    margin-top:7px;
    color:#64748b;
    font-size:11px;
    line-height:1.5;
}
.health-table{
    width:100%;
    border-collapse:collapse;
}
.health-table th,
.health-table td{
    padding:10px;
    border-bottom:1px solid #edf1f5;
    text-align:left;
    font-size:11px;
    vertical-align:middle;
}
.health-table th{
    color:#64748b;
    font-size:9px;
    text-transform:uppercase;
}
.health-error-list{
    display:grid;
    gap:8px;
}
.health-error{
    padding:10px 12px;
    border:1px solid #fecaca;
    border-radius:9px;
    background:#fff7f7;
    color:#7f1d1d;
    font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
    font-size:10px;
    line-height:1.45;
    word-break:break-word;
}
.health-empty{
    padding:20px;
    border:1px dashed #cbd5e1;
    border-radius:9px;
    color:#64748b;
    text-align:center;
    font-size:11px;
}
@media(max-width:1100px){
    .health-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .health-stats{
        grid-template-columns:repeat(3,minmax(0,1fr));
    }
}
@media(max-width:700px){
    .health-grid,
    .health-stats{
        grid-template-columns:1fr;
    }
}
</style>

<div class="health-hero">
    <div>
        <div style="font-weight:900;font-size:18px">
            Production safety overview
        </div>

        <div class="muted small" style="margin-top:4px">
            Database, storage, cache, backups and recent application errors.
        </div>
    </div>

    <div
        class="health-status {{
            $allCriticalHealthy
                ? 'ok'
                : 'warn'
        }}"
    >
        <span class="health-dot"></span>

        {{
            $allCriticalHealthy
                ? 'Core systems healthy'
                : 'Needs attention'
        }}
    </div>
</div>

<div class="health-grid">
    @foreach([
        'database' => 'Database',
        'public_storage' => 'Public storage',
        'storage_link' => 'Public storage link',
        'config_cache' => 'Config cache',
        'route_cache' => 'Route cache',
        'view_cache' => 'View cache',
    ] as $key => $label)
        @php
            $check =
                $checks[$key]
                ?? [
                    'ok' => false,
                    'label' => 'Unknown',
                ];
        @endphp

        <div class="card health-check">
            <div class="health-check-top">
                <div class="health-check-name">
                    {{ $label }}
                </div>

                <span
                    class="health-pill {{
                        $check['ok']
                            ? 'ok'
                            : 'warn'
                    }}"
                >
                    {{
                        $check['ok']
                            ? 'OK'
                            : 'CHECK'
                    }}
                </span>
            </div>

            <div class="health-check-value">
                {{ $check['label'] }}
            </div>
        </div>
    @endforeach
</div>

<div class="health-section">
    <h2>Content snapshot</h2>

    <div class="health-stats">
        @forelse($stats as $stat)
            <div class="card health-stat">
                <div class="health-stat-value">
                    {{ number_format($stat['value']) }}
                </div>

                <div class="health-stat-label">
                    {{ $stat['label'] }}
                </div>
            </div>
        @empty
            <div class="card health-empty">
                No content statistics available.
            </div>
        @endforelse
    </div>
</div>

<div class="health-section">
    <h2>Backup</h2>

    <div class="card">
        <div style="font-weight:800;margin-bottom:7px">
            Create a full content + media backup
        </div>

        <div class="health-command">
            php artisan app:backup-content
        </div>

        <div class="health-note">
            The backup is created under <b>storage/app/backups</b>.
            After it finishes, refresh this page and download the ZIP to your computer.
            Keeping a copy outside Railway protects you if the server volume is ever lost.
        </div>

        <div style="font-weight:800;margin:16px 0 7px">
            Faster content-only backup
        </div>

        <div class="health-command">
            php artisan app:backup-content --no-media
        </div>

        <div class="health-note">
            This version backs up article/category/site/chapter/media records but does not copy image files.
        </div>
    </div>

    <div class="card" style="margin-top:10px">
        @if(count($backups))
            <table class="health-table">
                <thead>
                    <tr>
                        <th>Backup file</th>
                        <th>Created</th>
                        <th>Size</th>
                        <th style="width:120px">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($backups as $backup)
                        <tr>
                            <td>
                                {{ $backup['filename'] }}
                            </td>

                            <td>
                                {{ $backup['modified'] }}
                            </td>

                            <td>
                                {{ $backup['size'] }}
                            </td>

                            <td>
                                <a
                                    class="btn secondary"
                                    href="{{
                                        route(
                                            'admin.system-health.backup',
                                            [
                                                'filename' =>
                                                    $backup['filename'],
                                            ]
                                        )
                                    }}"
                                >
                                    Download
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="health-empty">
                No backups created yet.
            </div>
        @endif
    </div>
</div>

<div class="health-section">
    <h2>Speed / cache</h2>

    <div class="card">
        <div style="font-weight:800;margin-bottom:7px">
            Safe Laravel optimization
        </div>

        <div class="health-command">
            php artisan optimize
        </div>

        <div class="health-note">
            Run this after a stable deployment to cache configuration, routes and views.
            If you deploy new code afterward, Railway deployment or
            <b>php artisan optimize:clear</b> can clear those caches before rebuilding them.
        </div>
    </div>
</div>

<div class="health-section">
    <h2>Recent application errors</h2>

    @if(count($recentErrors))
        <div class="health-error-list">
            @foreach($recentErrors as $error)
                <div class="health-error">
                    {{ $error }}
                </div>
            @endforeach
        </div>
    @else
        <div class="health-empty">
            No recent production.ERROR entries found in the Laravel log.
        </div>
    @endif

    <div class="health-note">
        Only the latest error headline is shown here; stack traces and environment secrets are not displayed.
    </div>
</div>
@endsection
