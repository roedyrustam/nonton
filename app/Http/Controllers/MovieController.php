<?php

namespace App\Http\Controllers;


use App\Episode;
use App\Network;
use App\MovieNetwork;
use App\Setting;
use App\Upcoming;
use Illuminate\Support\Facades\Auth;
use App\Genre;
use App\Cast;
use App\Http\Requests\MovieStoreRequest;
use App\Http\Requests\MovieUpdateRequest;
use App\Http\Requests\StoreImageRequest;
use App\Jobs\SendNotification;
use App\Movie;
use App\MovieGenre;
use App\MovieSubstitle;
use App\MovieVideo;
use App\MovieDownload;
use App\MovieCast;
use App\Serie;
use App\Anime;
use App\Livetv;
use App\Setting as SettingAlias;
use App\User;
use App\Featured;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\File;
use App\Http\ClearsResponseCache;
use ChristianKuri\LaravelFavorite\Traits\Favoriteable;
use BeyondCode\Comments\Comment;
use Illuminate\Validation\ValidationException;
use Spatie\Ray\Ray;

class MovieController extends Controller
{

    const STATUS = "status";
    const MESSAGE = "message";
    const VIDEOS = "videos";

    use ClearsResponseCache,Favoriteable;




    public function __construct()
    {
        $this->middleware('doNotCacheResponse', ['only' => ['deletecomment','moviecomment','addcomment']]);
    }




    public function addRating(Request $request)
    {



        $user = Auth::user();

        $this->validate($request, [
            'rating' => 'required',
            'type' => 'required',
            'media_id' => 'required'
        ]);


        if($request->type == "movie" ){


            DB::table('movies')
            ->where('id', $request->media_id)
           ->update(
    
               array(
                   "vote_average" => request('rating')));

        }else if($request->type == "serie" ) {


            DB::table('series')
            ->where('id', $request->media_id)
           ->update(
    
               array(
                   "vote_average" => request('rating')));

        }else if($request->type == "serie" ) {


            DB::table('animes')
            ->where('id', $request->media_id)
           ->update(
    
               array(
                   "vote_average" => request('rating')));
        }


    


        $data = ['status' => 200, 'message' => 'successfully Added',];

        return response()->json($data, 200);

    }


  // return movie details
    public function show($id)
    {


        $movie = Movie::query()
        ->with(['casters.cast' => function ($query) {
            $query->select('id', 'name','original_name','profile_path');
        },'genres.genre','videos','downloads','substitles','networks.network'])
        ->with(['genres.genre' => function ($query) {
            $query->select('id', 'name');
        }])
        ->where('id', '=', $id)->first()->makeHidden(['casters','networks']);

        
          $movie->increment('views');


                // ray()->showQueries1();
                 ray()->measure();

        return response()->json($movie);


    }


    // return all the movies for the admin panel
    public function web()

    {

        return response()->json(Movie::query()->with(['genres','casters'])->orderByDesc('created_at')
            ->paginate(12));
    }

     // return all the movies for the admin panel
     public function search(): \Illuminate\Http\JsonResponse

     {

         return response()->json(200, Movie::orderByDesc('created_at')
             ->paginate(12));
     }


    // create a new movie in the database
    public function store(MovieStoreRequest $request)
    {
        $movie = new Movie();
        $movie->fill($request->movie);
        $movie->save();

        $this->onStoreMovieDownloads($request,$movie);
        $this->onStoreMovieVideo($request,$movie);
        $this->onStoreMovieCasters($request,$movie);
        $this->onStoreMovieGenres($request,$movie);
        $this->onStoreMovieSubstitles($request,$movie);
        $this->onStoreMovieNetworks($request,$movie);

        if ($request->notification) {
            $this->dispatch(new SendNotification($movie));
        }


        $data = ['status' => 200, 'message' => 'created successfully', 'body' => $movie];

        return response()->json($data, $data['status']);
    }



    public function onStoreMovieNetworks($request,$movie) {

        if ($request->movie['networks']) {
            foreach ($request->movie['networks'] as $network) {
                $find = Network::query()->find($network['id']);
                if ($find == null) {
                    $find = new Network();
                    $find->fill($network);
                    $find->save();
                }
                $serieNetwork = new MovieNetwork();
                $serieNetwork->network_id = $network['id'];
                $serieNetwork->movie_id = $movie->id;
                $serieNetwork->save();
            }
        }
    }

