<?php

namespace App\Models;

use Database\Factories\RoleModulePermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleModulePermission extends Model
{
    /** @use HasFactory<RoleModulePermissionFactory> */
    use HasFactory;

    public const MODULE_ASSESSOR = 'assessor';

    public const MODULE_REVIEWER = 'reviewer';

    protected $fillable = ['role', 'module'];

    /**
     * @return array<string, string>
     */
    public static function modules(): array
    {
        return [
            self::MODULE_ASSESSOR => 'Assessor Module',
            self::MODULE_REVIEWER => 'Reviewer Module',
        ];
    }

    public static function roleCanAccessModule(string $role, string $module): bool
    {
        return self::query()
            ->where('role', $role)
            ->where('module', $module)
            ->exists();
    }
}
