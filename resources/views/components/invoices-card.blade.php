<div class="row">
    @php
        $json = file_get_contents(public_path('../public/assets/json/invoices-card.json'));
        $invoices = json_decode($json, true);
    @endphp
    @foreach ($invoices as $invoice)
        <div class="col-xl-2 col-lg-4 col-sm-6 col-12 d-flex">
            <div class="card inovices-card w-100">
                <div class="card-body">
                    <div class="dash-widget-header">
                        <span class="inovices-widget-icon {{ $invoice['class'] }}">
                            <img src="{{ URL::asset('/assets/img/icons/' . $invoice['icon']) }}" alt="invoice">
                        </span>
                        <div class="dash-count">
                            <div class="dash-title">{{ $invoice['title'] }}</div>
                            <div class="dash-counts">
                                <p>{{ $invoice['amount'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <p class="inovices-all">No of Invoice <span
                                class="rounded-circle bg-light-gray">{{ $invoice['number_of_invoice'] }}</span>
                        </p>
                        <p class="inovice-trending text-success-light">{{ $invoice['trending']['value'] }} <span
                                class="ms-2"><i class="{{ $invoice['trending']['direction'] }}"></i></span></p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
