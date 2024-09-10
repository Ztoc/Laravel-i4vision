<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use App\Client;
use App\User;
use App\Permission;
use File;

class ClientsController extends Controller
{
    const PAGE_NAME = "clients";

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkforsuperadmin');

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $clients = Client::orderBy('name')->get();

        return view('admin/clients/index', ['clients' => $clients, 'page_name' => self::PAGE_NAME]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin/clients/create', ['page_name' => self::PAGE_NAME]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = $request->validate([
            'name' => ['required', 'string', 'max:20', 'unique:clients'],
            'status' => 'required|boolean|max:255',
        ]);

		$folder =  strtolower(str_replace(' ', '-', $request->name));
		$path = public_path().'/css/'.$folder;
        File::makeDirectory($path, $mode = 0777, true, true);
	     $myfile = fopen(public_path()."/css/".$folder."/client.css", "w") or die("Unable to open file!");
           $txt = $request->layout_flow;
           fwrite($myfile, $txt);
           fclose($myfile);


        $client = new Client();
        $client->name = $request->name;
        $client->description = $request->description;
        $client->address = $request->address;
		$client->LayoutFlow = $request->layout_flow;
		$client->LayoutGoogle = $request->layout_google;
		$client->supervisor_email = $request->supervisor_email;

        $client->status = $request->status;

        $client->save();

         // Create client Folder
		$path = public_path().'/css/'.$request->name;
        File::makeDirectory($path, $mode = 0777, true, true);

        // Copy client.css to client Folder
        $filename = public_path().'/css/client.css';
        File::copy($filename,$path.'/client.css');


        return redirect(route('admin.clients'))->with('success', 'A new client was created.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $is_users_of_client_active = 0)
    {
        $client = Client::find($id);


        $folder =  strtolower(str_replace(' ', '-', $client->name));


        // Copy client.css to client Folder
	/*	$path = public_path().'/css/'.$folder;
        File::makeDirectory($path, $mode = 0777, true, true);
        $filename = public_path().'/css/client.css';
        File::copy($filename,$path.'/client.css');
    */

        if (empty($client)) {
            return redirect(route('admin.clients'))->with('warning', 'warning.');
        }

        return view('admin/clients/edit', ['client' => $client, 'is_users_of_client_active' => $is_users_of_client_active, 'page_name' => self::PAGE_NAME]);
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
            'name' => [
                            'required',
                            'string',
                            'max:20',
                            Rule::unique('clients')->ignore($id),
                        ],
            'status' => 'required|boolean|max:255',
        ]);


		$old_folder =  strtolower(str_replace(' ', '', $request->old_name));
        $folder =  strtolower(str_replace(' ', '', $request->name));
        if(File::exists(public_path().'/css/'.$request->old_name)) {
            // path exist
            $path = public_path().'/css/'.$folder;
            $old_path = public_path().'/css/'.$request->old_name;
        }else{
            $path = public_path().'/css/'.$folder;
		    $old_path = public_path().'/css/'.$old_folder;
        }
        //dd($request->old_name);
        rename($old_path, $path);



        /*File::makeDirectory($path, $mode = 0777, true, true);
	     $myfile = fopen(public_path()."/css/".$folder."/client.css", "w") or die("Unable to open file!");
           $txt = $request->layout_flow;
           fwrite($myfile, $txt);
           fclose($myfile);
        */

        $client = Client::find($id);

        $client->name = $request->name;
        $client->description = $request->description;
        $client->address = $request->address;
        $client->status = $request->status;
		$client->LayoutFlow = $request->layout_flow;
		$client->LayoutGoogle = $request->layout_google;
		$client->supervisor_email = $request->supervisor_email;

        $client->save();
        // if ($request->password) {
        //     $client->password = Hash::make($request->password);
        // }

        return redirect(url('admin/clients/' . $id . '/edit/is_users_of_client_tab_active'))->with('success', 'A client was updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Client::destroy($id);
        return redirect(url('admin/clients'))->with('success', 'A client was deleted.');
    }

	public function store_user_of_client(Request $request, $client_id, $is_users_of_client_active) {
        $validator = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => 'required|string|max:255',
            'gender' => 'required|boolean|max:255',
            'status' => 'boolean|max:255',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);


        $user = new User($request->all());
        $user->status = $request->status;
        $user->password = Hash::make($request->password);
        $user->type = 'admin';
        $user->client_id = $client_id;
        $user->save();


         // Create client Folder
        $username =  strtolower(str_replace(' ', '', $request->username));
		$path = public_path().'/css/'.$username;
        File::makeDirectory($path, $mode = 0777, true, true);
        // Copy client.css to client Folder
        $filename = public_path().'/css/client.css';
        File::copy($filename,$path.'/client.css');



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
		$permission->self  = $client_id;
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
		$permission->self  = $client_id;;
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
		$permission->self  = $client_id;;
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
		$permission->self  = $client_id;
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
		$permission->self  = $client_id;
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
		$permission->self  = $client_id;
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
		$permission->self  = $client_id;
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
		$permission->self  = $client_id;
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
		$permission->self  = $client_id;
		$permission->save();

        return redirect(url('admin/clients/' . $client_id . '/edit/is_users_of_client_tab_active/' . $is_users_of_client_active))->with('success', 'A new user was created.');
    }


    public function get_user($client_id, $user_id) {
        return response()->json([
            'user' => User::find($user_id),
        ]);
    }

    public function get_user_permission($client_id, $user_id) {
        $permission = Permission::where('user_id',$user_id)->get();
        $user = User::find($user_id);
        if($permission->isNotEmpty()){
            $html ='';
        foreach($permission as $per){
            if($per->module == "user"){ $active = 'active';}else{$active = '';}
            if($per->show != 0) { $show_checked ='checked';}else{$show_checked ='';}
            if($per->create != 0) { $create_checked ='checked';}else{$create_checked ='';}
            if($per->edit != 0) { $edit_checked ='checked';}else{$edit_checked ='';}
            if($per->delete != 0) { $delete_checked ='checked';}else{$delete_checked ='';}
            if($per->preview != 0) { $preview_checked ='checked';}else{$preview_checked ='';}
            if($per->clone != 0) { $clone_checked ='checked';}else{$clone_checked ='';}
            $html .= '<div class="tab-pane '.$active.'" id="'.$per->module.'">
            <div class="col-md-12">
                 <div class="checkbox p-2">
                  <label>

                   <input type="checkbox" '.$show_checked.' id="'.$per->module.'show" name="'.$per->module.'show" runat="server"> show
                  </label>
                     <label>
                   <input type="checkbox" '.$create_checked.' id="'.$per->module.'create" name="'.$per->module.'create" runat="server"> Create
                  </label>

                     <label>
                   <input type="checkbox" '.$edit_checked.' id="'.$per->module.'edit" name="'.$per->module.'edit" runat="server"> Edit
                  </label>

                  <label>
                   <input type="checkbox" '.$delete_checked.'  id="'.$per->module.'delete" name="'.$per->module.'delete" runat="server"> Delete
                  </label>';

                if($per->module != "user" && $per->module != "layout"){

                $html .= ' <label>
                      <input type="checkbox" '.$preview_checked.' id="'.$per->module.'preview" name="'.$per->module.'preview" runat="server"> Preview
                  </label>

                     <label>
                      <input type="checkbox" '.$clone_checked.' id="'.$per->module.'clone" name="'.$per->module.'clone" runat="server"> Clone
                  </label>';

                }

                $html .= '    </div>
              </div>

          </div>';

        }
    }else{
        $html ='<div class="tab-pane active" id="user">
        <div class="col-md-12">
             <div class="checkbox p-2">
              <label>
               <input type="checkbox" id="usershow" name="usershow" runat="server"> show
              </label>
                 <label>
               <input type="checkbox" id="usercreate" name="usercreate" runat="server"> Create
              </label>

                 <label>
               <input type="checkbox" id="useredit" name="useredit" runat="server"> Edit
              </label>

                 <label>
               <input type="checkbox" id="userdelete" name="userdelete" runat="server"> Delete
              </label>
              </div>
          </div>

      </div>

      <div class="tab-pane" id="device">
         <div class="col-md-12">
             <div class="checkbox p-2">
              <label>
               <input type="checkbox" id="deviceshow" name="deviceshow" runat="server"> show
              </label>
                 <label>
               <input type="checkbox" id="devicecreate" name="devicecreate" runat="server"> Create
              </label>

                 <label>
               <input type="checkbox" id="deviceedit" name="deviceedit" runat="server"> Edit
              </label>

                 <label>
               <input type="checkbox" id="devicedelete" name="devicedelete" runat="server"> Delete
              </label>

                 <label>
               <input type="checkbox" id="devicepreview" name="devicepreview" runat="server"> Preview
              </label>

              </div>
          </div>

      </div>
      <div class="tab-pane" id="layout">
          <div class="col-md-12">
             <div class="checkbox p-2">
             <label>
               <input type="checkbox" id="layoutshow" name="layoutshow" runat="server"> show
              </label>
              <label>
                  <input type="checkbox" id="layoutcreate" name="layoutcreate" runat="server"> Create
              </label>

              <label>
                  <input type="checkbox" id="layoutedit" name="layoutedit" runat="server"> Edit
              </label>

              <label>
                  <input type="checkbox" id="layoutdelete" name="layoutdelete" runat="server"> Delete
              </label>
              </div>
          </div>

      </div>
      <div class="tab-pane" id="images">
          <div class="col-md-12">
             <div class="checkbox p-2">
                  <label>
                     <input type="checkbox" id="imagesshow" name="imagesshow" runat="server"> show
                  </label>
                  <label>
                      <input type="checkbox" id="imagescreate" name="imagescreate" runat="server"> Create
                  </label>

                  <label>
                      <input type="checkbox" id="imagesedit" name="imagesedit" runat="server"> Edit
                  </label>

                  <label>
                      <input type="checkbox" id="imagesdelete" name="imagesdelete" runat="server"> Delete
                  </label>

                 <label>
                      <input type="checkbox" id="imagespreview" name="imagespreview" runat="server"> Preview
                  </label>
              </div>
          </div>
      </div>
      <div class="tab-pane" id="galleries">
          <div class="col-md-12">
             <div class="checkbox p-2">
              <label>
                  <input type="checkbox" id="galleriesshow" name="galleriesshow" runat="server"> show
              </label>
              <label>
                  <input type="checkbox" id="galleriescreate" name="galleriescreate" runat="server"> Create
              </label>

              <label>
                  <input type="checkbox" id="galleriesedit" name="galleriesedit" runat="server"> Edit
              </label>

              <label>
                  <input type="checkbox" id="galleriesdelete" name="galleriesdelete" runat="server"> Delete
              </label>

                 <label>
                  <input type="checkbox" id="galleriespreview" name="galleriespreview" runat="server"> Preview
              </label>

                 <label>
                  <input type="checkbox" id="galleriesclone" name="galleriesclone" runat="server"> Clone
              </label>
              </div>
          </div>
      </div>
      <div class="tab-pane" id="sites">
          <div class="col-md-12">
             <div class="checkbox p-2">
              <label>
                  <input type="checkbox" id="sitesshow" name="sitesshow" runat="server"> show
              </label>
              <label>
                  <input type="checkbox" id="sitescreate" name="sitescreate" runat="server"> Create
              </label>

              <label>
                  <input type="checkbox" id="sitesedit" name="sitesedit" runat="server"> Edit
              </label>

              <label>
                  <input type="checkbox" id="sitesdelete" name="sitesdelete" runat="server"> Delete
              </label>

                 <label>
                  <input type="checkbox" id="sitespreview" name="sitespreview" runat="server"> Preview
              </label>

                 <label>
                  <input type="checkbox" id="sitesclone" name="sitesclone" runat="server"> Clone
              </label>

              </div>
          </div>
      </div>
      <div class="tab-pane" id="schedules">
          <div class="col-md-12">
             <div class="checkbox p-2">
              <label>
                  <input type="checkbox" id="schedulesshow" name="schedulesshow" runat="server"> show
              </label>
              <label>
                  <input type="checkbox" id="schedulescreate" name="schedulescreate" runat="server"> Create
              </label>

              <label>
                  <input type="checkbox" id="schedulesedit" name="schedulesedit" runat="server"> Edit
              </label>

              <label>
                  <input type="checkbox" id="schedulesdelete" name="schedulesdelete" runat="server"> Delete
              </label>

                 <label>
                  <input type="checkbox" id="schedulespreview" name="schedulespreview" runat="server"> Preview
              </label>

                 <label>
                  <input type="checkbox" id="schedulesclone" name="schedulesclone" runat="server"> Clone
              </label>


              </div>
          </div>
      </div>
      <div class="tab-pane" id="videos">
          <div class="col-md-12">
             <div class="checkbox p-2">
              <label>
                  <input type="checkbox" id="videosshow" name="videosshow" runat="server"> show
              </label>
              <label>
                  <input type="checkbox" id="videoscreate" name="videoscreate" runat="server"> Create
              </label>

              <label>
                  <input type="checkbox" id="videosedit" name="videosedit" runat="server"> Edit
              </label>

              <label>
                  <input type="checkbox" id="videosdelete" name="videosdelete" runat="server"> Delete
              </label>

                 <label>
                  <input type="checkbox" id="videospreview" name="videospreview" runat="server"> Preview
              </label>

                 <label>
                  <input type="checkbox" id="videosclone" name="videosclone" runat="server"> Clone
              </label>

              </div>
          </div>
      </div>
      <div class="tab-pane" id="flows">
          <div class="col-md-12">
             <div class="checkbox p-2">
              <label>
                  <input type="checkbox" id="flowsshow" name="flowsshow" runat="server"> show
              </label>
              <label>
                  <input type="checkbox" id="flowscreate" name="flowscreate" runat="server"> Create
              </label>

              <label>
                  <input type="checkbox" id="flowsedit" name="flowsedit" runat="server"> Edit
              </label>

              <label>
                  <input type="checkbox" id="flowsdelete" name="flowsdelete" runat="server"> Delete
              </label>

                 <label>
                  <input type="checkbox" id="flowspreview" name="flowspreview" runat="server"> Preview
              </label>

                 <label>
                  <input type="checkbox" id="flowsclone" name="flowsclone" runat="server"> Clone
              </label>

              </div>
          </div>
      </div>';
    }


        return $html;
    }


    public function update_user_of_client(Request $request, $client_id, $user_id, $is_users_of_client_active) {
        $validator = $request->validate([
            // 'first_name' => 'required|string|max:255',
            // 'last_name' => 'required|string|max:255',
            // 'username' => 'required|string|max:255',
            // 'email' => [
            //                 'required',
            //                 'string',
            //                 'email',
            //                 'max:255',
            //                 Rule::unique('users')->ignore($user_id),
            //             ],
            // 'phone' => 'required|string|max:255',
            // 'gender' => 'required|boolean|max:255',
            'status' => 'boolean|max:255',
        ]);
        $user = User::find($user_id);
        // $user = $user->fill($request->all());
        $user->status = $request->status;
        // $user->password = Hash::make($request->password);
        // $user->type = 'admin';
        $user->save();


        $user_permission = Permission::where('user_id',$user_id)->where('module','user')->first();
		$device_permission = Permission::where('user_id',$user_id)->where('module','device')->first();
		$layout_permission = Permission::where('user_id',$user_id)->where('module','layout')->first();
		$images_permission = Permission::where('user_id',$user_id)->where('module','images')->first();
		$galleries_permission = Permission::where('user_id',$user_id)->where('module','galleries')->first();
		$sites_permission = Permission::where('user_id',$user_id)->where('module','sites')->first();
		$schedules_permission = Permission::where('user_id',$user_id)->where('module','schedules')->first();
		$videos_permission = Permission::where('user_id',$user_id)->where('module','videos')->first();
		$flows_permission = Permission::where('user_id',$user_id)->where('module','flows')->first();

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
            $permission->self  = $client_id;
            $permission->save();
        }else{
            $permission = new Permission();
            $permission->user_id = $user_id;
            $permission->module = 'user';
            $permission->show  = $usershow;
            $permission->create  = $usercreate;
            $permission->edit  = $useredit;
            $permission->delete  = $userdelete;
            $permission->self  = $client_id;
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
            $permission->self  = $client_id;
            $permission->save();
        }else{
            $permission = new Permission();
            $permission->user_id = $user_id;
            $permission->module = 'device';
            $permission->show  = $deviceshow;
            $permission->create  = $devicecreate;
            $permission->edit  = $deviceedit;
            $permission->delete  = $devicedelete;
            $permission->preview  = $devicepreview;
            $permission->self  = $client_id;;
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
            $permission->self  = $client_id;
            $permission->save();
        }else{
            $permission = new Permission();
            $permission->user_id = $user_id;
            $permission->module = 'layout';
            $permission->show  = $layoutshow;
            $permission->create  = $layoutcreate;
            $permission->edit  = $layoutedit;
            $permission->delete  = $layoutdelete;
            $permission->self  = $client_id;;
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
            $permission->self  = $client_id;
            $permission->save();
        }else{
            $permission = new Permission();
            $permission->user_id = $user_id;
            $permission->module = 'images';
            $permission->show  = $imagesshow;
            $permission->create  = $imagescreate;
            $permission->edit  = $imagesedit;
            $permission->delete  = $imagesdelete;
            $permission->preview  = $imagespreview;
            $permission->clone  = $imagesclone;
            $permission->self  = $client_id;
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
            $permission->self  = $client_id;
            $permission->save();
        }else{
            $permission = new Permission();
            $permission->user_id = $user_id;
            $permission->module = 'galleries';
            $permission->show  = $galleriesshow;
            $permission->create  = $galleriescreate;
            $permission->edit  = $galleriesedit;
            $permission->delete  = $galleriesdelete;
            $permission->preview  = $galleriesdelete;
            $permission->clone  = $galleriesdelete;
            $permission->self  = $client_id;
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
            $permission->self  = $client_id;
            $permission->save();
        }else{
            $permission = new Permission();
            $permission->user_id = $user_id;
            $permission->module = 'sites';
            $permission->show  = $sitesshow;
            $permission->create  = $sitescreate;
            $permission->edit  = $sitesedit;
            $permission->delete  = $sitesdelete;
            $permission->preview  = $sitespreview;
            $permission->clone  = $sitesclone;
            $permission->self  = $client_id;
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
            $permission->self  = $client_id;
            $permission->save();
        }else{
            $permission = new Permission();
            $permission->user_id = $user_id;
            $permission->module = 'schedules';
            $permission->show  = $schedulesshow;
            $permission->create  = $schedulescreate;
            $permission->edit  = $schedulesedit;
            $permission->delete  = $schedulesdelete;
            $permission->preview  = $schedulespreview;
            $permission->clone  = $schedulesclone;
            $permission->self  = $client_id;
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
            $permission->self  = $client_id;
            $permission->save();
        }else{
            $permission =  new Permission();
            $permission->user_id = $user_id;
            $permission->module = 'videos';
            $permission->show  = $videosshow;
            $permission->create  = $videoscreate;
            $permission->edit  = $videosedit;
            $permission->delete  = $videosdelete;
            $permission->preview  = $videospreview;
            $permission->clone  = $videosclone;
            $permission->self  = $client_id;
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
            $permission->self  = $client_id;
            $permission->save();
        }else{
            $permission =  new Permission();
            $permission->user_id = $user_id;
            $permission->module = 'flows';
            $permission->show  = $flowsshow;
            $permission->create  = $flowscreate;
            $permission->edit  = $flowsedit;
            $permission->delete  = $flowsdelete;
            $permission->preview  = $flowspreview;
            $permission->clone  = $flowsclone;
            $permission->self  = $client_id;
            $permission->save();
        }

        return redirect(url('admin/clients/' . $client_id . '/edit/is_users_of_client_tab_active/' . $is_users_of_client_active))->with('success', 'A user was updated.');
    }

    public function destroy_user($client_id, $user_id)
    {
        User::destroy($user_id);
        return redirect(url('admin/clients/' . $client_id . '/edit/is_users_of_client_tab_active'))->with('success', 'A user was deleted.');
    }
}
