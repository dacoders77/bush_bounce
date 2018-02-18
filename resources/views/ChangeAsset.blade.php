<?php

$allTableValues = DB::table('created_tables_for_history_data')->get();

foreach ($allTableValues as $tableValue){
    // Loop through acquired records
    // Drop the table (with history data)

    //echo "###########################table dropped: " . $tableValue->history_asset_name;
    echo '<a href="change_asset/' . $tableValue->history_asset_name.'">';
    echo $tableValue->history_asset_name;
    echo '</a>';
    echo ' ';
    //echo $tableValue->history_asset_name . " ";
}

?>


