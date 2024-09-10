<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Filesystem\Filesystem;
use App\Gallery;
use App\SyncGoogleImage;
use App\Layout;
use App\User;
use File;
use Storage;
use Carbon\Carbon;
class GalleriesController extends Controller
{
    const PAGE_NAME = "galleries";

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

        $galleries = Gallery::where('client_id', auth()->user()->client_id)->orderBy('client_id')->orderByDesc('id')->get();
      //  $galleries = Gallery::where('user_id', auth()->user()->client_id)->orderBy('user_id')->orderByDesc('id')->get();

        return view('admin/galleries/index', ['galleries' => $galleries, 'page_name' => self::PAGE_NAME]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $layout = Layout::where('client_id',auth()->user()->client_id)->get();
        return view('admin/galleries/create', ['page_name' => self::PAGE_NAME])->with(['layout'=>$layout]);
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
            'name' => ['required', 'string', 'unique:galleries', 'max:255','regex:/^\S*$/u'],
            'google_link' => ['required'],


        ]);

        $gallery = new Gallery();
        $gallery->client_id = auth()->user()->client_id;
        $gallery->name = $request->name;
        $gallery->google_link = $request->google_link;
        $gallery->description = $request->description;
        $gallery->gallery_style = $request->gallery_style;
        $gallery->layout = $request->layout;
        $gallery->user_id = auth()->user()->id;
        $gallery->last_access_time = Carbon::now();
        $gallery->save();


		return response()->json([
            'gallery' => $gallery
        ]);
        //  return redirect(route('admin.galleries'))->with('success', 'A new gallery was created.');
    }
    public function clone(Request $request)
    {

        $gallery = new Gallery($request->all());

        $gallery->client_id = auth()->user()->client_id;

        $gallery->user_id = auth()->user()->id;

        $gallery->save();


		return response()->json([
            'gallery' => $gallery
        ]);
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id,$key = null)
    {
        $user_id = auth()->user()->id;
        $gallery = Gallery::join('users', 'galleries.user_id', '=', 'users.id')
        ->select('galleries.*')
        ->where('galleries.id',$id)
        //->where('users.id',$user_id)
        ->first();

       // dd($gallery);

        $google_images = SyncGoogleImage::select('*')
        ->where('gallery_id',$id)
        ->get();

        // Create URL List from Public Storage directory
        $arr_local_filename = array();
        $files = Storage::disk('public_dir')->allFiles('storage/gallery/'.$id);
        foreach($files as $item){
            $arr_local_filename[] = str_replace('storage/gallery/' . $id."/", "", $item);
        }

        $layout = Layout::where('client_id',auth()->user()->client_id)->get();

        if(empty($gallery)) {
            return redirect(route('admin.galleries'))->with('warning', 'You are not allowed to access or edit this gallary.');
        }
        return view('admin/galleries/edit', ['gallery' => $gallery,'arr_local_filename'=>$arr_local_filename,'google_images'=>$google_images,'layout'=>$layout, 'key'=>$key, 'page_name' => self::PAGE_NAME]);
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
      // return 'maaz';
        $validator = $request->validate([
            'name' => [
                            'required',
                            'string',
                            'max:255',
				            'regex:/^\S*$/u',
                            Rule::unique('galleries')->ignore($id)
                        ],
            'google_link' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $gallery = Gallery::find($id);

        $gallery->fill($request->all());
		$gallery->update();
		return response()->json([
            'gallery' => $gallery
       ]);

		//if($request->key){
		//admin/flows/82/edit/is_flow_active
			//return redirect(url('admin/flows/' .$request->key. '/edit/is_flow_active'))->with('success', 'A gallery was updated.');
		//}else{
		// return redirect(url('admin/galleries/' . $id . '/edit'))->with('success', 'A gallery was updated.');
		//}

    }

    public function get_google_image($id,$google_image_id) {

        $google_image = SyncGoogleImage::find($google_image_id);

        return response()->json([
            'google_image' => $google_image,
        ]);
    }

    public function google_image_update(Request $request, $id, $google_image_id) {

        $google_image = SyncGoogleImage::find($google_image_id);
		$google_image->info_text = $request->info_text;
		$google_image->is_suppress = $request->is_suppress ? $request->is_suppress : 0;
		$google_image->is_enable_info_text = $request->is_enable_info_text;


        $google_image->save();

        return redirect()->back()->with('success', 'A Google Image was updated.');
    }






     /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function hasAccess(Request $request,$id)
    {
        $user_id = auth()->user()->id;
        $gallery = Gallery::join('users', 'galleries.user_id', '=', 'users.id')
        ->where('galleries.id',$request->id)
        ->where('users.id',$user_id)
        ->first();

        if (empty($gallery)) {
            return 'No';
        }else{
            return 'Yes';
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user_id = auth()->user()->id;
        $gallery = Gallery::join('users', 'galleries.user_id', '=', 'users.id')
        ->where('galleries.id',$id)
        ->where('users.id',$user_id)
        ->first();


        $directory_path = public_path().'/storage/gallery/'.$id;

        if(File::isDirectory($directory_path)){
            $file = new Filesystem;
            $file->cleanDirectory($directory_path);
            rmdir($directory_path); // This delete only empty folder
        }


        if (empty($gallery)) {
            return redirect(route('admin.galleries'))->with('warning', 'You are not allowed to delete this gallary.');
        }

        Gallery::destroy($id);
        return redirect(url('admin/galleries'))->with('success', 'A gallery was deleted.');
    }




}
