<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Governorate;
use App\Models\City;

class LocationController extends Controller
{
    public function governorates()
    {
      return response()->json(Governorate::select('id','name')->orderby('id','asc')->get());
    }
    public function cities( int $governorateid)
    {
       return response()->json(City::where('governorate_id',$governorateid)->select('id','name')->orderby('id','asc')->get());
    }
}
