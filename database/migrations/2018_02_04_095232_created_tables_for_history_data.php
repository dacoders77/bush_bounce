<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreatedTablesForHistoryData
 * The list of created tables (used for history data storage) is stored in this table.
 * Requested assets are stored in the DB using the controller: tickers_record_todb.php.
 * Assets are requested from the server in tickers_request.php controller.
 */
class CreatedTablesForHistoryData extends Migration
{
    /**
     * Run the migrations.
     *
     *
     * @return void
     */
    public function up()
    {
        Schema::create('created_tables_for_history_data', function (Blueprint $table) {
            $table->increments('id');
            $table->string('history_asset_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('created_tables_for_history_data');
    }
}
