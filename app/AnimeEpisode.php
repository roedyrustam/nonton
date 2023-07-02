<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use BeyondCode\Comments\Traits\HasComments;

class AnimeEpisode extends Model
{

    use HasComments;

    protected $fillable = ['tmdb_id', 'episode_number', 'name', 'overview', 'still_path','still_path_tv',
     'vote_average', 'vote_count', 'air_date','hasrecap','skiprecap_start_in','enable_stream','enable_media_download','enable_ads_unlock'];

    protected $with = ['videos', 'substitles','downloads'];

     use HasComments;

    
    protected $casts = [
        'hasrecap' => 'int',
        'skiprecap_start_in' => 'int',
        'enable_stream' => 'int',
        'enable_media_download' => 'int',
        'enable_ads_unlock' => 'int'

    ];
    

    public function season()
    {

        return $this->belongsTo(AnimeSeason::class, 'season_id');
    }

    public function videos()
    {
        return $this->hasMany('App\AnimeVideo');
    }


    public function downloads()
    {
        return $this->hasMany('App\AnimeDownload');
    }

    public function substitles()
    {
        return $this->hasMany('App\AnimeSubstitle');
    }
}
