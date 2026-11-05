<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'deliver_address1',
        'deliver_address2',
        'deliver_postal_code',
        'deliver_city',
        'deliver_country',
        'invoice_address1',
        'invoice_address2',
        'invoice_postal_code',
        'invoice_city',
        'invoice_country',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * Holt den User, der die Bestellung aufgegeben hat.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Holt alle bestellten Artikel dieser Bestellung.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
