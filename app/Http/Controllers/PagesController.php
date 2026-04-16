<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\HandleFacebookCommentJob;
use App\Jobs\HandleFacebookMessageJob;
use App\Models\FacebookPage;
use App\Models\Setting;



class PagesController extends Controller
{
    private $accessToken;

    private $userId;

    private $verifyToken;

    public function __construct()
    {
        $this->accessToken = Setting::get('facebook_access_token');

        $this->verifyToken = Setting::get('facebook_verify_token');

    }

    public function index(Request $request)
    {
        $refresh = $request->boolean('refresh', false);

        if ($refresh || FacebookPage::count() === 0) {
            $syncResult = $this->refreshFacebookPages();
            if ($syncResult !== true) {
                return back()->with('error', $syncResult ?: 'Failed to fetch pages from Facebook.');
            }
        }

        $pages = FacebookPage::all();

        $data = [
            'data' => $pages->map(function (FacebookPage $page) {
                return [
                    'id' => $page->page_id,
                    'name' => $page->page_name,
                    'category' => $page->page_category ?? 'N/A',
                    'access_token' => $page->page_token,
                ];
            })->toArray(),
        ];

        $activePages = $pages->mapWithKeys(function (FacebookPage $page) {
            return [$page->page_id => ['name' => $page->page_name, 'token' => $page->page_token, 'active' => true]];
        })->toArray();

        return view('fbpages.index', [
            'pages' => $data,
            'activePages' => $activePages,
        ]);
    }

    private function refreshFacebookPages()
    {
        if (empty($this->accessToken)) {
            return 'Facebook access token is not configured.';
        }

        $response = Http::get("https://graph.facebook.com/v25.0/me/accounts", [
            'access_token' => $this->accessToken,
        ]);

        if ($response->failed()) {
            return $response->body() ?: 'Failed to fetch pages from Facebook.';
        }

        $payload = $response->json();
        $pages = $payload['data'] ?? [];

        $pageIds = [];

        foreach ($pages as $page) {
            $pageIds[] = $page['id'];

            FacebookPage::updateOrCreate([
                'page_id' => $page['id'],
            ], [
                'page_name' => $page['name'] ?? 'Unknown',
                'page_token' => $page['access_token'] ?? '',
                'page_category' => $page['category'] ?? null,
            ]);
        }

        if (!empty($pageIds)) {
            FacebookPage::whereNotIn('page_id', $pageIds)->delete();
        }

        return true;
    }

    public function posts($page_id)
    {
        $response = Http::get("https://graph.facebook.com/v25.0/$page_id/feed", [
            'fields' => 'id,message,story,created_time,permalink_url',
            'access_token' =>  $this->getPageToken($page_id),
        ]);

        if ($response->failed()) {
            return back()->with('error', 'Failed to fetch posts from Facebook.');
        }

        $posts = $response->json();

        return view('fbpages.posts', compact('posts', 'page_id'));

    }


    public function comments($post_id, $page_id)
    {

        $response = Http::get("https://graph.facebook.com/v25.0/$post_id/comments", [
            'fields' => 'id,message,from,created_time',
            'access_token' => $this->getPageToken($page_id)
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
            'access_token' =>  $this->accessToken
        ]);

        $result = $response->json();

        if(isset($result['id'])){
            return back()->with('success','Reply posted successfully!');
        }

        return back()->with('error','Failed to post reply.');
    }

    public function subscription()
    {

        $appId = Setting::get('facebook_app_id');
        $appSecret = Setting::get('facebook_app_secret');

        $appAccessToken = $appId . '|' . $appSecret;

        $response = Http::get("https://graph.facebook.com/v25.0/{$appId}/subscriptions", [
            'access_token' => $appAccessToken
        ]);

        $data = $response->json();
        dd($data);
    }

    public function createSubscription()
    {

        $appId = Setting::get('facebook_app_id');
        $appSecret = Setting::get('facebook_app_secret');

        $appAccessToken = $appId . '|' . $appSecret;

        $response = Http::asForm()->post("https://graph.facebook.com/v25.0/{$appId}/subscriptions", [
            'object' => 'page',
            'callback_url' => url('facebook/webhook'),
            'fields' => 'feed,comments',
            'include_values' => true,
            'verify_token' => Setting::get('facebook_verify_token'),
            'access_token' => $appAccessToken
        ]);

        dd($response->json());
    }


    public function webhook(Request $request)
    {

        \Log::info("message");
        if ($request->hub_verify_token ===  $this->verifyToken) {
            return $request->hub_challenge;
        }

        return response('Invalid token', 403);
    }


