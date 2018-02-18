<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    protected $table='records';
    protected $fillable=['id', 'contry', 'city', 'date', 'no_of_aliens', 'color_of_aliens'];
}
