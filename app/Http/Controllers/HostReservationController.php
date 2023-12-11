<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HostReservationController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->tokenCan('reservation_show'),
        Response::HTTP_FORBIDDEN
        );

        validator(request()->all(),[
            'status' => [Rule::in([Reservation::STATUS_ACTIVE , Reservation::STATUS_CANCELED])],
            'office_id'=> ['integer'],
            'user_id' => ['integer'],
            'from_date' => ['date' , 'required_with:to_date'],
            'to_date' => ['date' , 'required_with:from_date','after:from_date'],

        ])->validate();
        
        $reservation = Reservation::query()

        ->whereRelation('office' ,'user_id', '=' ,auth()->id())

        ->when(request('office_id'),
        fn ($query) => $query->where('office_id' , request('office_id'))

        ->when(request('user_id'),
        fn ($query) => $query->where('user_id' , request('user_id'))

        )->when(request('status'),
        fn ($query) => $query->where('status' , request('status'))

        //  filtering by date range , this part is important;
        )->when(request('from_date') && request('to_date'),
        fn( $query) => $query->BetweenDates(request('from_date'),request('to_date')))
        
        ->with(['office' , 'office.featuredImage'])
        ->paginate(5));

        return ReservationResource::collection($reservation);
    }
}
