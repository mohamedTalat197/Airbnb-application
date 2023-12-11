<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\OfficeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Reservation;
use App\Models\Office;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;
use DB;
use App\Models\Validators\OfficeValidator;
use App\Notifications\OfficePendingApproval;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;


class OfficeController extends Controller
{
public function index()
{
    $offices = Office::query()
    // ->where('approval_status',Office::APPROVAL_APPROVED)
    // ->where('hidden', false)
    ->when(request('user_id') && auth()->user() && request('user_id') == auth()->id(),
    fn ($builder) => $builder, 
    fn ($builder) => $builder->where('approval_status',Office::APPROVAL_APPROVED)->where('hidden', false)
    )

    ->when(request('user_id'), fn(Builder $builder) => $builder->whereUserId(request('user_id')))
    ->when(request('visitor_id'), fn(Builder $builder)
    => $builder->whereRelation('reservations' , 'user_id', '=', (request('visitor_id'))))
    ->when(
        request('lat') && request('lng') ,
        fn (Builder $builder) => '....',
        fn (Builder $builder) => $builder->orderBy('id', 'ASC'),
    )

    ->latest('id')
    ->with(['user', 'images', 'tags'])
    ->withCount(['reservations' => fn ($builder) => $builder->where('status',Reservation::STATUS_ACTIVE)])
    ->get();


    return OfficeResource::collection($offices);

}

public function show(Office $office)
{
    $office->loadCount(['reservations' => fn ($builder) => $builder->where('status',Reservation::STATUS_ACTIVE)])
            ->load(['user', 'images', 'tags']);
    return OfficeResource::make($office);
}

public function create()
{
    abort_unless(auth()->user()->tokenCan('office.create'),
    Response::HTTP_FORBIDDEN
    );

    
    $attributes = (new OfficeValidator())->validate(
        $office = new Office(),
        request()->all());

    $attributes['approval_status'] = Office::APPROVAL_PENDING;
    $attributes['user_id'] = auth()->id();


    $office = DB::transaction(function () use( $office, $attributes){
        $office->fill(
            Arr::except($attributes, ['tags'])
        )->save();

        if(isset($attributes['tags'])){
            $office->tags()->sync($attributes['tags']);
        }
        return $office;
    });

    Notification::send(User::firstWhere('is_admin', true)->get(),new OfficePendingApproval($office));

    return OfficeResource::make($office)->additional([
        'tags' => $office->tags, 
    ]);
}

public function update(Office $office)
{
    abort_unless(auth()->user()->tokenCan('office.update'),
    Response::HTTP_FORBIDDEN
    );
    $this->authorize('update',$office);
    $attributes = (new OfficeValidator())->validate($office , request()->all());

    $office->fill(Arr::except($attributes, ['tags']));

    if($requiresReview = $office->isDirty('lat','lng','price_per_day'))
    {
        $office->fill(['approval_status'=> Office::APPROVAL_PENDING],);
    }

    DB::transaction(function () use( $office, $attributes){
        $office->save();
    
    if(isset($attributes['tags'])){
        $office->tags()->sync($attributes['tags']);
    }
    });

    if($requiresReview){

        Notification::send(User::firstWhere('is_admin', true)->get(),new OfficePendingApproval($office));
    }
    
    return OfficeResource::make($office)->additional([
        'tags' => $office->tags, 
    ]);
    
}

public function delete(Office $office)
{
    abort_unless(auth()->user()->tokenCan('office.delete'),
    Response::HTTP_FORBIDDEN
    );
    $this->authorize('delete',$office);

    // if($office->reservations()->where('status' , Reservation::STATUS_ACTIVE)->count() > 0)
    // {
    //     throw ValidationException::withMessages(['office'=> 'Can Not Delete This Office!']);
    // }

    throw_if(
        $office->reservations()->where('status' , Reservation::STATUS_ACTIVE)->count() > 0,
        ValidationException::withMessages(['office'=> 'Can Not Delete This Office!'])
    );

    $office->images()->each(function ($image){
        Storage::disk('public')->delete($image->path);
        $image->delete();
    });

    $office->delete();
}

}


