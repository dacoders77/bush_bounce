<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_direction',
        'order_volume',
        'trade_volume',
        'order_volume_remain',
        'trade_direction',
        'in_price',
        'out_price',
        'profit_per_contract',
        'profit_per_volume',
        'rebate_per_volume',
        'net_profit',
        'accum_profit'
    ];
}
