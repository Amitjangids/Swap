<?php
namespace App\Http\Middleware;
use Closure;
use Session;
class IsSubadminlogin
{
    /**
     * Handle an incoming request.
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     * 
     */
    public function handle($request, Closure $next){        
        if (Session::has('adminid')){ 
            if (Session::get('admin_usertype') == 'Admin'){
                return $next($request);
            }else{
                return redirect('/admin/admins/dashboard');
            }
        }else{
            return redirect('/admin/admins/login');
        }
        
        /********* get current controllr and action
         *  $action= Route::getFacadeRoot()->current()->getAction();
        $controller = class_basename($action['controller']);
        list($controller, $action) = explode('@', $controller);
        echo $action;exit;
        exit;
         */
    }
}