<div class="row">
    @php
        $json = file_get_contents(public_path('../public/assets/json/index-cards.json'));
        $cards = json_decode($json, true);
    @endphp
    @foreach ($cards as $card)
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card">
                <div class="card-body">
                    <div class="dash-widget-header">
                        <span class="{{ $card['dash-widget-icon'] }}">
                            <i class="{{ $card['icon-class'] }}"></i>
                        </span>
                        <div class="dash-count">
                            <div class="dash-title">{{ $card['dash-title'] }}</div>
                            <div class="dash-counts">
                                <p>{{ $card['dash-counts'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="progress progress-sm mt-3">
                        <div class="{{ $card['progress-bar'] }}" role="progressbar"
                            style="width: {{ $card['progress-width'] }}" aria-valuenow="{{ $card['progress-aria'] }}"
                            aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="text-muted mt-3 mb-0"><span class="{{ $card['arrow-color'] }}"><i
                                class="{{ $card['arrow-class'] }}"></i>{{ $card['since-last-week'] }}</span> since last
                        week
                    </p>
                </div>
            </div>
        </div>
    @endforeach
</div>
