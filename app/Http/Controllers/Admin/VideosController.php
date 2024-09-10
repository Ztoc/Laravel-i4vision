<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Routing\Route;


use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Video;

class VideosController extends Controller
{
    const PAGE_NAME = "videos";

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

        $videos = Video::where('client_id', auth()->user()->client_id)->orderBy('client_id')->orderByDesc('id')->get();

        return view('admin.videos/index', ['videos' => $videos, 'page_name' => self::PAGE_NAME]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.videos/create', ['page_name' => self::PAGE_NAME]);
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
           'name' => ['required', 'string',  'max:255','regex:/^\S*$/u'],
           'url' => 'required|url',
           'time' => 'nullable|int',
           'params' => 'nullable|string',
           'description' => 'nullable|string'
        ]);
		
		$videos = Video::where('client_id', auth()->user()->client_id )
			   ->where('name',$request->name)
			    ->first();
		
		 //return $videos->name;
		
		if(empty($videos->name)){
		   
		$video = new Video($request->all());
        $video->client_id = auth()->user()->client_id;
        $video->user_id = auth()->user()->id;

        $video->save();
        return redirect(route('admin.videos'))->with('success', 'A new video was created.');
		
		}
		else
		{
			
			     return redirect(route('admin.videos.create'))
					 ->with('name', $request->name)
					 ->with('url', $request->url)
					 ->with('time', $request->time)
					 ->with('params', $request->params)
					 ->with('description', $request->description)
					 ->withErrors(['Name is Already Inserted']);

			//return back()->with( ['name1' => $request->name] );
		}
		
        //dd($request);
       
    }
    public function clone(Request $request)
    {
        $validator = $request->validate([
            'name' => ['required', 'string', 'unique:videos', 'max:255','regex:/^\S*$/u'],
            'url' => 'required|string|max:255',
            'time' => 'nullable|string',
            'params' => 'nullable|string',
            'description' => 'nullable|string',
        ]);
        $video = new Video($request->all());

        $video->client_id = auth()->user()->client_id;

        $video->user_id = auth()->user()->id;

        $video->url = str_replace('-', '&', $video->url);

        $video->save();

		return response()->json([
            'video' => $video,
        ]);
		
        // return redirect(route('admin.galleries'))->with('success', 'A new gallery was created.');
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id , $key= null)
    {
        $video = Video::find($id);

        if (empty($video)) {
            return redirect(route('admin.videos'))->with('warning', 'warning.');
        }

        return view('admin.videos/edit', ['video' => $video,'key'=>$key, 'page_name' => self::PAGE_NAME]);
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
           'name' => ['required', 'string',  'max:255','regex:/^\S*$/u'],
           'url' => 'required|url',
           'time' => 'nullable|int',
           'params' => 'nullable|string',
           'description' => 'nullable|string'
        ]);
        
        $video = Video::find($id);

        $video->fill($request->all());
        $video->save();
		
		if($request->key){
		//admin/flows/82/edit/is_flow_active
			return redirect(url('admin/flows/' .$request->key. '/edit/is_flow_active'))->with('success', 'A video was updated.');
		}else{
		  return redirect(url('admin/videos/' . $id . '/edit'))->with('success', 'A video was updated.');
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
        Video::destroy($id);
        return redirect(url('admin/videos'))->with('success', 'A video was deleted.');
    }
}
