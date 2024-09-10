<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    protected $fillable = [
        'name', 'description', 'google_link','gallery_style','layout'
    ];

	public function google_images_for_flow()
    {
        return $this->hasMany(SyncGoogleImage::class)->limit(14);
    }

    public function sync_google_images()
    {
        return $this->hasMany(SyncGoogleImage::class);
    }
}
