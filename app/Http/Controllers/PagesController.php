<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;


class PagesController extends Controller
{
    private $accessToken;

    private $pagesToken;

    private $userId;

    private $verifyToken;

    public function __construct()
    {
        $this->accessToken = 'EAAQacaaqBVMBQ4qdprjQPLmcMveTzJhBZBZBI1d7KcXFP6NU3rmLtOXBCaMjsMghAMGOn6vBQwLzCtWh8ZBXRNwvCZCHWBLSoSIiGHrcZA5DshseCqy5S8otzsGu3EO3br5oyv8ZB4yhaZADkERVO5RIzV61pQoBXSuSLpbHx57SfXV6CXi6sppUi3t47Pn';

        $this->pagesToken = 'EAAQacaaqBVMBQzZCIHyzjvk1MCYrattJkcDtM2vCgAOLhZA6OFCVnKkKZCWrjCfWS25BDZCV3iEjisjOJHaXxhIRcEXHBJG5hpAFX67fvNZCmZBT0auvqzaMNKJPH4STeOnxMWBPjTdvZCAXudJ1VAY6rZCvqWvglxDqsAaQHDw9G9ZCYKu4P4E36IBlOUn8RgQBeb4EcXw1Q';

        $this->userId = '122125172163000307';

        $this->verifyToken = 'test-token';


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

    public function subscription()
    {

        $appId = env('FACEBOOK_APP_ID');
        $appSecret = env('FACEBOOK_APP_SECRET');

        $appAccessToken = $appId . '|' . $appSecret;

        $response = Http::get("https://graph.facebook.com/v25.0/{$appId}/subscriptions", [
            'access_token' => $appAccessToken
        ]);

        $data = $response->json();
        dd($data);
    }

    public function createSubscription()
    {

        $appId = env('FACEBOOK_APP_ID');
        $appSecret = env('FACEBOOK_APP_SECRET');

        $appAccessToken = $appId . '|' . $appSecret;

        $response = Http::asForm()->post("https://graph.facebook.com/v25.0/{$appId}/subscriptions", [
            'object' => 'page',
            'callback_url' => url('facebook/webhook'),
            'fields' => 'feed,comments',
            'include_values' => true,
            'verify_token' => env('FACEBOOK_VERIFY_TOKEN'),
            'access_token' => $appAccessToken
        ]);

        dd($response->json());
    }


    public function webhook(Request $request)
    {
        dd($request->all());
        \Log::info("message");
        if ($request->hub_verify_token ===  $this->verifyToken) {
            return $request->hub_challenge;
        }

        return response('Invalid token', 403);
    }

    public function webhookReply()
    {

        $data = $request->all();

        // Check that it's a page webhook

        // Get access token dynamically

        if ($data['object'] === 'page') {

            foreach ($data['entry'] as $entry) {

                foreach ($entry['changes'] as $change) {

                    $value = $change['value'];

                    if ($value['item'] === 'comment' && $value['verb'] === 'add') {

                        $commentId = $value['comment_id'];
                        $message = $value['message']; // original comment text

                        $reply = $this->checkDataset($userMessage);

                        if (!$reply) {
                            $reply = $this->generateHFReply($userMessage);
                        }
                        // Reply to comment
                        Http::post("https://graph.facebook.com/v25.0/{$commentId}/comments", [
                            'message' => $reply,
                            'access_token' => $this->pagesToken,
                        ]);
                    }
                }
            }
        }

        return response('EVENT_RECEIVED', 200);

    }

    private function generateHFReply(string $userMessage): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('HUGGING_FACE_TOKEN'),
        ])->post('https://api-inference.huggingface.co/models/gpt2', [
            'inputs' => "Reply to this comment politely: \"$userMessage\"",
        ]);

        $body = $response->json();

        return $body[0]['generated_text'] ?? "Thanks for your comment!";
    }

    private function checkDataset(string $userMessage): ?string
    {
        $dataset = json_decode(file_get_contents(storage_path('comments_replies.json')), true);
        foreach ($dataset as $item) {
            if (strtolower($item['comment']) === strtolower($userMessage)) {
                return $item['reply'];
            }
        }
        return null; // No match found
    }




}