    public function onStoreMovieSubstitles($request,$movie) {


        if ($request->linksubs) {
            foreach ($request->linksubs as $substitle) {
                $movieSubstitle = new MovieSubstitle();
                $movieSubstitle->fill($substitle);
                $movieSubstitle->movie_id = $movie->id;
                $movieSubstitle->save();
            }
        }

    }




    public function onStoreMovieDownloads($request,$movie) {

        if ($request->linksDownloads) {
            foreach ($request->linksDownloads as $link) {

                $movieVideo = new MovieDownload();
                $movieVideo->fill($link);
                $movieVideo->movie_id = $movie->id;
                $movieVideo->save();
            }
        }
    }

    public function onStoreMovieVideo($request,$movie) {

        if ($request->links) {
            foreach ($request->links as $link) {

                $movieVideo = new MovieVideo();
                $movieVideo->fill($link);
                $movieVideo->movie_id = $movie->id;
                $movieVideo->save();
            }
        }

    }



    public function onStoreMovieGenres($request,$movie) {

        if ($request->movie['genres']) {
            foreach ($request->movie['genres'] as $genre) {
                $find = Genre::query()->find($genre['id']);
                if ($find == null) {
                    $find = new Genre();
                    $find->fill($genre);
                    $find->save();
                }
                $movieGenre = new MovieGenre();
                $movieGenre->genre_id = $genre['id'];
                $movieGenre->movie_id = $movie->id;
                $movieGenre->save();
            }
        }

    }

    public function onStoreMovieCasters($request,$movie) {

        if ($request->movie['casterslist']) {
            foreach ($request->movie['casterslist'] as $cast) {
                $find = Cast::find($cast['id']);
                if ($find == null) {
                    $find = new Cast();
                    $find->fill($cast);
                    $find->save();
                }
                $movieGenre = new MovieCast();
                $movieGenre->cast_id = $cast['id'];
                $movieGenre->movie_id = $movie->id;
                $movieGenre->save();
            }
        }

    }




    public function moviecomment($id)
    {


        $movie = Movie::query()->withOnly(['comments'])
        ->where('id', '=', $id)->first();


        return response()
        ->json(['comments' => $movie->comments], 200);


     


    }



    public function addcomment(Request $request)
    {


        $user = Auth::user();


        try {
            $this->validate($request, [
                'comments_message' => 'required',
                'movie_id' => 'required'
            ]);



            $movie = Movie::query()->where('id', '=', $request->movie_id)->first();

            $comment = $movie->commentAsUser($user, $request->comments_message);

            return response()->json($comment, 200);


        } catch (ValidationException $e) {


            return response()->json("Error", 400);
        }



    }



     public function deletecomment($movie,Request $request)
    {

        $user = Auth::user();

        if ($movie != null) {

            Comment::query()->find($movie)->delete();

            $data = ['status' => 200, 'message' => 'successfully deleted',];
        } else {
            $data = ['status' => 400, 'message' => 'could not be deleted',];
        }


        return response()->json($data, 200);

    }



    public function deletecommentweb($movie)
    {

        if ($movie != null) {

            $movie = Comment::query()->where('commentable_id', '=', $movie)->first();
            $movie->delete();
            $data = ['status' => 200, 'message' => 'successfully deleted',];
        } else {
            $data = [
                'status' => 400,
                'message' => 'could not be deleted',
            ];
        }

        return response()->json($data, $data['status']);
    }


    public function addtofav($movie,Request $request)
    {

        $movie = Movie::query()->where('id', '=', $movie)->first()->addFavorite($request->user()->id);

            return response()->json("Success", 200);

    }


    public function removefromfav($id,Request $request)
    {

        $movie =   Movie::query()->where('id', '=', $id)->first()->removeFavorite($request->user()->id);


        return response()->json("Added", 200);


    }


    public function isMovieFavorite($id,Request $request)
    {

        $movie = Movie::where('id', '=', $id)->first();

        if($movie->isFavorited($request->user()->id)) {

            $data = ['status' => 200, 'status' => 1,];

        }else {

            $data = ['status' => 400, 'status' => 0,];
        }

        return response()->json($data, 200);
    }





    public function userfav()
    {


        $user = Auth::user();
        $user->favorite(Movie::class);

        return response()->json($user, 200);


    }







