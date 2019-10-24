<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Fields\Place;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\MorphMany;
use DigitalCloud\NovaResourceNotes\Fields\Notes;
use Benjacho\BelongsToManyField\BelongsToManyField;


class Job extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var  string
     */
    public static $model = \App\Models\Job::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var  string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var  array
     */
    public static $search = [
        'id', 'name'
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return  string
     */
    public static function label()
    {
        return __('Jobs');
    }

    /**
    * Get the displayable singular label of the resource.
    *
    * @return  string
    */
    public static function singularLabel()
    {
        return __('Job');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param    \Illuminate\Http\Request  $request
     * @return  array
     */
    public function fields(Request $request)
    {
        return [
            ID::make( __('Id'),  'id')
            ->rules('required')
            ->sortable(),
            BelongsTo::make('Hirer', 'owner', 'App\Nova\User')
            ->searchable()
            ->sortable(),  
            BelongsTo::make('Freelancer', 'profile', 'App\Nova\Profile')
            ->searchable()
            ->sortable(),    
            Text::make( __('Title'),  'name')
            ->sortable(),       
            Trix::make( __('Description'),  'description')
            ->withFiles('public'), 
            BelongsTo::make('Industry')
            ->searchable()
            ->sortable(),
            BelongsToManyField::make('Skiils', 'skills', 'App\Nova\Skill')->options(\App\Models\Skill::all())->nullable(),
            BelongsToManyField::make('Attachments', 'attachments', 'App\Nova\ProfileAttachment')->options(\App\Models\Attachment::all())->nullable(),
            BelongsTo::make('Select Budget', 'job_budget', 'App\Nova\Job')
            ->searchable()
            ->sortable(),
            Select::make( __('Status'),  'status')
            ->sortable()
            ->options([
                'not assigned' => 'Not Assigned',
                'assigned' => 'Assigned',
                'completed' => 'Completed',
                'inactive' => 'Make inactive',
            ]),
            Select::make( __('Featured'),  'featured')
            ->sortable()
            ->options([
                'no' => 'No',
                'yes' => 'Yes',
            ])->displayUsingLabels(),

            Place::make( __('City'),  'city')
                ->sortable()
                ->onlyCities(),

            BelongsTo::make('Country')
                ->searchable()
                ->sortable(),
            Select::make( __('On Time'),  'ontime')
            ->sortable()
            ->options([
                'no' => 'No',
                'yes' => 'Yes',
            ]),
            Select::make( __('On Budget'),  'onbudget')
            ->sortable()
            ->options([
                'no' => 'No',
                'yes' => 'Yes',
            ]),
            Files::make('Supporting Document', 'project_files')
            ->customProperties([
                'type' => 'project files',
            ]),
            MorphMany::make('Reviews'),
            HasMany::make('Bid'),
            Notes::make('Notes','notes')->onlyOnDetail(),

            ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param    \Illuminate\Http\Request  $request
     * @return  array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param    \Illuminate\Http\Request  $request
     * @return  array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param    \Illuminate\Http\Request  $request
     * @return  array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param    \Illuminate\Http\Request  $request
     * @return  array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
