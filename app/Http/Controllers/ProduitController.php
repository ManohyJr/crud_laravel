<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class ProduitController extends Controller
{
    public function __construct()
{
    $this->middleware('auth');
}

    public function index()
    {
        $articles = DB::table('produits')->orderBy('created_at', 'desc')->get();
        return view('articles.index', compact('articles'));
    }

    public function create()
    {
        return view('articles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code_pro' => 'required|string|max:255',
            'designation' => 'required|string',
        ]);

        DB::table('produits')->insert([
            'code_pro' => $request->code_pro,
            'designation' => $request->designation,
            'prix_unitaire' => $request->prix_unitaire,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Redirect::route('articles.index')
                       ->with('success', 'Article créé avec succès.');
    }

    public function show($id)
    {
        $article = DB::table('produits')->where('id', $id)->first();
        return view('articles.show', compact('article'));
    }

    public function edit($id)
    {
        $article = DB::table('produits')->where('id', $id)->first();
        return view('articles.edit', compact('article'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code_pro' => 'required|string|max:255',
            'designation' => 'required|string',
        ]);

        DB::table('produits')->where('id', $id)->update([
            'code_pro' => $request->code_pro,
            'designation' => $request->designation,
            'prix_unitaire' => $request->prix_unitaire,
            'updated_at' => now(),
        ]);

        return Redirect::route('articles.index')
                       ->with('success', 'Article mis à jour avec succès.');
    }

    public function destroy($id)
    {
        DB::table('produits')->where('id', $id)->delete();

        return Redirect::route('articles.index')
                       ->with('success', 'Articles supprimé avec succès.');
    }
}


