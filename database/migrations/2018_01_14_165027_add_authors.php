<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

// https://www.youtube.com/watch?v=lEZ8cnVGVZE&index=2&list=PL09BB956FCFB5C5FD

class AddAuthors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('authors')->insert(array(
            'name'=>'Boris Borisov',
            'bio'=>'Boris is the best huy in the world',
            'created_at'=>date('Y-m-d H:m:s'),
            'updated_at'=>date('Y-m-d H:m:s'),
        ));

        DB::table('authors')->insert(array(
            'name'=>'Pavel Perminov',
            'bio'=>'He is crazy about Navalny and so on...',
            'created_at'=>date('Y-m-d H:m:s'),
            'updated_at'=>date('Y-m-d H:m:s'),
        ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Delete two records where name = Boris Voinov, Pave; Perminov
        DB::table('authors')->where('name', '=', 'Boris Borisov')->delete();
        DB::table('authors')->where('name', '=', 'Pavel Perminov')->delete();
    }
}
