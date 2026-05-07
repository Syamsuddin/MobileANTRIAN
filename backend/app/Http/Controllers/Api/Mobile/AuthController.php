<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\ApiToken;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Mobile\OperatorStateService;
use App\Support\MobileApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends MobileController
{
    public function login(Request $request, AuditLogger $auditLogger, OperatorStateService $stateService): JsonResponse
    {
        try {
            $payload = $this->validatePayload($request, [
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
                'device' => ['nullable', 'array'],
                'device.installation_id' => ['nullable', 'string', 'max:128'],
                'device.platform' => ['nullable', 'string', 'max:32'],
                'device.app_version' => ['nullable', 'string', 'max:32'],
                'device.device_name' => ['nullable', 'string', 'max:128'],
            ]);
        } catch (ValidationException $exception) {
            return $this->validationError($request, $exception);
        }

        $user = User::query()->where('email', $payload['email'])->first();

        if (! $user || ! Hash::check($payload['password'], $user->password)) {
            return $this->errorLogin($request, 'INVALID_CREDENTIALS', 'Email atau password salah.', 401);
        }

        if ($user->role !== 'operator') {
            return $this->errorLogin($request, 'ROLE_NOT_ALLOWED', 'Akses mobile hanya untuk operator.', 403);
        }

        if (! $user->is_active) {
            return $this->errorLogin($request, 'USER_INACTIVE', 'Akun operator tidak aktif. Hubungi admin.', 403);
        }

        $plainToken = str()->random(80);
        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'mobile',
            'token_hash' => hash('sha256', $plainToken),
            'device' => $payload['device'] ?? [],
        ]);

        $user->forceFill(['last_login_at' => now()])->save();
        $auditLogger->log($user, 'auth.login', User::class, $user->id, [
            'device' => $payload['device'] ?? [],
            'request_id' => $request->attributes->get('request_id'),
        ], $request->attributes->get('request_id'));

        return $this->success($request, [
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
            'assignment' => $stateService->state($user)['assignment'],
        ]);
    }

    public function logout(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $user = $request->attributes->get('mobile_user');
        $token = $request->attributes->get('mobile_token');

        $token?->forceFill(['revoked_at' => now()])->save();
        $auditLogger->log($user, 'auth.logout', User::class, $user?->id, [], $request->attributes->get('request_id'));

        return $this->success($request, ['success' => true]);
    }

    public function me(Request $request, OperatorStateService $stateService): JsonResponse
    {
        $user = $request->attributes->get('mobile_user');

        return $this->success($request, [
            'user' => $this->userPayload($user),
            'assignment' => $stateService->state($user)['assignment'],
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }

    private function errorLogin(Request $request, string $code, string $message, int $status): JsonResponse
    {
        return MobileApi::error($request, $code, $message, $status);
    }
}
