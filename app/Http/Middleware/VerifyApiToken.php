<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $authorization = $request->header('Authorization');

        if (!$authorization || !preg_match('/Basic\s+(.*)$/i', $authorization, $matches)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authorization header missing'
            ], 401, ['WWW-Authenticate' => 'Basic']);
        }

        $decoded = base64_decode($matches[1]);
        [$username, $password] = explode(':', $decoded, 2);

        if (
            $username !== API_BASIC_USER ||
            $password !== API_BASIC_PASS
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid username or password'
            ], 401, ['WWW-Authenticate' => 'Basic']);
        }

        return $next($request);
    }
}
