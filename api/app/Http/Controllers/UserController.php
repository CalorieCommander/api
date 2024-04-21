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
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    public function register(RegisterRequest $request)
    {
        $request['password'] = Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $user = new User;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->save();

        return response()->json(['message' => 'Account aangemaakt! Je kan nu inloggen!']);
    }
    public function login(LoginRequest $request)
    {
        $credentials = request(['email', 'password']);
        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }
    public function logout(Request $request)
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user(),
        ]);
    }
    public function user()
    {
        $data = [
            'user' => auth()->user(),
            'goal' => Goal::where('user_id', auth()->user()->id)->first(),
        ];
        return $data;
    }
    public function update_user_data(Request $request)
    {
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
        }
        else
        {
            $height = ($request->height / 100);
        }
        $user->bmi = $weight / ($height * $height);
        $goal = Goal::where('user_id', auth()->user()->id)->first();
        if ($goal == null) {
            $goal = new Goal();
            $goal->user_id = auth()->user()->id;
            $goal->date = Carbon::parse($request->goal_date);
            $today = now()->startOfDay();
            $target_date = Carbon::parse($request->goal_date)->startOfDay();
            $days_remaining = $today->diffInDays($target_date);
            $goal->goal_weight = $request->goal_weight;
            if ($request->gender == "Man") {
                $bmr = round(66 + (13.7 * $weight) + (5 * $height) - (6.8 * $age), 1);
                
            }
            else{
                $bmr = round(655 + (9.6 * $weight) + (1.8 * $height) - (4.7 * $age), 1);
            }
            $tdee = $bmr * 1.375;
            $calorie_deficit = ($weight - $request->goal_weight) * 7700;
            $goal->daily_calories = round($tdee + ($calorie_deficit / $days_remaining), 0);
            $goal->save();
        } else {
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
        $user->update();
    }
    public function update_user_password(ChangePasswordRequest $request)
    {
        $user = auth()->user();
        if ($request->password !== $request->password_confirmation) {
            return response()->json(['error' => 'Passwords do not match.'], 401);
        }
        auth()->logout();
        $user->password = Hash::make($request->password);
        $user->update();
        return response()->json(['message' => 'Successfully updated password. Please login again.', 'user' => $user]);
    }
    public function add_admin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_admin' => 'required',
            'user_id' => 'required|exists:users,id',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        if (auth()->user()->is_admin !== 1) {

        }
        $user = User::where('id', $request->user_id)->first();
        $user->is_admin = $request->is_admin;
        $user->update();
        return response()->json(['message' => 'Succesfully updated admin permissions.', 'user' => $user]);
    }
}
