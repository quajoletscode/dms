<?php

namespace App\Modules\MasterData\Actions;

use App\Modules\MasterData\Domain\Exceptions\ExpiredBatchException;
use App\Modules\MasterData\Models\Batch;
use Illuminate\Support\Carbon;

final class CreateBatch
{
    /**
     * @param  array{product_id: int, batch_no: string, expiry_date?: string|null, received_at?: string|null, allow_expired?: bool}  $data
     */
    public function execute(array $data): Batch
    {
        $expiryDate = $data['expiry_date'] ?? null;

        if ($expiryDate !== null && Carbon::parse($expiryDate)->isPast() && ! ($data['allow_expired'] ?? false)) {
            throw new ExpiredBatchException("Batch [{$data['batch_no']}] has an expiry date in the past.");
        }

        return Batch::query()->create([
            'product_id' => $data['product_id'],
            'batch_no' => $data['batch_no'],
            'expiry_date' => $expiryDate,
            'received_at' => $data['received_at'] ?? now(),
        ]);
    }
}