    public function comments($movie)
    {


        $movie = Movie::where('id', '=', $movie)->first();

        $comments = $movie->comments;


        return response()
            ->json(['comments' => $comments], 200);

    }



    public function share($type,$movie)
    {


        if($type == "movie") {

            $movies = Movie::query()->select('movies.title','movies.id')->addSelect(DB::raw("'movie' as type"))
            ->where('id', '=', $movie)
            ->get();


            return response()->json($movies->makeHidden(['casterslist','casters','seasons','genres','genreslist','overview','backdrop_path','preview_path','videos'
            ,'substitles','vote_average','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview'
            ,'networks','downloads','networkslist']), 200);

        }else {

            $series = Serie::query()->select('series.name','series.id')->addSelect(DB::raw("'movie' as type"))
            ->where('id', '=', $movie)
            ->first();


            return response()->json($series->makeHidden(['casterslist','casters','seasons','genres','genreslist','overview','backdrop_path','preview_path','videos'
            ,'substitles','vote_average','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview'
            ,'networks','downloads','networkslist']), 200);

        }

    }



    // update a movie in the database
     public function update(MovieUpdateRequest $request, Movie $movie)
    {
        $movie->fill($request->movie);
        $movie->save();

        $this->onUpdateMovieCasts($request,$movie);
        $this->onUpdateMovieVideo($request,$movie);
        $this->onUpdateMovieGenres($request,$movie);
        $this->onUpdateMovieSubstitles($request,$movie);
        $this->onUpdateMovieDownloads($request,$movie);
        $this->onUpdateMovieNetwork($request,$movie);

        $data = ['status' => 200, 'message' => 'successfully updated', 'body' => "Success"];

        return response()->json($data, $data['status']);
    }

        public function onUpdateMovieNetwork($request,$movie) {

        if ($request->movie['networks']) {
            foreach ($request->movie['networks'] as $netwok) {
                if (!isset($netwok['network_id'])) {
                    $find = Network::query()->find($netwok['id']) ?? new Network();
                    $find->fill($netwok);
                    $find->save();
                    $movieNetwork = MovieNetwork::query()->where('movie_id', $movie->id)->where('network_id', $netwok['id'])->get();
                    if (count($movieNetwork) < 1) {
                        $movieNetwork = new MovieNetwork();
                        $movieNetwork->network_id = $netwok['id'];
                        $movieNetwork->movie_id = $movie->id;
                        $movieNetwork->save();
                    }
                }
            }
        }

    }


public function onUpdateMovieDownloads($request,$movie) {

        if ($request->linksDownloads) {
            foreach ($request->linksDownloads as $link) {
                if (!isset($link['id'])) {
                    $movieVideo = new MovieDownload();
                    $movieVideo->movie_id = $movie->id;
                    $movieVideo->fill($link);
                    $movieVideo->save();
                }
            }
        }

    }

public function onUpdateMovieVideo($request,$movie) {

        if ($request->links) {
            foreach ($request->links as $link) {
                if (!isset($link['id'])) {
                    $movieVideo = new MovieVideo();
                    $movieVideo->movie_id = $movie->id;
                    $movieVideo->fill($link);
                    $movieVideo->save();
                }
            }
        }

    }


public function onUpdateMovieCasts($request,$movie){

        if ($request->movie['casterslist']) {
            foreach ($request->movie['casterslist'] as $genre) {

                    $find = Cast::find($genre['id'] ?? 0) ?? new Cast();
                    $find->fill($genre);
                    $find->save();
                    $movieGenre = MovieCast::where('movie_id', $movie->id)
                        ->where('cast_id', $genre['id'])->get();

                    if (count($movieGenre) < 1) {
                        $movieGenre = new MovieCast();
                        $movieGenre->cast_id = $genre['id'];
                        $movieGenre->movie_id = $movie->id;
                        $movieGenre->save();

                    }

            }
        }

}

    public function onUpdateMovieGenres($request,$movie){

        if ($request->movie['genres']) {
            foreach ($request->movie['genres'] as $genre) {
                if (!isset($genre['genre_id'])) {
                    $find = Genre::find($genre['id'] ?? 0) ?? new Genre();
                    $find->fill($genre);
                    $find->save();
                    $movieGenre = MovieGenre::where('movie_id', $movie->id)
                        ->where('genre_id', $genre['id'])->get();
                    if (count($movieGenre) < 1) {
                        $movieGenre = new MovieGenre();
                        $movieGenre->genre_id = $genre['id'];
                        $movieGenre->movie_id = $movie->id;
                        $movieGenre->save();
                    }
                }
            }
        }

    }


