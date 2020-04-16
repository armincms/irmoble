<?php

namespace Armincms\Irmoble;

use Laravel\Nova\Tools\ResourceManager as Tool; 
use Illuminate\Http\Request;
use Laravel\Nova\Nova; 

class ResourceManager extends Tool
{  
    /**
     * Build the view that renders the navigation links for the tool.
     *
     * @return \Illuminate\View\View
     */
    public function renderNavigation()
    {
        if(! \Auth::guard('admin')->check()) {
            return $this->userNavigation();
        }

        $request = request();
        $groups = Nova::groups($request);
        $navigation = collect(Nova::groupedResources($request))
            ->map->filter(function ($resource) {
                return $resource::$displayInNavigation;
            })
            ->filter->count();

        return view('nova::resources.navigation', [
            'navigation' => $navigation,
            'groups' => $groups,
        ]);
    }

    public function userNavigation()
    {  
        return view('irmoble::navigation');
    }
}
