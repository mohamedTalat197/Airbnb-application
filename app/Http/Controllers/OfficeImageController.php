<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Office;
use App\Models\User;
use App\Models\Image;
use App\Http\Resources\ImageResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;


class OfficeImageController extends Controller
{
    public function store(Office $office)
    {
        abort_unless(auth()->user()->tokenCan('office.update'),
        Response::HTTP_FORBIDDEN
        );

        $this->authorize('update',$office);

        request()->validate([
            'image' => ['file', 'max:5000', 'mimes:jpg,png']
        ]);

        $path = request()->file('image')->storePublicly('/',['disk'=>'public']);

        $image = $office->images()->create([
            'path'=> $path
        ]);

        return ImageResource::make($image);
    }

    public function delete(Office $office , Image $image)
    {
        abort_unless(auth()->user()->tokenCan('office.update'),
        Response::HTTP_FORBIDDEN
        );

        $this->authorize('update',$office);

        // throw_if($image->resource_type !== 'office' || $image->resource_id !== $office->id,
        // ValidationException::withMessages(['image'=> 'Can Not Delete This Image..!'])
        // );

        throw_if($office->images()->count() == 1,
        ValidationException::withMessages(['image'=> 'Can Not Delete The Only Image..!'])
        );

        throw_if($office->featured_image_id == $image->id,
        ValidationException::withMessages(['image'=> 'Can Not Delete The Featured Image..!'])
        );

        Storage::disk('public')->delete($image->path);
        $image->delete();
    }
}
