<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Order; // Model link

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Add a record to DB with order data.
     */
    public static function addOpenOrder(string $orderDirection, float $orderVolume, $inPrice){
        $recordId = Order::create([
            'order_direction' => $orderDirection,
            'order_volume' => $orderVolume,
            'in_price' => $inPrice
        ])->id;
        return $recordId;
    }

    /**
     * Add trade record.
     */
    public static function addTrade(string $tradeDirection, float $tradeVolume, float $outPrice, float $rebatePerVolume){
        $recordId = Order::create([
            'trade_direction' => $tradeDirection,
            'trade_volume' => $tradeVolume,
            'out_price' => $outPrice,
            'rebate_per_volume' => $rebatePerVolume
        ])->id;
        return $recordId;
    }

    /**
     * When a trade is added. Profit can be calculated.
     * Accordingly to the given volume (trade volume).
     * @param int   $id id of the record for which profit will be calculated.
     * @void mixed
     */
    public static function calculateProfit(int $id){
        // id in price
        $inPriceRecord = Order::where('in_price', '!=', null)->orderBy('id', 'desc');
        $profitPerVolume = (Order::where('id', $id)->value('out_price') - $inPriceRecord->value('in_price')) * Order::where('id', $id)->value('trade_volume');
        $netProfit = $profitPerVolume + Order::where('id', $id)->value('rebate_per_volume');
        Order::where('id', $id)
            ->update([
                'profit_per_contract' => Order::where('id', $id)->value('out_price') - $inPriceRecord->value('in_price'),
                'order_volume' => Order::where('id', $id - 1)->value('order_volume') - Order::where('id', $id)->value('trade_volume'),
                'profit_per_volume' => $profitPerVolume,
                'net_profit' => $netProfit,
                'accum_profit' => Order::where('id', $id - 1)->value('accum_profit') + $netProfit,
                ]);

        // if sell: open order price - current trade price

        //Execution::where('signal_id', $request['id'])
        //    ->where('client_id', $execution->client_id)
        //    ->update(['client_funds' => $response, 'open_response' => 'Got balance ok']);



    }
}
