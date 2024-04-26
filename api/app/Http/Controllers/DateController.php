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
    //API middleware op elke functie in deze controller
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    //Functie om de geselecteerde datum te pakken en alle data voor het dagoverzicht mee te nemen
    public function date(Request $request)
    {
        //De meegegeven datum uit de request parsen en formatten zodat we ermee kunnen werken
        $date = Carbon::parse($request->date);
        $date_show = $date->format("d M Y");
        $date_format = $date->toDateString();
        //Weeknummer pakken doormiddel van functie
        $week_number = $date->weekOfYear();
        //Dagen verschil uitrekenen (voor als user in de toekomst of het verleden kijkt)
        $diff = $date->diffInDays(Carbon::now());
        $diff_round = round($diff, 0);
        //Paar if-else statements om de goede tekst te pakken bij de datum die de user bekijkt (vergeleken met de huidige datum)
        if ($diff_round < 1 and $diff_round > -1) {
            $date_text = "Vandaag";
        } elseif ($diff_round == 1) {
            $date_text = "Gisteren";
        } elseif ($diff_round == -1) {
            $date_text = "Morgen";
        } elseif ($date->lt(Carbon::now())) {
            $date_text = $diff_round . " dagen geleden";
        } else {
            $diff_new = -$diff_round;
            $date_text = $diff_new . " dagen vooruit";
        }
        //Kijken of er een "date" instantie bestaat met de meegegeven datum
        $date = Date::where('user_id', auth()->user()->id)->where("date", $date_format)->first();
        //Als $date bestaat dan worden de date_activities en $date_meals opgevraagd
        if ($date !== null) {
            $date_activities = Date_activity::where('date_id', $date->id)->get();
            $date_meals = Date_meal::where('date_id', $date->id)->with("meal")->get();
        }
        //Als $date niet bestaat zijn date_activities en date_meal lege arrays 
        //Dit wordt gedaan omdat de front-end anders errors geeft dat $date_activities of $date_meals niet bestaan
        else {
            $date_activities = [];
            $date_meals = [];
        }
        //Histories (eerdere zoekopdrachten van gebruiker) ophalen en sorteren op aflopende datum
        $histories = History::where('user_id', auth()->user()->id)->orderBy('created_at', 'desc')->get();
        //Activiteiten, maaltijden en goals ophalen (benodigdheden front-end)
        $activities = Activity::all();
        $meals = Meal::all();
        $goal = Goal::where('user_id', auth()->user()->id)->first();
        //Alle opgehaalde data in een array zetten zodat het makkelijk meegegeven kan worden
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
    //Functie om maaltijd toe te voegen aan datum
    public function add_activity($request)
    {
        //Datum parsen zodat we ermee kunnen werken
        $date = Carbon::parse($request->date);
        //Kijken of er een date record bestaat
        $date_check = Date::where('date', Carbon::parse($date))->where('user_id', auth()->user()->id)->get();
        //Als $date_check niet bestaat, maak een date record aan om mee te werken
        if (count($date_check) == 0 or count($date_check) == null) {
            $new_date = new Date;
            $new_date->date = auth()->user()->weight;
            $new_date->date = $date;
            $new_date->save();
        }
        //Activiteit ophalen via id
        $activity = Activity::where('id', $request->activity_id)->first();
        //Date_activity record aanmaken
        $date_activity = new Date_activity;
        $date_activity->activity_id = $request->activity_id;
        $date_activity->kilometers = $request->kilometers;
        //Verbande calorieën uitrekenen door gesportte kilometers keer calorieën per kilometer
        $date_activity->total_burned_cal = $request->kilometers * $activity->calories_per_km;
        $date_activity->save();
        //Na het opslaan een confirmation meesturen
        return response()->json(['message' => 'Activiteit opgeslagen!']);
    }
}
