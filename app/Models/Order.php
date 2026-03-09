<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'status',
        'total_amount',
        'shipping_address',
        'notes',
        'payment_method',    
        'payment_status',    
        'tracking_number',  
        'ordered_at',        
        'delivered_at',     
    ];

    protected $casts = [
        'total_amount'  => 'decimal:2',
        'ordered_at'    => 'datetime',
        'delivered_at'  => 'datetime',
    ];

    // Order Statuses
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED    = 'shipped';
    const STATUS_DELIVERED  = 'delivered';
    const STATUS_CANCELLED  = 'cancelled';

    // Payment Statuses
    const PAYMENT_UNPAID   = 'unpaid';
    const PAYMENT_PAID     = 'paid';
    const PAYMENT_REFUNDED = 'refunded';

    // Payment Methods
    const PAYMENT_CASH        = 'cash';
    const PAYMENT_CREDIT_CARD = 'credit_card';
    const PAYMENT_GCASH       = 'gcash';

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    // Usage: Order::pending()->get()
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // Usage: Order::delivered()->get()
    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    // Usage: Order::paid()->get()
    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_PAID);
    }

    // Usage: Order::forUser(1)->get()
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function calculateTotal(): float
{
    return $this->orderItems->sum('subtotal');
}

public function scopeByStatus($query, string $status)
{
    return $query->where('status', $status);
}
}