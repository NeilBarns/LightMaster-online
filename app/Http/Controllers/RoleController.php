<?php

namespace App\Http\Controllers;

use App\Models\Permissions;
use App\Models\RolePermissions;
use App\Models\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function GetRoles()
    {
        $roles = Roles::all();
        return view('manage-roles', compact('roles'));
    }

    public function GetRole($roleId)
    {
        $role = $roleId == 0 ? new Roles() : Roles::with('rolePermissions')->findOrFail($roleId);
        $permissions = Permissions::all();
        return view('role', compact('role', 'permissions'));
    }

    public function InsertRole(Request $request)
    {
        $request->validate([
            'role_name' => 'required|string',
            'permissions' => 'required'
        ]);

        try {
            $role = Roles::create([
                'RoleName' => $request->input('role_name'),
                'Description' => $request->input('role_description')
                // 'CreatedByUserID' => auth()->id() // Assuming you have authentication set up
            ]);

            $permissions = json_decode($request->input('permissions'), true);

            foreach ($permissions as $permissionId) {
                RolePermissions::create([
                    'RoleID' => $role->RoleID,
                    'PermissionID' => $permissionId,
                    'CreatedByUserID' => auth()->id()
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Role created successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving role: ' . $e->getMessage()
            ]);
        }
    }

    public function DeleteRole($roleId)
    {
        try {
            $role = Roles::findOrFail($roleId);
            $role->rolePermissions()->delete();
            $role->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting role: ' . $e->getMessage()]);
        }
    }

    public function UpdateRole(Request $request, $roleId)
    {
        $request->validate([
            'role_name' => 'required|string',
            'permissions' => 'required'
        ]);

        try {
            $role = Roles::findOrFail($roleId);
            $role->RoleName = $request->input('role_name');
            $role->Description = $request->input('role_description');
            $role->save();

            $permissions = json_decode($request->input('permissions'), true);

            RolePermissions::where('RoleID', $roleId)->delete();
            foreach ($permissions as $permissionId) {
                RolePermissions::create([
                    'RoleID' => $role->RoleID,
                    'PermissionID' => $permissionId,
                    // 'CreatedByUserID' => auth()->id() // or another user ID reference
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating role: ' . $e->getMessage()]);
        }
    }
}
