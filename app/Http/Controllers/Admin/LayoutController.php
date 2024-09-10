<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Flow;
use App\FlowEntry;
use App\Layout;
use App\Schedule;
use File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;



class LayoutController extends Controller
{
    const PAGE_NAME = "layout";

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
        if(auth()->user()->type=='user'){
            $layout = Layout::where('user_id',auth()->user()->id)->orderBy('id')->orderByDesc('id')->distinct('name')->get();
        }else{
            $layout = Layout::where('client_id',auth()->user()->client_id)->orwhere('client_id',0)->orderBy('id')->orderByDesc('id')->distinct('name')->get();
        }

        $folder =  strtolower(Auth::user()->client->name);
        $path = public_path().'/css/'.$folder;
        $filename = $path.'/client.css';
        $client_css_layout = File::get($filename);

        $flow_css = $path.'/flow.css';
        if(file_exists(public_path($flow_css))){
            $flow_css_layout = File::get($flow_css);
        }else{
            $flow_css_layout = File::get(public_path().'/css/pages/flow.css');
        }


        return view('admin/layout/index', [
            'layout' => $layout,
            'client_css_layout' => $client_css_layout,
            'flow_css_layout' => $flow_css_layout,
            'page_name' => self::PAGE_NAME
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        return view('admin/layout/create', ['page_name' => self::PAGE_NAME]);
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
            'name' => ['required', 'string','regex:/^\S*$/u'],
			 'css' => 'required|string',
            //'layout' => 'nullable|string|max:255',
        ]);


		$folder =  strtolower(Auth::user()->client->name);


		$path = public_path().'/css/'.$folder;
        File::makeDirectory($path, $mode = 0777, true, true);

		//return public_path();

	     $myfile = fopen(public_path().'/css/'.$folder."/".$request->name.".css", "w") or die("Unable to open file!");
           $txt = $request->css;
           fwrite($myfile, $txt);
           fclose($myfile);


