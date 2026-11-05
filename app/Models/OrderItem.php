<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'price',
        'quantity',
    ];

    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
    ];

    /**
     * Holt die dazugehörige Bestellung.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Holt das verknüpfte Produkt.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
