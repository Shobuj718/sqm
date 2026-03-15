<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;


class PagesController extends Controller
{
    private $accessToken;

    private $pagesToken;

    private $userId;

    public function __construct()
    {
        $this->accessToken = 'EAAQacaaqBVMBQ4qdprjQPLmcMveTzJhBZBZBI1d7KcXFP6NU3rmLtOXBCaMjsMghAMGOn6vBQwLzCtWh8ZBXRNwvCZCHWBLSoSIiGHrcZA5DshseCqy5S8otzsGu3EO3br5oyv8ZB4yhaZADkERVO5RIzV61pQoBXSuSLpbHx57SfXV6CXi6sppUi3t47Pn';

        $this->pagesToken = 'EAAQacaaqBVMBQzZCIHyzjvk1MCYrattJkcDtM2vCgAOLhZA6OFCVnKkKZCWrjCfWS25BDZCV3iEjisjOJHaXxhIRcEXHBJG5hpAFX67fvNZCmZBT0auvqzaMNKJPH4STeOnxMWBPjTdvZCAXudJ1VAY6rZCvqWvglxDqsAaQHDw9G9ZCYKu4P4E36IBlOUn8RgQBeb4EcXw1Q';

        $this->userId = '122125172163000307';
    }

    public function index()
    {

        $response = Http::get("https://graph.facebook.com/v25.0/me/accounts",[
            'access_token'=> $this->accessToken
        ]);

        $pages = $response->json();

        //dd($pages);

        if ($response->failed()) {
            return back()->with('error', 'Failed to fetch posts from Facebook.');
        }

        return view('fbpages.index', ['pages' => $pages]);

    }

    public function posts($page_id)
    {
        $response = Http::get("https://graph.facebook.com/v25.0/$page_id/feed", [
            'fields' => 'id,message,story,created_time,permalink_url',
            'access_token' =>  $this->pagesToken,
        ]);

        if ($response->failed()) {
            return back()->with('error', 'Failed to fetch posts from Facebook.');
        }

        $posts = $response->json();

        return view('fbpages.posts', compact('posts', 'page_id'));

    }


    public function comments($post_id)
    {
        $accessToken = $this->accessToken;

        $response = Http::get("https://graph.facebook.com/v25.0/$post_id/comments", [
            'fields' => 'id,message,from,created_time',
            'access_token' => $this->pagesToken
        ]);

        if ($response->failed()) {
            return back()->with('error', 'Failed to fetch comments.');
        }

        $comments = $response->json();

        return view('fbpages.comments', compact('comments', 'post_id'));
    }


    public function replyComment(Request $request, $comment_id)
    {
        $request->validate([
            'message' => 'required|string|max:500'
        ]);


        $response = Http::post("https://graph.facebook.com/v25.0/$comment_id/comments", [
            'message' => $request->message,
            'access_token' =>  $this->pagesToken
        ]);

        $result = $response->json();

        if(isset($result['id'])){
            return back()->with('success','Reply posted successfully!');
        }

        return back()->with('error','Failed to post reply.');
    }




}
