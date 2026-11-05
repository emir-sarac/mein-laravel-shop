<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    /**
     * Die Attribute, die massenhaft zugewiesen werden dürfen.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'address1',
        'address2',
        'postal_code',
        'city',
        'country',
        'is_shipping',
        'is_billing',
    ];

    /**
     * Typenkonvertierung für die Attribute.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_shipping' => 'boolean',
        'is_billing' => 'boolean',
    ];

    /**
     * Holt den User, dem die Adresse gehört.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
