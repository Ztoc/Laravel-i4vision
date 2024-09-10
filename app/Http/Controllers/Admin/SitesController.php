<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Site;

class SitesController extends Controller
{
    const PAGE_NAME = "sites";

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

        $sites = Site::where('client_id', auth()->user()->client_id)->orderBy('client_id')->orderByDesc('id')->get();

        return view('admin.sites/index', ['sites' => $sites, 'page_name' => self::PAGE_NAME]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.sites/create', ['page_name' => self::PAGE_NAME]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $site = Site::where('name',$request->name)->where('client_id',auth()->user()->client_id)->first();
        if($site){
            $validator = $request->validate([
                'name' => ['required', 'string', 'unique:sites', 'max:400','regex:/^\S*$/u'],
                'url' => 'required|string|max:600',
                'description' => 'nullable|string',
             ]);
        }else{
            $validator = $request->validate([
                'name' => ['required', 'string', 'max:400','regex:/^\S*$/u'],
                'url' => 'required|string|max:600',
                'description' => 'nullable|string',
            ]);
        }



        $site = new Site($request->all());
        $site->isVideo = isset($request->isVideo) ? 1 : 0;
        $site->client_id = auth()->user()->client_id;
        $site->user_id = auth()->user()->id;

        $site->save();

        return redirect(route('admin.sites'))->with('success', 'A new site was created.');
    }
    public function clone(Request $request)
    {
        $validator = $request->validate([
            'name' => ['required', 'string', 'unique:sites', 'max:400'],
            'url' => 'required|string|max:400',
            'description' => 'nullable|string',
        ]);
        $site = new Site($request->all());

        $site->client_id = auth()->user()->client_id;

        $site->user_id = auth()->user()->id;

        $site->url = str_replace('-', '&', $site->url);

        $site->save();

		return response()->json([
            'site' => $site,
        ]);

        // return redirect(route('admin.galleries'))->with('success', 'A new gallery was created.');
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id , $key = null)
    {
        $site = Site::find($id);

        if (empty($site)) {
            return redirect(route('admin.sites'))->with('warning', 'warning.');
        }

        return view('admin.sites/edit', ['site' => $site, 'key' => $key, 'page_name' => self::PAGE_NAME]);
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
        $site = Site::where('name',$request->name)->where('client_id',auth()->user()->client_id)->first();
        if($site->id !== intval($id)){
            $validator = $request->validate([
                'name' => ['required', 'string', 'unique:sites', 'max:400','regex:/^\S*$/u'],
                'url' => 'required|string|max:600',
                'description' => 'nullable|string',
             ]);
        }else{
            $validator = $request->validate([
                'name' => ['required', 'string', 'max:400','regex:/^\S*$/u'],
                'url' => 'required|string|max:600',
                'description' => 'nullable|string',
            ]);
        }


        $site = Site::find($id);

        $site->fill($request->all());
        $site->isVideo = isset($request->isVideo) ? 1 : 0;
        $site->save();

		if($request->key){

			return redirect(url('admin/flows/' .$request->key. '/edit/is_flow_active'))->with('success', 'A Site was updated.');
		}else{

			return redirect(url('admin/sites/' . $id . '/edit'))->with('success', 'A site was updated.');
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
        Site::destroy($id);
        return redirect(url('admin/sites'))->with('success', 'A site was deleted.');
    }
}
