<?php

namespace Armincms\Irmoble;

use Core\User\Models\User as Model; 

class User extends Model
{ 
	public static function boot()
	{
		parent::boot(); 

        static::addGlobalScope(new ProfileScope);
	}
}
