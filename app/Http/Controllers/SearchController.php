<?php

namespace App\Http\Controllers;


use App\Http\Requests\Request;
use App\Movie;
use App\Serie;
use App\Livetv;
use App\Anime;
use App\User;
use App\Cast;
use BeyondCode\Comments\Comment;
use App\Setting;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    // returns all the movies, animes and livetv that match the search
    public function index($search)
    {

        $settings = Setting::query()->first();

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


        if($settings->anime){


            $searhQuery = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$selectLive,
            $genresMovies,$genresSeries,$genresAnimes,$genresLive,$search) {
                $query->where('title', 'LIKE', '%'.$search.'%')->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                    ->from('movies')
                    ->where('active', '=', 1)
                    ->orderBy('created_at', 'desc')
                    ->limit(10);
    
                $query->unionAll(function ($query) use ($selectSerie,$genresSeries,$search) {
                    $query->where('name', 'LIKE', '%'.$search.'%')->select(array_merge(
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
    
                $query->unionAll(function ($query) use ($selectAnime,$genresAnimes,$search) {
                    $query->where('name', 'LIKE', '%'.$search.'%')->select(array_merge(
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

        }else {


            $searhQuery = DB::table(function ($query) use ($selectMovie,$selectSerie,$selectAnime,$selectLive,
            $genresMovies,$genresSeries,$genresAnimes,$genresLive,$search) {
                $query->where('title', 'LIKE', '%'.$search.'%')->select(array_merge(
                    $selectMovie,
                    [
                        $genresMovies,
                    ]
                ))
                    ->from('movies')
                    ->where('active', '=', 1)
                    ->orderBy('created_at', 'desc')
                    ->limit(10);
    
                $query->unionAll(function ($query) use ($selectSerie,$genresSeries,$search) {
                    $query->where('name', 'LIKE', '%'.$search.'%')->select(array_merge(
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

    

        return response()->json(['search' => $searhQuery], 200);
    }




    public function searchFeatured()
    {

        $query = \Request::get('q');
    	$movies = Movie::select('*')->whereRaw("title LIKE '%" . $query . "%'")
        ->orWhereRaw("original_name LIKE '%" . $query . "%'")
        ->where('active', '=', 1)
        ->addSelect(DB::raw("'Movie' as type"))->limit(50)->get();

        $series = Serie::select('*')->whereRaw("name LIKE '%" . $query . "%'")
        ->orWhereRaw("original_name LIKE '%" . $query . "%'")->where('active', '=', 1)
        ->addSelect(DB::raw("'Serie' as type"))->limit(50)->get();


        $anime = Anime::select('*')->whereRaw("name LIKE '%" . $query . "%'")
        ->orWhereRaw("original_name LIKE '%" . $query . "%'")->where('active', '=', 1)
        ->addSelect(DB::raw("'Anime' as type"))->limit(50)->get();

        $livetv = Livetv::select('*')->whereRaw("name LIKE '%" . $query . "%'")
        ->addSelect(DB::raw("'Streaming' as type"))->limit(50)->get();

        $array = array_merge($movies->toArray(),
         $series->toArray()
         ,$anime->makeHidden('seasons','episodes')->toArray(),$livetv->toArray());

        return response()->json(['search' => $array], 200);

    }



    public function searchCasts()
    {
    	$query = \Request::get('q');

        $casts = Cast::select('*')->where('name', 'LIKE', "%$query%")->limit(50)->get();

    	return response()->json([ 'casts' => $casts ],Response::HTTP_OK);
    }


    public function searchComments()
    {
    	$query = \Request::get('q');

        $comments = Comment::select('*')->where('comment', 'LIKE', "%$query%")->limit(50)->get();

    	return response()->json([ 'comments' => $comments ],Response::HTTP_OK);
    }


    public function searchMovies()
    {
    	$query = \Request::get('q');
        $movies = Movie::select('*')->where('title', 'LIKE', "%$query%")->limit(10)->get();

    	return response()->json([ 'movies' => $movies ],Response::HTTP_OK);
    }


    public function searchSeries()
    {
    	$query = \Request::get('q');
        $movies = Serie::select('*')->where('name', 'LIKE', "%$query%")->limit(10)->get();

    	return response()->json([ 'series' => $movies ],Response::HTTP_OK);
    }


    
    public function searchAnimes()
    {
    	$query = \Request::get('q');
        $movies = Anime::select('*')->where('name', 'LIKE', "%$query%")->limit(10)->get();

    	return response()->json([ 'animes' => $movies ],Response::HTTP_OK);
    }



    public function searchStreaming()
    {
    	$query = \Request::get('q');
        $movies = Livetv::select('*')->where('name', 'LIKE', "%$query%")->limit(10)->get();

    	return response()->json([ 'streaming' => $movies ],Response::HTTP_OK);
    }

    public function searchUsers()
    {
    	$query = \Request::get('q');
        $movies = User::select('*')->where('email', 'LIKE', "%$query%")->get();

    	return response()->json([ 'users' => $movies ],Response::HTTP_OK);
    }


}