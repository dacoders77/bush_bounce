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
    public static function addOpenOrder(string $orderDirection, float $orderVolume, $inPrice, $orderPlaceTime){

        // If order table is empty - add 0 to accum_profit.
        // If not - dublicate accum value from the previous record.
        $recordId = Order::create([
            'order_time' => $orderPlaceTime,
            'order_direction' => $orderDirection,
            'order_volume' => $orderVolume,
            'in_price' => $inPrice
        ])->id;

        if (Order::count() == 1){
            Order::where('id', $recordId)->update([
                'accum_profit' => 0
            ]);
        }
        else{
            Order::where('id', $recordId)->update([
                'accum_profit' => Order::where('id', $recordId - 1)->value('accum_profit')
            ]);
        }

        // Add an empty trade record. This record is used when close response is not received.
        // If close response is not received - we will have this record.
        // Needed for Order not found error handle

        return $recordId;
    }

    /**
     * Add empty trade right adther order is created.
     * This empty trade record will be the one rest of No order found error occurs.
     * In this case we don't have exit response thus we will use out price from placed exit order.
     * @todo Case when we use this empty order is OK but the case when such record is placed but placed limit order is not executed, may be the problem.
     * @param string $tradeDirection
     * @param float $tradeVolume
     * @param float $outPrice
     */
    public static function addEmptyTrade(string $tradeDirection, float $tradeVolume, float $outPrice){
        $id = Order::create([
            'order_direction' => '**',
            'trade_direction' => $tradeDirection,
            'trade_volume' => $tradeVolume,
            'out_price' => $outPrice,
            'rebate_per_volume' => 0
        ])->id;
        return $id;
    }


    /**
     * Add trade record.
     */
    public static function addTrade(string $tradeDirection, float $tradeVolume, float $outPrice, float $rebatePerVolume){


        /* If previous record is an empty record - update it
         * Else - create a new one
         * */
        $lastRecord = Order::orderBy('id', 'desc'); // Get the last record

        if ($lastRecord->value('order_direction') == '**'){
            // update
            Order::where('id', $lastRecord->value('id'))
                ->update([
                    'order_direction' => ' ', // Get rid of ** symbols which represent an empty trade record
                    'trade_direction' => $tradeDirection,
                    'trade_volume' => $tradeVolume,
                    'out_price' => $outPrice,
                    'rebate_per_volume' => $rebatePerVolume * 2
                ]);
            return $lastRecord->value('id');
        }
        else{
            $recordId = Order::create([
                'trade_direction' => $tradeDirection,
                'trade_volume' => $tradeVolume,
                'out_price' => $outPrice,
                'rebate_per_volume' => $rebatePerVolume * 2
            ])->id;
            return $recordId;
        }

        // if ($lastRecord->where('id', $lastRecord->value('id') - 1)->value('order_direction') == '**'){

    }

    /**
     * When a trade is added. Profit can be calculated.
     * Accordingly to the given volume (trade volume).
     * @param int   $id id of the record for which profit will be calculated.
     * @void mixed
     */
    public static function calculateProfit(int $id){
        // id IN price
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
    }
}
