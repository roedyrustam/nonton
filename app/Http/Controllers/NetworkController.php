<?php

namespace App\Http\Controllers;

use App\Network;
use App\MovieCast;
use App\Movie;
use App\Serie;
use App\Anime;
use App\Setting;
use App\Http\Requests\NetworkRequest;
use App\Http\Requests\GenreRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Http\Requests\StoreImageRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Validator;


class NetworkController extends Controller
{


    const STATUS = "status";
    const MESSAGE = "message";
    const VIEWS = "views";



    // return all movies of a genre
    public function showNetworks($network)
    {


        $genresMovies =
        DB::raw('(SELECT SUBSTRING_INDEX(GROUP_CONCAT(genres.name SEPARATOR ", "), ",", 1)
        FROM genres JOIN movie_genres ON genres.id = movie_genres.genre_id WHERE movie_genres.movie_id = movies.id)
        AS genre_name');


        $genresSeries =
        DB::raw('(SELECT SUBSTRING_INDEX(GROUP_CONCAT(genres.name SEPARATOR ", "), ",", 1)
        FROM genres JOIN serie_genres ON genres.id = serie_genres.genre_id WHERE serie_genres.serie_id = series.id) AS genre_name');

        $genresAnimes =
        DB::raw('(SELECT SUBSTRING_INDEX(GROUP_CONCAT(genres.name SEPARATOR ", "), ",", 1)
        FROM genres JOIN anime_genres ON genres.id = anime_genres.genre_id WHERE anime_genres.anime_id = animes.id) AS genre_name');


        $selectSerie = [
            'series.id', 'name', 'poster_path', 'backdrop_path',
                        'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date',
                        'pinned', 'series.created_at','series.updated_at', 'views', DB::raw("'serie' AS type")
        ];


        $selectAnime = [
            'animes.id', 'name', 'poster_path', 'backdrop_path',
                    'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date', 
                    'pinned', 'animes.created_at','animes.updated_at','views', DB::raw("'anime' AS type")
        ];


        $selectMovie = [
            'movies.id',
            'title AS name',
            'poster_path',
            'backdrop_path',
            'backdrop_path_tv',
            'vote_average',
            'subtitle',
            'overview',
            'release_date',
            'pinned',
            'movies.created_at',
            'movies.updated_at',
            'views',
            DB::raw("'movie' AS type")
        ];

    
        $settings = Setting::query()->first();

        if ($settings->anime) {

            $latest = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes,$network) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                ->join('movie_networks', 'movies.id', '=', 'movie_networks.movie_id')
                ->where('movie_networks.network_id', '=', $network)
                    ->from('movies')
                    ->where('active', '=', 1)
                    ->orderBy('movies.created_at', 'desc');

                    $query->unionAll(function ($query) use ($selectSerie,$genresSeries,$network) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                        ->join('serie_networks', 'series.id', '=', 'serie_networks.serie_id')
                         ->where('serie_networks.network_id', '=', $network)
                            ->from('series')
                            ->where('active', '=', 1)
                            ->orderBy('created_at', 'desc');
                    });
    
                    $query->unionAll(function ($query) use ($selectAnime,$genresAnimes,$network) {
                        $query->select(array_merge(
                            $selectAnime,
                            [
                                $genresAnimes,
                            ]
                        ))
                        ->join('anime_networks', 'animes.id', '=', 'anime_networks.anime_id')
                        ->where('anime_networks.network_id', '=', $network)
                            ->from('animes')
                            ->where('active', '=', 1)
                            ->orderBy('created_at', 'desc');
                    });
            })
           ->orderByDesc('created_at');

        } 

        return response()->json($latest->paginate(12), 200);
    }



     // save a new image in the movies folder of the storage
     public function storeImg(StoreImageRequest $request)
     {
         if ($request->hasFile('image')) {
             $filename = Storage::disk('casts')->put('', $request->image);
             $data = ['status' => 200, 'image_path' => $request->root() . '/api/casts/image/' . $filename, 'message' => 'successfully uploaded'];
         } else {
             $data = ['status' => 400, 'message' => 'could not be uploaded'];
         }
 
         return response()->json($data, $data['status']);
     }


 // returns all genres for the api
 public function index()
 {
     return response()->json(Network::All(), 200);
 }

 // returns all genres for the admin panel
 public function datawebnetworks()
 {
     return response()->json(Network::All(), 200);
 }

 // create a new genre in the database
 public function store(NetworkRequest $request)
 {
     $genre = new Network();
     $genre->fill($request->all());
     $genre->save();

     $data = [
         'status' => 200,
         'message' => 'successfully created',
         'body' => $genre
     ];

     return response()->json($data, $data['status']);
 }


 public function destroy($genre)
    {
        if ($genre != null) {
            Network::find($genre)->delete();
            $data = [
                'status' => 200,
                'message' => 'successfully deleted'
            ];
        } else {
            $data = [
                'status' => 400,
                'message' => 'could not be deleted'
            ];
        }

        return response()->json($data, $data['status']);
    }

 // update a genre in the database
 public function update(NetworkRequest $request, Network $network)
 {


        if ($network != null) {


            $network->fill($request->all());
            $network->save();
            $data = [
                self::STATUS => 200,
                self::MESSAGE => 'successfully updated',
                'body' => $network
            ];


        } else {
            $data = [
                self::STATUS => 400,
                self::MESSAGE => 'could not be updated',
            ];
        }


        return response()->json($data, $data[self::STATUS]);
    
 }

 // return all genres only with the id and name properties
 public function list()
 {


     return response()->json(['networks' => Network::select(['id',
     'name','logo_path'])->limit(10)->get()], 200);

 }


 public function lists()
 {
     return response()->json(['networks' => Network::select('networks.id',
     'networks.name','networks.logo_path')->get()], 200);

 }


      // return an image from the movies folder of the storage
      public function getImg($filename)
      {
  
          $image = Storage::disk('casts')->get($filename);
  
          $mime = Storage::disk('casts')->mimeType($filename);
  
          return (new Response($image, 200))->header('Content-Type', $mime);
      }
  

}
