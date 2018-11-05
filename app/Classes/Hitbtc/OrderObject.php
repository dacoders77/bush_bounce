<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 10/14/2018
 * Time: 10:09 AM
 */

namespace App\Classes\Hitbtc;


/*
 * Object order class.
 *
 * Usage case 1:
 * Used for placing order through. Instance of the class is added to cache.
 * Object order sent as a parameter then read and injected into a socket connection stream.
 * The added value is picked up in ccxtsocket.php
 *
 * Usage case 2:
 * Accumulate volume and price for trades which belong to the same order.
 * Used in Trading.php
 */

class OrderObject{

    public $action; // Place order, move order, get statuses etc.
    public $direction;
    public $price;
    public $quantity;
    public $orderId; // Order ID tobe moved
    public $newOrderId; // New order ID for the moved order. Order ID has to be refreshed after the order is amended


    public function __construct(string $action = null, string $direction = null, string $price = null, $quantity = null, string $oredrId = null, string $newOrderId = null)
    {
        $this->action = $action;
        $this->direction = $direction;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->orderId = $oredrId;
        $this->newOrderId = $newOrderId;
    }
}