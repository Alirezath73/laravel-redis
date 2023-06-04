<?php

use App\Models\Article;
use App\Models\Lesson;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

// Redis String

Route::get('/visitors', function () {
    // $visits =  Redis::incr('visits');

    $redis = Redis::connection('cache');

    $visits =  Redis::incrBy('visits', 5);

    return view('visitors')->withVisits($visits);
});

// Redis nameSpacing

Route::get('/videos/{video}', function ($video) {
    $downloads = Redis::get("video.{$video}.download");

    return view('videos')->withDownloads($downloads);
});

Route::get('/videos/{video}/download', function ($video) {
    Redis::incr("video.{$video}.download");

    return back();
});

// Redis Zset(sorted set)

Route::get('/articles/{article}', function (Article $article) {
    Redis::zincrBy("trending_articles", 1, $article);

    return $article;
});

Route::get('/articles-trending', function () {
    $articles = Redis::zrevrange("trending_articles", 0, 2);

    $articles = array_map('json_decode', $articles);

    return $articles;
});

// Redis Hash

Route::get('/users/{user}', function ($user) {

    // Cache::put('bar', 'baz', 600);

    // dd(Cache::get('bar'));

    $user1 = [
        'first_name' => 'saeed',
        'last_name' => 'barzegar',
        'counter' => 1,
    ];

    Redis::hmset("user.{$user}.stats", $user1);

    $person = Redis::hgetall("user.{$user}.stats");

    return $person;
});

Route::get('/users/{user}/stats', function ($user) {
    return Redis::hgetall("user.{$user}.stats");
});

Route::get('/users/{user}/counter', function ($user) {
    $redis = Redis::hincrBy("user.{$user}.stats", 'counter', 1);

    return $redis;
});

// Redis Cache

function remember($key, $expire, $callback)
{
    if($value = Redis::get($key)) {
        return $value;
    }

    Redis::setex($key, $expire, $value = serialize($callback()));

    return unserialize($value);
}

Route::get('/articles', function () {
    /*return remember('articles.all', 60*60, function () {
        return Article::all();
    });*/

    return Cache::remember('articles.all', 60*60, function () {
        return Article::all();
    });
});

// Redis in progress lessons

Route::get('/lessons-in-progress', function () {
    return collect(Redis::zrevrange('user.1.inProgress', 0, 2))->map(function ($lessonId) {
        return Lesson::find($lessonId);
    });
});

Route::get('/lessons/{lesson}', function (Lesson $lesson) {
    Redis::zincrby('user.1.inProgress', 1, $lesson->id);

    return $lesson;
});