    public function onUpdateMovieSubstitles($request,$movie) {

        if ($request->linksubs) {
            foreach ($request->linksubs as $substitle) {
                if (!isset($substitle['id'])) {

                    $movieVideo = new MovieSubstitle();
                    $movieVideo->movie_id = $movie->id;
                    $movieVideo->fill($substitle);
                    $movieVideo->save();
                }
            }
        }

    }

    // delete a movie in the database
    public function destroy(Movie $movie)
    {
        if ($movie != null) {
            $movie->delete();

            $data = ['status' => 200, 'message' => 'successfully removed',];
        } else {
            $data = ['status' => 400, 'message' => 'could not be deleted',];
        }

        return response()->json($data, $data['status']);
    }

    // remove the genre of a movie from the database
    public function destroyGenre($genre)
    {

        if ($genre != null) {

            MovieGenre::find($genre)->delete();

            $data = ['status' => 200, 'message' => 'successfully deleted',];
        } else {
            $data = ['status' => 400, 'message' => 'could not be deleted',];
        }

        return response()->json($data, 200);

    }



     // remove Network from  a movie
     public function destroyNetworks($id)
     {

         if ($id != null) {

             MovieNetwork::find($id)->delete();
             $data = ['status' => 200, 'message' => 'successfully deleted',];
         } else {
             $data = [
                 'status' => 400,
                 'message' => 'could not be deleted',
             ];
         }

         return response()->json($data, $data['status']);

     }


    // remove the cast of a movie from the database
    public function destroyCast($id)
    {

        if ($id != null) {

            $movie = MovieCast::where('cast_id', '=', $id)->first();
            $movie->delete();
            $data = ['status' => 200, 'message' => 'successfully deleted',];
        } else {
            $data = [
                'status' => 400,
                'message' => 'could not be deleted',
            ];
        }

        return response()->json($data, $data['status']);

    }

    // save a new image in the movies folder of the storage
    public function storeImg(StoreImageRequest $request)
    {
        if ($request->hasFile('image')) {
            $filename = Storage::disk('movies')->put('', $request->image);
            $data = ['status' => 200, 'image_path' => $request->root() . '/api/movies/image/' . $filename, 'message' => 'successfully uploaded'];
        } else {
            $data = ['status' => 400, 'message' => 'could not be uploaded'];
        }

        return response()->json($data, $data['status']);
    }

    // return an image from the movies folder of the storage
    public function getImg($filename)
    {

        $image = Storage::disk('movies')->get($filename);

        $mime = Storage::disk('movies')->mimeType($filename);

        return (new Response($image, 200))->header('Content-Type', $mime);
    }



    // remove a video from a movie from the database
    public function videoDestroy($video)
    {
        if ($video != null) {

            MovieVideo::find($video)->delete();


            $videoMovie = MovieVideo::find($video)->video_name;


            if ($videoMovie != null) {

                Storage::disk('videos')->delete($videoMovie);

            }


            $data = ['status' => 200, 'message' => 'successfully deleted',];
        } else {
            $data = ['status' => 400, 'message' => 'could not be deleted',];
        }

        return response()->json($data, 200);
    }



    public function downloadDestroy($download)
    {
        if ($download != null) {

            MovieDownload::find($download)->delete();

            $data = ['status' => 200, 'message' => 'successfully deleted',];
        } else {
            $data = ['status' => 400, 'message' => 'could not be deleted',];
        }

        return response()->json($data, 200);
    }


    public function substitleDestroy($substitle)
    {
        if ($substitle != null) {

            MovieSubstitle::find($substitle)->delete();

            $data = ['status' => 200, 'message' => 'successfully deleted',];
        } else {
            $data = ['status' => 400, 'message' => 'could not be deleted',];
        }

        return response()->json($data, 200);
    }





