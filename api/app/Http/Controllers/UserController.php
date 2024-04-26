<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Goal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends \Illuminate\Routing\Controller
{
    //API middleware op elke functie in deze controller
    //Hier vallen login en register niet onder zodat een gebruiker hier wel kan komen zonder ingelogd te zijn
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    //Functie voor het registeren van een nieuwe gebruiker
    public function register(RegisterRequest $request)
    {
        //Wachtwoord hashen
        $request['password'] = Hash::make($request['password']);
        //User instantie aanvragen en data invullen
        $user = new User;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->save();

        return response()->json(['message' => 'Account aangemaakt! Je kan nu inloggen!']);
    }
    //Functie voor het inloggen van een bestaande gebruiker
    public function login(LoginRequest $request)
    {
        //Kijken of de ingevoerde gegevens gelijk staan aan de database
        $credentials = request(['email', 'password']);
        if (!$token = auth()->attempt($credentials)) {
            //Als verificatie faalt wordt er een 401 error gegooid
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        //Als verificatie slaagt wordt er een JWT-token teruggegeven
        return $this->respondWithToken($token);
    }
    //Functie voor het uitloggen van een ingelogde gebruiker
    public function logout(Request $request)
    {
        //Doormiddel van de meegegeven token kan Laravel automatisch de gebruiker uitloggen (token invalideren)
        auth()->logout();
        return response()->json(['message' => 'Uitgelogd!']);
    }
    //Functie voor het genereren van een JWT-token
    protected function respondWithToken($token)
    {
        //Token wordt gegenereerd door een composer package, dit gebeurd automatisch
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user(),
        ]);
    }
    //Functie voor het teruggeven van de user data van de ingelogde user
    public function user()
    {
        //Pak ingelogde user data en bijbehorende goal en geef deze terug
        $data = [
            'user' => auth()->user(),
            'goal' => Goal::where('user_id', auth()->user()->id)->first(),
        ];
        return $data;
    }
    //Functie voor het updaten van een user
    public function update_user_data(Request $request)
    {
        //Allemaal if == null checks omdat we anders errors krijgen...
        $user = auth()->user();
        if ($request->gender == null) {
            $gender = $user->gender;
        }
        else
        {
            $gender = $request->gender;
        }
        if ($request->age == null) {
            $age = $user->age;
        }
        else
        {
            $age = $request->age;
        }
        if ($request->weight == null) {
            $weight = $user->weight;
        }
        else
        {
            $weight = $request->weight;
        }
        if ($request->height == null) {
            $height = ($user->height / 100);
            $height_cm = $user->height;
        }
        else
        {
            $height = ($request->height / 100);
            $height_cm = $request->height;
        }
        //User data invullen
        $user->gender = $gender;
        $user->height = $height_cm;
        $user->weight = $weight;
        $user->age = $age;
        $user->bmi = $weight / ($height * $height);
        $user->update();
        //Zoeken naar goal van bijbehorende user
        $goal = Goal::where('user_id', auth()->user()->id)->first();
        //Als goal niet bestaat maken we een nieuwe aan
        if ($goal == null) {
            $goal = new Goal();
            $goal->user_id = auth()->user()->id;
            //Datum parsen...
            $goal->date = Carbon::parse($request->goal_date);
            $today = now()->startOfDay();
            $target_date = Carbon::parse($request->goal_date)->startOfDay();
            //Dagen uitrekenen die we nog hebben totdat de doel-datum berijkt is
            $days_remaining = $today->diffInDays($target_date);
            $goal->goal_weight = $request->goal_weight;
            //bmr uitrekenen voor man of vrouw
            if ($request->gender == "Man") {
                $bmr = round(66 + (13.7 * $weight) + (5 * $height) - (6.8 * $age), 1);
                
            }
            else{
                $bmr = round(655 + (9.6 * $weight) + (1.8 * $height) - (4.7 * $age), 1);
            }
            //tdee uitrekenen
            $tdee = $bmr * 1.375;
            //Uitrekenen hoeveel calorieën we in totaal minder moeten eten
            $calorie_deficit = ($weight - $request->goal_weight) * 7700;
            //Dagelijkse calorieën uitrekenen om ons doel binnen de gegeven datum te halen
            $goal->daily_calories = round($tdee + ($calorie_deficit / $days_remaining), 0);
            $goal->save();
        }
        //Hetzelfde riedeltje maar dan voor wanneer $goal wel bestaat 
        else {
            //Meer if == null checks om errors te voorkomen
            if ($request->goal_date == null) {
                $goal_date = $goal->goal_date;
            }
            else
            {
                $goal_date = $request->goal_date;
            }
            if ($request->goal_weight == null) {
                $goal_weight = $goal->weight;
            }
            else
            {
                $goal_weight = $request->goal_weight;
            }
            $goal->date = Carbon::parse($goal_date);
            $today = now()->startOfDay();
            $target_date = Carbon::parse($request->goal_date)->startOfDay();
            $days_remaining = $today->diffInDays($target_date);
            $goal->goal_weight = $goal_weight;
            if ($request->gender == "Man") {
                $bmr = round((10 * $weight) + (6.25 * ($height * 100)) - (5 * $age) + 5, 1);
                
            }
            else{
                $bmr = round((10 * $weight) + (6.25 * ($height * 100)) - (5 * $age) - 161, 1);
            }
            $tdee = $bmr * 1.375;
            $calorie_deficit = ($weight - $goal_weight) * 7700;
            $goal->daily_calories = round($tdee - ($calorie_deficit / $days_remaining), 0);
            $goal->update();
        }
        return response()->json(['message' => 'User data geüpdate!']);
    }
    //Functie voor het updaten van het wachtwoord van de gebruiker
    public function update_user_password(ChangePasswordRequest $request)
    {
        //Pak gebruiker
        $user = auth()->user();
        //Gebruiker uitloggen (gebruiker wordt gevraagd om opnieuw in te loggen als wachtwoord veranderd)
        auth()->logout();
        $user->password = Hash::make($request->password);
        $user->update();
        //Geef bevestiging en user data terug
        return response()->json(['message' => 'Wachtwoord geüpdate. Log alstublieft opnieuw in.', 'user' => $user]);
    }
}
