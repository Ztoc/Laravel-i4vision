<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Storage;
use File;
use App\SyncGoogleImage;
use App\Gallery;
use DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
class Crawler {

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $data;

    /**
     * Crawler constructor.
     * @param Client $client
     */
    public function __construct(Client $client = null)
    {
        if ($client === null) {
            $this->client = new Client();
        } else {
            $this->client = $client;
        }
    }

    /**
     * Fetch a google photo album using the public url
     * will return:
     *  id: id of the album
     *  name: name of the album
     *  images[]:
     *      id: id of the image
     *      url: the base url to download the image
     *      width: the max width of the image
     *      height: the max height of the image
     *
     * @param $url
     * @return array
     */

     public function getAlbum($url)
     {

         try {

             $response = $this->client->get($url);
             $html = $response->getBody()->getContents();
             \Illuminate\Support\Facades\Log::info('HTML content:', ['html' => $html]);

             $re = '/<script class="ds:[^"]+" nonce="[^"]+">AF_initDataCallback\(\{[^<]+, data:([^<]+)\}\);<\/script>/m';

            
            preg_match_all($re, $html, $matches, PREG_SET_ORDER, 0);
             $json = str_replace(', sideChannel: {}', '', $matches[1]);

            $data = json_decode($json[1], true);

            if(isset($data[1])){
             // limit to 15 entries by gallery
             //$data[1] = array_slice($data[1], 0, 15);
             $images = array_map(function ($image) {
                 // . '=w4032-h2268-no'
                 //  . '=w' . $image[1][1] . '-h' . $image[1][2] . '-no'
                 $image[1][1] = 1920;
                 $image[1][2] = 1280;
                 return [
                     'id' => $image[0],
                     // default url
                     'url' => $image[1][0] . '=w' . $image[1][1] . '-h' . $image[1][2] . '-no',
                     // max size
                     'width' => $image[1][1],
                     'height' => $image[1][2]
                 ];
             }, $data[1]);
             $this->data = [
                 'id' => $data[3][0],
                 'name' => $data[3][1],
                 'images' => $images
             ];
             return $this->data;
            }
         } catch (\GuzzleHttp\Exception\RequestException $e) {
             //dd("Not found");
         }
     }
 }

class SyncGoogleImagesController extends Controller
{
    public function save(Request $request) {

        $url = $request->albumURI;
        $gallery_id = $request->gallery_id;
        $message = $this->fn_store_file_to_directory_and_database($gallery_id, $url);
        return $message ;
    }

    public function sync_google_images_by_google_link(Request $request)
    {
        $gallery_id = $request->gallery_id;

        $url = 'https://photos.app.goo.gl/'.$request->google_link;
        $message = $this->fn_store_file_to_directory_and_database($gallery_id, $url);
        return $message;
    }

    public function sync_all_google_images(Request $request) {

        ini_set('max_execution_time', '3000');
        $galleries = Gallery::join('clients', 'galleries.client_id', '=', 'clients.id')
        ->select('galleries.*','clients.name')
        //->whereBetween('galleries.id',[381,410])
        ->get();
        //dd($galleries);
        foreach($galleries as $gallery){
            $gallery_id =  $gallery->id;
            $url = 'https://photos.app.goo.gl/'.$gallery->google_link;
            $this->fn_store_file_to_directory_and_database($gallery_id, $url);
        }
        return "congratulation!";
    }


    function fn_store_file_to_directory_and_database($gallery_id,$url){
        $crawler = new Crawler();
        $album = $crawler->getAlbum($url);

        if(!empty($album)){

            //delete current data by gallery id
            SyncGoogleImage::where('gallery_id',$gallery_id)->delete();
            $directory_path = public_path().'/storage/gallery/'.$gallery_id;
            //dd($directory_path);
            //Create New  directory
            File::makeDirectory($directory_path, $mode = 0777, true, true);

            foreach ($album['images'] as $image) {

                $url = $image['url'];
                $contents = file_get_contents($url);
                $name = substr($url, strrpos($url, '/') + 1);
                $local_path = 'storage/gallery/'.$gallery_id."/".$name;
                Storage::disk('public_dir')->put($local_path, $contents);

                DB::table('sync_google_images')
                ->updateOrInsert(
                    [
                        'gallery_id' => $gallery_id,
                        'url' => $url
                    ]
                );
            }
        return "congratulation!";
        }else{
            return "Not Found on Google Album...";
        }

    }

}
