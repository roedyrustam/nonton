<?php

namespace App\Http\Controllers;

use App\Certification;
use App\Http\Requests\GenreRequest;
use App\Movie;
use App\Serie;
use App\Anime;
use App\Livetv;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CertificationController extends Controller
{
    // returns all genres for the api
    public function index()
    {
        return response()->json(Certification::All(), 200);
    }

    // returns all genres for the admin panel
    public function datagenres()
    {
        return response()->json(Certification::All(), 200);
    }

    // create a new genre in the database
    public function store(Request $request)
    {
        $genre = new Certification();
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
            $genre = Certification::find($genreMovie['id']);
            if ($genre == null) {
                $genre = new Certification();
                $genre->id = $genreMovie['id'];
            }
            $genre->name = $genreMovie['name'];
            $genre->meaning = $genreMovie['meaning'];
            $genre->order = $genreMovie['order'];
            $genre->save();
        }

        foreach ($genreSeries as $genreSerie) {
            $genre = Certification::find($genreSerie['id']);
            if ($genre == null) {
                $genre = new Certification();
                $genre->id = $genreSerie['id'];
            }
            $genre->certification = $genreSerie['certification'];
            $genre->meaning = $genreSerie['meaning'];
            $genre->order = $genreSerie['order'];
            $genre->save();
        }

        $genres = Certification::all();

        $data = [
            'status' => 200,
            'message' => 'successfully updated',
            'body' => $genres
        ];

        return response()->json($data, $data['status']);
    }

    // delete a genre from the database
    public function destroy(Certification $genre)
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

        return response()->json(['genres' => Certification::all('id', 'name')], 200);
    }


}
