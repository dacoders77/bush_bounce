<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 10/14/2018
 * Time: 10:09 AM
 */

namespace App\Classes\Hitbtc;


/*
 * Object order class. Used for placing on order through a cache value.
 * Object order sent as a parameter then read and injected to a socket connection stream.
 * Used in ccxtsocket.php
 */

class OrderObject{

    public $direction;
    public $price;
    public $orderId; // Order ID tobe moved
    public $newOrderId; // New order ID for the moved order. Order ID has to be refreshed after the order is amended
    public $moveOrder; // Boolean. True when the order needs to be moved


    public function __construct(bool $moveOrder, string $direction, string $price, string $oredrId, string $newOrderId)
    {
        $this->moveOrder = $moveOrder;
        $this->direction = $direction;
        $this->price = $price;
        $this->orderId = $oredrId;
        $this->newOrderId = $newOrderId;
    }

}