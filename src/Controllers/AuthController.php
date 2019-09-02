<?php
/**
 * AuthController class.
 *
 * @package bookkeeper-api
 * @file /src/Controllers/AuthController.php
 * @author Claudio Varandas <claudiovarandas@boldint.com>
 * @since 0.1
 *
 */

namespace Bookkeeper\Controllers;

use Bookkeeper\Models\User;
use GuzzleHttp\Client;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AuthController extends ApiController
{
    use ThrottlesLogins, SendsPasswordResetEmails;

    protected $passwordBroker;

    protected $httpClient;

    public function __construct(
        PasswordBroker $passwordBroker
    ) {

        $this->passwordBroker = $passwordBroker;

        $this->httpClient = new Client();
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'email';
    }

    /**
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            $user = User::query()
                ->where(
                    [
                        $this->username() => $request->get('username'),
                    ]
                )->first();

            if (!$user) {
                throw new UnauthorizedHttpException("Credentials invalid / Unauthorized.");
            }

            if ($this->hasTooManyLoginAttempts($request)) {
                $this->fireLockoutEvent($request);
                $this->sendLockoutResponse($request);
            }

            $oauthClientData = $this->getOauthClient();

            if ($oauthClientData === null) {
                return $this->respond()->unauthorized();
            }

            $oauthRequest = Request::create('/oauth/token', 'POST', [
                'grant_type' => 'password',
                'client_id' => $oauthClientData->id,
                'client_secret' => $oauthClientData->secret,
                'username' => $request->input('username'),
                'password' => $request->input('password'),
                'scope' => '*',
            ]);

            $response = app()->handle($oauthRequest);
            $responseData = json_decode($response->getContent(), true);

            if ($response->getStatusCode() === 200) {
                $this->clearLoginAttempts($request);
                return response()->json($responseData);
            }

            $this->incrementLoginAttempts($request);
            throw new UnauthorizedHttpException($responseData['message']);

        } catch (ValidationException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('Internal server error.');
        }
    }

    public function logout()
    {
        $accessToken = auth()->user()->token();
        DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $accessToken->id)
            ->update([
                'revoked' => true
            ]);
        $accessToken->revoke();
        return $this->respond()->success();
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->input('refresh_token', false);

        if (!$refreshToken) {
            return $this->validationFailed("Refresh token is required.");
        }

        $oauthClientData = $this->getOauthClient();
        if (!$oauthClientData) {
            return $this->respond()->unauthorized();
        }

        $oauthRequest = Request::create('/oauth/token', 'POST', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $oauthClientData->id,
            'client_secret' => $oauthClientData->secret,
            'scope' => '*',
        ]);

        $response = app()->handle($oauthRequest);
        $responseData = json_decode($response->getContent(), true);
        if ($response->getStatusCode() !== 200) {
            return $this->respond()->internalServerError("Something went wrong.");
        }

        $user = auth()->user();
        $responseData['user'] = fractal()
            ->item($user)
            ->transformWith(new UserTransformer())
            ->toArray();
        return $this->respond()->success($responseData, 'Refreshed.');
    }

    /**
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->passwordBroker->reset(
            $credentials,
            function ($user, $password) {
                $user->password = $password;
                $user->status = StatusesService::STATUS_ACTIVE;
                $user->email_verified_at = now();
                $user->save();
                event(new PasswordReset($user));
            }
        );

        return $response == $this->passwordBroker::PASSWORD_RESET
            ? $this->respond()->success()
            : $this->respond()->validationFailed(trans($response));
    }

    public function forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->passwordBroker->sendResetLink(
            $request->only('email')
        );
        return $response == $this->passwordBroker::RESET_LINK_SENT
            ? $this->respond()->success([], "Your password has been reset, please check your inbox.")
            : $this->respond()->validationFailed(trans($response));
    }

    protected function getOauthClient()
    {
        $clientId = config('services.oauth.clients_id.mobile_apps');

        return $clientId !== null ?
            DB::table('oauth_clients')
                ->where('id', $clientId)
                ->where('revoked', 0)
                ->first(['id', 'secret']) :
            null;
    }
}
