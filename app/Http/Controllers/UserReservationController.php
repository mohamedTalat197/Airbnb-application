<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\Response;
use App\Http\Resources\ReservationResource;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;


class UserReservationController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->tokenCan('reservation_show'),
        Response::HTTP_FORBIDDEN
        );

        validator(request()->all(),[
            'status' => [Rule::in([Reservation::STATUS_ACTIVE , Reservation::STATUS_CANCELED])],
            'office_id'=> ['integer'],
            'from_date' => ['date' , 'required_with:to_date'],
            'to_date' => ['date' , 'required_with:from_date','after:from_date'],

        ])->validate();

        $reservation = Reservation::query()

        ->where('user_id' , auth()->id())

        ->when(request('office_id'),
        fn ($query) => $query->where('office_id' , request('office_id'))

        )->when(request('status'),
        fn ($query) => $query->where('status' , request('status'))

        //  filtering by date range Using A scopeBetweenDates , this part is important;
        )->when(request('from_date') && request('to_date'),
        fn( $query) => $query->BetweenDates(request('from_date'),request('to_date')))

        ->with(['office' , 'office.featuredImage'])
        ->paginate(5);

        return ReservationResource::collection($reservation);
    }

    public function create()
    {

        abort_unless(auth()->user()->tokenCan('reservation_show'),
        Response::HTTP_FORBIDDEN
        );


        validator(request()->all(),[
            'office_id' => ['required','integer'],
            'start_date' => ['required','date:Y-m-d','after:'.now()->addDay()->toDateString()],
            'end_date' => ['required', 'date:Y-m-d', 'after:start_date']
        ]);

        try {
            $office = Office::findOrFAil(request('office_id'));
        } catch(ModelNotFoundEexception $e) {
            ValidationException::withMessages([
                'office_id'=> 'inValid office_id !'
            ]);
        }
        if($office->user_id == auth()->id())
        {
            ValidationException::withMessages([
                'office_id'=> 'you cant make reservation in your own office'
            ]);
        }

        if($office->reservations()->activeBetween(request('start_date'), request('end_date'))->exists())
        {
            ValidationException::withMessages([
                'office_id'=> 'you cant make reservation during this time!'
            ]);
        }

        $reservation = Cache::lock('reservations_office_'.$office->id, 10)->block(3, function() use($office) {

            $numberOfDays = Carbon::parse(request('end_date'))->endOfDay()->diffInDays(
                Carbon::parse(request('start_date'))->startOfDay()
            );

            if($numberOfDays <2) {
                ValidationException::withMessages([
                    'office_id'=> 'you cant make reservation for only one day!'
                ]);
            }

            if($office->reservations()->activeBetween(request('start_date'), request('end_date'))->exists())
            {
                ValidationException::withMessages([
                    'office_id'=> 'you cant make reservation during this time!'
                ]);
            }

            $price = $numberOfDays * $office->price_per_day;

            if($numberOfDays > 28 && $office->monthly_discount)
            {
                $price = $price - ($price * $price->monthly_discount / 100);
            }

            return Reservation::create([
                'user_id' => auth()->id(),
                'start_date' => request('start_date'),
                'end_date' => request('end_date'),
                'office_id' => $office->id,
                'status' => Reservation::STATUS_ACTIVE,
                'price' => $price
            ]);

        });

        return ReservationResource::make(
            $reservation->load('office')
        );
    }
}
