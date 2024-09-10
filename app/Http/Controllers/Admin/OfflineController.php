<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\User;
use App\Offline;
use File;
use ZipArchive;
use Response;

class OfflineController extends Controller
{
    const PAGE_NAME = "offline";

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

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $folder =  Auth::user();

        $offlines = Offline::where('user_id', auth()->user()->id)->orderBy('client_id')->get();

        $user_type =  strtolower(Auth::user()->type);

        if($user_type=='admin'){
            $users = User::where('client_id', auth()->user()->client_id)->where('type', 'user')->orderBy('id')->orderByDesc('id')->get();
            return view('admin/offline/index-admin', ['users' => $users, 'page_name' => self::PAGE_NAME]);
        }else{
            return view('admin/offline/index', ['offlines' => $offlines, 'page_name' => self::PAGE_NAME]);
        }

    }

     /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin/offline/create', ['page_name' => self::PAGE_NAME]);
    }


    public function store(Request $request){
        $offline = new Offline($request->all());
        $user = User::with('client')->where('id', auth()->user()->id)->first();
        $client_name = $user->client->name;
        $user_name =  Auth::user()->username;
		$client_path = public_path().'/kalipso/Clients/'.$client_name;
		$user_path = $client_path.'/'.$user_name;

        File::makeDirectory($user_path, $mode = 0777, true, true);

        $offline->url = Str::of($request->file->store('/kalipso/Clients/'.$client_name."/".$user_name))->after('public');
        $request->file->move(public_path('/kalipso/Clients/'.$client_name."/".$user_name), $offline->url->after('public'));
        $offline->client_id = auth()->user()->client_id;
        $offline->user_id = auth()->user()->id;
        $offline->save();

        $this->createZip($client_path,$user_name);

        return redirect(route('admin.offline'))->with('message', 'A new offline image was created.');

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id , $key = null)
    {

		//return $key;
        $offline = Offline::find($id);

        if (empty($offline)) {
            return redirect(route('admin.offline'))->with('warning', 'warning.');
        }

        return view('admin/offline/edit', ['offline' => $offline,'key'=>$key, 'page_name' => self::PAGE_NAME]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id )
    {

        $url=$request->input('url');
        if(empty($file)){
            $request->validate([
                //'file' => 'nullable|image',
            ]);
        }


        $offline = Offline::find($id);
        $folder =  Auth::user()->username;
        $path = public_path().'/kalipso/Clients/'.$folder;
        $user = User::with('client')->where('id', auth()->user()->id)->first();
        $client_name = $user->client->name;
        $user_name =  Auth::user()->username;
        $client_path = public_path().'/kalipso/Clients/'.$client_name;
        $user_path = $client_path.'/'.$user_name;

        if ($request->file) {

        File::delete(public_path($offline->url)); // delete old file
        File::makeDirectory($user_path, $mode = 0777, true, true);

        $offline->url = Str::of($request->file->store('/kalipso/Clients/'.$client_name."/".$user_name))->after('public');
        $request->file->move(public_path('/kalipso/Clients/'.$client_name."/".$user_name), $offline->url->after('public'));
        $offline->user_id = auth()->user()->id;
        $offline->update();
        // Create Zip file
        $this->createZip($client_path,$user_name);

        return redirect(url('admin/offline/' . $id . '/edit'))->with('success', 'A image was updated.');



        }
    }

    public function download(Request $request, $id)
    {

        $user =  User::select('username')->where('id',$id)->first();
        $fileName = $user->username.'-offline.zip';
        $zip = new ZipArchive;

        if ($zip->open(public_path()."/kalipso/Clients/".$fileName, ZipArchive::CREATE) === TRUE)
        {
            $files = File::files(public_path().'/kalipso/Clients/'.$user->username);

            foreach ($files as $key => $value) {
                $relativeNameInZipFile = basename($value);
                $zip->addFile($value, $relativeNameInZipFile);
            }
            $zip->close();
        }
        $url = public_path()."/kalipso/Clients/".$fileName;
        return Response::download($url);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $offline = Offline::find($id);

        File::delete(public_path($offline->url));
        $offline->delete();

        $user = User::with('client')->where('id', auth()->user()->id)->first();
        $client_name = $user->client->name;
        $user_name =  Auth::user()->username;
		$client_path = public_path().'/kalipso/Clients/'.$client_name;

        $folder = Auth::user()->username;
        $this->createZip($client_path,$user_name);

        return redirect(route('admin.offline'))->with('success', 'A offline image was deleted.');
    }

    public function createZip($client_path,$user_name){

		$user_path = $client_path.'/'.$user_name;
        $fileName ='offline.zip';
        File::delete($client_path."/".$fileName);

        $zip = new ZipArchive;

        if ($zip->open($client_path."/".$fileName, ZipArchive::CREATE) === TRUE)
        {
            $files = File::files($user_path);

            foreach ($files as $key => $value) {
                $relativeNameInZipFile = basename($value);
                $zip->addFile($value, $relativeNameInZipFile);
            }
            $zip->close();
        }
    }

}




