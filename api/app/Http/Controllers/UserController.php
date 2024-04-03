<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $request['password']=Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $user = new User;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->save();
        return response()->json(['message' => 'Account aangemaakt! Je kan nu inloggen!']);
    }
    public function login(Request $request)
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
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
    public function user()
    {
        return response()->json(auth()->user());
    }
    public function update_user_data(Request $request)
    {
        $user = auth()->user();
        $user->gender = $request->gender;
        $user->weight = $request->weight;
        $user->height = $request->height;
        $user->age = $request->age;
        #height (m) squared divided by weight
        $user->bmi = number_format(($request->weight / (($request->height / 100) * ($request->height / 100))), 0,);
        $user->update();
        return response()->json(['message' => 'Successfully updated account', 'user' => $user]);
    }
    public function update_user_password(Request $request)
    {
        $user = auth()->user();
        if($request->password !== $request->password_confirmation)
        {
            return response()->json(['error' => 'Passwords do not match.'], 401);
        }
        auth()->logout();
        $user->password = Hash::make($request->password);
        $user->update();
        return response()->json(['message' => 'Successfully updated password. Please login again.', 'user' => $user]);
    }
}
