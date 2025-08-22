<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users|max:255',
            'phoneNumber' => 'required|string|unique:users|min:11|max:11',
            'password' => 'required|max:40',
            'role' => 'required|max:225',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create($request->all());
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|exists:users',
            'password' => 'required|max:40',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'The Password is incorrect',
            ], 422);
        }

        $token = $user->createToken($user->email);

        return response()->json([
            'status' => true,
            'message' => 'User Logged In successfully',
            'data' => $user,
            'token' => $token->plainTextToken
        ], 201);
    }

    public function getUser()
    {
        return response()->json([
            'status' => true,
            'data' => User::all(),
        ]);
    }

    public function getLoggedInUser(Request $request)
    {
        $user = $request->user();

        if ($user->role_id === 1) {

            $currentUser = User::with([
                'driver' => function ($query) {
                    $query->select('*');
                }
            ])->where('id', $user->id)
                ->get(['id', 'phone_number', 'email']);
        } elseif ($user->role_id === 0) {

            $currentUser = User::with([
                'hitchhiker' => function ($query) {
                    $query->select('*');
                }
            ])->where('id', $user->id)
                ->get(['id', 'phone_number', 'email']);
        }

        return response()->json([
            'status' => true,
            'data' => $currentUser,
        ]);
    }

    public function setPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|max:40|regex:/^(?=.*[0-9])(?=.*[\W_]).*$/',
            'confirmPassword' => 'required|same:password',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        $setPassword = User::findOrFail($user->id);
        $setPassword->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Passwrod set sucessfully',
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldPassword' => 'required|string|min:8|max:40',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:40',
                'regex:/^(?=.*[0-9])(?=.*[\W_]).*$/'
            ],
            'confirmPassword' => 'required|same:password',
        ], [
            'oldPassword.required' => 'Old password is required.',
            'oldPassword.min' => 'Old password must be at least 8 characters.',
            'password.required' => 'New password is required.',
            'password.min' => 'New password must be at least 8 characters.',
            'password.regex' => 'New password must contain at least 1 number and 1 special character.',
            'confirmPassword.required' => 'Confirm password is required.',
            'confirmPassword.same' => 'Confirm password must match new password.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->oldPassword, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Old password is incorrect',
            ], 422);
        }

        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'New password cannot be the same as the old password',
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully',
        ], 200);
    }

    public function destroy($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->tokens()->delete();

        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully'
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'User logged out successfully'
        ], 204);
    }
}
