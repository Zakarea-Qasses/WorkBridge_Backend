<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Governorate;
use App\Models\City;

class LocationController extends Controller
{
    public function governorates(Request $request)
    {
      $query = Governorate::select('id','name')->orderby('id','asc');

      if ($request->boolean('with_cities')) {
        $query->with(['cities:id,governorate_id,name']);
      }

      return response()->json($query->get());
    }

    public function cities( int $governorateid)
    {
       return response()->json(City::where('governorate_id',$governorateid)->select('id','name')->orderby('id','asc')->get());
    }

    public function allCities(Request $request)
    {
       $cities = City::with('governorate:id,name')
          ->when($request->governorate_id, fn ($query, $governorateId) => $query->where('governorate_id', $governorateId))
          ->select('id','governorate_id','name')
          ->orderby('name','asc')
          ->get();

       return response()->json($cities);
    }

    public function city(int $id)
    {
       return response()->json(
          City::with('governorate:id,name')->select('id','governorate_id','name')->findOrFail($id)
       );
    }
}
