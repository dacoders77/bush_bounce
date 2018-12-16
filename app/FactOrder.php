<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

/**
 * Calculate profit using order history from the exchnage.
 *
 * Class FactOrder
 * @package App
 */
class FactOrder extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'trade_id',
        'client_order_id',
        'order_id',
        'symbol',
        'side',
        'quantity',
        'price',
        'fee',
        'time',
        'profit',
        'net_profit',
        'accum_profit'
    ];
}
