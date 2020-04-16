<?php

namespace Armincms\Irmoble;

use Armincms\Koomeh\Nova\Residence as Resource; 
use Illuminate\Http\Request; 
use Armincms\Wizard\Step; 

use Armincms\Tab\Tab; 
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\BelongsTo;
use Armincms\Nova\Fields\Images;
use Armincms\Fields\BelongsToMany;
use Armincms\Fields\MorphedByMany;
use Armincms\Facility\Facility as FacilityModel;
use OwenMelbz\RadioField\RadioButton;
use Armincms\RawData\Common;
use Armincms\Facility\Nova\Fields\ManyToMany;
use Armincms\Koomeh\Nova\Configuration;

class Residence extends Resource
{     
    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = false;

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    { 
        return [  
            Step::make(__("Residences Specifications"), [$this, 'stepOne']),

            Step::make(__("Facilities"), [$this, 'StepTwo']),

            // Step::make('Step One', [
            //     Text::make(__('INN'), 'inn')->hideFromIndex(),
            // ]),

            // Step::make('Step Two', [
            //     Text::make(__('INN'), 'inn')->readonly()->hideFromIndex(),
            //     Text::make(__('Title'), function ()  {
            //         return time();
            //     })->readonly()->hideFromIndex(),

            //     Text::make(__('email'), 'email')->rules('required')->hideFromIndex(),
            // ]),

            // Step::make(__("Conditions"), [$this, 'conditionFields']),
                
            // Step::make(__("Settings"), [$this, 'settingFields']),
        ];
    }


    public function stepOne()
    {
        return  [
            ID::make()->sortable(),

            BelongsTo::make(
                __("Residences Type"),
                'residencesType',
                \Armincms\Koomeh\Nova\ResidencesType::class
            )->withoutTrashed(),

            BelongsTo::make(
                    __("Reservation Method"),
                    'reservation',
                    \Armincms\Koomeh\Nova\ResidencesReservation::class
                )
                ->withoutTrashed()
                ->hideFromIndex(! (bool) Configuration::option("_residences_reservation_")) 
                ->hideFromDetail(! (bool) Configuration::option("_residences_reservation_")) 
                ->hideWhenCreating(! (bool) Configuration::option("_residences_reservation_")) 
                ->hideWhenUpdating(! (bool) Configuration::option("_residences_reservation_")), 

            BelongsTo::make(__("City"), 'city', \Armincms\Location\Nova\City::class)
                ->searchable()
                ->hideFromIndex(),

            BelongsTo::make(__("Zone"), 'zone', \Armincms\Location\Nova\Zone::class)
                ->searchable()
                ->hideFromIndex(),

            Text::make(__("Location"), function() {
                return optional($this->zone)->name ." - ".optional($this->city)->name;
            }),

            BelongsToMany::make(
                    __("Usage"), 'usages', \Armincms\Koomeh\Nova\ResidencesUsage::class
                )
                ->required()
                ->rules('required')
                ->display("usage"), 

            BelongsToMany::make(
                    __("Pricings"), 'pricings', \Armincms\Koomeh\Nova\ResidencesPricing::class
                )
                ->fields(function ($request) {
                    $pricing = \Armincms\Koomeh\Nova\ResidencesPricing::newModel()->find($request->relatedId);

                    return $pricing->adaptive ? [] : [

                        RadioButton::make(__("Adaptive"), "adaptive")->options([
                            __("No"), __("Yes")
                        ])->toggle([
                            1 => ['price']
                        ])->required()->default(0),


                        $this->priceField("Price", 'price', option("_residences_currency_", "IRR"))
                            ->required() 
                            ->rules(['numeric', function ($attribute, $value, $fail) {
                                if (request('adaptive') == 0 && floatval($value) <= 0) {
                                    $fail(__("You should enter valid price"));
                                }
                            }]),

                    ];
                })
                ->pivots()
                ->fillUsing(function ($value) {
                    return [
                        'adaptive' => (int) ($value['adaptive'] ?? 0),
                        'price' => floatval($value['price'] ?? 0),
                    ];
                }),
 
            $this
                ->imageField() 
                ->stacked()
                ->customPropertiesFields([
                    $this->toggle(__("Master"), "master"),
                ]),

            $this->translatable([ 
                $this->abstractField(),

                $this->gutenbergField(),
            ]),
        ];
    }

    public function stepTwo()
    {
        return [
            // MorphedByMany::make(
            //         __("Facilities"), 'facilities', \Armincms\Koomeh\Nova\Facility::class
            //     )
            //     ->fields(function ($request) {
            //         $fieldClass = data_get(
            //             $facility = FacilityModel::find($request->relatedId),
            //             'field'
            //         );

            //         if (! class_exists($fieldClass)) {
            //             $fieldClass = Text::class;
            //         }

            //         return [tap($fieldClass::make($facility->label, 'value'), function ($field) use ($facility) {
            //             if (method_exists($field, 'options')) {
            //                 $field
            //                     ->options(collect($facility->options)->pluck('text'))
            //                     ->required()
            //                     ->rules('required');
            //             }

            //             if (method_exists($field, 'displayUsingLabels')) {
            //                 $field->displayUsingLabels();
            //             }
            //         })];
            //     })
            //     ->pivots()
            //     ->hideFromIndex()
            //     ->fillUsing(function ($value) {
            //         if (isset($value['value']) && is_array($value['value'])) {
            //             $value['value'] = json_encode($value['value']);
            //         }

            //         return $value;
            //     }), 


            ManyToMany::make("", "Numbers")->onlyNumbers(), 

            ManyToMany::make("", "Others")->sortUsing(function($field, $index) { 
                return ! method_exists($field, 'options') ?: 0;
            })->withoutNumbers(), 

            BelongsToMany::make(
                    __("Residences Condition"), 
                    'conditions', 
                    \Armincms\Koomeh\Nova\ResidencesCondition::class
                )
                ->hideFromIndex()
                ->display("condition")
                ->hideFromIndex(), 

            Text::make(__("Latitude"), 'latitude')
                    ->hideFromIndex(),

            Text::make(__("Longitude"), 'longitude')
                    ->hideFromIndex(),


            $this->translatable([
                Text::make(__("Address"), 'address')
                    ->nullable()
                    ->hideFromIndex(), 
            ]),
        ];
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
