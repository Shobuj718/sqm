<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Schema::defaultStringLength(191);

        // Register OpenAI client
        $this->app->singleton('openai', function ($app) {
            return \OpenAI::client(env('OPENAI_API_KEY'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.layouts.app.sidebar', function ($view) {
            $agents = User::where('role', User::ROLE_USER)
                ->orWhereHas('roles', fn ($query) => $query->where('name', User::ROLE_USER))
                ->withCount(['tickets as pending_tickets_count' => fn ($query) => $query->whereIn('status', ['open', 'waiting'])])
                ->with(['tickets' => fn ($query) => $query->whereIn('status', ['open', 'waiting'])->latest('updated_at')->limit(5)])
                ->get();

            $view->with('sidebarAgents', $agents);
        });
    }
}
