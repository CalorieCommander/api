<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Date;
use App\Models\Date_activity;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityController extends \Illuminate\Routing\Controller
{
    //API middleware op elke functie in deze controller
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    //Functie voor toevoegen van activiteit aan een datum
    public function add(Request $request)
    {
        //Pak activiteit uit database gebaseerd op meegegeven id
        $activity = Activity::where('id', $request->activity_id)->first();
        $date_parse = Carbon::parse($request->date);
        $date_format = $date_parse->toDateString();
        //Checken of er een datum bestaat voor de huidige dag bij de huidige user
        $date = Date::where('user_id', auth()->user()->id)->where("date", $date_format)->first();
        //Als er geen datum bestaat, maak een nieuwe aan voor huidige datum
        if ($date == null) {
            $date = new Date;
            $date->user_id = auth()->user()->id;
            $date->user_weight = auth()->user()->weight;
            $date->date = $date_parse;
            $date->save();
        }
        //Nieuw koppeltabel record voor maaltijden en activiteiten
        $date_activity = new Date_activity;
        $date_activity->date_id = $date->id;
        $date_activity->activity_id = $activity->id;
        $date_activity->kilometers = $request->kilometers;
        //Verbrande calorieën uitrekenen via kilometers en calorieën per kilometer van de activiteit
        $date_activity->total_burned_cal = $request->kilometers * $activity->calories_per_km;
        $date_activity->save();
        //Herberekenen verbrande calorieën voor de datum. Dit wordt gedaan door het verzamelen van alle date_activities met de date_id van onze date.
        //Hiervan wordt van elke record de "total_burned_cal" opgeteld.
        $date->calories_burned = Date_activity::where('date_id', $date->id)->sum("total_burned_cal");
        $date->update();
        return response()->json(['message' => 'Activiteit opgeslagen!']);
    }
    //Functie voor verwijderen van activiteit aan een datum
    public function remove(Request $request)
    {
        //Pak de date_activity van de meegegeven id
        $date_activity = Date_activity::where('id', $request->activity_id)->first();
        //Pak ook de date die vastzit aan de date_activity
        $date = Date::where('id', $date_activity->date_id)->first();
        //Herberekenen verbrande calorieën
        $date->calories_burned = Date_activity::where('date_id', $date->id)->sum("total_burned_cal");
        $date->update();
        //Datum updaten en koppelrecord verwijderen
        $date_activity->delete();
        return response()->json(['message' => 'Activiteit verwijderd!']);
    }
}
