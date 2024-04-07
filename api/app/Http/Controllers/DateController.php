<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Date;
use App\Models\Date_activity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
class DateController  extends \Illuminate\Routing\Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function date(Request $request)
    {
        $date = Carbon::parse($request->date);
        $dates = Date::where('user_id', auth()->user()->id)->where('date', $date)->with('dates_activities', 'dates_meals', 'meals', 'nutritions', 'activities')->get();
        return $dates;
    }
    public function add_activity($request)
    {
        $date = Carbon::parse($request->date);
        $date_check = Date::where('date', Carbon::parse($date))->where('user_id', auth()->user()->id)->get();
        if(count($date_check) == 0 or count($date_check)  == null)
        {
            $new_date = new Date;
            $new_date->date = auth()->user()->weight;
            $new_date->date = $date;
            $new_date->save(); 
        }
        $activity = Activity::where('id', $request->activity_id)->first();
        $date_activity = new Date_activity;
        $date_activity->activity_id = $request->activity_id;
        $date_activity->kilometers = $request->kilometers;
        $date_activity->total_burned_cal = $request->kilometers * $activity->calories_per_km;
        $date_activity->save();
        return response()->json(['message' => 'Activiteit opgeslagen!']);
    }
    public function add_meal($date)
    {
        $date = Carbon::parse($date);
        $dates = Date::where(auth()->user()->id())->where('date', $date)->with('dates_activities', 'dates_meals', 'meals', 'nutritions', 'activities');
        return $dates;
    }
}
