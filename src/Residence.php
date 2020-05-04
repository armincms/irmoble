<?php

namespace Armincms\Irmoble;

use Armincms\Koomeh\Nova\Residence as Resource; 
use Illuminate\Http\Request;  
use Laravel\Nova\Http\Requests\NovaRequest;

use Armincms\Tab\Tab; 
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\BelongsTo;
use Armincms\Nova\Fields\Images;
use Armincms\Fields\BelongsToMany; 
use Armincms\Koomeh\Nova\Facility;
use OwenMelbz\RadioField\RadioButton;
use Armincms\RawData\Common; 
use Armincms\Koomeh\Nova\Configuration;
use Zareismail\NovaWizard\Contracts\Wizard;
use Zareismail\NovaWizard\Step;
use Armincms\Koomeh\Nova\ResidencesType;
use Armincms\Koomeh\Nova\ResidencesUsage;
use Armincms\Koomeh\Nova\ResidencesPricing;
use Armincms\Koomeh\Nova\ResidencesCondition;
use Armincms\Koomeh\Nova\ResidencesReservation;
use Armincms\Location\Location;
use Armincms\Location\Nova\Zone;
use Armincms\Location\Nova\City;
use Hubertnnn\LaravelNova\Fields\DynamicSelect\DynamicSelect;
use Manmohanjit\BelongsToDependency\BelongsToDependency;

class Residence extends Resource implements Wizard
{     
    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = false;

    public static $pricings;
    public static $facilities;

    public $width = ['pricings'];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    { 
        return [  
            (new Step(__("Residences Specifications"), $this->stepOne($request)))
                ->checkpoint()
                ->withToolbar(),

            new Step(__("Pricing"), $this->stepTwo($request)),  

            $this->when($this->getFacilities()->count(), new Step(__("Facilities"), $this->stepThree($request))), 
        ];
    }


    public function stepOne($request)
    {
        $locations = Location::get(); 

        return  [
            ID::make()->sortable(),

            BelongsTo::make(__("Residences Type"), 'residencesType', ResidencesType::class)
                ->withoutTrashed(),

            BelongsTo::make(__("Reservation Method"), 'reservation', ResidencesReservation::class)
                ->withoutTrashed()
                ->hideFromIndex(! (bool) Configuration::option("_residences_reservation_")) 
                ->hideFromDetail(! (bool) Configuration::option("_residences_reservation_")) 
                ->hideWhenCreating(! (bool) Configuration::option("_residences_reservation_")) 
                ->hideWhenUpdating(! (bool) Configuration::option("_residences_reservation_")),

            BelongsTo::make(__('City'), 'city', City::class)
                ->hideFromIndex(), 

            BelongsToDependency::make(__('Zone'), 'zone', Zone::class)
                ->dependsOn('city', 'location_id')
                ->hideFromIndex(), 
 
            Text::make(__("Location"), function() {
                return optional($this->zone)->name ." - ".optional($this->city)->name;
            })->onlyOnIndex(),

            $this->when($count = ResidencesUsage::newModel()->count() > 1, function() {
                return  BelongsToMany::make(__("Usage"), 'usages', ResidencesUsage::class)
                            ->required()
                            ->rules('required')
                            ->display("usage");
            }),  

            $this->when($count == false && ! $request->isMethod('get'), function() {
                if($usage = ResidencesUsage::newModel()->first()) {
                    return  Text::make('usages')->fillUsing(function($request, $model) use ($usage) {
                                $model::saved(function($model) use ($request, $usage) {
                                    $model->usages()->sync($usage);
                                });
                            }); 
                }
            }),   

            Text::make(__('Price'), function() {
                return $this->pricings->map(function($pricing) {
                    return "{$pricing->label}:".($pricing->pivot->adaptive ? __('Adaptive') : $pricing->pivot->price);
                })->implode(' - ');
            })->asHtml(),      

            $this
                ->imageField() 
                ->stacked()
                ->customPropertiesFields([
                    $this->toggle(__("Master"), "master"),
                ]),

            $this->abstractField(_('Write about your home')),
        ];
    }

