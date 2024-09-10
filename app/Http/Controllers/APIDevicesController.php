<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Device;
use App\User;
use App\Image;
use App\SyncGoogleImage;
use App\Client;
use App\Gallery;
use App\Site;
use App\Video;
use App\Schedule;
use App\ScheduleEntry;
use App\Flow;
use Illuminate\Filesystem\Filesystem;
use Storage;
use Carbon\Carbon;
use File;
class APIDevicesController extends Controller
{
    public function index(Request $request) {

        if (empty($request->all()) || $request->filled("google")|| $request->filled("googlev2") || $request->filled("googlev3") || $request->filled("flow") || $request->filled("site") || $request->filled("video") || $request->filled("schedule") || $request->filled("image")) {
            $data = [];
			if ($request->filled('title') && $request->title == "off") {
                $data['title'] = 'off';
            }

			if ($request->filled('border')) {
				$data['border'] = $request->border;
			}

			if ($request->filled('color')) {
				$data['color'] = $request->color;
			}

			if ($request->filled('design')) {
                $data['design'] = $request->design;
            }

            if ($request->filled('clientname')) {
                $client = Client::where('name', $request->clientname)->first();
                $client_folder = strtolower($request->clientname);

                if(File::exists(public_path().'/css/'.$request->clientname)) {
                    // path exist
                    $client_folder = strtolower($request->clientname);
                }else{
                    //find folder without space
                    $client_folder = strtolower(str_replace(' ', '', $request->clientname));
                }

                if (!empty($client)) {

                    if ($request->filled("google")) {
                        $gallery = Gallery::where('client_id',$client->id)->where('name', $request->google)->first();
                        
                        if ($gallery) { // Null-Check for checking, if the gallery exists
                            $gallery_id = $gallery->id;
                            $data['google_images'] = $gallery->sync_google_images;
                            $data['label'] = "";
                            if ($gallery->description) {
                                $data['label'] = $gallery->description;
                            } else{
                                abort(404, 'Gallery not found');
                            }
                        }

                        if($gallery){ // Null-Check for checking, if the gallery exists
                            if($gallery->gallery_style=='v2'){
                                //dd($gallery);
                                return view('google_gallery_v2', ['client_folder'=>$client_folder,'gallery_id' => $gallery_id,'gallery' => $gallery,'data' => $data]);
                                //dd($gallery);
                            }elseif($gallery->gallery_style=='v3'){

                                return view('google_gallery_v3', ['client_folder'=>$client_folder,'gallery_id' => $gallery_id,'gallery' => $gallery,'data' => $data]);
                            }else{
                                return view('google_gallery', ['client_folder'=>$client_folder,'gallery_id' => $gallery_id,'gallery' => $gallery,'data' => $data]);
                            }
                        }
                    }elseif ($request->filled("site")) {

                        $data['site'] = Site::where('client_id',$client->id)->where('name', $request->site)->first();

                        return view('site', $data);
                    } elseif ($request->filled("video")) {

                        $data['video'] = Video::where('client_id',$client->id)->where('name', $request->video)->first();
                        return view('video', $data);
                    } elseif ($request->filled("image")) {
                        $data['image'] = Image::where('client_id',$client->id)->where('name', $request->image)->first();

                        return view('image', $data);
                    } elseif ($request->filled("schedule")) {

                        $current_date = date('Y-m-d');
                        $schedule = Schedule::where('client_id',$client->id)->where('name', $request->schedule)->first();
                        $data['schedule'] = $schedule;

                        $schedule_entries = ScheduleEntry::where('schedule_id',$schedule->id)->get();

                        foreach ($schedule_entries as $schedule_entry) {
                            $sch_date = date('Y-m-d',strtotime($schedule_entry->date));
                            if($sch_date < $current_date) {
                                continue;
                            }else{
                                $data['schedule_entries'][$schedule_entry->id] = $schedule_entry ;
                                $data['images'][$schedule_entry->id] = Image::where('id',$schedule_entry->image_id)->get();
                            }

                        }

                        if ($data) {
                            $client = Client::where('id', $schedule->client_id)->first();
                            $data['client_name'] = strtolower($client->name);

                            if($schedule->schedule_style=='liste'){
                                return view('schedule_liste', ['data' => $data] );
                            }
                            return view('schedule', ['data' => $data] );
                        }
                    } elseif ($request->filled("flow")) {
                        $data['client_name'] = strtolower($client->name);
                        $current_date = date('Y-m-d');
                        $flow = Flow::where('client_id',$client->id)->where('name', $request->flow)->first();
                        if(!empty($flow)) {
                        $flow_entries  = $flow->flow_entries()->orderBy('sequence')->get();

                            foreach ($flow_entries as $flow_entry) {

                                if ($flow_entry->run_from){
                                    if (!(date('Y-m-d' , strtotime($flow_entry->run_from)) <= $current_date)) {
                                        continue;
                                    }
                                }

                                if ($flow_entry->run_to){
                                    if (!(date('Y-m-d' , strtotime($flow_entry->run_to)) >= $current_date)) {
                                        continue;
                                    }
                                }

                                if ($flow_entry->dates){
                                    if (!in_array(date('d.m.Y' , strtotime($current_date)), explode(",", $flow_entry->dates))) {
                                        continue;
                                    }
                                }

                                if ($flow_entry->flow_entriable_type == "App\Gallery") {
                                    $gallery = Gallery::find($flow_entry->flow_entriable_id);
                                    $data['gallery_ids'][] = $gallery->id;
                                    if ($gallery) {
                                        $data[$flow_entry->sequence]['description'] = $gallery->description;
                                        $data[$flow_entry->sequence]['google_images'][$flow_entry->id] = $gallery->google_images_for_flow;
                                        //$data['time']['google_images'][$flow_entry->id] = $flow_entry->time;

                                        $data[$flow_entry->sequence]['title']['google_images'][$flow_entry->id] = "";
                                        if ($gallery->google_images_for_flow()->first()) {
                                            $data[$flow_entry->sequence]['title']['google_images'][$flow_entry->id] = $gallery->google_images_for_flow()->first()->title;
                                        }
                                    }
                                } elseif ($flow_entry->flow_entriable_type == "App\Site") {

                                    $data[$flow_entry->sequence]['sites'][$flow_entry->id] = Site::find($flow_entry->flow_entriable_id);
                                    $data[$flow_entry->sequence]['time']['sites'][$flow_entry->id] = $flow_entry->time;
                                    $data[$flow_entry->sequence]['refreshTime']['sites'][$flow_entry->id] = $flow_entry->refreshTime;

                                } elseif($flow_entry->flow_entriable_type == "App\Video") {

                                    $data[$flow_entry->sequence]['videos'][$flow_entry->id] = Video::find($flow_entry->flow_entriable_id);
                                    $data[$flow_entry->sequence]['time']['videos'][$flow_entry->id] = $flow_entry->time;

                                } elseif ($flow_entry->flow_entriable_type == "App\Image") {
                                    $data[$flow_entry->sequence]['images'][$flow_entry->id] = Image::find($flow_entry->flow_entriable_id);
                                    $data[$flow_entry->sequence]['time']['images'][$flow_entry->id] = $flow_entry->time;
                                    $data[$flow_entry->sequence]['width'] = $flow_entry->width;

                                }  elseif ($flow_entry->flow_entriable_type == "App\Schedule") {

                                    $schedule = Schedule::where('name', $flow_entry->flow_entriable_id)->first();

                                    $current_date = date('Y-m-d');
                                    if($schedule){
                                        $schedule_entries = ScheduleEntry::where('schedule_id',$schedule->id)->get();

                                    $schedule->schedule_entries()->get();
                                    //dd($schedule);
                                    //$data['schedule'] = $schedule;

                                    foreach ($schedule_entries as $schedule_entry) {
                                        $sch_date = date('Y-m-d',strtotime($schedule_entry->date));
                                        if($sch_date < $current_date) {
                                            continue;
                                        }
                                        $data[$flow_entry->sequence]['schedule_style'] = $schedule->schedule_style;
                                        $data[$flow_entry->sequence]['schedule_entries'][$flow_entry->id] = $schedule->schedule_entries()->get();
                                        $data[$flow_entry->sequence]['description'] = $schedule->description;
                                        $data[$flow_entry->sequence]['time']['schedule_entries'][$flow_entry->id] = $flow_entry->time;
                                        $images = Image::where('client_id',$client->id)->get();
                                        $temp_images = [];
                                        foreach( $images as $image  )
                                        {
                                            $temp_images[$image->id] = $image;
                                        }
                                        $data[$flow_entry->sequence]['schedule']['images'] = $temp_images;


                                    }


                                    }



                                    //dd($data['images'][$flow_entry->id]);
                                    //dd($data['schedule_entries']);
                                }




                            }
                        }

                        //if ($data) {
                            return view('flow', ['data' => $data]);
                        //}
                    }
                }



            }
            return view('welcome', ['data' => $data]);
        }


        function replaceGermanMonth($month) {
            switch ($month) {
            case 'Januar':
                return "January";
                break;
            case 'Februar':
                return "February";
                break;
            case "März":
                return "March";
                break;
            case "April":
                return "April";
                break;
            case "Mai":
                return "May";
                break;
            case "Juni":
                return "June";
                break;
            case "Juli":
                return "July";
                break;
            case "August":
                return "August";
                break;
            case "September":
                return "September";
                break;
            case "Oktober":
                return "October";
                break;
            case "November":
                return "November";
                break;
            case "Dezember":
                return "December";
                break;
            default:
                break;
            }
        }


		date_default_timezone_set('Europe/Berlin');
        $current_timestamp = date('Y-m-d H:i:s');

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $current_ip = $_SERVER['HTTP_CLIENT_current_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $current_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $current_ip = $_SERVER['REMOTE_ADDR'];
        }

        switch ($request->api){
            case "register":

                if (!$request->filled('device_code')) {
                    return "Error: Device register";
                }

                $device = Device::where("device_code", $request->device_code)->first();

                if(empty($device)) {

                    $device = new Device();
                    $device->device_code = $request->device_code;
                    // $device->user_id
                    $device->enabled = 0;
                    $device->timestamp_registered = $current_timestamp;
                    // $device->eMail_of_admin
                    // $device->configuration
                    // $device->device_up_time
                    // $device->device_down_time
                    // $device->device_heartbeat_minutes
                    $device->timestamp_last_accessed = $current_timestamp;
                    $device->timestamp_last_register = $current_timestamp;
                    $device->ip_address_of_last_access = $current_ip;
                    $device->force_restart_enabled = 0;
                    $device->save();

                    return response()->json(["info" => "New device registered, please wait for enabling"]);
                } else if($device->enabled) {
//                    $device_update = new Device();
                    $device_update = Device::find($device->id);
                   // $device_update->fill($device_update);
                    $device_update->force_restart_enabled = 0;
                    $device_update->timestamp_last_register = $current_timestamp;
					$device_update->force_restart_enabled = 0;
                    $device_update->save();
					if ($device->client_id) {
                        $client_name = Client::where('id', $device->client_id)->get();
	                    return response()->json(["id"=>$device->client_id , "client_name" => $client_name[0]->name] );
					} else {
						return response()->json(["error" => "Not assign client"]);
					}
                }
                return response()->json(["error" => "Not yet enabled"]);

                break;
            case "setup":

                $device = Device::where("device_code", $request->device_code)->first();

                $device_update = Device::find($device->id);
                $device_update->timestamp_last_setup = $current_timestamp;
                $device_update->save();
                if (!$request->filled('device_code') || !$request->filled('client_id')) {
                    return response()->json(["error" => "Device Code and Client ID are required."]);
                }

                $device = Device::select("device_code", "client_id", "enabled", "timestamp_registered", "eMail_of_admin", "configuration", "device_up_time", "device_down_time", "device_heartbeat_minutes", "timestamp_last_accessed", "ip_address_of_last_access","show_at_frontend","frontend_refer","frontend_layout")->where('device_code', $request->device_code)->where('client_id', $request->client_id)->first();

				if (empty($device)) {
					return response()->json(["error" => "Not register"]);
				}
				$len = strlen($device->show_at_frontend);
				//dd($len);
				$new_letter = strtolower($device->show_at_frontend);
				if($new_letter != "free") $new_letter = str_replace("app\\","",$new_letter);
				if($new_letter == "gallery") $new_letter = "google";
                return response()->json([
                    "configuration" => $device->configuration,
                    "ShowAtFrontend" => $new_letter,
                    "FrontendRefer" => $device->frontend_refer,
                    "Layout" => $device->frontend_layout,
                    "Heartbeat" => $device->device_heartbeat_minutes
                ]);

                break;
            case "heartbeat":

                if (!$request->filled('device_code') || !$request->filled('client_id')) {
                    return response()->json(["error" => "Device Code and Client ID are required."]);
                }

                $device = Device::where("device_code", $request->device_code)->where("client_id", $request->client_id)->first();

                if (empty($device)) {
                    return response()->json(["error" => "Not register"]);
                }

                $device->timestamp_last_accessed = $current_timestamp;

                // if ($request->filled("ip_address_of_last_access")) {
                //     $device->ip_address_of_last_access = $request->ip_address_of_last_access;
                // }

                $device->save();
				$force_restart = $device->force_restart_enabled == 0? "Off":"On";
				return response()->json([
					'status' => 'ok',
					'ForceRestart' => $force_restart,
                    'Index'=> $device->ix
				]);
        }
    }

/**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function last_access_time_by_customer(Request $request)
    {
        if($request->ajax()){
            Gallery::whereIn('id',explode(',', $request->gallery_ids))
            ->update([
                'last_access_time'=> Carbon::now()
            ]);;
        }
    }


        /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cronjob_clean_gallery(Request $request)
    {

        $month_limit = Carbon::now()->startOfMonth()->subMonths($request->months)->toDateTimeString();
        SyncGoogleImage::join('galleries', 'galleries.id', '=', 'sync_google_images.gallery_id')->where('galleries.last_access_time', '<', $month_limit)
        ->select('last_access_time')
        ->delete();


        $galleries = Gallery::where('galleries.last_access_time', '<', $month_limit)
        ->select('id')
        ->get();

        foreach($galleries as $gallery){
            $directory_path = public_path().'/storage/gallery/'.$gallery->id;

            if(File::isDirectory($directory_path)){
                $file = new Filesystem;
                $file->cleanDirectory($directory_path);
                rmdir($directory_path); // This delete only empty folder
            }
        }


    }

}
