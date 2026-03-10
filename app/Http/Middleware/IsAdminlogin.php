<?php
namespace App\Http\Middleware;
use Closure;
use Session;
Use DB;
class IsAdminlogin
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
            $admin =  DB::table('admins')->where('id', Session::get('adminid'))->first();
            if($admin->activation_status==0)
            {
                return redirect('/admin/admins/logout');
            }
            return $next($request);
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