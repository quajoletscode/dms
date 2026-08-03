<?php

use App\Modules\Finance\FinanceServiceProvider;
use App\Modules\MasterData\MasterDataServiceProvider;
use App\Modules\Sales\SalesServiceProvider;
use App\Modules\Van\VanServiceProvider;
use App\Modules\Warehouse\WarehouseServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    FinanceServiceProvider::class,
    WarehouseServiceProvider::class,
    VanServiceProvider::class,
    MasterDataServiceProvider::class,
    SalesServiceProvider::class,
];
