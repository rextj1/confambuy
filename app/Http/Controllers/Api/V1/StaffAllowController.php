<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreStaffAllowRequest;
use App\Models\StaffAllow;
use App\Notifications\StaffRegistrationInvite;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * @group Staff Allowlist
 *
 * Manage registration allowlisted staff emails.
 */
class StaffAllowController extends Controller
{
    /**
     * Add email to allowlist.
     *
     * @authenticated
     */
    public function store(StoreStaffAllowRequest $request): JsonResponse
    {
        $registrationUrl = $this->registrationUrl();

        $staffAllow = DB::transaction(function () use ($request, $registrationUrl): StaffAllow {
            $staffAllow = StaffAllow::query()->create($request->validated());

            Notification::route('mail', $staffAllow->email)
                ->notify(new StaffRegistrationInvite($staffAllow->email, $registrationUrl));

            return $staffAllow;
        });

        return response()->json([
            'message' => 'Staff email added to allowlist.',
            'data' => [
                'id' => $staffAllow->id,
                'email' => $staffAllow->email,
                'invite_sent' => true,
            ],
        ], 201);
    }

    /**
     * Remove email from allowlist.
     *
     * @authenticated
     */
    public function destroy(StaffAllow $staffAllow): JsonResponse
    {
        $staffAllow->delete();

        return ApiResponse::message('Staff email removed from allowlist.');
    }

    protected function registrationUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/register';
    }
}
