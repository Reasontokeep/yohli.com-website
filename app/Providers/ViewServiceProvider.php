<?php

namespace App\Providers;

use App\Models\Bid;
use App\Models\Invite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\Job;
use App\Models\Profile;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('partials.dashboard_sidebar', function ($view) {
            $user = Auth::user();
            if (!empty($user)) {
                $freelancer = Profile::where('user_id', $user->id)->first();
    
                if ($user->hasRole('hirer')) { 
                    $newJobsCount = Job::where('user_id',  $freelancer->id)->where('status', 'not assigned')->count();
                    $ongoingJobsCount = Job::where('user_id',  $freelancer->id)->where('status', 'assigned')->count();
                    $completedJobsCount = Job::where('user_id',  $freelancer->id)->where('status', 'completed')->count();
                    $bidsCount = 0;
                    $invitesCount = Invite::where('user_id', $freelancer->id)->count();
                } else {
                    $newJobsCount = 0;
                    $ongoingJobsCount = Job::where('profile_id',  $freelancer->id)->where('status', 'assigned')->count();
                    $completedJobsCount = Job::where('profile_id',  $freelancer->id)->where('status', 'completed')->count();
                    $bidsCount = Bid::where('profile_id', $freelancer->id)->count();
                    $invitesCount = Invite::where('profile_id', $freelancer->id)->count();
                }
                
            }
            $view->with('ongoing_jobs_count', $ongoingJobsCount);
            $view->with('completed_jobs_count', $completedJobsCount);
            $view->with('bids_count', $bidsCount);
            $view->with('invites_count', $invitesCount);
            $view->with('new_jobs_count', $newJobsCount);
        });
    }
}