<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'category', 'description', 'quantity', 'unit_amount', 'amount'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_amount' => 'decimal:2', 'amount' => 'decimal:2'];
    }
}
