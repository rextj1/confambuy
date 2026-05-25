<?php

namespace App\Listeners;

use App\Models\StaffAllow;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Spatie\Permission\Models\Role;

class AssignRoleAfterEmailVerified
{
    /**
     * Handle the event.
     */
    public function handle(Verified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if ($user->hasRole('admin')) {
            return;
        }

        $normalizedEmail = strtolower(trim($user->email));
        $isAllowlistedStaffEmail = StaffAllow::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
            ->exists();

        $roleName = $isAllowlistedStaffEmail ? 'staff' : 'customer';

        Role::findOrCreate($roleName, 'web');
        $user->syncRoles([$roleName]);
    }
}