    public function mobile()
    {



        $selectAnimeEpisodes =  ['anime_videos.anime_episode_id','animes.id'
        ,'animes.name','anime_episodes.still_path','anime_episodes.anime_season_id','anime_episodes.name as episode_name','anime_videos.link','anime_videos.server','anime_videos.lang'
        ,'anime_videos.embed','anime_videos.youtubelink','anime_videos.hls','anime_seasons.name as seasons_name','anime_seasons.season_number','anime_episodes.vote_average'
        ,'animes.premuim','animes.tmdb_id','anime_episodes.episode_number','animes.poster_path',
        'anime_episodes.hasrecap',
        'anime_episodes.skiprecap_start_in','anime_videos.supported_hosts'
        ,'anime_videos.drmuuid','anime_videos.drmlicenceuri','anime_videos.drm'];




        $selectSerieEpisodes =  [  'serie_videos.episode_id','series.id','series.tmdb_id as serieTmdb'
        ,'series.name','episodes.still_path','episodes.season_id','episodes.name as episode_name','serie_videos.link','serie_videos.server','serie_videos.lang'
        ,'serie_videos.embed','serie_videos.youtubelink','serie_videos.hls','seasons.name as seasons_name','seasons.season_number','episodes.vote_average'
        ,'series.premuim','episodes.episode_number','series.poster_path','episodes.hasrecap','episodes.skiprecap_start_in'
        ,'serie_videos.supported_hosts','serie_videos.header','serie_videos.useragent','series.imdb_external_id'
        ,'serie_videos.drmuuid','serie_videos.drmlicenceuri','serie_videos.drm'];

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

        $selectMovie = [
            'id', 'title AS name', 'poster_path', 'backdrop_path',
                    'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'release_date', 'pinned',
                    'created_at','updated_at', 'views', DB::raw("'movie' AS type"),DB::raw("0 AS newEpisodes")
        ];

        $selectSerie = [
            'id', 'name', 'poster_path', 'backdrop_path',
                        'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date',
                        'pinned', 'created_at','updated_at', 'views', DB::raw("'serie' AS type"),"newEpisodes"
        ];


        $selectAnime = [
            'id', 'name', 'poster_path', 'backdrop_path',
                    'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date', 
                    'pinned', 'created_at','updated_at','views', DB::raw("'anime' AS type"),"newEpisodes"
        ];

        $settings = Setting::query()->first();

        $upcoming = Upcoming::query()->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $networks = Network::select(['id',
            'name', 'logo_path'])->orderByDesc('created_at')->limit(10)->get();

        if ($settings->anime) {

            $latest = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                    ->from('movies')
                    ->where('active', '=', 1)
                    ->orderBy('created_at', 'desc')
                    ->limit(10);

                $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                    $query->select(array_merge(
                        $selectSerie,
                        [
                            $genresSeries,
                        ]
                    ))
                        ->from('series')
                        ->where('active', '=', 1)
                        ->orderBy('created_at', 'desc')
                        ->limit(10);
                });