    public function stepTwo($request)
    {  
        return $this->getPricings()->map(function($pricing, $index) {
            $pivot = optional($this->pricings)->count() 
                        ? optional($this->pricings->where('id', $pricing->id)->first())->pivot
                        : null; 

            return [
                Heading::make($pricing->label),

                RadioButton::make(__("Adaptive"), "pricings[{$pricing->id}][adaptive]")
                        ->options([
                            1 => __("Yes"), 0  => __("No"), 
                        ])
                        ->toggle([
                            1 => ["pricings[{$pricing->id}][price]"]
                        ])
                        ->required()
                        ->default(0)
                        ->fillUsing(function() {})
                        ->resolveUsing(function($value, $resource, $attribute) use ($pivot) { 
                            return optional($pivot)->adaptive;
                        })
                        ->onlyOnForms(),

                $this->priceField("Price", "pricings[{$pricing->id}][price]", option("_residences_currency_", "IRR"))
                        ->required() 
                        ->rules([
                            'numeric', 
                            function ($attribute, $value, $fail) {
                                if (request('adaptive') == 0 && floatval($value) <= 0) {
                                    $fail(__("You should enter valid price"));
                                }
                            }
                        ])
                        ->fillUsing(function($request, $model) use ($index) {
                            if($this->getPricings()->count() - $index == 1) {
                                $model::saved(function($model) use ($request) {
                                    $model->pricings()->sync((array) $request->get('pricings'));  
                                }); 
                            }
                        })
                        ->resolveUsing(function($value, $resource, $attribute) use ($pivot) { 
                            return optional($pivot)->price;
                        })
                        ->onlyOnForms()
            ];
        })->flatten(1)->map->onlyOnForms()->toArray(); 
    }

    public function stepThree()
    {
        return $this->getFacilities()->map(function($facility, $index) {
            $fieldClass = $facility->field;
            $attribute = "facilities[{$facility->id}][value]";

            if (! class_exists($fieldClass)) {
                $fieldClass = Text::class;
            }

            $fillUsing = $this->getFacilities()->count() - $index !== 1 
                            ? function() {} 
                            : function($request, $model, $attribute) {
                                $model::saved(function($model) use ($request) {
                                    $model->facilities()->sync(
                                        collect($request->get('facilities'))->filter(function($facility) {
                                            return ! empty($facility['value']);
                                        })->map(function($facility) {
                                            if(is_array($facility['value'])) {
                                                $facility['value'] = json_encode($facility['value']);
                                            }

                                            return $facility;
                                        })->toArray()
                                    );
                                });
                            };

            return tap($fieldClass::make($facility->label, $attribute)->fillUsing($fillUsing), function($field) use ($facility) {
                    if (method_exists($field, 'options')) {
                        $field->options(collect($facility->options)->pluck('text')) ;
                    }

                    if (method_exists($field, 'displayUsingLabels')) {
                        $field->displayUsingLabels();
                    }

                    $field->hideFromIndex();
                    $field->resolveUsing(function($value, $resource) use ($facility) {
                        $facilities = optional($this->facilities)->where('id', $facility->id);

                        if(optional($facilities)->count()) {
                            return $facilities->first()->pivot->value;
                        }
                    });
            });
        })->toArray(); 
    }

    public function getPricings()
    {
        if(! isset(static::$pricings)) {
            static::$pricings = ResidencesPricing::newModel()->get(); 
        } 

        return static::$pricings;
    }

    public function getFacilities()
    {
        if(! isset(static::$facilities)) {
            static::$facilities = Facility::newModel()->get(); 
        } 

        return static::$facilities->sortBy(function($facility) { 
            switch (class_basename($facility->field)) {
                case 'Number':
                    return 0;
                    break;

                case 'Text':
                    return 1;
                    break;

                case 'Boolean':
                    return time();
                    break;
                    break; 
                
                default:
                    return 3;
                    break;
            }
        });
    }


    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return __("My ads");
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return __("Advertisement");
    }   


    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function uriKey()
    {
        return 'wizard-' . parent::uriKey();
    }

    /**
     * Get meta information about this resource for client side comsumption.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public static function additionalInformation(Request $request)
    {
        return ['wizard' => true];
    }
}
