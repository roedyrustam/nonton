<?php

namespace App\Http\Middleware;
use Jenssegers\Agent\Agent;
use Closure;
use Illuminate\Support\Str;
use App\Setting;
use Illuminate\Support\Facades\Hash;



class Decrypter extends Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
     
    
    public function handle($request, Closure $next, ...$guards)
    {

        
        $package = $request->header('packagename');

        $password = $request->header('password');

        $token = $request->bearerToken();
 
         $agent = new Agent();
 
         $settings = Setting::first();
    
   
     if($agent->isAndroidOS() && env('PACKAGE_NAME') == $package) {
 
      return $next($request);
     
      } else {
 
      return response()->json(404, 401);
             
    }

   }
}