                $query->unionAll(function ($query) use ($selectAnime,$genresAnimes) {
                    $query->select(array_merge(
                        $selectAnime,
                        [
                            $genresAnimes,
                        ]
                    ))
                        ->from('animes')
                        ->where('active', '=', 1)
                        ->orderBy('created_at', 'desc')
                        ->limit(10);
                });
            })
                 ->orderByDesc('created_at')
                ->get();

        } else {

            $latest = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                    ->from('movies')
                    ->where('active', '=', 1)
                    ->orderBy('created_at', 'desc')
                    ->limit(10);

                    $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                        ->from('series')
                        ->where('active', '=', 1)
                        ->orderBy('created_at', 'desc')
                        ->limit(10);
                });
            })
                ->orderByDesc('created_at')
                ->get();
        }

        if($settings->anime){

            $arraythisweek = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->orderBy('created_at', 'desc')
                      ->limit(10);
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->orderBy('created_at', 'desc')
                          ->limit(10);
                });
            
                $query->unionAll(function ($query) use ($selectAnime,$genresAnimes) {
                    $query->select(array_merge(
                        $selectAnime,
                        [
                            $genresAnimes,
                        ]
                    ))
                          ->from('animes')
                          ->where('active', '=', 1)
                          ->orderBy('created_at', 'desc')
                          ->limit(10);
                });
            })
            ->orderByDesc('created_at')
            ->where('created_at', '>', Carbon::now()->startOfWeek())
            ->get();
            
                


        }else {

            $arraythisweek = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->orderBy('created_at', 'desc')
                      ->limit(10);
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->orderBy('created_at', 'desc')
                          ->limit(10);
                });
            })
            ->orderByDesc('created_at')
            ->where('created_at', '>', Carbon::now()->startOfWeek())
            ->get();
            
                

        }
       


        if($settings->anime){

            $arraychoosed = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->limit(10);
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->limit(10);
                });
            
                $query->unionAll(function ($query) use ($selectAnime,$genresAnimes) {
                    $query->select(array_merge(
                        $selectAnime,
                        [
                            $genresAnimes,
                        ]
                    ))
                          ->from('animes')
                          ->where('active', '=', 1)
                          ->limit(10);
                });
            })
            ->inRandomOrder()
            ->get();
            

        }else {


            $arraychoosed = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->limit(10);
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->limit(10);
                });
            })
            ->inRandomOrder()
            ->get();
            
        }
        


        if($settings->anime){


            $arraytrending = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->limit(10);
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->limit(10);
                });
            
                $query->unionAll(function ($query) use ($selectAnime,$genresAnimes) {
                    $query->select(array_merge(
                        $selectAnime,
                        [
                            $genresAnimes,
                        ]
                    ))
                          ->from('animes')
                          ->where('active', '=', 1)
                          ->limit(10);
                });
            })
            ->where('views', '>', 500)
            ->orderByDesc('views')
            ->get();

        }else {

            $arraytrending = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->limit(10);
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->limit(10);
                });
            })
            ->where('views', '>', 500)
            ->orderByDesc('views')
            ->get();

        }
       



        if($settings->anime){

        $arrayrecommended = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
            $query->select(array_merge(
                $selectMovie,
                [
                    $genresMovies,
                ]
            ))
                  ->from('movies')
                  ->where('active', '=', 1)
                  ->limit(10);
        
                  $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                    $query->select(array_merge(
                        $selectSerie,
                        [
                            $genresSeries,
                        ]
                    ))
                      ->from('series')
                      ->where('active', '=', 1)
                      ->limit(10);
            });
        
            $query->unionAll(function ($query) use ($selectAnime,$genresAnimes) {
                $query->select(array_merge(
                    $selectAnime,
                    [
                        $genresAnimes,
                    ]
                ))
                      ->from('animes')
                      ->where('active', '=', 1)
                      ->limit(10);
            });
        })
        ->where('views', '>', 500)
        ->get();

        }else {



            $arrayrecommended = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->limit(10);
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->limit(10);
                });
            })
            ->where('views', '>', 500)
            ->get();

        }



        


        if($settings->anime){

            $arraypinned = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1);
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1);
                });
            
                $query->unionAll(function ($query) use ($selectAnime,$genresAnimes) {
                    $query->select(array_merge(
                        $selectAnime,
                        [
                            $genresAnimes,
                        ]
                    ))
                          ->from('animes')
                          ->where('active', '=', 1);
                });
            })
            ->where('pinned', 1)
            ->orderByDesc('updated_at')
            ->get();

        }else {


            $arraypinned = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1);
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1);
                });
            })
            ->where('pinned', 1)
            ->orderByDesc('created_at')
            ->get();

        }

           


        if($settings->anime){

            $arraytop10 = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->orderBy('views', 'desc')
                      ->limit(4);
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->orderBy('views', 'desc')
                          ->limit(3);
                });
            
                $query->unionAll(function ($query) use ($selectAnime,$genresAnimes) {
                    $query->select(array_merge(
                        $selectAnime,
                        [
                            $genresAnimes,
                        ]
                    ))
                          ->from('animes')
                          ->where('active', '=', 1)
                          ->orderBy('views', 'desc')
                          ->limit(3);
                });
            })
            ->orderBy('views', 'desc')
            ->get();

        }else {


            $arraytop10 = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->orderBy('views', 'desc')
                      ->limit(4);
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->orderBy('views', 'desc')
                          ->limit(3);
                });
            })
            ->orderBy('views', 'desc')
            ->get();
        }
   


        if($settings->anime){

        $animeslatest = DB::table(function ($query) use ($selectAnime,$genresAnimes) {
            $query->select(array_merge(
                $selectAnime,
                [
                    $genresAnimes,
                ]
            ))
                  ->from('animes')
                  ->where('active', '=', 1)
                  ->orderBy('created_at', 'desc')
                  ->limit(10);
        })
        ->orderBy('created_at', 'desc')
        ->get();
    }


        $popularSeries = DB::table(function ($query) use ($selectSerie,$genresSeries) {
            $query->select(array_merge(
                $selectSerie,
                [
                    $genresSeries,
                ]
            ))
                  ->from('series')
                  ->where('active', '=', 1)
                  ->orderBy('created_at', 'desc')
                  ->limit(20);
        })
        ->where('views', '>', 500)
        ->orderByDesc('views')
        ->get();

        

        $recentSeries = DB::table(function ($query) use ($selectSerie,$genresSeries) {
            $query->select(array_merge(
                $selectSerie,
                [
                    $genresSeries,
                ]
            ))
                  ->from('series')
                  ->where('active', '=', 1)
                  ->orderBy('created_at', 'desc')
                  ->limit(10);
        })
        ->orderBy('created_at', 'desc')
        ->get();



        $streaming = DB::table(function ($query) {
            $query->select('livetvs.id','livetvs.name','livetvs.poster_path','livetvs.overview', 'created_at',DB::raw("'streaming' as type"))
             ->selectRaw('(SELECT SUBSTRING_INDEX(GROUP_CONCAT(categories.name SEPARATOR ", "), ",", 1)
              FROM categories JOIN livetv_genres ON categories.id = livetv_genres.category_id WHERE livetv_genres.livetv_id = livetvs.id) AS genre_name')
                  ->from('livetvs')
                  ->where('active', '=', 1)
                  ->limit(10);
        })
        ->orderByDesc('created_at')
        ->get();


        $casts = Cast::query()->select(['id','name','profile_path','gender','views','biography'])
        ->where('active', '=', 1)
        ->orderByDesc('views')
        ->limit(10)
        ->get();
             

            $featured = Featured::query()->orderBy('position')->orderByDesc('updated_at')
            ->limit($settings->featured_home_numbers)
             ->get();


                $newEpisodes2 = DB::table('series')
                ->select(
                       
                    array_merge(
                        $selectSerieEpisodes,
                        [
                            $genresSeries,
                        ]
                    ))
                    ->join('seasons', 'seasons.serie_id', '=', 'series.id')
                    ->join('episodes', 'episodes.season_id', '=', 'seasons.id')
                    ->join('serie_videos', function ($join) {
                        $join->on('serie_videos.episode_id', '=', 'episodes.id')
                            ->orderBy('serie_videos.updated_at', 'desc');
                    })
                    ->where('series.active', '=', 1)
                    ->limit(20)
                    ->orderBy('serie_videos.updated_at', 'desc')
                    ->groupBy('episodes.id')
                    ->get();



                if($settings->anime){


                    $latestEpisodesAnimes = DB::table('animes')
                    ->select(
                       
                    array_merge(
                        $selectAnimeEpisodes,
                        [
                            $genresAnimes,
                        ]
                    ))
                    ->join('anime_seasons', 'anime_seasons.anime_id', '=', 'animes.id')
                    ->join('anime_episodes', 'anime_episodes.anime_season_id', '=', 'anime_seasons.id')
                    ->join('anime_videos', function ($join) {
                        $join->on('anime_videos.anime_episode_id', '=', 'anime_episodes.id')
                            ->orderBy('anime_videos.updated_at', 'desc');
                    })
                    ->where('animes.active', '=', 1)
                    ->limit(20)
                    ->orderBy('anime_videos.updated_at', 'desc')
                    ->groupBy('anime_episode_id')
                    ->get();

                 }




                        //ray()->showQueries1();
                        ray()->measure();


                        return response()
                    ->json([
                            'upcoming' => $upcoming,
                            'networks' => $networks,
                            'latest' => $latest,
                            'latest_episodes' => $newEpisodes2,
                            'latest_episodes_animes' => $latestEpisodesAnimes,
                            'thisweek' => $arraythisweek,
                            'choosed' => $arraychoosed,
                            'recommended' => $arrayrecommended,
                            'trending' => $arraytrending,
                            'pinned' => $arraypinned,
                            'top10' => $arraytop10,
                            'popular_casters' => $casts,
                            'featured' => $featured,
                            'livetv' => $streaming,
                            'anime' => $animeslatest,
                            'popularSeries' => $popularSeries,
                            'recents' => $recentSeries]);




            }



