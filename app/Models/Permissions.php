<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permissions extends Model
{
    use HasFactory;

    protected $fillable = [
        'PermissionId',
        'PermissionName',
        'Description'
    ];

    public $timestamps = false;

    protected $primaryKey = 'PermissionId';
}
