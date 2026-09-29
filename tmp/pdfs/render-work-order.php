<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$payload = App\Models\ProductionWorkOrder::printPayload(3);
if (!$payload) { throw new RuntimeException('WO 3 not found'); }
file_put_contents(__DIR__.'/wo-a5.pdf', Barryvdh\DomPDF\Facade\Pdf::loadView('Backoffice.PDF.WorkOrderProduction', $payload)->setPaper('a5', 'landscape')->output());
$row = $payload['items'][0];
$payload['items'] = [];
for ($i = 1; $i <= 20; $i++) {
    $row['no'] = $i;
    $row['product_name'] = 'PEGASUS AIR ZUUR 30 x 600ML - PRODUK DENGAN NAMA PANJANG';
    $payload['items'][] = $row;
}
file_put_contents(__DIR__.'/wo-a5-many.pdf', Barryvdh\DomPDF\Facade\Pdf::loadView('Backoffice.PDF.WorkOrderProduction', $payload)->setPaper('a5', 'landscape')->output());
