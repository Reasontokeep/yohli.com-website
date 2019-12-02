<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Profile;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $profile_uuid
     * @return \Illuminate\Http\Response
     */
    public function update_password(Request $request)
    {
        $messages = [
            'password.regex' => 'Password must contain at least one number, one symbol and both uppercase and lowercase letters.'
        ];

        Validator::make($request->all(), [
            'current_password' => 'required|string|min:6',
            'password' => 'required|min:6|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).+$/',
        ], $messages)->validate();


        $current_password = Auth::User()->password;
        if (Hash::check($request->current_password, $current_password)) {
            $user = User::find(Auth::id());
            $user->password = Hash::make($request->password);
            $user->save();
            return redirect()->back()->with('success', 'Password updated successfully');
        } else {

            return redirect()->back()->with('error', 'Current Password was incorrect');
        }
    }

    public function update_role(Request $request){
        $user = User::find(Auth::id()); 
        $profile = Profile::find($user->profile->id);

        if($request->account_type == 'freelancer'){
            if(!$profile->verified){
                toastr()->error('Sorry, could not switch because your account hasn\'t been verified');
                return back();
            }
        }
        $user->syncRoles([$request->account_type]); 

       
        $profile->type = $request->account_type;
        $profile->save();

        session(['role' => $request->account_type]);

        toastr()->success('Your account type has been switched to '. ucfirst($request->account_type));

        return back();
    }

    public function update_basic_info(Request $request)
    {

        Validator::make($request->all(), [
            'email' =>  [
                'required', 'email',
                Rule::unique('users', 'email')->ignore(Auth::id()),
            ],
            'first_name' => 'required|string|min:3',
            'last_name' => 'required|string|min:3',
            'phone' => ['required', 'numeric', Rule::unique('users', 'phone')->ignore(Auth::id())],
            'country_id' => 'required|numeric',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [])->validate();

        try {
            DB::beginTransaction();

            $user = User::find(Auth::id());
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->save();

            $profile = Profile::find($user->profile->id);
            $profile->name = $user->first_name . " ". $user->last_name;
            $profile->email = $user->email;
            $profile->phone = $user->phone;
            $profile->country_id = $request->country_id;

            $profile->save();

            if ($request->hasFile('picture')) {
                // Check if media already exists
                $media = $profile->getMedia('profile');
                if (sizeof($media) != 0) {
                    // Remove old image
                    $media[0]->delete();
                }
                // Add New Image
                $profile
                    ->addMediaFromRequest('picture')
                    ->toMediaCollection('profile');
            }

            DB::commit();
            return back()->with('success', 'Profile updated successfully');
        } catch (\Exception $e) {
            Log::error($e->getMessage(), [
                "code" => $e->getCode(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
            ]);

            // Rollback DB transactions is an error occurred
            DB::rollback();

            return back()->with('error', 'Something went wrong')->withInput();
        }
    }

    public function update_freelancer_info(Request $request)
    {
        Validator::make($request->all(), [
            'rate' => 'required',
            'headline' => 'required',
            'description' => 'required',
            'documents.*' => 'file|max:10240',
        ], [])->validate();

        $skills = [];

        foreach ($request->skills as $index => $skill) {
            if(is_numeric($skill)){
                array_push($skills, $skill);
            }  else {
                $skillId = $this->createSkill($skill);
                array_push($skills, $skillId);
            }
            
        }

        try {
            DB::beginTransaction();

            $profile = Profile::find(Auth::user()->profile->id);
            $profile->rate = $request->rate;
            $profile->headline = $request->headline;
            $profile->description = $request->description;
            $profile->skills()->sync($skills);

            $profile->save();


            if ($request->hasFile('documents')) {
                $profile
                    ->addMultipleMediaFromRequest(['documents'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('cv');
                    });
            }

            DB::commit();
            return back()->with('success', 'Profile updated successfully');
        } catch (\Exception $e) {
            Log::error($e->getMessage(), [
                "code" => $e->getCode(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
            ]);

            // Rollback DB transactions is an error occurred
            DB::rollback();

            return redirect()->back()->with('error', 'Something went wrong')->withInput();
        }
    }

    private function createSkill($skillName){
        $skill = new Skill();
        $skill->title = $skillName;
        $skill->save();
        return $skill->id;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $profile_uuid
     * @return \Illuminate\Http\Response
     */
    public function update_profile(Request $request)
    {
        $validateData = $request->validate([
            'profile_name' => 'required|string',
            'profile_email' => 'required|unique:companies,email',
            'profile_phone' => 'required|numeric',
            'address' => 'nullable|string',
            'headline' => 'nullable|string',
            'city' => 'nullable|string',
            'region' => 'nullable|string',
            'country' => 'nullable',
            'rate' => 'nullable|string',
            'skills' => 'nullable',
            'picture' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048',
            'documents' => 'nullable|file'
        ]);

        $profile = Profile::where('user_id', Auth::user()->id)->first();
        $profile->name = $request->profile_name;
        $profile->email = $request->profile_email;
        $profile->phone1 = $request->profile_phone;
        $profile->address = $request->address;
        $profile->city = $request->city;
        $profile->region = $request->region;
        $profile->country = $request->country;
        $profile->rate = $request->rate;
        $profile->headline = $request->headline;
        $profile->save();

        $profile->skills()->sync($request->skills);


        if ($request->hasFile('picture')) {
            $profile
                ->addMediaFromRequest('picture') //starting method
                ->toMediaCollection('profile');
        }

        if ($request->hasFile('documents')) {
            $fileAdders = $profile
                ->addMultipleMediaFromRequest($request->file('document'))
                ->each(function ($fileAdder) {
                    $fileAdder->toMediaCollection('cv');
                });
        }

        return response()->back()->with('status', "profile updated Succesfully");
    }
}
