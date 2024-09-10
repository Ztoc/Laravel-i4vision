<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use File;
use App\Image;
use Illuminate\Support\Facades\Auth;
class ImagesController extends Controller
{
    const PAGE_NAME = "images";

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

        $images = Image::where('client_id', auth()->user()->client_id)->orderBy('client_id')->orderByDesc('id')->get();
        return view('admin/images/index', ['images' => $images, 'page_name' => self::PAGE_NAME]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin/images/create', ['page_name' => self::PAGE_NAME]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
		 $request->validate([
               'name' => 'required|regex:/^\S*$/u',
           ],

			['name.required' =>  __('backend.name_required'),
			 'name.alpha' =>  __('backend.name_alpha'),
			 'name.regex:/^\S*$/u' => __('backend.name_regex')
			]
			 );

        $image = new Image($request->all());
        $filename=$request->input('filename');
        if(empty($filename)){
            $validator = $request->validate([
            'file' => 'required|image',
        ]);
        }
       if(empty($filename)){
        $folder =  strtolower(Auth::user()->client->name);
		$path = public_path().'/storage/images/'.$folder;
        File::makeDirectory($path, $mode = 0777, true, true);

        $image->url = Str::of($request->file->store('public/images/'.$folder))->after('public/');
        $request->file->move(public_path('storage/images/'.$folder), $image->url->after('images'));
        }else{
            $image->url =$filename;
        }
        $image->client_id = auth()->user()->client_id;
        $image->user_id = auth()->user()->id;
        $image->save();

        return redirect(route('admin.images'))->with('success', 'A new image was created.');
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
        $image = Image::find($id);

        if (empty($image)) {
            return redirect(route('admin.images'))->with('warning', 'warning.');
        }

        return view('admin/images/edit', ['image' => $image,'key'=>$key, 'page_name' => self::PAGE_NAME]);
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


		$request->validate([
                'name' => 'required|regex:/^\S*$/u',
            ]
			 );
        $url=$request->input('url');
        if(empty($file)){
            $request->validate([
                //'file' => 'nullable|image',
            ]);
        }


        $image = Image::find($id);

        if ($request->file) {

            $folder =  strtolower(Auth::user()->client->name);
            $path = public_path().'/storage/images/'.$folder;

            if(File::exists(public_path('storage/'.$image->url))){
                File::delete(public_path('storage/'.$image->url));
            }


            File::makeDirectory($path, $mode = 0777, true, true);

            $image->url = Str::of($request->file->store('public/images/'.$folder))->after('public/');
            $request->file->move(public_path('storage/images/'.$folder), $image->url->after('images'));

            }else{
                $image->url =$url;
            }

            $image->fill($request->all());
            $image->save();

            if($request->key){
            //admin/flows/82/edit/is_flow_active
            return redirect(url('admin/flows/' .$request->key. '/edit/is_flow_active'))->with('success', 'A image was updated.');
            }else{
            return redirect(url('admin/images/' . $id . '/edit'))->with('success', 'A image was updated.');
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
        $image = Image::find($id);
        Storage::delete('public/' . $image->url);
        $image->delete();
        return redirect(url('admin/images'))->with('success', 'A image was deleted.');
    }
}
