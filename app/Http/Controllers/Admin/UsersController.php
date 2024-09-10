<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use App\User;
use App\Client;
use App\Permission;

class UsersController extends Controller
{
    const PAGE_NAME = "users";

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

		$current_admin = auth()->user();
        //$users = $current_admin->users;
        $users = User::where('client_id',$current_admin->client_id)->get();
        return view('admin/users/index', ['users' => $users, 'page_name' => self::PAGE_NAME]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $clients = Client::orderBy('name')->get();
        return view('admin/users/create', ['clients' => $clients, 'page_name' => self::PAGE_NAME]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

		//return $request->usercreate;

        $validator = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => 'required|string|max:255',
            //'gender' => 'required|boolean|max:255',
            'status' => 'required|boolean|max:255',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);


        $current_admin = auth()->user();


        $user = new User($request->all());
        $user->status = $request->status;
        $user->type = $request->type;
        $user->password = Hash::make($request->password);
        $user->client_id = $current_admin->client_id;
        $user->user_id = auth()->user()->id;
        $user->save();

		$user_id = User::latest('id')->first();
		$usershow = 0;
		$usercreate = 0;
		$useredit = 0;
		$userdelete = 0;
		if($request->usershow != ""){ $usershow = 1;}
		if($request->usercreate != ""){$usercreate = 1;}
		if($request->useredit != ""){$useredit = 1;}
		if($request->userdelete != ""){$userdelete = 1;}
		$permission = new Permission();
		$permission->user_id = $user_id->id;
		$permission->module = 'user';
		$permission->show  = $usershow;
		$permission->create  = $usercreate;
		$permission->edit  = $useredit;
		$permission->delete  = $userdelete;
		$permission->self  = auth()->user()->client_id;
		$permission->save();

		$deviceshow = 0;
		$devicecreate = 0;
		$deviceedit = 0;
		$devicedelete = 0;
		$devicepreview= 0;
		if($request->deviceshow != ""){ $deviceshow = 1;}
		if($request->devicecreate != ""){$devicecreate = 1;}
		if($request->deviceedit != ""){$deviceedit = 1;}
		if($request->devicedelete != ""){$devicedelete = 1;}
		if($request->devicepreview != ""){$devicepreview = 1;}
		$permission = new Permission();
		$permission->user_id = $user_id->id;
		$permission->module = 'device';
		$permission->show  = $deviceshow;
		$permission->create  = $devicecreate;
		$permission->edit  = $deviceedit;
		$permission->delete  = $devicedelete;
		$permission->preview  = $devicepreview;
		$permission->self  = auth()->user()->client_id;
		$permission->save();


		$layoutshow = 0;
		$layoutcreate = 0;
		$layoutedit = 0;
		$layoutdelete = 0;
		if($request->layoutshow != ""){ $layoutshow = 1;}
		if($request->layoutcreate != ""){$layoutcreate = 1;}
		if($request->layoutedit != ""){$layoutedit = 1;}
		if($request->layoutdelete != ""){$layoutdelete = 1;}
		$permission = new Permission();
		$permission->user_id = $user_id->id;
		$permission->module = 'layout';
		$permission->show  = $layoutshow;
		$permission->create  = $layoutcreate;
		$permission->edit  = $layoutedit;
		$permission->delete  = $layoutdelete;
		$permission->self  = auth()->user()->client_id;
		$permission->save();

		$imagesshow = 0;
		$imagescreate = 0;
		$imagesedit = 0;
		$imagesdelete = 0;
		$imagespreview = 0;
		$imagesclone = 0;
		if($request->imagesshow != ""){ $imagesshow = 1;}
		if($request->imagescreate != ""){$imagescreate = 1;}
		if($request->imagesedit != ""){$imagesedit = 1;}
		if($request->imagesdelete != ""){$imagesdelete = 1;}
		if($request->imagespreview != ""){$imagespreview = 1;}
		if($request->imageclone != ""){$imagesclone = 1;}
		$permission = new Permission();
		$permission->user_id = $user_id->id;
		$permission->module = 'images';
		$permission->show  = $imagesshow;
		$permission->create  = $imagescreate;
		$permission->edit  = $imagesedit;
		$permission->delete  = $imagesdelete;
		$permission->preview  = $imagespreview;
		$permission->clone  = $imagesclone;
		$permission->self  = auth()->user()->client_id;
		$permission->save();


		$galleriesshow = 0;
		$galleriescreate = 0;
		$galleriesedit = 0;
		$galleriesdelete = 0;
		$galleriespreview = 0;
		$galleriesclone = 0;
		if($request->galleriesshow != ""){ $galleriesshow = 1;}
		if($request->galleriescreate != ""){$galleriescreate = 1;}
		if($request->galleriesedit != ""){$galleriesedit = 1;}
		if($request->galleriesdelete != ""){$galleriesdelete = 1;}
		if($request->galleriespreview != ""){$galleriespreview = 1;}
		if($request->galleriesclone != ""){$galleriesclone = 1;}
		$permission = new Permission();
		$permission->user_id = $user_id->id;
		$permission->module = 'galleries';
		$permission->show  = $galleriesshow;
		$permission->create  = $galleriescreate;
		$permission->edit  = $galleriesedit;
		$permission->delete  = $galleriesdelete;
		$permission->preview  = $galleriesdelete;
		$permission->clone  = $galleriesdelete;
		$permission->self  = auth()->user()->client_id;
		$permission->save();

		$sitesshow = 0;
		$sitescreate = 0;
		$sitesedit = 0;
		$sitesdelete = 0;
		$sitespreview = 0;
		$sitesclone = 0;
		if($request->sitesshow != ""){ $sitesshow = 1;}
		if($request->sitescreate != ""){$sitescreate = 1;}
		if($request->sitesedit != ""){$sitesedit = 1;}
		if($request->sitesdelete != ""){$sitesdelete = 1;}
		if($request->sitespreview != ""){$sitespreview = 1;}
		if($request->sitesclone != ""){$sitesclone = 1;}
		$permission = new Permission();
		$permission->user_id = $user_id->id;
		$permission->module = 'sites';
		$permission->show  = $sitesshow;
		$permission->create  = $sitescreate;
		$permission->edit  = $sitesedit;
		$permission->delete  = $sitesdelete;
		$permission->preview  = $sitespreview;
	    $permission->clone  = $sitesclone;
		$permission->self  = auth()->user()->client_id;
		$permission->save();


		$schedulesshow = 0;
		$schedulescreate = 0;
		$schedulesedit = 0;
		$schedulesdelete = 0;
		$schedulespreview = 0;
		$schedulesclone = 0;
		if($request->schedulesshow != ""){ $schedulesshow = 1;}
		if($request->schedulescreate != ""){$schedulescreate = 1;}
		if($request->schedulesedit != ""){$schedulesedit = 1;}
		if($request->schedulesdelete != ""){$schedulesdelete = 1;}
		if($request->schedulespreview != ""){$schedulespreview = 1;}
		if($request->schedulesclone != ""){$schedulesclone = 1;}
		$permission = new Permission();
		$permission->user_id = $user_id->id;
		$permission->module = 'schedules';
		$permission->show  = $schedulesshow;
		$permission->create  = $schedulescreate;
		$permission->edit  = $schedulesedit;
		$permission->delete  = $schedulesdelete;
		$permission->preview  = $schedulespreview;
		$permission->clone  = $schedulesclone;
		$permission->self  = auth()->user()->client_id;
		$permission->save();

		$videosshow = 0;
		$videoscreate = 0;
		$videosedit = 0;
		$videosdelete = 0;
		$videospreview = 0;
		$videosclone = 0;
		if($request->videosshow != ""){ $videosshow = 1;}
		if($request->videoscreate != ""){$videoscreate = 1;}
		if($request->videosedit != ""){$videosedit = 1;}
		if($request->videosdelete != ""){$videosdelete = 1;}
		if($request->videospreview != ""){$videospreview = 1;}
		if($request->videosclone != ""){$videosclone = 1;}
		$permission = new Permission();
		$permission->user_id = $user_id->id;
		$permission->module = 'videos';
		$permission->show  = $videosshow;
		$permission->create  = $videoscreate;
		$permission->edit  = $videosedit;
		$permission->delete  = $videosdelete;
		$permission->preview  = $videospreview;
		$permission->clone  = $videosclone;
		$permission->self  = auth()->user()->client_id;
		$permission->save();

		$flowsshow = 0;
		$flowscreate = 0;
		$flowsedit = 0;
		$flowsdelete = 0;
		$flowspreview = 0;
		$flowsclone = 0;
		if($request->flowsshow != ""){ $flowsshow = 1;}
		if($request->flowscreate != ""){$flowscreate = 1;}
		if($request->flowsedit != ""){$flowsedit = 1;}
		if($request->flowsdelete != ""){$flowsdelete = 1;}
		if($request->flowspreview != ""){$flowspreview = 1;}
		if($request->flowsclone != ""){$flowsclone = 1;}
		$permission = new Permission();
		$permission->user_id = $user_id->id;
		$permission->module = 'flows';
		$permission->show  = $flowsshow;
		$permission->create  = $flowscreate;
		$permission->edit  = $flowsedit;
		$permission->delete  = $flowsdelete;
		$permission->preview  = $flowspreview;
		$permission->clone  = $flowsclone;
		$permission->self  = auth()->user()->client_id;
		$permission->save();

        return redirect(route('admin.users'))->with('success', 'A new user was created.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::find($id);
        $clients = Client::orderBy('name')->get();

		$permission = Permission::where('user_id',$id)->get();

        if (empty($user)) {
            return redirect(route('admin.users'))->with('warning', 'warning.');
        }

        return view('admin/users/edit', ['user' => $user, 'permission' => $permission,'clients' => $clients, 'page_name' => self::PAGE_NAME]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'email' => [
                            'required',
                            'string',
                            'email',
                            'max:255',
                            Rule::unique('users')->ignore($id),
                        ],
            'phone' => 'required|string|max:255',
           // 'gender' => 'required|boolean|max:255',
            'status' => 'required|boolean|max:255',
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $current_admin = auth()->user();
        $user = User::find($id);
        $user->fill($request->all());
        $user->type = $request->type;
        $user->status = $request->status;
        $user->client_id = $current_admin->client_id;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

		$user_permission = Permission::where('user_id',$id)->where('module','user')->first();
		$device_permission = Permission::where('user_id',$id)->where('module','device')->first();
		$layout_permission = Permission::where('user_id',$id)->where('module','layout')->first();
		$images_permission = Permission::where('user_id',$id)->where('module','images')->first();
		$galleries_permission = Permission::where('user_id',$id)->where('module','galleries')->first();
		$sites_permission = Permission::where('user_id',$id)->where('module','sites')->first();
		$schedules_permission = Permission::where('user_id',$id)->where('module','schedules')->first();
		$videos_permission = Permission::where('user_id',$id)->where('module','videos')->first();
		$flows_permission = Permission::where('user_id',$id)->where('module','flows')->first();

		//return $user_permission->id;


		$usershow = 0;
		$usercreate = 0;
		$useredit = 0;
		$userdelete = 0;
		if($request->usershow != ""){ $usershow = 1;}
		if($request->usercreate != ""){$usercreate = 1;}
		if($request->useredit != ""){$useredit = 1;}
		if($request->userdelete != ""){$userdelete = 1;}
		if($user_permission){
            $permission =  Permission::find($user_permission->id);
            $permission->show  = $usershow;
            $permission->create  = $usercreate;
            $permission->edit  = $useredit;
            $permission->delete  = $userdelete;
            $permission->self  = auth()->user()->client_id;
            $permission->save();
        }

		$deviceshow = 0;
		$devicecreate = 0;
		$deviceedit = 0;
		$devicedelete = 0;
		$devicepreview= 0;
		if($request->deviceshow != ""){ $deviceshow = 1;}
		if($request->devicecreate != ""){$devicecreate = 1;}
		if($request->deviceedit != ""){$deviceedit = 1;}
		if($request->devicedelete != ""){$devicedelete = 1;}
		if($request->devicepreview != ""){$devicepreview = 1;}
        if($device_permission){
            $permission =  Permission::find($device_permission->id);
            $permission->show  = $deviceshow;
            $permission->create  = $devicecreate;
            $permission->edit  = $deviceedit;
            $permission->delete  = $devicedelete;
            $permission->preview  = $devicepreview;
            $permission->self  = auth()->user()->client_id;
            $permission->save();
        }


		$layoutshow = 0;
		$layoutcreate = 0;
		$layoutedit = 0;
		$layoutdelete = 0;
		if($request->layoutshow != ""){ $layoutshow = 1;}
		if($request->layoutcreate != ""){$layoutcreate = 1;}
		if($request->layoutedit != ""){$layoutedit = 1;}
		if($request->layoutdelete != ""){$layoutdelete = 1;}


        if($layout_permission){
            $permission = Permission::find($layout_permission->id);
            $permission->show  = $layoutshow;
            $permission->create  = $layoutcreate;
            $permission->edit  = $layoutedit;
            $permission->delete  = $layoutdelete;
            $permission->self  = auth()->user()->client_id;
            $permission->save();
        }


		$imagesshow = 0;
		$imagescreate = 0;
		$imagesedit = 0;
		$imagesdelete = 0;
		$imagespreview = 0;
		$imagesclone = 0;
		if($request->imagesshow != ""){ $imagesshow = 1;}
		if($request->imagescreate != ""){$imagescreate = 1;}
		if($request->imagesedit != ""){$imagesedit = 1;}
		if($request->imagesdelete != ""){$imagesdelete = 1;}
		if($request->imagespreview != ""){$imagespreview = 1;}
		if($request->imageclone != ""){$imagesclone = 1;}
		if($images_permission){
            $permission =  Permission::find($images_permission->id);
            $permission->show  = $imagesshow;
            $permission->create  = $imagescreate;
            $permission->edit  = $imagesedit;
            $permission->delete  = $imagesdelete;
            $permission->preview  = $imagespreview;
            $permission->clone  = $imagesclone;
            $permission->self  = auth()->user()->client_id;
            $permission->save();
        }


		$galleriesshow = 0;
		$galleriescreate = 0;
		$galleriesedit = 0;
		$galleriesdelete = 0;
		$galleriespreview = 0;
		$galleriesclone = 0;
		if($request->galleriesshow != ""){ $galleriesshow = 1;}
		if($request->galleriescreate != ""){$galleriescreate = 1;}
		if($request->galleriesedit != ""){$galleriesedit = 1;}
		if($request->galleriesdelete != ""){$galleriesdelete = 1;}
		if($request->galleriespreview != ""){$galleriespreview = 1;}
		if($request->galleriesclone != ""){$galleriesclone = 1;}
        if($galleries_permission){
            $permission =  Permission::find($galleries_permission->id);
            $permission->show  = $galleriesshow;
            $permission->create  = $galleriescreate;
            $permission->edit  = $galleriesedit;
            $permission->delete  = $galleriesdelete;
            $permission->preview  = $galleriesdelete;
            $permission->clone  = $galleriesdelete;
            $permission->self  = auth()->user()->client_id;
            $permission->save();
        }

		$sitesshow = 0;
		$sitescreate = 0;
		$sitesedit = 0;
		$sitesdelete = 0;
		$sitespreview = 0;
		$sitesclone = 0;
		if($request->sitesshow != ""){ $sitesshow = 1;}
		if($request->sitescreate != ""){$sitescreate = 1;}
		if($request->sitesedit != ""){$sitesedit = 1;}
		if($request->sitesdelete != ""){$sitesdelete = 1;}
		if($request->sitespreview != ""){$sitespreview = 1;}
		if($request->sitesclone != ""){$sitesclone = 1;}
		if($sites_permission){
            $permission =  Permission::find($sites_permission->id);
            $permission->show  = $sitesshow;
            $permission->create  = $sitescreate;
            $permission->edit  = $sitesedit;
            $permission->delete  = $sitesdelete;
            $permission->preview  = $sitespreview;
            $permission->clone  = $sitesclone;
            $permission->self  = auth()->user()->client_id;
            $permission->save();
        }


		$schedulesshow = 0;
		$schedulescreate = 0;
		$schedulesedit = 0;
		$schedulesdelete = 0;
		$schedulespreview = 0;
		$schedulesclone = 0;
		if($request->schedulesshow != ""){ $schedulesshow = 1;}
		if($request->schedulescreate != ""){$schedulescreate = 1;}
		if($request->schedulesedit != ""){$schedulesedit = 1;}
		if($request->schedulesdelete != ""){$schedulesdelete = 1;}
		if($request->schedulespreview != ""){$schedulespreview = 1;}
		if($request->schedulesclone != ""){$schedulesclone = 1;}
		if($schedules_permission){
            $permission =  Permission::find($schedules_permission->id);
            $permission->show  = $schedulesshow;
            $permission->create  = $schedulescreate;
            $permission->edit  = $schedulesedit;
            $permission->delete  = $schedulesdelete;
            $permission->preview  = $schedulespreview;
            $permission->clone  = $schedulesclone;
            $permission->self  = auth()->user()->client_id;
            $permission->save();
        }

		$videosshow = 0;
		$videoscreate = 0;
		$videosedit = 0;
		$videosdelete = 0;
		$videospreview = 0;
		$videosclone = 0;
		if($request->videosshow != ""){ $videosshow = 1;}
		if($request->videoscreate != ""){$videoscreate = 1;}
		if($request->videosedit != ""){$videosedit = 1;}
		if($request->videosdelete != ""){$videosdelete = 1;}
		if($request->videospreview != ""){$videospreview = 1;}
		if($request->videosclone != ""){$videosclone = 1;}
		if($videos_permission){
            $permission =  Permission::find($videos_permission->id);
            $permission->show  = $videosshow;
            $permission->create  = $videoscreate;
            $permission->edit  = $videosedit;
            $permission->delete  = $videosdelete;
            $permission->preview  = $videospreview;
            $permission->clone  = $videosclone;
            $permission->self  = auth()->user()->client_id;
            $permission->save();
        }

		$flowsshow = 0;
		$flowscreate = 0;
		$flowsedit = 0;
		$flowsdelete = 0;
		$flowspreview = 0;
		$flowsclone = 0;
		if($request->flowsshow != ""){ $flowsshow = 1;}
		if($request->flowscreate != ""){$flowscreate = 1;}
		if($request->flowsedit != ""){$flowsedit = 1;}
		if($request->flowsdelete != ""){$flowsdelete = 1;}
		if($request->flowspreview != ""){$flowspreview = 1;}
		if($request->flowsclone != ""){$flowsclone = 1;}
        if($videos_permission){
            $permission =  Permission::find($flows_permission->id);
            $permission->show  = $flowsshow;
            $permission->create  = $flowscreate;
            $permission->edit  = $flowsedit;
            $permission->delete  = $flowsdelete;
            $permission->preview  = $flowspreview;
            $permission->clone  = $flowsclone;
            $permission->self  = auth()->user()->client_id;
            $permission->save();
        }






		//return $flowsedit;
        return redirect(url('admin/users/' . $id . '/edit'))->with('success', 'A user was updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        User::destroy($id);

        return redirect(url('admin/users'))->with('success', 'A user was deleted.');
    }
}
