<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;
use Illuminate\Support\Collection;


class Office extends Model
{
    use HasFactory , softDeletes ;

    const APPROVAL_PENDING = 1;
    const APPROVAL_APPROVED = 2;
    const APPROVAL_REJECTED = 3;


    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'approval_status' => 'integer',
        'hidden' => 'boolean',
        'price_per_day' => 'integer',
        'monthly_discount' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class ,'resource');
    }

    public function featuredImage()
    {
        return $this->belongsTo(Image::class ,'featured_image_id');
    }


    public function tags()
    {
        return $this->belongsToMany(Tag::class ,'offices_tags');
    }

}


