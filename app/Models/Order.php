<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'customer_name', 
        'customer_email', 
        'customer_phone', 
        'product_id', 
        'quantity', 
        'total_amount', 
        'status'
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            if (blank($order->public_token)) {
                $order->public_token = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_token';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
