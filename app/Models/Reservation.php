<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\ImmutableDate;


class Reservation extends Model
{
    use HasFactory;
    const STATUS_ACTIVE = 1;
    const STATUS_CANCELED = 2;



protected $casts = [
    'status' => 'integer',
    'price' => 'integer',
    'start_date' => 'immutable_date',
    'end_date' => 'immutable_date',
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function scopeActiveBetween($query , $from,$to)
    {
        $query->whereStatus(Reservation::STATUS_ACTIVE)
              ->BetweenDates($from, $to);
    }

    public function scopeBetweenDates($query , $from,$to)
    {
            return $query->where(function($query) use ($from,$to){
            return $query->whereBetween('start_date' , [$from , $to])
                         ->orWhereBetween('end_date' , [$from , $to]);
        });
    }

}
