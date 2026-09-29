<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$wo = App\Models\ProductionWorkOrder::findOrFail(3);
Illuminate\Support\Facades\Session::put('active_warehouse_id', $wo->warehouse_id);
$response = (new App\Http\Controllers\ProductionController())->printProductionPlanning($wo->production_planning_id);
file_put_contents(__DIR__.'/spk-a5.pdf', $response->getContent());
$planning = (new App\Models\ProductionPlanning())->getDetail($wo->production_planning_id);
$rows = [];
for ($i=0; $i<20; $i++) { $row = $planning['items'][0]; $row['product_name'] = 'PEGASUS RADIATOR COOLANT PRODUK DENGAN NAMA PANJANG 12 x 1 LITER'; $row['pic_name'] = null; $row['skala_code'] = null; $rows[] = $row; }
$planning['items'] = $rows;
file_put_contents(__DIR__.'/spk-many.pdf', Barryvdh\DomPDF\Facade\Pdf::loadView('Backoffice.PDF.ProductionPlanningOrder', [
    'planning' => $planning, 'company_name' => 'Pegasus Hikari Group', 'logo_base64' => null,
    'skalas' => App\Models\ProductionSkala::where('status', 1)->orderBy('code')->get(),
])->setPaper('a5','landscape')->output());