public function suggestedcontent($statusapi)

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

    $selectMovie = [
        'id', 'title AS name', 'poster_path', 'backdrop_path',
                'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'release_date', 'pinned',
                'created_at','updated_at', 'views', DB::raw("'movie' AS type")
    ];

    $selectSerie = [
        'id', 'name', 'poster_path', 'backdrop_path',
                    'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date',
                    'pinned', 'created_at','updated_at', 'views', DB::raw("'serie' AS type")
    ];


    $selectAnime = [
        'id', 'name', 'poster_path', 'backdrop_path',
                'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date', 
                'pinned', 'created_at','updated_at','views', DB::raw("'anime' AS type")
    ];

    $settings = Setting::query()->first();


    if ($settings->anime) {

        $array = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
            $query->select(array_merge(
                $selectMovie,
                [
                    $genresMovies,
                ]
            ))
                ->from('movies')
                ->where('active', '=', 1)
                ->orderBy('created_at', 'desc')
                ->limit(4);

            $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                $query->select(array_merge(
                    $selectSerie,
                    [
                        $genresSeries,
                    ]
                ))
                    ->from('series')
                    ->where('active', '=', 1)
                    ->orderBy('created_at', 'desc')
                    ->limit(3);
            });

            $query->unionAll(function ($query) use ($selectAnime,$genresAnimes) {
                $query->select(array_merge(
                    $selectAnime,
                    [
                        $genresAnimes,
                    ]
                ))
                    ->from('animes')
                    ->where('active', '=', 1)
                    ->orderBy('created_at', 'desc')
                    ->limit(3);
            });
        })
        ->inRandomOrder() 
             ->orderByDesc('created_at')
            ->get();

    } else {

        $array = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
            $query->select(array_merge(
                $selectMovie,
                [
                    $genresMovies,
                ]
            ))
                ->from('movies')
                ->where('active', '=', 1)
                ->orderBy('created_at', 'desc')
                ->limit(10);

                $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                    $query->select(array_merge(
                        $selectSerie,
                        [
                            $genresSeries,
                        ]
                    ))
                    ->from('series')
                    ->where('active', '=', 1)
                    ->orderBy('created_at', 'desc')
                    ->limit(10);
            });
        })
        ->inRandomOrder() 
            ->orderByDesc('created_at')
            ->get();
    }


       // ray()->showQueries1();
        ray()->measure();

        return response()
    ->json(['suggested' => $array], 200);

}

    public function randomcontent($statusapi)

    {



        $movies = Movie::query()->inRandomOrder() ->where('active', '=', 1)->limit(1)->get();


        return response()
            ->json(['random' => $movies], 200);

    }

    public function randomMovie()

    {

        $movies = Movie::query()->inRandomOrder()->where('active', '=', 1)->limit(1)->first();

   

        return response()
            ->json($movies, 200);
    }


    // returns 12 movies related to a movie
    public function relateds($movie)
    {
    
         $moviesGenre = Movie::withOnly('genres.genre')
         ->where('id', '=', $movie)
         ->select(['movies.id', 'title', 'poster_path', 'backdrop_path', 
         'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'release_date', 'pinned',
          'movies.created_at', 'views', DB::raw("'movie' AS type")])
         ->where('active', '=', 1)
         ->limit(1)
         ->orderByDesc('movies.created_at')
         ->get();



         foreach( $moviesGenre as $movie){


            $genre = $movie->genres[0]->genre_id;

        }



        $movies = DB::table('movies')->join('movie_genres', 'movies.id', '=', 'movie_genres.movie_id')
         ->where('movie_genres.genre_id', '=', $genre)
         ->where('movie_genres.movie_id', '!=', $movie)
         ->select(['movies.id', 'title', 'poster_path', 'backdrop_path', 
         'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'release_date', 'pinned',
          'movies.created_at', 'views', DB::raw("'movie' AS type")])
          ->where('active', '=', 1)
         ->limit(10)
         ->orderByDesc('movies.created_at')
         ->get();


        return response()->json(['relateds' => $movies], 200);
    }

    // return all the videos of a movie
    public function videos(Movie $movie)
    {
        return response()->json($movie->videos, 200);
    }


    // return all the Downlaods of a movie
    public function downloads(Movie $movie)
    {
        return response()->json($movie->downloads, 200);
    }


    public function casters(Movie $movie)
    {
        return response()->json($movie->casterslist, 200);
    }

    // return all the videos of a movie
    public function substitles(Movie $movie)
    {
        return response()->json($movie->substitles, 200);
    }
}

