<?php

namespace App\Modules\Sales\Models;

use App\Casts\MoneyCast;
use App\Modules\MasterData\Models\Customer;
use App\Modules\Van\Models\VanStorage;
use App\Modules\Warehouse\Models\Warehouse;
use App\Support\Auditable;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $no
 * @property string $source
 * @property int $customer_id
 * @property int $warehouse_id
 * @property int|null $van_storage_id
 * @property int|null $sales_order_id
 * @property int|null $proforma_invoice_id
 * @property Carbon|null $due_date
 * @property string $status
 * @property Money $subtotal
 * @property Money $tax_total
 * @property Money $discount_total
 * @property Money $grand_total
 * @property int|null $created_by
 * @property-read Customer $customer customer_id is a required FK — always present once persisted
 * @property-read Warehouse $warehouse warehouse_id is a required FK — always present once persisted
 */
class Invoice extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'subtotal' => MoneyCast::class,
            'tax_total' => MoneyCast::class,
            'discount_total' => MoneyCast::class,
            'grand_total' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<VanStorage, $this>
     */
    public function vanStorage(): BelongsTo
    {
        return $this->belongsTo(VanStorage::class);
    }

    /**
     * @return BelongsTo<SalesOrder, $this>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * @return BelongsTo<ProformaInvoice, $this>
     */
    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * @return HasMany<InvoicePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }
}
