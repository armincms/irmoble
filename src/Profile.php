<?php

namespace Armincms\Irmoble;

use Armincms\Nova\User as Resource;
use Illuminate\Http\Request; 

class Profile extends Resource
{ 
    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = false;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = User::class;


    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return collect(parent::fields($request))->filter(function($field) { 
            return $field->attribute !== 'status';
        })->toArray();
    }
}
