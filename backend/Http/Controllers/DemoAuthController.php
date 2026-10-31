<?php

namespace Modules\Demo\Http\Controllers;

use App\Exceptions\Entities\AuthorizationException;
use App\Http\Transformers\AuthTokenTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Demo\Commands\DemoReset;
use Modules\Demo\Support\DemoAccounts;
use Throwable;

class DemoAuthController extends Controller
{
    /**
     * @api             {get} /demo/info Demo info
     * @apiDescription  Demo accounts available for the passwordless login and the time of the next reset
     *
     * @apiSuccess {String[]}  data.accounts       Keys of the accounts that can sign in, in the selector order
     * @apiSuccess {ISO8601}   data.next_reset_at  When the demo data is reset next
     */
    public function info(): JsonResponse
    {
        return responder()->success([
            'accounts' => DemoAccounts::available(),
            'next_reset_at' => DemoReset::nextResetAt()->toIso8601String(),
        ])->respond();
    }

    /**
     * @api             {post} /demo/login Demo login
     * @apiDescription  Sign in as a demo account without a password; the response matches /auth/login
     *
     * @apiParam {String}  role  Account key from /demo/info
     *
     * @apiUse          AuthToken
     * @throws Throwable
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['role' => ['required', 'string', Rule::in(DemoAccounts::keys())]]);

        $user = DemoAccounts::find($data['role']);

        throw_unless($user, new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED));
        throw_unless($user->active, new AuthorizationException(AuthorizationException::ERROR_TYPE_USER_DISABLED));

        auth()->setUser($user);

        return responder()->success([
            'token' => $user->createToken(Str::uuid())->plainTextToken,
        ], new AuthTokenTransformer)->respond();
    }
}
