<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Invite;
use App\Models\Milestone;
use Illuminate\Http\Request;

class HirerController extends Controller
{
    /**
     * post a job
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function post_job(Request $request)
    {
        $validateData = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'industry_id' => 'required',
            'currency_code' => 'required',
            'job_budget_id' => 'nullable',
            'budget_to' => 'nullable',
            'budget_from'=> 'nullable',
            'budget_type' => 'nullable',
            'skills' => 'required',
            'description' => 'required',
            'documents' => 'nullable'
        ]);

        $job = new Job;
        $job->name = $request->name;
        $job->job_budget_id = $request->job_budget_id;
        $job->country_id = $request->currency_code;
        $job->description = $request->description;
  

    }

     /**
     * recent not assigned jobs jobs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function not_assigned_jobs()
    {
        $jobs = Job::where('user_id', Auth::user()->id)
                    ->where('status', 'not assigned')
                    ->with('bids')
                    ->withCount('bids')
                    ->get();

        return view('dashboard.jobs.new_jobs', compact('jobs'));
    }

     /**
     * recent assigned jobs jobs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function assigned_jobs()
    {
        $jobs = Job::where('user_id', Auth::user()->id)
                    ->where('status', 'assigned')
                    ->with('milestones', 'accepted_bid')
                    ->withCount('milestones')
                    ->get();

        return view('dashboard.jobs.ongoing_jobs', compact('jobs'));
    }

    /**
     * recent assigned jobs jobs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function completed_jobs()
    {
        $jobs = Job::where('user_id', Auth::user()->id)
                    ->where('status', 'completed')
                    ->with('milestones', 'accepted_bid', 'payments')
                    ->withCount('milestones')
                    ->get();

        return view('dashboard.jobs.completed_jobs', compact('jobs'));
    }

     /**
     * manage bids for a job.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $job_uuid
     * @return \Illuminate\Http\Response
     */
    public function manage_bids(Request $request, $job_uuid)
    {
        $job = Job::where('uuid', $job_uuid)->first();
        $bids = Bid::where('job_id', $job->id)->with('profile')->get();

        return view('dashboard.jobs.bidders', compact('bids'));
    }
    


     /**
     * accept bids.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $bid_uuid
     * @return \Illuminate\Http\Response
     */
    public function accept_bid(Request $request, $bid_uuid)
    {
        $validateData = $request->validate([
            'profile_id' => 'required'
        ]);
        $bid = Bid::where('uuid', $bid_uuid)->first();
        $bid->status = 'accepted';
        $bid->save();

        $job = Job::where('id', $bid->id)->first();
        $job->status = 'assigned';
        $job->profile_id = $request->profile_id;
        $job->save();

        return response()->json([
            'status' => "Success",
            'message' => "Job was assigned successfully"
        ]);
    }

    /**
     * view milestones.
     *
     * @param  string  $job_uuid
     * @return \Illuminate\Http\Response
     */
    public function milestones($job_uuid)
    {

        $job = Job::where('uuid', $job_uuid)->with('milestones')->first();

        return view('dashboard.milestones', compact('job'));
    }


    /**
     * update milestone status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $mile_uuid
     * @return \Illuminate\Http\Response
     */
    public function update_milestone(Request $request, $mile_uuid)
    {
        $validateData = $request->validate([
            'status' => 'required'
        ]);

        $milestone = Milestone::where('uuid', $mile_uuid)->first();
        $milestone->status = $request->status;
        $milestone->save();

        return response()->json([
            'status' => "Success",
            'message' => "Milestone Status was changed successfully"
        ]);
    }


    /**
     * Review a freelancer.
     *
     * @param  string  $profile_uuid
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function review_freelancer(Request $request, $profile_uuid)
    {
        $validateData = $request->validate([
            'rating' => 'required',
            'body' => 'nullable',
        ]);

        $profile = Profile::where('uuid', $profile_uuid)->first();

        $profile->reviews()->create([
            'user_id' => Auth::user()->id,
            'rating' => $request->rating,
            'body' => $request->body
        ]);

        return response()->json([
            'status' => "Success",
            'message' => "Review was saved successfully"
        ]);


    }


    /**
     * View All Reviews.
     *
     * @return \Illuminate\Http\Response
     */
    public function reviews()
    {

        $reviews = Review::where('user_id', Auth::user()->id)->get();

        return view('dashboard.reviews',  compact('reviews'));


    }

      /**
     * Send Invite.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function send_invite(Request $request)
    {
        $validateData = $request->validate([
            'profile_id' => 'required',
            'job_id' => 'required',
            'message' => 'nullable',
            'document' => 'nullable',
        ]);

        $invite = new Invite;
        $invite->job_id = $request->job_id;
        $invite->user_id = Auth::user()->id;
        $invite->profile_id = $request->profile_id;
        $invite->message = $request->message;
        $invite->status = 'pending';
        $invite->save();

        $invite->addMultipleMediaFromRequest('file');

        $fileAdders = $invite
                        ->addMultipleMediaFromRequest($request->file('document'))
                        ->each(function ($fileAdder) {
                            $fileAdder->toMediaCollection('project_files');
                        });

        return view('freelancers.show')->with('status', "Invite successfully sent");

    }

    /**
     * View all Invites.
     *
     * @return \Illuminate\Http\Response
     */
    public function invites()
    {
        $invites = Invite::where('user_id', Auth::user()->id)
                    ->with('job', 'profile')
                    ->get();

        return view('dashboard.jobs.invites', compact('invites'));
    }

     /**
     * Bookmark freelancer.
     *
     * @param  string  $profile_uuid
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bookmark_freelancer($profile_uuid)
    {
        $profile = Profile::where('uuid', $job_uuid)->first();
        $bookmark = new Bookmark;
        $bookmark->user_id = Auth::user()->id;
        $bookmark->profile_id = $profile->id;
        $bookmark->save();

        return response()->json([
            'status' => 'Success',
            'message' => "Bookmarked Successfully"
        ]);
    }


     /**
     * View Bookmarks.
     *
     * @return \Illuminate\Http\Response
     */
    public function bookmarks()
    {
        $bookmarks = Bookmark::where('user_id', Auth::user()->id)->get();

        return view('dashboard.bookmarks', compact('bookmarks'));
    }
}