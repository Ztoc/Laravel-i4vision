<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Flow;
use App\FlowEntry;
use App\Schedule;
use App\Layout;
use App\SyncGoogleImage;
class FlowsController extends Controller
{
    const PAGE_NAME = "flows";

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


	public function check_sequence(Request $request)
    {
		$FlowEntry = FlowEntry::where('flow_id', $request->flow_id )
			   ->where('sequence',$request->sequence)
			    ->first();

		//return $FlowEntry->sequence;

		if(!empty($FlowEntry->sequence)){
			return 'success';
		}

    }

    public function index()
    {

        $flows = Flow::where('client_id', auth()->user()->client_id)->orderBy('client_id')->orderByDesc('id')->get();

        return view('admin/flows/index', ['flows' => $flows, 'page_name' => self::PAGE_NAME]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
		//dd(auth()->user()->id);
        if(auth()->user()->type=='user'){
            $layout = Layout::where('user_id',auth()->user()->id)->orderBy('id')->orderByDesc('id')->distinct('name')->get();
        }else{
            $layout = Layout::where('client_id',auth()->user()->client_id)->orwhere('client_id',0)->orderBy('id')->orderByDesc('id')->distinct('name')->get();
        }
        //dd($layout);
        return view('admin/flows/create', ['page_name' => self::PAGE_NAME] )->with(['layout'=>$layout]);
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
            'name' => ['required', 'string', 'unique:flows', 'max:255','regex:/^\S*$/u'],
			 'description' => 'required|string',
            'layout' => 'nullable|string|max:255',
        ]);

        $flow = new Flow($request->all());

        $flow->client_id = auth()->user()->client_id;
        $flow->user_id = auth()->user()->id;

        $flow->save();

