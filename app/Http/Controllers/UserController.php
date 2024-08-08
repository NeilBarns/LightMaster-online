<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use App\Models\UserRoles;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function GetUsers()
    {
        $users = Users::with('roles')->get();
        return view('manage-users', compact('users'));
    }

    public function GetUser($userId)
    {
        $user = $userId == 0 ? new Users() : Users::with('roles')->findOrFail($userId);
        $roles = Roles::all();
        return view('user', compact('user', 'roles'));
    }

    public function InsertUser(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'user_name' => 'required|string',
            'password' => 'required|string',
            'roles' => 'required'
        ]);

        try {
            $user = Users::create([
                'FirstName' => $request->input('first_name'),
                'LastName' => $request->input('last_name'),
                'UserName' => $request->input('user_name'),
                'Password' => Hash::make($request->input('password')),
                'Active' => 1
            ]);

            $roles = json_decode($request->input('roles'), true);

            foreach ($roles as $roleId) {
                UserRoles::create([
                    'UserId' => $user->UserID,
                    'RoleId' => $roleId
                ]);
            }

            return response()->json(['success' => true, 'message' => 'User created successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving user: ' . $e->getMessage()
            ]);
        }
    }

    public function UpdateUser(Request $request, $userId)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'user_name' => 'required|string',
            'password' => 'required|string',
            'roles' => 'required'
        ]);

        try {
            $user = Users::findOrFail($userId);
            $user->FirstName = $request->input('first_name');
            $user->LastName = $request->input('last_name');
            $user->UserName = $request->input('user_name');
            $user->Password = $request->input('password');
            $user->save();

            $roles = json_decode($request->input('roles'), true);

            UserRoles::where('UserID', $userId)->delete();
            foreach ($roles as $roleId) {
                UserRoles::create([
                    'UserId' => $user->UserID,
                    'RoleId' => $roleId
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()]);
        }
    }

    public function DeleteUser($userId)
    {
        try {
            $user = Users::findOrFail($userId);
            // $user->roles()->delete();
            $user->roles()->detach();
            $user->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
        }
    }

    public function UserStatus($userId, $status)
    {
        try {
            $user = Users::findOrFail($userId);
            $user->Active = $status;
            $user->save(); // Save the changes to the database

            return response()->json(['success' => true, 'message' => 'User status changed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error on user status change: ' . $e->getMessage()]);
        }
    }
}
