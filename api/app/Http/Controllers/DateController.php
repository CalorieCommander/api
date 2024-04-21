<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Date;
use App\Models\Date_activity;
use App\Models\Date_meal;
use App\Models\Goal;
use App\Models\History;
use App\Models\Meal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DateController extends \Illuminate\Routing\Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function date(Request $request)
    {
        $date = Carbon::parse($request->date);
        $date_show = $date->format("d M Y");
        $date_format = $date->toDateString();
        $week_number = $date->weekOfYear();
        $diff = $date->diffInDays(Carbon::now());
        $diff_round = round($diff, 0);
        if ($diff_round < 1 and $diff_round > -1) {
            $date_text = "Vandaag";
        } elseif ($diff_round == 1) {
            $date_text = "Gisteren";
        } elseif ($diff_round == -1) {
            $date_text = "Morgen";
        } elseif ($date->lt(Carbon::now())) {
            $date_text = $diff_round . " dagen geleden";
        } else {
            $diff_new = ($diff_round * $diff_round) / 2;
            $date_text = $diff_new . " dagen vooruit";
        }

        $date = Date::where('user_id', auth()->user()->id)->where("date", $date_format)->first();
        if ($date !== null) {
            $date_activities = Date_activity::where('date_id', $date->id)->get();
            $date_meals = Date_meal::where('date_id', $date->id)->with("meal")->get();
        } else {
            $date_activities = [];
            $date_meals = [];
        }
        $histories = History::where('user_id', auth()->user()->id)->orderBy('created_at', 'desc')->get();
        $activities = Activity::all();
        $meals = Meal::all();
        $goal = Goal::where('user_id', auth()->user()->id)->first();
        $data = [
            "histories" => $histories,
            'date_show' => $date_show,
            "date_text" => $date_text,
            "week_number" => $week_number,
            "activities" => $activities,
            "meals" => $meals,
            "date_activities" => $date_activities,
            "date_meals" => $date_meals,
            "date" => $date,
            "goal" => $goal,
        ];
        return response()->json($data);
    }
    public function add_activity($request)
    {
        $date = Carbon::parse($request->date);
        $date_check = Date::where('date', Carbon::parse($date))->where('user_id', auth()->user()->id)->get();
        if (count($date_check) == 0 or count($date_check) == null) {
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
    public function search_meal(Request $request)
    {
        $response = Http::get('https://world.openfoodfacts.org/api/v2/search/' . $request->search_data);
        if ($response->successful()) {
            $data = json_decode($response->getBody());
            $employees = $data;
            return view('employees.index', compact('employees'));
        } else {
            return redirect()->back()->with('error', 'Could not gather data!');
        }
    }
}