        return redirect(route('admin.flows'))->with('success', 'A new flow was created.');
    }

    public function clone(Request $request)
    {


        $validator = $request->validate([
           // 'name' => ['required', 'string', 'unique:flows', 'max:255','alpha','regex:/^\S*$/u'],
            'description' => 'required|string',
			//'sequence' => 'required',
            'layout' => 'nullable|string|max:255',
        ]);
        $flow = new Flow($request->all());
		$flow->layout = $request->layout == "null" ? "" : $request->layout ;
        $flow->client_id = auth()->user()->client_id;
        $flow->user_id = auth()->user()->id;


        $flow->save();
        $flow_previous = Flow::where('name' , substr($flow->name , 0 , -5))->get();
        $flow_entries = FlowEntry::where('flow_id', $flow_previous[0]->id)->get();
        //dd($flow_entries);
        $flow_current = Flow::where('name' ,$flow->name)->get();
        foreach ($flow_entries as $each_entry) {
            unset($each_entry->id,$each_entry->created_at,$each_entry->updated_at);

            $new_flow_entry['dates'] = $each_entry->dates;
            $new_flow_entry['time'] = $each_entry->time;
            $new_flow_entry['refreshTime'] = $each_entry->refreshTime;
            $new_flow_entry['run_to'] = $each_entry->run_to;
            $new_flow_entry['run_from'] = $each_entry->run_from;
            $new_flow_entry['sequence'] = $each_entry->sequence;
            $new_flow_entry['flow_entriable_id'] = $each_entry->flow_entriable_id;
            $new_flow_entry['flow_id'] = $each_entry->flow_id;
            $new_flow_entry['flow_entriable_type'] = $each_entry->flow_entriable_type;

            $n_flow_entry = new FlowEntry($new_flow_entry);
            $n_flow_entry->flow_id = $flow_current[0]->id;
            $n_flow_entry->user_id = auth()->user()->id;

            $n_flow_entry->save();
        }
        return response()->json([
            'flow' => $flow
        ]);
        //return redirect(route('admin.flows'))->with('success', 'A new flow was created.');
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $is_flow_active = 1)
    {

        if(auth()->user()->type=='user'){
            $layout = Layout::where('user_id',auth()->user()->id)->orderBy('id')->orderByDesc('id')->distinct('name')->get();
        }else{
            $layout = Layout::where('client_id',auth()->user()->client_id)->orwhere('client_id',0)->orderBy('id')->orderByDesc('id')->distinct('name')->get();
        }

        $flow_list = Flow::where('client_id',auth()->user()->client_id)->get();

		//$flow_list = Flow::get();
		//return $flow_list;

		$flow = Flow::find($id);

		//$flow =

			$sequence = FlowEntry::where('flow_id', $id)->max('sequence');
		    //return $flow_sequence + 10 ;
		    //$sequence  = $flow_sequence;

		//return $flow;
        if (empty($flow)) {
            return redirect(route('admin.flows'))->with('warning', 'warning.');
        }
        //dd($flow);
        return view('admin/flows/edit', ['flow' => $flow,'sequence' => $sequence+10, 'is_flow_active' => $is_flow_active, 'page_name' => self::PAGE_NAME , 'flow_list'=>$flow_list])->with(['layout'=>$layout]);
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
                        'max:255',
				         'regex:/^\S*$/u',
                        Rule::unique('flows')->ignore($id)
                    ],

            'description' => 'required|string',
            'layout' => 'nullable|string|max:255',
        ]);

        $flow = Flow::find($id);

        $flow->fill($request->all());
        $flow->save();

        return redirect(url('admin/flows/' . $id . '/edit/is_flow_active'))->with('success', 'A flow was updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Flow::destroy($id);
        return redirect(url('admin/flows'))->with('success', 'A flow was deleted.');
    }

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


    public function get_flow_entriable_screenshot(Request $request) {
        $flow_entriable_names = collect();

        switch ($request->flow_entriable_type) {
            case 'App\Image':
                $data = $request->flow_entriable_type:: select('url')->where('id', $request->id)->first();
                $html = '<img style="width:200px" src="https://i4vision.de/storage/'.$data->url.'" >';
                break;
            case 'App\Gallery':
                    $data = SyncGoogleImage:: select('url')->where('gallery_id', $request->id)->first();
                    $html = '<img style="width:200px" src="'.$data->url.'" >';
                    break;
            case 'App\Site':
                $data = $request->flow_entriable_type:: select('url')->where('id', $request->id)->first();
                $html = '<iframe style="width:300px;border:none;" src="'.$data->url.'"></iframe>';
                break;
            case 'App\Video':
                $video = $request->flow_entriable_type::where('id', $request->id)->first();
                $html = '<iframe style="border: none;height:200px;left:0;top:0"  src="'.$video->url.'"  "'.$video->url.'"  ></iframe>';
                break;
        }

        return response()->json([
            'html' => $html,
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
		$flow_entry->refreshTime = $request->refreshTime;
        $flow_entry->user_id = auth()->user()->id;
        $flow_entry->save();
         return redirect(url('admin/flows/' . $flow_id . '/edit/is_flow_active/' . $is_flow_active))->with('success', 'A new flow entry was created.');
			}
		else
		{

			return redirect()->back()->withInput($request->all())->withErrors(['Sequence Id is Already Inserted'])
				->with('sequence',$request->sequence)
				->with('time',$request->time)
				->with('entriable',$request->flow_entriable_type);

			//return back()->withErrors(['Sequence Id is Already Inserted'])->with(['time' => '324523']);
		}


    }
    public function flow_entry_move(Request $request, $id, $is_flow_active) {

        $flow_entry = FlowEntry::find($id);
        $flow_entry->flow_id = $request->flow_id;
		$flow_entry->refreshTime = $request->refreshTime;
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
		$flow_entry->refreshTime = $request->refreshTime;
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
		$flow_entry->width = $request->width;
		$flow_entry->refreshTime = $request->refreshTime;
        $flow_entry->user_id = auth()->user()->id;
        $flow_entry->save();


		return redirect()->back()->with('success', 'A flow entry was updated.');
		 //return redirect(url('admin/flows/'))->with('success', 'A flow entry was updated.');

        //return redirect(url('admin/flows/' . $flow_id . '/edit/is_flow_active/' . $is_flow_active))->with('success', 'A flow entry was updated.');

    }

    public function flow_entry_delete($flow_id, $flow_entry_id) {
        FlowEntry::destroy($flow_entry_id);

        return redirect(url('admin/flows/' . $flow_id . '/edit/is_flow_active'))->with('success', 'A flow entry was deleted.');

    }
}




