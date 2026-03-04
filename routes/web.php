<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\AuthController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


//principe: Route::http('url',callback);
//Route::get(url,callback);
//Route::post(url,callback);
//Route::put(url,callback);
//Route::delete(url,callback);
//Route::patch(url,callback); pour faire aussi la modification des objects
//Route::options(url,callback); pour récupérer des headers spécifiques
Route::get('/',function(){
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

Route::post('/register', [AuthController::class, 'register'])->name('register.post');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
// Liste des produits
Route::middleware('auth')->group(function () {
Route::get('/articles', [ProduitController::class, 'index'])->name('articles.index');

Route::get('/articles/create', [ProduitController::class, 'create'])->name('articles.create');

// Stocker un nouvel article
Route::post('/articles', [ProduitController::class, 'store'])->name('articles.store');

// Afficher un article précis
Route::get('/articles/{id}', [ProduitController::class, 'show'])->name('articles.show');

// Formulaire d'édition
Route::get('/articles/{id}/edit', [ProduitController::class, 'edit'])->name('articles.edit');

// Mettre à jour un article
Route::put('/articles/{id}', [ProduitController::class, 'update'])->name('articles.update');

// Supprimer un article
Route::delete('/articles/{id}', [ProduitController::class, 'destroy'])->name('articles.destroy');
});

// Route::get('/home',function (){
//     return view('home', ['framework' => 'laravel']);
// });


// Route::get('/hello/{var}',function($var){
//     return ('hello '.$var);
// });
// Route::get('/hello/{var?}',function($var="Diary"){
//     return ('hello '.$var);
// });
Route::get('/show',[UserController::class,'create'])->name('creer');


//Permet de generer /users, /users/create, /users/{user}, /users/{user}/edit, /users(post), /users/{user}(delete)
Route::resource('users',UserController::class);

//Redirect
//Route::redirect('/ici','/la');

//Créer une route avec nom afin de l'utiliser apres au lieu de donner l'url complet
// Route::get('/users/profile',function(){
//     return 'profile';
// })->name('profile');
// Route::get('redirect',function(){
//     return redirect()->route('profile');
// });