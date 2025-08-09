<!-- Inovices card -->
<div class="row">
    @php
        $json = file_get_contents(public_path('../public/assets/json/tickets-card.json'));
        $tickets = json_decode($json, true);
    @endphp
    @foreach ($tickets as $ticket)
        <div class="col-xl-2 col-lg-4 col-sm-6 col-12">
            <div class="card inovices-card">
                <div class="card-body">
                    <div class="dash-widget-header mb-0">
                        <span class="inovices-widget-icon rounded-circle {{ $ticket['class'] }}">
                            <img src="{{ URL::asset('/assets/img/icons/' . $ticket['icon']) }}" alt="invoices">
                        </span>
                        <div class="dash-count">
                            <div class="dash-title">{{ $ticket['title'] }}</div>
                            <div class="dash-counts">
                                <p class="mb-0">{{ $ticket['amount'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
<!-- /Inovices card -->
