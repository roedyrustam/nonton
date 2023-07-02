<?php

namespace App\Http\Controllers;

use App\Genre;
use App\Http\Requests\GenreRequest;
use App\Movie;
use App\Serie;
use App\Setting;
use App\Anime;
use App\Livetv;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GenreController extends Controller
{







    public function showMedia($genre)
    {


        if($settings->anime){

            $latest = DB::table(function ($query) use ($genre) {
                $query->select('movies.id', 'title AS name', 'poster_path', 'backdrop_path', 
                'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'release_date', 'pinned',
                 'movies.created_at', 'views', DB::raw("'movie' AS type"))
                 ->selectRaw('(SELECT SUBSTRING_INDEX(GROUP_CONCAT(genres.name SEPARATOR ", "), ",", 1)
                  FROM genres JOIN movie_genres ON genres.id = movie_genres.genre_id WHERE movie_genres.movie_id = movies.id) AS genre_name')
                  ->join('movie_genres', 'movies.id', '=', 'movie_genres.movie_id')
                  ->where('movie_genres.genre_id', '=', $genre)
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->orderBy('created_at', 'desc');
                      
                    $query->unionAll(function ($query) use ($genre) {
                        $query->select('series.id', 'name', 'poster_path', 'backdrop_path',
                         'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date', 
                         'pinned', 'series.created_at', 'views', DB::raw("'serie' AS type"))
                         ->selectRaw('(SELECT SUBSTRING_INDEX(GROUP_CONCAT(genres.name SEPARATOR ", "), ",", 1)
                         FROM genres JOIN serie_genres ON genres.id = serie_genres.genre_id WHERE serie_genres.serie_id = series.id) AS genre_name')
                         ->join('serie_genres', 'series.id', '=', 'serie_genres.serie_id')
                         ->where('serie_genres.genre_id', '=', $genre)
                              ->from('series')
                              ->where('active', '=', 1)
                              ->orderBy('created_at', 'desc');
                    });
    
                    $query->unionAll(function ($query) use ($genre) {
                        $query->select('animes.id', 'name', 'poster_path', 'backdrop_path',
                        'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date', 
                        'pinned', 'animes.created_at', 'views', DB::raw("'anime' AS type"))
                        ->selectRaw('(SELECT SUBSTRING_INDEX(GROUP_CONCAT(genres.name SEPARATOR ", "), ",", 1)
                        FROM genres JOIN anime_genres ON genres.id = anime_genres.genre_id WHERE anime_genres.anime_id = animes.id) AS genre_name')
                        ->join('anime_genres', 'animes.id', '=', 'anime_genres.anime_id')
                        ->where('anime_genres.genre_id', '=', $genre)
                              ->from('animes')
                              ->where('active', '=', 1)
                              ->orderBy('created_at', 'desc')
                              ->limit(10);
                    });
            })
            ->orderByDesc('created_at')
            ->paginate(12);
    
    

        }else {

            $latest = DB::table(function ($query) use ($genre) {
                $query->select('movies.id', 'title AS name', 'poster_path', 'backdrop_path', 
                'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'release_date', 'pinned',
                 'movies.created_at', 'views', DB::raw("'movie' AS type"))
                 ->selectRaw('(SELECT SUBSTRING_INDEX(GROUP_CONCAT(genres.name SEPARATOR ", "), ",", 1)
                  FROM genres JOIN movie_genres ON genres.id = movie_genres.genre_id WHERE movie_genres.movie_id = movies.id) AS genre_name')
                  ->join('movie_genres', 'movies.id', '=', 'movie_genres.movie_id')
                  ->where('movie_genres.genre_id', '=', $genre)
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->orderBy('created_at', 'desc');
                      
                    $query->unionAll(function ($query) use ($genre) {
                        $query->select('series.id', 'name', 'poster_path', 'backdrop_path',
                         'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date', 
                         'pinned', 'series.created_at', 'views', DB::raw("'serie' AS type"))
                         ->selectRaw('(SELECT SUBSTRING_INDEX(GROUP_CONCAT(genres.name SEPARATOR ", "), ",", 1)
                         FROM genres JOIN serie_genres ON genres.id = serie_genres.genre_id WHERE serie_genres.serie_id = series.id) AS genre_name')
                         ->join('serie_genres', 'series.id', '=', 'serie_genres.serie_id')
                         ->where('serie_genres.genre_id', '=', $genre)
                              ->from('series')
                              ->where('active', '=', 1)
                              ->orderBy('created_at', 'desc');
                    });
            })
            ->orderByDesc('created_at')
            ->paginate(12);
        }

      

        return response()->json($latest, 200);

    }




    public function showMediaTypeSelected($genre)
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
                    'created_at', 'views', DB::raw("'movie' AS type")
        ];

        $selectSerie = [
            'id', 'name', 'poster_path', 'backdrop_path',
                        'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date',
                        'pinned', 'created_at', 'views', DB::raw("'serie' AS type")
        ];


        $selectAnime = [
            'id', 'name', 'poster_path', 'backdrop_path',
                    'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date', 
                    'pinned', 'created_at', 'views', DB::raw("'anime' AS type")
        ];


        $settings = Setting::query()->first();



        if($genre === 'allgenres'){


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
                        ->orderBy('created_at', 'desc');
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
                        ->orderBy('created_at', 'desc');
                });
            })
                 ->orderByDesc('created_at')
                ->paginate(12);

        }else if($genre === 'latestadded'){


          
            $latest = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                    ->from('movies')
                    ->where('active', '=', 1)
                    ->orderBy('created_at', 'desc');

                $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                    $query->select(array_merge(
                        $selectSerie,
                        [
                            $genresSeries,
                        ]
                    ))
                        ->from('series')
                        ->where('active', '=', 1)
                        ->orderBy('created_at', 'desc');
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
                        ->orderBy('created_at', 'desc');
                });
            })
                 ->orderByDesc('created_at')
                 ->paginate(12);
       

            
            }else if($genre === 'byrating'){


                $latest = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                    $query->select(array_merge(
                        $selectMovie,
                        [
                            $genresMovies,
                        ]
                    ))
                        ->from('movies')
                        ->where('active', '=', 1)
                        ->orderByDesc('vote_average')
                        ->orderBy('created_at', 'desc');
    
                    $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                            ->from('series')
                            ->where('active', '=', 1)
                            ->orderByDesc('vote_average')
                            ->orderBy('created_at', 'desc');
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
                            ->orderByDesc('vote_average')
                            ->orderBy('created_at', 'desc');
                    });
                })
                ->orderByDesc('vote_average')
                ->orderBy('created_at', 'desc')
                ->paginate(12);
           


                }else if($genre === 'byyear'){




                    $latest = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                        $query->select(array_merge(
                            $selectMovie,
                            [
                                $genresMovies,
                            ]
                        ))
                            ->from('movies')
                            ->where('active', '=', 1)
                            ->orderByDesc('release_date')
                            ->orderBy('created_at', 'desc');
        
                        $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                            $query->select(array_merge(
                                $selectSerie,
                                [
                                    $genresSeries,
                                ]
                            ))
                                ->from('series')
                                ->where('active', '=', 1)
                                ->orderByDesc('release_date')
                                ->orderBy('created_at', 'desc');
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
                                ->orderByDesc('release_date')
                            ->orderBy('created_at', 'desc');
                        });
                    })
                    ->orderByDesc('release_date')
                    ->orderBy('created_at', 'desc')
                    ->paginate(12);
               

              
                    }else if($genre === 'byviews'){


                        $latest = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                            $query->select(array_merge(
                                $selectMovie,
                                [
                                    $genresMovies,
                                ]
                            ))
                                ->from('movies')
                                ->where('active', '=', 1)
                                ->orderByDesc('views')
                                ->orderBy('created_at', 'desc');
            
                            $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                                $query->select(array_merge(
                                    $selectSerie,
                                    [
                                        $genresSeries,
                                    ]
                                ))
                                    ->from('series')
                                    ->where('active', '=', 1)
                                    ->orderByDesc('views')
                                    ->orderBy('created_at', 'desc');
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
                                    ->orderByDesc('views')
                                    ->orderBy('created_at', 'desc');
                            });
                        })
                        ->orderByDesc('views')
                        ->orderBy('created_at', 'desc')
                        ->paginate(12);

                
                    }

        

                        //ray()->showQueries1();
                        ray()->measure();

        return response()->json($latest, 200);
       

    }













    // returns all genres for the api
    public function index()
    {
        return response()->json(Genre::All(), 200);
    }

    // returns all genres for the admin panel
    public function datagenres()
    {
        return response()->json(Genre::All(), 200);
    }

    // create a new genre in the database
    public function store(GenreRequest $request)
    {
        $genre = new Genre();
        $genre->fill($request->all());
        $genre->save();

        $data = [
            'status' => 200,
            'message' => 'successfully created',
            'body' => $genre
        ];

        return response()->json($data, $data['status']);
    }

    //create or update all themoviedb genres in the database
    public function fetch(Request $request)
    {
        $genreMovies = $request->movies['genres'];
        $genreSeries = $request->series['genres'];

        foreach ($genreMovies as $genreMovie) {
            $genre = Genre::find($genreMovie['id']);
            if ($genre == null) {
                $genre = new Genre();
                $genre->id = $genreMovie['id'];
            }
            $genre->name = $genreMovie['name'];
            $genre->save();
        }

        foreach ($genreSeries as $genreSerie) {
            $genre = Genre::find($genreSerie['id']);
            if ($genre == null) {
                $genre = new Genre();
                $genre->id = $genreSerie['id'];
            }
            $genre->name = $genreSerie['name'];
            $genre->save();
        }

        $genres = Genre::all();

        $data = [
            'status' => 200,
            'message' => 'successfully updated',
            'body' => $genres
        ];

        return response()->json($data, $data['status']);
    }

    // delete a genre from the database
    public function destroy(Genre $genre)
    {
        if ($genre != null) {
            $genre->delete();
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
    public function update(GenreRequest $request, Genre $genre)
    {
        if ($genre != null) {
            $genre->fill($request->all());
            $genre->save();
            $data = [
                'status' => 200,
                'message' => 'successfully updated',
                'body' => $genre
            ];
        } else {
            $data = [
                'status' => 400,
                'message' => 'could not be updated'
            ];
        }

        return response()->json($data, $data['status']);
    }

    // return all genres only with the id and name properties
    public function list()
    {


        $genres =  Genre::select('id','name','logo_path')->get();


        return response()->json(['genres' => $genres], 200);


    }




    public function showLatestAdded()
    {



        $movies = Movie::select('movies.title','movies.id','movies.poster_path'
        ,'movies.vote_average','movies.subtitle','movies.backdrop_path','movies.backdrop_path_tv','movies.subtitle')->where('created_at', '>', Carbon::now()->subMonth())
        ->where('active', '=', 1)
        ->orderByDesc('created_at')
        ->addSelect(DB::raw("'movie' as type"))
        ->paginate(12);

        $movies->setCollection($movies->getCollection()
        ->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
        ,'substitles','vote_count','popularity','runtime',
        'release_date','imdb_external_id','hd','pinned','preview']));
        return $movies;

        return response()->json($movies, 200);
    }


    public function showByYear()
    {


        $movies = Movie::select('movies.title','movies.id','movies.poster_path','movies.vote_average','movies.subtitle','movies.backdrop_path','movies.backdrop_path_tv')
        ->orderBy('release_date', 'desc')->where('active', '=', 1)
        ->addSelect(DB::raw("'movie' as type"))->paginate(12);

        $movies->setCollection($movies->getCollection()
        ->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
        ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
        return $movies;


      return response()->json($movies, 200);
    }


    public function showByRating()
    {


        $movies = Movie::select('movies.title','movies.id','movies.poster_path','movies.vote_average','movies.subtitle','movies.backdrop_path','movies.backdrop_path_tv')
        ->orderByDesc('vote_average')->where('active', '=', 1)
        ->addSelect(DB::raw("'movie' as type"))->paginate(12);

        $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
        ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
        return $movies;

        return response()->json($movies, 200);

    }



    public function showByViews()
    {



        $movies = Movie::select('movies.title','movies.id','movies.poster_path','movies.vote_average','movies.subtitle','movies.backdrop_path','movies.backdrop_path_tv')
        ->orderByDesc('views')->where('active', '=', 1)
        ->addSelect(DB::raw("'movie' as type"))->paginate(12);

        $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
        ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
        return $movies;

        return response()->json($movies, 200);
    }



    public function showLatestAddedtv()
    {


        $movies = Serie::select('series.id','series.name','series.poster_path','series.vote_average','series.subtitle','series.backdrop_path','series.backdrop_path_tv')->where('created_at', '>', Carbon::now()->subMonth())
        ->where('active', '=', 1)
        ->orderByDesc('created_at')
        ->addSelect(DB::raw("'serie' as type"))->paginate(12);


            $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
            ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
            return $movies;

        return response()->json($movies, 200);
    }


    public function showByYeartv()
    {


        $movies = Serie::select('series.id','series.name','series.poster_path','series.vote_average','series.subtitle','series.backdrop_path','series.backdrop_path_tv')
        ->where('active', '=', 1)->orderBy('first_air_date', 'asc')
        ->addSelect(DB::raw("'serie' as type"))->paginate(12);


            $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
            ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
            return $movies;


        return response()->json($movies, 200);
    }


    public function showByRatingtv()
    {


        $movies = Serie::select('series.id','series.name','series.poster_path','series.vote_average','series.subtitle','series.backdrop_path','series.backdrop_path_tv')
        ->where('active', '=', 1)->orderByDesc('vote_average')
        ->addSelect(DB::raw("'serie' as type"))->paginate(12);



            $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
            ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
            return $movies;

        return response()->json($movies, 200);
    }



    public function showByViewstv()
    {


        $movies = Serie::select('series.id','series.name','series.poster_path','series.vote_average','series.subtitle','series.backdrop_path','series.backdrop_path_tv')->where('active', '=', 1)->orderByDesc('views')
        ->addSelect(DB::raw("'serie' as type"))->paginate(12);


            $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
            ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
            return $movies;


        return response()->json($movies, 200);

    }








    public function showLatestAddedAnime()
    {


        $movies = Anime::select('animes.id','animes.name',
        'animes.poster_path','animes.vote_average','animes.subtitle','animes.is_anime')->where('created_at', '>', Carbon::now()->subMonth())
        ->where('active', '=', 1)
        ->orderByDesc('created_at')
        ->addSelect(DB::raw("'anime' as type"))->paginate(12);

       $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
       ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
       return $movies;



        return response()->json($movies, 200);
    }


    public function showByYearAnime()
    {


        $movies = Anime::select('animes.id','animes.name','animes.poster_path','animes.vote_average','animes.subtitle','animes.is_anime')->where('active', '=', 1)->orderBy('first_air_date', 'asc')
        ->addSelect(DB::raw("'anime' as type"))->paginate(12);

       $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
       ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
       return $movies;

        return response()->json($movies, 200);
    }


    public function showByRatingAnime()
    {


        $movies = Anime::select('animes.id','animes.name','animes.poster_path','animes.vote_average','animes.subtitle','animes.is_anime')->where('active', '=', 1)->orderBy('vote_average', 'asc')
        ->addSelect(DB::raw("'anime' as type"))->paginate(12);

       $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
       ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
       return $movies;


        return response()->json($movies, 200);


    }



    public function showByViewsAnime()
    {


        $movies = Anime::where('active', '=', 1)
        ->orderByDesc('views')
        ->addSelect(DB::raw("'anime' as type"))->paginate(12);

        $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path']));
        return $movies;


        return response()->json($movies, 200);
    }



      // return all movies with all genres
      public function showMoviesAllGenres()
      {

        $movies = Movie::select('movies.title','movies.id','movies.poster_path','movies.vote_average','movies.subtitle','movies.backdrop_path','movies.backdrop_path_tv')->orderByDesc('created_at')->where('active', '=', 1)
        ->addSelect(DB::raw("'movie' as type"))->paginate(12);

        $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters'
        ,'overview','preview_path','videos'
        ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
        return $movies;

        return response()->json($movies, 200);
      }


    // return all movies with all genres
    public function showSeriesAllGenres()
        {


            $movies = Serie::select('series.id','series.name','series.poster_path'
            ,'series.vote_average','series.subtitle','series.backdrop_path','series.backdrop_path_tv')->orderByDesc('created_at')->where('active', '=', 1)
            ->addSelect(DB::raw("'serie' as type"))->paginate(12);

            $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','seasons','overview','preview_path','videos'
            ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
            return $movies;


        return response()->json($movies, 200);


    }

         // return all movies with all genres
    public function showAnimesAllGenres()
    {


        $animes = Anime::select('animes.id','animes.name','animes.poster_path','animes.vote_average','animes.subtitle','is_anime')->orderByDesc('created_at')->where('active', '=', 1)
        ->addSelect(DB::raw("'anime' as type"))->paginate(12);

        $animes->setCollection($animes->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
        ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
        return $animes;
        return response()->json($animes, 200);
    }







    // return all movies of a genre
    public function showMovies(Genre $genre)
    {


        $order = 'desc';
        $movies = Movie::whereHas('genres', function ($query) use ($genre) {
            $query->where('genre_id', '=', $genre->id);
        })->select('movies.title','movies.id','movies.poster_path','movies.vote_average','movies.subtitle','movies.backdrop_path','movies.backdrop_path_tv')->where('active', '=', 1)
        ->addSelect(DB::raw("'movie' as type"))->paginate(12);

        $movies->setCollection($movies->getCollection()
        ->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path'
    ,'networkslist','videos','downloads','networks','substitles']));
        return $movies;

        return response()->json($movies, 200);
    }


    // return all series of a genre
    public function showSeries(Genre $genre)
    {
        $series = Serie::whereHas('genres', function ($query) use ($genre) {
            $query->where('genre_id', '=', $genre->id);
        })->select('series.id','series.name','series.poster_path','series.vote_average','series.subtitle','series.backdrop_path','series.backdrop_path_tv')->where('active', '=', 1)
        ->addSelect(DB::raw("'serie' as type"))->paginate(12);

        

        $series->setCollection($series->getCollection()
        ->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path'
    ,'networkslist','videos','downloads','networks','substitles']));
        return $series;

        return response()->json($series, 200);
    }


    public function showMoviesPlayer(Genre $genre)
    {
        $movies = Movie::whereHas('genres', function ($query) use ($genre) {
            $query->where('genre_id', '=', $genre->id);
        })->where('active', '=', 1)->addSelect(DB::raw("'movie' as type"))->paginate(6);

        $movies->setCollection($movies->getCollection()
        ->makeHidden(['genres','genreslist','casterslist','casters','overview','backdrop_path','preview_path'
    ,'networkslist','videos','downloads','networks','substitles']));
        return $movies;

        return response()->json($movies, 200);
    }


    // return all series of a genre
    public function showSeriesPlayer(Genre $genre)
    {

        $series = Serie::select('series.id','series.name','series.poster_path','series.vote_average','series.subtitle','series.backdrop_path','series.backdrop_path_tv')->
        whereHas('genres', function ($query) use ($genre) {
            $query->where('genre_id', '=', $genre->id);
        })->where('active', '=', 1)->addSelect(DB::raw("'serie' as type"))->paginate(6);


        $series->setCollection($series->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path'
    ,'networkslist','genres','genreslist','networks']));
        return $series;

        return response()->json($series, 200);
    }


    public function showAnimesPlayer(Genre $genre)
    {
        $series = Anime::whereHas('genres', function ($query) use ($genre) {
            $query->where('genre_id', '=', $genre->id);
        })->where('active', '=', 1)->addSelect(DB::raw("'anime' as type"))->paginate(6);


        return response()->json($series, 200);
    }



    // return all Animes of a genre
    public function showAnimes(Genre $genre)
    {
        $animes = Anime::whereHas('genres', function ($query) use ($genre) {
            $query->where('genre_id', '=', $genre->id);
        })->select('animes.id','animes.name','animes.poster_path','animes.vote_average','animes.subtitle','animes.is_anime')->where('active', '=', 1)
        ->addSelect(DB::raw("'anime' as type"))->paginate(6);

        $animes->setCollection($animes->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path']));
        return $animes;

        return response()->json($animes, 200);
    }



    public function networkGenre(Genre $genre)
    {



        $order = 'desc';
        $movies = Movie::whereHas('genres', function ($query) use ($genre) {
            $query->where('genre_id', '=', $genre->id);
        })->select('movies.title','movies.id','movies.poster_path','movies.vote_average','movies.subtitle','movies.backdrop_path','movies.backdrop_path_tv')->where('active', '=', 1)
        ->addSelect(DB::raw("'movie' as type"));



        $series = Serie::whereHas('genres', function ($query) use ($genre) {
            $query->where('genre_id', '=', $genre->id);
        })->select('series.name','series.id','series.poster_path','series.vote_average','series.subtitle','series.backdrop_path','series.backdrop_path_tv')->where('active', '=', 1)
        ->addSelect(DB::raw("'serie' as type"));


        $animes = Anime::whereHas('genres', function ($query) use ($genre) {
            $query->where('genre_id', '=', $genre->id);
        })->select('animes.name','animes.id','animes.poster_path','animes.vote_average','animes.subtitle')->where('active', '=', 1)
        ->addSelect(DB::raw("'anime' as type"));



        $query = $movies->union($series)
        ->union($animes);


        return response()->json($query->paginate(12), 200);

    }


    public function topteen()
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
                    'created_at', 'views', DB::raw("'movie' AS type")
        ];

        $selectSerie = [
            'id', 'name', 'poster_path', 'backdrop_path',
                        'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date',
                        'pinned', 'created_at', 'views', DB::raw("'serie' AS type")
        ];


        $selectAnime = [
            'id', 'name', 'poster_path', 'backdrop_path',
                    'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date', 
                    'pinned', 'created_at', 'views', DB::raw("'anime' AS type")
        ];


        $settings = Setting::query()->first();


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
                      ->orderBy('views', 'desc');
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->orderBy('views', 'desc');
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
                          ->orderBy('views', 'desc');
                });
            })
            ->orderBy('views', 'desc');

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
                      ->orderBy('views', 'desc');
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->orderBy('views', 'desc');
                });
            })
            ->orderBy('views', 'desc');
        }
   

    

        return response()->json($arraytop10->paginate(12), 200);

    }



    public function recommended()
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
                    'created_at', 'views', DB::raw("'movie' AS type")
        ];

        $selectSerie = [
            'id', 'name', 'poster_path', 'backdrop_path',
                        'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date',
                        'pinned', 'created_at', 'views', DB::raw("'serie' AS type")
        ];


        $selectAnime = [
            'id', 'name', 'poster_path', 'backdrop_path',
                    'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date', 
                    'pinned', 'created_at', 'views', DB::raw("'anime' AS type")
        ];


        $settings = Setting::query()->first();



        if($settings->anime){

            $arrayrecommended = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
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
            ->where('views', '>', 500);
    
            }else {
    
    
    
                $arrayrecommended = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
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
                ->where('views', '>', 500);
    
            }
    
            return response()->json($arrayrecommended->paginate(12), 200);

    }



    public function choosed()
    {

        $movies = Movie::select('movies.title','movies.id','movies.poster_path','movies.vote_average','movies.subtitle','movies.backdrop_path','movies.backdrop_path_tv')->addSelect(DB::raw("'movie' as type"))->inRandomOrder()->where('active', '=', 1);

        $series = Serie::select('series.name','series.id','series.poster_path','series.vote_average','series.subtitle','series.backdrop_path','series.backdrop_path_tv')->addSelect(DB::raw("'serie' as type"))->inRandomOrder()->where('active', '=', 1);



        $query = $movies->unionAll($series);

        return response()->json($query->paginate(12), 200);
    }


    public function trending()
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
                    'created_at', 'views', DB::raw("'movie' AS type")
        ];

        $selectSerie = [
            'id', 'name', 'poster_path', 'backdrop_path',
                        'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date',
                        'pinned', 'created_at', 'views', DB::raw("'serie' AS type")
        ];


        $selectAnime = [
            'id', 'name', 'poster_path', 'backdrop_path',
                    'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date', 
                    'pinned', 'created_at', 'views', DB::raw("'anime' AS type")
        ];


        $settings = Setting::query()->first();

        if($settings->anime){


            $arraytrending = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
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
            ->where('views', '>', 500)
            ->orderByDesc('views');

        }else {

            $arraytrending = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
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
            ->where('views', '>', 500)
            ->orderByDesc('views');

        }



        return response()->json($arraytrending->paginate(12), 200);
    }


    public function popularseries()
    {


        $movies = Serie::select('series.name','series.id','series.poster_path','series.vote_average','series.subtitle','series.backdrop_path','series.backdrop_path_tv')->addSelect(DB::raw("'serie' as type"))->orderByDesc('popularity')->where('active', '=', 1)->paginate(12);


        $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','seasons','episodes','videos','substitles','overview','backdrop_path','preview_path']));
        return $movies;


        return response()->json($movies, 200);
    }


    public function popularmovies()
    {


        $movies = Movie::select('movies.title','movies.id','movies.poster_path','movies.vote_average','movies.subtitle','movies.backdrop_path','movies.backdrop_path_tv')->addSelect(DB::raw("'movie' as type"))->orderByDesc('popularity') ->where('active', '=', 1)->orderByDesc('created_at')
        ->paginate(12);


        $movies->setCollection($movies->getCollection()->makeHidden(['casterslist','casters','videos','substitles','overview','backdrop_path','preview_path']));
        return $movies;



        return response()->json($movies, 200);
    }



    public function latestseries()
    {



        $genresSeries =
        DB::raw('(SELECT SUBSTRING_INDEX(GROUP_CONCAT(genres.name SEPARATOR ", "), ",", 1)
        FROM genres JOIN serie_genres ON genres.id = serie_genres.genre_id WHERE serie_genres.serie_id = series.id) AS genre_name');

        $selectSerie = [
            'id', 'name', 'poster_path', 'backdrop_path',
                        'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date',
                        'pinned', 'created_at', 'views', DB::raw("'serie' AS type")
        ];

        $recentSeries = DB::table(function ($query) use ($selectSerie,$genresSeries) {
            $query->select(array_merge(
                $selectSerie,
                [
                    $genresSeries,
                ]
            ))
                  ->from('series')
                  ->where('active', '=', 1)
                  ->orderBy('created_at', 'desc');
        })
        ->orderBy('created_at', 'desc');


        return response()->json($recentSeries->paginate(12), 200);
    }



    public function new()
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
                    'created_at', 'views', DB::raw("'movie' AS type")
        ];

        $selectSerie = [
            'id', 'name', 'poster_path', 'backdrop_path',
                        'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date',
                        'pinned', 'created_at', 'views', DB::raw("'serie' AS type")
        ];


        $selectAnime = [
            'id', 'name', 'poster_path', 'backdrop_path',
                    'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date', 
                    'pinned', 'created_at', 'views', DB::raw("'anime' AS type")
        ];


        $settings = Setting::query()->first();


        if($settings->anime){

            $latest = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->orderBy('created_at', 'desc');
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->orderBy('created_at', 'desc');
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
                          ->orderBy('created_at', 'desc');
                });
            })
            ->orderBy('created_at', 'desc');

        }else {


            $latest = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$genresMovies,$genresSeries,$genresAnimes) {
                $query->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                      ->from('movies')
                      ->where('active', '=', 1)
                      ->orderBy('created_at', 'desc');
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->orderBy('created_at', 'desc');
                });
            })
            ->orderBy('created_at', 'desc');
        }

        return response()->json($latest->paginate(12), 200);
    }

    public function thisweek()
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
                    'created_at', 'views', DB::raw("'movie' AS type")
        ];

        $selectSerie = [
            'id', 'name', 'poster_path', 'backdrop_path',
                        'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date',
                        'pinned', 'created_at', 'views', DB::raw("'serie' AS type")
        ];


        $selectAnime = [
            'id', 'name', 'poster_path', 'backdrop_path',
                    'backdrop_path_tv', 'vote_average', 'subtitle', 'overview', 'first_air_date AS release_date', 
                    'pinned', 'created_at', 'views', DB::raw("'anime' AS type")
        ];


        $settings = Setting::query()->first();


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
                      ->orderBy('created_at', 'desc');
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->orderBy('created_at', 'desc');
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
                          ->orderBy('created_at', 'desc');
                });
            })
            ->orderByDesc('created_at')
            ->where('created_at', '>', Carbon::now()->startOfWeek());
            
                


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
                      ->orderBy('created_at', 'desc');
            
                      $query->unionAll(function ($query) use ($selectSerie,$genresSeries) {
                        $query->select(array_merge(
                            $selectSerie,
                            [
                                $genresSeries,
                            ]
                        ))
                          ->from('series')
                          ->where('active', '=', 1)
                          ->orderBy('created_at', 'desc');
                });
            })
            ->orderByDesc('created_at')
            ->where('created_at', '>', Carbon::now()->startOfWeek());
            
                

        }
       

        return response()->json($arraythisweek->paginate(12), 200);
    }

    public function latestanimes()
    {


        $Anime = Anime::select('animes.id','animes.name','animes.poster_path','animes.vote_average','animes.subtitle','animes.is_anime')
        ->addSelect(DB::raw("'anime' as type"))->orderByDesc('created_at')->where('active', '=', 1)->paginate(12);



        $Anime->setCollection($Anime->getCollection()->makeHidden(['casterslist','casters','seasons','overview','backdrop_path','preview_path','videos'
        ,'substitles','vote_count','popularity','runtime','release_date','imdb_external_id','hd','pinned','preview']));
        return $Anime;

        return response()->json($Anime, 200);
    }




}
