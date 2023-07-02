<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Cashier\Billable;
use App\Notifications\PasswordReset;
use Laravel\Passport\HasApiTokens;
use BeyondCode\Comments\Contracts\Commentator;
use ChristianKuri\LaravelFavorite\Traits\Favoriteability;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Carbon;


class User extends Authenticatable implements Commentator ,MustVerifyEmail
{
    use Notifiable, HasApiTokens,Billable,HasFactory,Favoriteability;

    protected $with = ['profiles','devices'];



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','avatar', 'premuim','manual_premuim','pack_name','pack_id','start_at','expired_in','role','email_verified_at'
        ,'type', 'provider_name', 'provider_id','phone'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];


    protected $casts = [
        'premuim' => 'int',
    
    ];


    protected $appends = ['favoritesMovies','favoritesSeries','favoritesAnimes','favoritesStreaming'];


    protected $dates = [
        'email_verified_at' => 'datetime', 'trial_ends_at', 'subscription_ends_at','created_at'
    ];



    public function findFacebookUserForPassport($token) {
        // Your logic here using Socialite to push user data from Facebook generated token.
    }

    
    public function sendPasswordResetNotification($token)
{
    $this->notify(new PasswordReset($token));
}



    public function needsCommentApproval($model): bool
    {
        return false;    
    }



    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }


    public function devices()
    {



     return $this->hasMany(Device::class);


    }

    public function getFavoritesMoviesAttribute()
    {


        $settings = Setting::query()->first();

        $newEpisodes = [];

        if ($settings->favoriteonline) {


            $movies = $this->favorite(Movie::class);

            $subset = $movies->map(function ($user) {
                return collect($user->toArray())
                    ->only(['id', 'title', 'poster_path'])
                    ->all();
            });
    
    
          
            foreach ($subset as $item) {
                array_push($newEpisodes, $item);
            }

        }

        return $newEpisodes;


    }


    public function getFavoritesSeriesAttribute()
    {


        $settings = Setting::query()->first();

        $newEpisodes = [];

        if ($settings->favoriteonline) {

            $movies = $this->favorite(Serie::class);

            $subset = $movies->map(function ($user) {
                return collect($user->toArray())
                ->only(['id', 'name', 'poster_path'])
                    ->all();
            });
    

            foreach ($subset as $item) {
                array_push($newEpisodes, $item);
            }


        }

       
        return $newEpisodes;
    }



    public function getFavoritesAnimesAttribute()
    {


        $settings = Setting::query()->first();

        $newEpisodes = [];

        if ($settings->favoriteonline) {

            $movies = $this->favorite(Anime::class);

            $subset = $movies->map(function ($user) {
                return collect($user->toArray())
                ->only(['id', 'name', 'poster_path'])
                    ->except(['seasons', 'casterslist', 'casters', 'networkslist', 'networks.network'])
                    ->all();
            });
    

            foreach ($subset as $item) {
                array_push($newEpisodes, $item);
            }


        }
      
       
        return $newEpisodes;
    }

    public function getFavoritesStreamingAttribute()
    {

        $settings = Setting::query()->first();

        $newEpisodes = [];

        if ($settings->favoriteonline) {

            $livetv = $this->favorite(Livetv::class);

            $subset = $livetv->map(function ($user) {
                return collect($user->toArray())
                ->only(['id', 'name', 'poster_path'])
                    ->all();
            });
    
    
            foreach ($subset as $item) {
                array_push($newEpisodes, $item);
            }


        }

       
        return $newEpisodes;
    }


    
}
