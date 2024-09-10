<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Wetter;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('welcome');
    }
    public function weatherImage(){
        $alllinks=Wetter::all();
        foreach ($alllinks as $alllink) {
            $html_content=$this->getImageFromUrl($alllink->url,$alllink->city."_".$alllink->imagetype);
            Wetter::where('id',$alllink->id)->update(['imagepath' => $html_content]);
        }

    }
    public function getImageFromUrl($url,$id){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/32.0.1700.107 Chrome/32.0.1700.107 Safari/537.36');
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie-name');  //could be empty, but cause problems on some hosts
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');  //could be empty, but cause problems on some hosts
        $html = curl_exec($ch);
        if (curl_error($ch)) {
            echo curl_error($ch);
        }

        $doc =new \DOMDocument(); // create DOMDocument
        libxml_use_internal_errors(true);

        $doc->loadHTML($html); // load HTML you can add $html
        //another request preserving the session
        if ($html) {
            $xpath =  new \DOMXPath($doc);
            $backLink = $xpath->query('//div[@id="blooimage"]');
            $node = $backLink->item(0);
            $href = $node->getAttribute('data-href');
            $path = "https:".$href;
            $filename =$id.".png";
            //dd(public_path('storage/images/demo/').$filename);
            file_put_contents(public_path('storage/images/demo/').$filename,file_get_contents($path));
            //Image::make($path)->save(storage_path('images/' . $filename));
            //copy($href, storage_path()."/images/wetterbruchsal.jpg");
            if(file_exists(public_path('storage/images/demo/').$filename)){
               return "images/demo/".$filename;
            }

        }
    }
}