    public function webhookReply(Request $request)
    {
        Log::info('FB Webhook Received:', $request->all());

        $data = $request->json()->all();

        if (($data['object'] ?? null) !== 'page') {
            return response('EVENT_RECEIVED', 200);
        }

        foreach ($data['entry'] as $entry) {

            $pageId = $entry['id'] ?? null;

            $pageToken = $this->getPageToken($pageId);

            if (!$pageToken) {
                Log::error('Page token not found', ['page_id' => $pageId]);
                continue;
            }

            /**
             * =========================================
             * HANDLE COMMENTS (feed)
             * =========================================
             */
            if (!empty($entry['changes'])) {

                foreach ($entry['changes'] as $change) {

                    $value = $change['value'] ?? [];

                    if (
                        ($value['item'] ?? null) === 'comment' &&
                        ($value['verb'] ?? null) === 'add'
                    ) {
                        $commentId = $value['comment_id'] ?? null;
                        $message   = $value['message'] ?? '';
                        $fromId    = $value['from']['id'] ?? null;

                        if ($fromId == $pageId) {
                            continue;
                        }

                        Log::info('New Comment', [
                            'page_id'   => $pageId,
                            'comment_id'=> $commentId,
                            'message'   => $message
                        ]);

                        HandleFacebookCommentJob::dispatch(
                            $pageId,
                            $pageToken,
                            $commentId,
                            $message,
                            $fromId
                        );
                    }
                }
            }

            /**
             * =========================================
             * HANDLE MESSENGER (messages)
             * =========================================
             */
            if (!empty($entry['messaging'])) {

                foreach ($entry['messaging'] as $event) {

                    if (isset($event['message']) && !isset($event['message']['is_echo'])) {

                        $senderId = $event['sender']['id'];
                        $message  = $event['message']['text'] ?? '';

                        Log::info('New Messenger Message', [
                            'page_id'  => $pageId,
                            'sender_id'=> $senderId,
                            'message'  => $message
                        ]);

                        HandleFacebookMessageJob::dispatch(
                            $pageId,
                            $pageToken,
                            $senderId,
                            $message
                        );
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


    private function checkDataset(string $userMessage, $pageId): ?string
    {
        $filePath = resource_path("views/social/replies/{$pageId}.json");

        // 🔥 If file not exists → no reply
        if (!file_exists($filePath)) {
            return null;
        }

        $dataset = json_decode(file_get_contents($filePath), true);

        if (!$dataset) {
            return null;
        }

        foreach ($dataset as $item) {

            if (strtolower($item['comment']) === strtolower($userMessage)) {
                return $item['reply'];
            }
        }

        return null; // No match found
    }

    private function getPageToken($pageId)
    {
        return FacebookPage::where('page_id', $pageId)
            ->value('page_token');
    }

     public function replyIndex(Request $request, $pageId)
    {
        $file = resource_path("views/social/replies/{$pageId}.json");

        $dir = dirname($file);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($file)) {
            file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
        }

        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;

        $data = json_decode(file_get_contents($file), true);

        if ($search !== '') {
            $lowerSearch = strtolower($search);
            $data = array_values(array_filter($data, function ($item) use ($lowerSearch) {
                return str_contains(strtolower($item['comment'] ?? ''), $lowerSearch)
                    || str_contains(strtolower($item['reply'] ?? ''), $lowerSearch);
            }));
        }

        $total = count($data);
        $items = array_slice($data, ($page - 1) * $perPage, $perPage);

        return view('fbpages.prebuilt_replies', compact('items', 'pageId', 'total', 'page', 'perPage', 'search'));
    }

    public function replyAdd(Request $request)
    {
        $pageId = $request->page_id;

        $file = resource_path("views/social/replies/{$pageId}.json");

        $data = file_exists($file)
            ? json_decode(file_get_contents($file), true)
            : [];

        $data[] = [
            'comment' => $request->comment,
            'reply'   => $request->reply
        ];

        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

        return back()->with('success', 'Added successfully');
    }

    public function replyUpdate(Request $request)
    {
        $pageId = $request->page_id;

        $file = resource_path("views/social/replies/{$pageId}.json");

        $data = json_decode(file_get_contents($file), true);

        $index = $request->index;

        if (isset($data[$index])) {
            $data[$index]['comment'] = $request->comment;
            $data[$index]['reply']   = $request->reply;
        }

        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

        return back()->with('success', 'Updated successfully');
    }

    public function replyDelete(Request $request)
    {
        $pageId = $request->page_id;

        $file = resource_path("views/social/replies/{$pageId}.json");

        $data = json_decode(file_get_contents($file), true);

        unset($data[$request->index]);

        $data = array_values($data);

        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

        return back()->with('success', 'Deleted successfully');
    }




}
