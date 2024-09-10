<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Schedule;
use App\Image;
use App\ScheduleEntry;
use App\Layout;
class SchedulesController extends Controller
{
    const PAGE_NAME = "schedules";

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

        $schedules = Schedule::where('client_id', auth()->user()->client_id)->orderBy('client_id')->orderByDesc('id')->get();

        return view('admin/schedules/index', ['schedules' => $schedules, 'page_name' => self::PAGE_NAME]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $layout = Layout::where('client_id',auth()->user()->client_id)->get();
        return view('admin/schedules/create', ['page_name' => self::PAGE_NAME])->with(['layout' => $layout]);
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
            'name' => ['required', 'string', 'unique:schedules', 'max:255','regex:/^\S*$/u'],
            //'description' => 'required|string',
            'layout' => 'nullable|string|max:255',
        ]);

        $schedule = new Schedule($request->all());
        $schedule->client_id = auth()->user()->client_id;
        $schedule->user_id = auth()->user()->id;

        $schedule->save();

        return redirect(route('admin.schedules'))->with('success', 'A new schedule was created.');
    }
    public function clone(Request $request)
    {
        $validator = $request->validate([
            'name' => ['required', 'string', 'unique:schedules', 'max:255','regex:/^\S*$/u'],
            //'description' => 'required|string',
            'layout' => 'nullable|string|max:255',
        ]);
        $schedule = new Schedule($request->all());
        $schedule->client_id = auth()->user()->client_id;
        $schedule->user_id = auth()->user()->id;

        $schedule->save();
        $schedule_previous = Schedule::where('name' , substr($schedule->name , 0 , -5))->get();
        $schedule_entries = ScheduleEntry::where('schedule_id', $schedule_previous[0]->id)->get();

        $schedule_current = Schedule::where('name' ,$schedule->name)->get();
        foreach ($schedule_entries as $schedule_entry) {
            unset($schedule_entry->id,$schedule_entry->created_at,$schedule_entry->updated_at);
            $new_schedule_entry['date'] = $schedule_entry->date;
            $new_schedule_entry['time'] = $schedule_entry->time;
            $new_schedule_entry['line1'] = $schedule_entry->line1;
            $new_schedule_entry['line2'] = $schedule_entry->line2;
            $new_schedule_entry['line3'] = $schedule_entry->line3;
            $new_schedule_entry['image_id'] = $schedule_entry->image_id;
            $new_schedule_entry['schedule_entriable_id'] = $schedule_entry->schedule_entriable_id;

            $n_schedule_entry = new ScheduleEntry($new_schedule_entry);
            $n_schedule_entry->schedule_id = $schedule_current[0]->id;
            $n_schedule_entry->user_id = auth()->user()->id;
            $n_schedule_entry->save();
        }
        return response()->json([
            'schedule' => $schedule,
        ]);
        //return redirect(route('admin.schedules'))->with('success', 'A new schedule was created.');
    }
    public function schudule_entry_clone(Request $request)
    {


        $schedule_entry = new ScheduleEntry($request->all());
        $schedule_entry->client_id = auth()->user()->client_id;
        $schedule_entry->user_id = auth()->user()->id;

        $schedule_entry->save();

        return response() -> json ([
            'schedule_entry' => $schedule_entry
        ]);

    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id,  $key = null ,$is_schedule_active = 1)
    {

		//return  $key;
        $layout = Layout::where('client_id',auth()->user()->client_id)->get();
        $user_id = auth()->user()->id;

        $schedule = Schedule::join('users', 'schedules.user_id', '=', 'users.id')
        ->select('schedules.*')
        ->where('schedules.id',$id)
        ->first();
        if(auth()->user()->type=='user'){
            $schedule->where('users.id', $user_id);
        }
        $schedule->first();

        if (empty($schedule)) {
            return redirect(route('admin.schedules'))->with('warning', 'You are not allowed to access or edit this schedule.');
        }

        $schedule_entries = Schedule::where('client_id',auth()->user()->client_id)->get();

       // $schedule_entries= ScheduleEntry::where('schedule_id' , $schedule->id)->get();
        $images = Image:: where('user_id', $schedule->user_id )->get();

		$temp_images = [];
		foreach($images as $image) {
			$temp_images[$image->id] = $image;
		}


        if (empty($schedule)) {
            return redirect(route('admin.schedules'))->with('warning', 'warning.');
        }

        return view('admin/schedules/edit', ['schedule' => $schedule,'key'=>$key, 'is_schedule_active' => $is_schedule_active, 'page_name' => self::PAGE_NAME , 'images' => $temp_images , 'schedule_entries'=>$schedule_entries ])->with(['layout' => $layout]);
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
                            Rule::unique('schedules')->ignore($id)
                        ],
            //'description' => 'required|string',
            'layout' => 'nullable|string|max:255',
        ]);

        $schedule = Schedule::find($id);

        $schedule->fill($request->all());
        $schedule->save();

		if($request->key){
		//admin/flows/82/edit/is_flow_active
			return redirect(url('admin/flows/' .$request->key. '/edit/is_flow_active'))->with('success', 'A schedule was updated.');
		}else{
		  return redirect(url('admin/schedules/' . $id . '/edit/is_schedule_active#schedule_entries'))->with('success', 'A schedule was updated.');
		}



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
        $schedule = Schedule::join('users', 'schedules.user_id', '=', 'users.id')
        ->select('schedules.*')
        ->where('schedules.id',$request->id)
        ->where('users.id',$user_id)
        ->first();

        if (empty($schedule)) {
            return 'No Access';
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
        $schedule = Schedule::join('users', 'schedules.user_id', '=', 'users.id')
        ->select('schedules.*')
        ->where('schedules.id',$id)
        ->where('users.id',$user_id)
        ->first();

        if(empty($schedule)) {
            return redirect(route('admin.schedules'))->with('warning', 'You are not allowed to delete this schedule.');
        }

        Schedule::destroy($id);
        return redirect(url('admin/schedules'))->with('success', 'A schedule was deleted.');
    }


    public function schedule_entry_store(Request $request, $schedule_id, $is_schedule_active) {
        $validator = $request->validate([
			'schedule_entriable_id' => 'required',
        ]);


        $schedule_entry = new ScheduleEntry($request->all());
        $schedule_entry->schedule_id = $schedule_id;
        $schedule_entry->client_id = auth()->user()->client_id;

        $schedule_entry->user_id = auth()->user()->id;
        $schedule_entry->save();


        return redirect(url('admin/schedules/' . $schedule_id . '/edit/is_schedule_active/' . $is_schedule_active))->with('success', 'A new schedule entry was created.');
    }
    public function schedule_entry_clone(Request $request,$schedule_id) {


        //dd($request->all());
        $current_schedule_entry = ScheduleEntry::find($request->id);

        $schedule_entry = new ScheduleEntry();
        $schedule_entry->date = $current_schedule_entry->date;
		$schedule_entry->schedule_entriable_id = $current_schedule_entry->schedule_entriable_id;
        $schedule_entry->run_from = $current_schedule_entry->run_from;
        $schedule_entry->run_to = $current_schedule_entry->run_to;
        $schedule_entry->time = $current_schedule_entry->time;
        $schedule_entry->image_id = $current_schedule_entry->image_id;
	    $schedule_entry->line1 = $current_schedule_entry->line1;
        $schedule_entry->line2 = $current_schedule_entry->line2;
        $schedule_entry->line3 = $current_schedule_entry->line3;
		$schedule_entry->schedule_id =  $current_schedule_entry->schedule_id;
        $schedule_entry->client_id = auth()->user()->client_id;
        $schedule_entry->user_id = auth()->user()->id;

        $schedule_entry->save();

        return response()->json([
            'schedule_entry'=>$schedule_entry
        ]);

    }
    public function schedule_entry_move(Request $request, $id, $is_schedule_active) {

		if($request->_copy == 1){

	    $schedule = ScheduleEntry::find($request->_schedule_entry_id);

		$schedule_entry = new ScheduleEntry();

	    $schedule_entry->date = $schedule->date;
		$schedule_entry->schedule_entriable_id = $schedule->schedule_entriable_id;
        $schedule_entry->run_from = $schedule->run_from;
        $schedule_entry->run_to = $schedule->run_to;
        $schedule_entry->time = $schedule->time;
        $schedule_entry->image_id = $schedule->image_id;
	    $schedule_entry->line1 = $schedule->line1;
        $schedule_entry->line2 = $schedule->line2;
        $schedule_entry->line3 = $schedule->line3;
		$schedule_entry->schedule_id = $request->_schedule;
        $schedule_entry->client_id = auth()->user()->client_id;
        $schedule_entry->user_id = auth()->user()->id;

        $schedule_entry->save();

		}
		else{

		$schedule_entry = ScheduleEntry::find($request->_schedule_entry_id);
        $schedule_entry->schedule_id = $request->_schedule;
        $schedule_entry->user_id = auth()->user()->id;
        $schedule_entry->save();
		}


        return response()->json([
            'schedule_entry'=>$schedule_entry
        ]);
    }

    public function get_schedule_entry($id, $schedule_entry_id) {


        $schedule_entry = ScheduleEntry::find($schedule_entry_id);
        //print_r($schedule_entry);
        $image='';
        if($schedule_entry->image_id){
            $images = Image :: where('id' , $schedule_entry->image_id)->get();
            if(!empty($images[0])){
                $image = $images[0];
            }
        }

        $schedule_entriable_names = collect();

        switch ($schedule_entry->schedule_entriable_id) {
            case 'kids':
            case 'adults':
            case 'general':
                $schedule_entriable_id = $schedule_entry->schedule_entriable_id;
                break;
        }
        return response()->json([
            'schedule_entry' => $schedule_entry,
            'schedule_entriable_id' => $schedule_entriable_id,
            'images' => $image
        ]);
    }

    public function schedule_entry_update(Request $request, $schedule_id, $schedule_entry_id, $is_schedule_active) {

        $validator = $request->validate([
			'schedule_entriable_id' => 'required',
        ]);


        $schedule_entry = ScheduleEntry::find($schedule_entry_id);
        $schedule_entry->fill($request->all());
        $schedule_entry->schedule_id = $schedule_id;
        $schedule_entry->user_id = auth()->user()->id;
        $schedule_entry->save();

        return redirect(url('admin/schedules/' . $schedule_id . '/edit/is_schedule_active/' . $is_schedule_active))->with('success', 'A schedule entry was updated.');

    }

    public function schedule_entry_delete($schedule_id, $schedule_entry_id) {
        ScheduleEntry::destroy($schedule_entry_id);

        return redirect(url('admin/schedules/' . $schedule_id . '/edit/is_schedule_active'))->with('success', 'A schedule entry was deleted.');

    }
}