        $layout = new Layout($request->all());
		$layout->client_id =  auth()->user()->client_id;
        $layout->user_id =  auth()->user()->id;
        $layout->save();
        return redirect(route('admin.layout'))->with('success', 'A new layout was created.');
    }




    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

		$layout = Layout::find($id);

        $folder =  strtolower(Auth::user()->client->name);
        $path = public_path().'/css/'.$folder;
        $filename = $path.'/'.$layout->name.'.css';
        $client_css_layout = File::get($filename);
        if (empty($layout)) {
            return redirect(route('admin.layout'))->with('warning', 'warning.');
        }

        return view('admin/layout/edit', ['layout' => $layout,'client_css_layout'=>$client_css_layout,'page_name' => self::PAGE_NAME ]);
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

		$folder =  strtolower(Auth::user()->client->name);


		$path = public_path().'/css/'.$folder;
        File::makeDirectory($path, $mode = 0777, true, true);


		//return public_path();

        //$myfile = fopen("/var/www/vhosts/i4vision.de/front.i4vision.de/public/css/".$folder."/".$request->name.".css", "w") or die("Unable to open file!");
        $myfile = fopen(public_path().'/css/'.$folder."/".$request->name.".css", "w") or die("Unable to open file!");
           $txt = $request->css;
           fwrite($myfile, $txt);
           fclose($myfile);

        $layout = Layout::find($id);


        $layout->fill($request->all());
		$layout->client_id =  auth()->user()->client_id;
		$layout->user_id =  auth()->user()->id;
        $layout->save();

        return redirect(url('admin/layout/' . $id . '/edit/'))->with('success', 'A layout was updated.');
    }



    public function edit_client_css()
    {

        $folder =  strtolower(Auth::user()->client->name);
        $filename = public_path().'/css/'.$folder.'/client.css';
        $client_css_layout = File::get($filename);

        if (empty($client_css_layout)) {
            return redirect(route('admin.layout'))->with('warning', 'warning.');
        }

        return view('admin/layout/edit_client_css', ['client_css_layout'=>$client_css_layout ,'page_name' => self::PAGE_NAME ]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update_client_css_layout(Request $request)
    {

		$folder =  strtolower(Auth::user()->client->name);


		$path = public_path().'/css/'.$folder;
        $filename = public_path().'/css/'.$folder.'/client.css';

        //$myfile = fopen("/var/www/vhosts/i4vision.de/front.i4vision.de/public/css/".$folder."/".$request->name.".css", "w") or die("Unable to open file!");
        $myfile = fopen(public_path().'/css/'.$folder."/client.css", "w") or die("Unable to open file!");
           $txt = $request->css;
           fwrite($myfile, $txt);
           fclose($myfile);

        return redirect(url('admin/layout/edit_client_css'))->with('success', 'Client layout was updated.');
    }




    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) /** Geändert mit Unlink und allen Definitionen zum löschen auf dem Server */
    {

        $layout = Layout::find($id);
        $folder =  strtolower(Auth::user()->client->name);
        $path = public_path().'/css/'.$folder;
        $filename = $path.'/'.$layout->name.'.css';

        unlink($filename);
        Layout::destroy($id);
        return redirect(url('admin/layout'))->with('success', 'A Layout was deleted.');
    }

    //public function destroy($id)
    //{
    //    Layout::destroy($id);
    //    return redirect(url('admin/layout'))->with('success', 'A Layout was deleted.');
    //}

    public function get_flow_entriable_names(Request $request) {
        $flow_entriable_names = collect();

        switch ($request->flow_entriable_type) {
            case 'App\Image':
            case 'App\Gallery':
            case 'App\Site':
                $flow_entriable_names = $request->flow_entriable_type::where('client_id', auth()->user()->client_id)->orderBy('name')->get();
                break;
            case 'App\Video':
                $flow_entriable_names = $request->flow_entriable_type::where('client_id', auth()->user()->client_id)->orderBy('name')->get();
                break;
            case 'App\Device':
                $flow_entriable_names = $request->flow_entriable_type::where('client_id', auth()->user()->client_id)->orderBy('device_code')->get();
                break;
            case 'App\Schedule':
                $flow_entriable_names = Schedule::select('name')->where('client_id', auth()->user()->client_id)->groupBy('name')->orderBy('name')->get();
                break;
        }
        //dd($flow_entriable_names);
        return response()->json([
            'flow_entriable_names' => $flow_entriable_names,
        ]);
    }

    public function flow_entry_store(Request $request, $flow_id, $is_flow_active) {
        $validator = $request->validate([
			'flow_entriable_type' => 'required',
			'flow_entriable_id' => 'required',
			//'sequence' => 'required|integer|between:1,10',
			'sequence' => [function ($attribute, $value, $fail) {
            if ($value < 10) {
                $fail(':attribute needs more cowbell!');
            }
        }]

		]);


		$FlowEntry = FlowEntry::where('flow_id', $flow_id )
			   ->where('sequence',$request->sequence)
			    ->first();

		//return $FlowEntry;

		 //return $videos->name;

		if(empty($FlowEntry->sequence)){

        $flow_entry = new FlowEntry($request->all());
        $flow_entry->flow_id = $flow_id;
        $flow_entry->user_id = auth()->user()->id;
        $flow_entry->save();
         return redirect(url('admin/layouts/' . $flow_id . '/edit/is_flow_active/' . $is_flow_active))->with('success', 'A new flow entry was created.');
			}
		else
		{
			return back()->withErrors(['Sequence Id is Already Inserted']);
		}


    }
    public function flow_entry_move(Request $request, $id, $is_flow_active) {

        $flow_entry = FlowEntry::find($id);
        $flow_entry->flow_id = $request->flow_id;
        $flow_entry->user_id = auth()->user()->id;
        $flow_entry->save();

        return response()->json([
            'flow_entry'=>$flow_entry
        ]);
    }
    public function flow_entry_clone(Request $request, $flow_id, $is_flow_active) {
        $validator = $request->validate([
			'flow_entriable_type' => 'required',
			'flow_entriable_id' => 'required',
			'sequence' => 'required',
        ]);

        $flow_entry_list = FlowEntry::select("sequence")->where('flow_id',$flow_id)->get();
        $flow_entry_sequence = -1;
        foreach ( $flow_entry_list as $flow_entry){
            if($flow_entry['sequence']>$flow_entry_sequence) $flow_entry_sequence = $flow_entry['sequence'];
        }
        //dd($flow_entry_sequence);
        $flow_entry = new FlowEntry($request->all());
        $flow_entry->flow_id = $flow_id;
        $flow_entry->sequence = $flow_entry_sequence+10;
        $flow_entry->user_id = auth()->user()->id;
        $flow_entry->save();

        return response()->json([
            'flow_entry'=> $flow_entry
        ]);
        // return redirect(url('admin/flows/' . $flow_id . '/edit/is_flow_active/' . $is_flow_active))->with('success', 'A new flow entry was cloned.');
    }
    public function get_flow_entry($id, $flow_entry_id) {

        $flow_entry = FlowEntry::find($flow_entry_id);

        $flow_entriable_names = collect();

        switch ($flow_entry->flow_entriable_type) {
            case 'App\Image':
            case 'App\Gallery':
            case 'App\Site':
                $flow_entriable_names = $flow_entry->flow_entriable_type::where('user_id', auth()->user()->id)->orderBy('name')->get();
                break;
            case 'App\Device':
                $flow_entriable_names = $flow_entry->flow_entriable_type::where('user_id', auth()->user()->id)->orderBy('device_code')->get();
                break;
            case 'App\Schedule':
                $flow_entriable_names = Schedule::select('name')->where('user_id', auth()->user()->id)->groupBy('name')->orderBy('name')->get();
                break;
            case 'App\Video':
                $flow_entriable_names = $flow_entry->flow_entriable_type::where('user_id', auth()->user()->id)->orderBy('name')->get();
                break;


        }

        return response()->json([
            'flow_entry' => $flow_entry,
            'flow_entriable_names' => $flow_entriable_names,
        ]);
    }

    public function flow_entry_update(Request $request, $flow_id, $flow_entry_id, $is_flow_active) {

        $validator = $request->validate([

			'flow_entriable_type' => 'required'
        ]);

        $flow_entry = FlowEntry::find($flow_entry_id);
        //dd($request->all());
        $flow_entry->fill($request->all());
        $flow_entry->flow_id = $flow_id;
        $flow_entry->user_id = auth()->user()->id;
        $flow_entry->save();

		 return redirect(url('admin/flows/'))->with('success', 'A flow entry was updated.');

        //return redirect(url('admin/flows/' . $flow_id . '/edit/is_flow_active/' . $is_flow_active))->with('success', 'A flow entry was updated.');

    }

    public function flow_entry_delete($flow_id, $flow_entry_id) {
        FlowEntry::destroy($flow_entry_id);

        return redirect(url('admin/flows/' . $flow_id . '/edit/is_flow_active'))->with('success', 'A flow entry was deleted.');

    }
}
