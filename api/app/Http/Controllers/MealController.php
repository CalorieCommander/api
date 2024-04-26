<?php

namespace App\Http\Controllers;

use App\Models\Date;
use App\Models\Date_meal;
use App\Models\History;
use App\Models\Meal;
use App\Models\Nutrient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class MealController extends \Illuminate\Routing\Controller
{
    //API middleware op elke functie in deze controller
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    //Functie voor het zoeken naar maaltijden
    public function search(Request $request)
    {
        //Externe API #1 vragen voor alle producten waar de naam gelijk is aan wat de gebruiker ingetypt heeft
        $response = Http::get('https://world.openfoodfacts.org/cgi/search.pl?search_terms=' . $request->search_data . '&search_simple=1&action=process&json=1');
        if ($response->successful()) {
            $data = json_decode($response->getBody());
            //Producten uit response data halen
            $products = $data->products;
            //Zoekopdracht opslaan in database
            $history = new History;
            $history->data = $request->search_data;
            $history->user_id = auth()->user()->id;
            $history->save();
            //Aantal zoekopdrachten checken
            $histories = History::where('user_id', auth()->user()->id)->get();
            if (count($histories) >= 5) {
                //Als er meer dan of 5 zoekopdrachten zijn dan wordt de oudste verwijderd
                $delete = History::orderBy('created_at', 'asc')->first();
                $delete->delete();
            }
            //Maak een collection van de array products zodat we hem kunnen filteren
            $products = collect($products);
            //Alleen de producten pakken die voedselwaarden hebben (sommige producten staan inde API zonder voedselwaarden)
            $filtered_products = $products->filter(function ($product) {
                return is_object($product->nutriments) && !empty ((array) $product->nutriments);
            });
            //Array maken om op te sturen
            $searched_data = [
                "products" => $filtered_products,
            ];
            return $searched_data;
        } else {
            return response()->json(['message' => 'Er is iets fout gegaan!']);
        }
    }
    //Functie om voedselwaarden van een maaltijd op te halen (nadat de user op toevoegen klikt in het zoekscherm)
    public function search_nutriments(Request $request)
    {
        //Front-end applicatie geeft barcode terug, die wordt opgezocht via externe API #2
        $response = Http::get('https://world.openfoodfacts.net/api/v2/product/' . $request->barcode);
        if ($response->successful()) {
            $data = json_decode($response->getBody());
            //Voedselwaarden teruggeven
            return $data;
        } else {
            return response()->json(['message' => 'Er is iets fout gegaan!']);
        }
    }
    //Functie om een maaltijd aan een datum toe te voegen
    public function add(Request $request)
    {
        //Datum parsen om mee te werken
        $date_parse = Carbon::parse($request->date);
        $date_format = $date_parse->toDateString();
        //Checken of date al in database bestaat
        $date = Date::where('user_id', auth()->user()->id)->where('date', $date_format)->first();
        //Nieuwe date instantie aanmaken als dit niet zo is
        if ($date == null) {
            $date = new Date;
            $date->user_id = auth()->user()->id;
            $date->user_weight = auth()->user()->weight;
            $date->date = $date_parse;
            $date->save();
        }
        //Kijken of maaltijd al in database opgeslagen staat
        $meal = Meal::where('barcode', $request->barcode)->first();
        //Zo niet dan wordt er een nieuwe meal instantie gemaakt voor de geselecteerde maaltijd
        if ($meal == null) {
            $meal = new Meal;
            //Externe API om voedselwaarden binnen te halen
            $response = Http::get('https://world.openfoodfacts.net/api/v2/product/' . $request->barcode);
            if ($response->successful()) {
                $data = json_decode($response->getBody());
                //Verschillende waardes uit response data los declaren zodat ermee werken makkelijker is
                $product = $data->product;
                $name = $product->product_name;
                $meal_nutrients = $product->nutriments;
                $calories_per_gram = $product->nutriments->{'energy-kcal_100g'} / 100;
                $brand = $product->brands;

                //Maaltijd instantie aanmaken en invullen met data uit API
                $meal = new Meal;
                $meal->name = $name;
                $meal->brand = $brand;
                $meal->barcode = $request->barcode;
                $meal->calories_per_gram = $calories_per_gram;
                $meal->nutrition_grade = $product->nutrition_grades;
                $meal->save();

                //Voedselwaarden instantie aanmaken, vullen met data uit API en koppellen aan eerder gemaakte maaltijd
                $nutriments = new Nutrient;
                $nutriments->meal_id = $meal->id;
                $nutriments->sugar_100g = $meal_nutrients->sugars_100g;
                $nutriments->salt_100g = $meal_nutrients->salt_100g;
                $nutriments->fat_100g = $meal_nutrients->fat_100g;
                $nutriments->carbohydrates_100g = $meal_nutrients->carbohydrates_100g;
                $nutriments->proteins_100g = $meal_nutrients->proteins_100g;
                $nutriments->save();

            } else {
                return response()->json(['message' => 'Er is iets fout gegaan!']);
            }
        }
        //Nieuwe instantie van koppeltabel record aanmaken
        $date_meal = new Date_meal;
        $date_meal->date_id = $date->id;
        $date_meal->meal_id = $meal->id;
        //Uitrekenen hoeveel calorieën de maaltijd bevat
        $date_meal->calories_total = round($meal->calories_per_gram * 100);
        $date_meal->save();
        //Alle koppeltabel records pakken maar de date_id gelijk is aan de huidige datum
        //Hiervan worden alle calories_total (per maaltijd) opgeteld zodat je een uiteindelijk totaal hebt van de hele dag
        $date->calories_consumed = Date_meal::where('date_id', $date->id)->sum("calories_total");
        $date->update();
        return response()->json(['message' => 'Maaltijd opgeslagen!']);
    }
    public function remove(Request $request)
    {
        //Date_meal koppel record en datum opvragen
        $date_meal = Date_meal::where('id', $request->date_meal_id)->first();
        $date = Date::where('id', $date_meal->date_id)->first();
        //Eerst koppel record verwijderen in verband met foreign keys
        $date_meal->delete();
        //Totaal aantal calorieën herberekenen
        $date->calories_consumed = Date_meal::where('date_id', $date->id)->sum("calories_total");
        $date->update();
        return response()->json(['message' => 'Maaltijd verwijderd!']);
    }
}
