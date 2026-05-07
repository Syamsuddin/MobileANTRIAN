<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Support\MobileApi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileBearerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('request_id', MobileApi::requestId($request));

        $plainToken = $request->bearerToken();
        if (! $plainToken) {
            return MobileApi::error($request, 'TOKEN_EXPIRED', 'Sesi berakhir. Silakan login kembali.', 401);
        }

        $token = ApiToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('revoked_at')
            ->first();

        if (! $token || ! $token->user) {
            return MobileApi::error($request, 'TOKEN_EXPIRED', 'Sesi berakhir. Silakan login kembali.', 401);
        }

        if ($token->user->role !== 'operator') {
            return MobileApi::error($request, 'ROLE_NOT_ALLOWED', 'Akses mobile hanya untuk operator.', 403);
        }

        if (! $token->user->is_active) {
            return MobileApi::error($request, 'USER_INACTIVE', 'Akun operator tidak aktif. Hubungi admin.', 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('mobile_user', $token->user);
        $request->attributes->set('mobile_token', $token);

        return $next($request);
    }
}
