<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;
    // protected $table = "follow_up";
    protected $guarded = [];
    protected $casts = [
        'start_time' => 'datetime: H:i',
        'end_time'=> 'datetime: H:i'
    ]; 
}
