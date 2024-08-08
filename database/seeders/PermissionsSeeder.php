<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('Permissions')->insert([
            //ADMIN/OVERALL
            ['PermissionName' => 'view_dashboard', 'Description' => 'Access to view the dashboard'],
            ['PermissionName' => 'all_access_to_device', 'Description' => 'Full access permission to devices'],
            ['PermissionName' => 'all_access_to_reports', 'Description' => 'Full access permission to all reports'],
            ['PermissionName' => 'all_access_to_users', 'Description' => 'Full access permission to manage user'],


            //DEVICE SPECIFIC
            ['PermissionName' => 'can_view_devices', 'Description' => 'Access to device management tab'],
            ['PermissionName' => 'can_view_device_details', 'Description' => 'Can view device details'],
            ['PermissionName' => 'can_control_device_time', 'Description' => 'Can start, extend, and end time for a device'],
            ['PermissionName' => 'can_trigger_free_light', 'Description' => 'Can trigger free light function'],
            ['PermissionName' => 'can_delete_device', 'Description' => 'Can delete device'],
            ['PermissionName' => 'can_disable_device', 'Description' => 'Can disable device'],
            ['PermissionName' => 'can_edit_device_base_time', 'Description' => 'Can edit/update device base time'],
            ['PermissionName' => 'can_add_device_increments', 'Description' => 'Can add/create device increments'],
            ['PermissionName' => 'can_disable_device_increments', 'Description' => 'Can disable device increments'],
            ['PermissionName' => 'can_delete_device_increments', 'Description' => 'Can delete device increments'],
            ['PermissionName' => 'can_view_device_specific_rate_usage_report', 'Description' => 'Can view specific device rate and usage report'],
            ['PermissionName' => 'can_view_device_specific_time_transaction_report', 'Description' => 'Can view specific device time transaction report'],


            //REPORTS
            // ['PermissionName' => 'access_to_reports_tab', 'Description' => 'Full access permission for user management'],
            // ['PermissionName' => 'view_all_reports', 'Description' => 'Permission to view all reports'],


            //SETTINGS
            // ['PermissionName' => 'access_to_device_management_tab', 'Description' => 'Full access permission for user management'],
            // ['PermissionName' => 'view_all_reports', 'Description' => 'Permission to view all reports'],
        ]);
    }



    // ('view_users', 'Permission to view user records.', '2023-06-01'),
    // ('create_users', 'Permission to create new user records.', '2023-06-17'),
    // ('update_users', 'Permission to update existing user records.', '2023-07-02'),
    // ('delete_users', 'Permission to delete user records.', '2023-07-22'),

    // ('manage_vehicles', 'Full CRUD permissions for vehicle management.', '2023-09-03'),
    // ('view_vehicles', 'Permission to view vehicle records.', '2023-09-17'),
    // ('create_vehicles', 'Permission to create new vehicle records.', '2023-10-01'),
    // ('update_vehicles', 'Permission to update existing vehicle records.', '2023-10-16'),
    // ('delete_vehicles', 'Permission to delete vehicle records.', '2023-11-02'),



    // ('manage_owners', 'Full CRUD permissions for owner management.', '2023-09-03'),
    // ('view_owners', 'Permission to view owner records.', '2023-09-17'),
    // ('create_owners', 'Permission to create new owner records.', '2023-10-01'),
    // ('update_owners', 'Permission to update existing owner records.', '2023-10-16'),
    // ('delete_owners', 'Permission to delete owner records.', '2023-11-02'),
}
