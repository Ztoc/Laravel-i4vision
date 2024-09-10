<?php

namespace App\Http\Middleware;

use Closure;
use App\Permission;
use Illuminate\Http\Request;

class UserPermission 
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
   public function handle(Request $request, Closure $next)
{
      
	  $permission = Permission::where('module','user')->where('user_id',auth()->user()->id)->first();
	   echo $permission;
	   if($permission->create != 0){
	      return $next($request);
	   }
	   else{
		   
		   return redirect()->back()->with('flash_message','you are not allowed to access this');
	   }
   
	   
	   
    
}
}
