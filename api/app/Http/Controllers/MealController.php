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

class MealController extends Controller
{

    public function search(Request $request)
    {
        $response = Http::get('https://world.openfoodfacts.org/cgi/search.pl?search_terms=' . $request->search_data . '&search_simple=1&action=process&json=1');
        if ($response->successful()) {
            $data = json_decode($response->getBody());
            $products = $data->products;
            $history = new History;
            $history->data = $request->search_data;
            $history->user_id = auth()->user()->id;
            $history->save();
            $histories = History::where('user_id', auth()->user()->id)->get();
            if(count($histories) > 5)
            {
                $delete = History::orderBy('created_at', 'asc')->first();
                $delete->delete();
            }
            $products = collect($products);
            $filtered_products = $products->filter(function ($product) {
                return is_object($product->nutriments) && !empty((array) $product->nutriments);
            });
            $searched_data = [
                "products" => $filtered_products,
            ];

            return $searched_data;
        } else {
            return response()->json('wattafak');
        }
    }
    public function search_nutriments(Request $request)
    {
        $response = Http::get('https://world.openfoodfacts.net/api/v2/product/' . $request->barcode);
        if ($response->successful()) {
            $data = json_decode($response->getBody());
            return $data;
        } else {
            return response()->json('wattafak');
        }
    }
    public function add(Request $request)
    {
        $date_parse = Carbon::parse($request->date);
        $date_format = $date_parse->toDateString();
        $date = Date::where('user_id', auth()->user()->id)->where('date', $date_format)->first();
        if ($date == null) {
            $date = new Date;
            $date->user_id = auth()->user()->id;
            $date->user_weight = auth()->user()->weight;
            $date->date = $date_parse;
            $date->save();

        }
        $meal = Meal::where('barcode', $request->barcode)->first();
        if ($meal == null) {
            $meal = new Meal;
            $response = Http::get('https://world.openfoodfacts.net/api/v2/product/'.$request->barcode);
            if ($response->successful()) {
                $data = json_decode($response->getBody());
                $product = $data->product;
                $name = $product->product_name;

                $meal_nutrients = $product->nutriments;
                $calories_per_gram = $product->nutriments->{'energy-kcal_100g'} / 100;

                $brand = $product->brands;
                $meal = new Meal;
                $meal->name = $name;
                $meal->brand = $brand;
                $meal->barcode = $request->barcode;
                $meal->calories_per_gram = $calories_per_gram;
                $meal->nutrition_grade = $product->nutrition_grades;

                $meal->save();
                $nutriments = new Nutrient;
                $nutriments->meal_id = $meal->id;
                $nutriments->sugar_100g = $meal_nutrients->sugars_100g;
                $nutriments->salt_100g = $meal_nutrients->salt_100g;
                $nutriments->fat_100g = $meal_nutrients->fat_100g;
                $nutriments->carbohydrates_100g = $meal_nutrients->carbohydrates_100g;
                $nutriments->proteins_100g = $meal_nutrients->proteins_100g;
                $nutriments->save();

            } else {
                return response()->json('wattafak');
            }
        }
        $date_meal = new Date_meal;
        $date_meal->date_id = $date->id;
        $date_meal->meal_id = $meal->id;;
        $date_meal->calories_total = round($meal->calories_per_gram * 100);
        $date_meal->save();
        $date->calories_consumed = Date_meal::where('date_id', $date->id)->sum("calories_total");
        $date->update();
        return response()->json('Toppie');
    }
    public function edit(Request $request)
    {
        $date_meal = Date_meal::where('id', $request->date_meal_id)->first();
        $date_meal->grams = $request->grams;
        $date_meal->calories_total = round($request->grams * $date_meal->meal->calories_per_gram, 0);
        $date_meal->update();
        $date = Date::where('id', $date_meal->date_id)->first();
        //$date->update();

    }
    public function remove(Request $request)
    {
        $date_meal = Date_meal::where('id', $request->date_meal_id)->first();
        $date = Date::where('id', $date_meal->date_id)->first();
        $date_meal->delete();
        $date->calories_consumed = Date_meal::where('date_id', $date->id)->sum("calories_total");
        $date->update();
        if(count(Date_meal::where('date_id', $date->id)->get()) == 0)
        {
            $date->delete();
        }
    }
}
