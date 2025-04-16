<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'title',
        'short_description',
        'max_possible_points',
        'path_to_description',
    ];
}
