{{-- Echo + Reverb untuk live PP / Kanban / WO (bukan poll). --}}
<script>
    window.PEGASUS_REVERB = {
        key: @json(config('broadcasting.connections.reverb.key')),
        host: @json(config('broadcasting.connections.reverb.options.host') ?: 'localhost'),
        port: {{ (int) (config('broadcasting.connections.reverb.options.port') ?: 8080) }},
        scheme: @json(config('broadcasting.connections.reverb.options.scheme') ?: 'http'),
    };
    window.PEGASUS_WAREHOUSE_ID = {{ (int) (session('active_warehouse_id') ?? 0) }};
</script>
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>window.LaravelEcho = window.Echo;</script>
<script src="{{ asset('Custom_js/Shared/pp-live.js') }}?v={{ time() }}"></script>
