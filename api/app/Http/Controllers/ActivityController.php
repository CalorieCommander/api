<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Date;
use App\Models\Date_activity;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function add(Request $request)
    {
        $activity = Activity::where('id', $request->activity_id)->first();
        $date_parse = Carbon::parse($request->date);
        $date_format = $date_parse->toDateString();
        $date = Date::where('user_id', auth()->user()->id)->where("date", $date_format)->first();
        if ($date == null) {
            $date = new Date;
            $date->user_id = auth()->user()->id;
            $date->user_weight = auth()->user()->weight;
            $date->date = $date_parse;
            $date->save();
        }
        $date_activity = new Date_activity;
        $date_activity->date_id = $date->id;
        $date_activity->activity_id = $activity->id;
        $date_activity->kilometers = $request->kilometers;
        $date_activity->total_burned_cal = $request->kilometers * $activity->calories_per_km;
        $date_activity->save();
        $date->calories_burned = $date->calories_burned + ($request->kilometers * $activity->calories_per_km);
        $date->update();
        return $date_parse;
    }
    public function edit(Request $request)
    {
        $date_activity = Date_activity::where('id', $request->date_activity_id)->first();
        $date_activity->kilometers = $request->kilometers;
        $date_activity->total_burned_cal = $request->kilometers * $date_activity->activity->calories_per_km;
        $date_activity->update();
        return redirect()->back()->with('success', 'Activiteit is aangepast!');
    }
    public function remove(Request $request)
    {
        $date_activity = Date_activity::where('id', $request->activity_id)->first();
        $date = Date::where('id', $date_activity->date_id)->first();
        $date->calories_burned = $date->calories_burned - $date_activity->total_burned_cal;
        $date->update();
        $date_activity->delete();
        return redirect()->back()->with('success', 'Activiteit is van datum verwijderd!');
    }
}
