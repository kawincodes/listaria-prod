<?php

namespace App\Http\Resources;

use App\Models\Language;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use JsonSerializable;
use Throwable;

class ItemCollection extends ResourceCollection {
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     * @throws Throwable
     */
    public function toArray(Request $request) {
        try {
            $response = [];
            foreach ($this->collection as $key => $collection) {
                /* NOTE : This code can be improved */
                $response[$key] = $collection->toArray();
                if ($collection->status == "approved" && $collection->relationLoaded('featured_items')) {
                    $response[$key]['is_feature'] = count($collection->featured_items) > 0;
                }else{
                    $response[$key]['is_feature'] = false;
                }


                /*** Favourites ***/
                if ($collection->relationLoaded('favourites')) {
                    $response[$key]['total_likes'] = $collection->favourites->count();
                    if (Auth::check()) {
//                        $response[$key]['is_liked'] = $collection->favourites->where(['item_id' => $collection->id, 'user_id' => Auth::user()->id])->count() > 0;
                        $response[$key]['is_liked'] = $collection->favourites->where('item_id', $collection->id)->where('user_id', Auth::user()->id)->count() > 0;
                    } else {
                        $response[$key]['is_liked'] = false;
                    }
                }
                if ($collection->relationLoaded('user') && !is_null($collection->user)) {

                    $response[$key]['user'] = $collection->user;
                    $response[$key]['user']['reviews_count'] = $collection->user->sellerReview()->count();
                    $response[$key]['user']['average_rating'] = $collection->user->sellerReview->avg('ratings');
                    if ($collection->user->show_personal_details == 0) {
                        $response[$key]['user']['mobile'] = '';
                        $response[$key]['user']['country_code'] = '';
                        $response[$key]['user']['email'] = '';

                    }
                }

                $contentLangCode = request()->header('Content-Language') ?? app()->getLocale();
                $currentLanguage = Language::where('code', $contentLangCode)->first();

                $translatedItem = [
                    'name'              => $collection->name,
                    'description'       => $collection->description,
                    'address'           => $collection->address,
                    'rejected_reason'   => $collection->rejected_reason ?? null,
                    'admin_edit_reason' => $collection->admin_edit_reason ?? null,
                    'city'              => $collection->city,
                    'state'             => $collection->state,
                    'country'           => $collection->country,
                ];

                    // If matching translation exists, override
                    if ($currentLanguage && $collection->relationLoaded('translations')) {
                        $translation = $collection->translations->firstWhere('language_id', $currentLanguage->id);
                        if ($translation) {
                            $translatedItem = [
                                'name'              => $translation->name,
                                'description'       => $translation->description,
                                'address'           => $translation->address,
                                'rejected_reason'   => $translation->rejected_reason,
                                'admin_edit_reason' => $translation->admin_edit_reason,
                                'city'              => $translation->city,
                                'state'             => $translation->state,
                                'country'           => $translation->country,
                            ];
                        }
                    }

                    $response[$key]['translated_item'] = $translatedItem;

                if ($collection->relationLoaded('item_custom_field_values')) {
                    $response[$key]['custom_fields'] = [];
                    $response[$key]['translated_custom_fields'] = [];
                    $response[$key]['all_translated_custom_fields'] = [];

                    $contentLangCode = request()->header('Content-Language') ?? app()->getLocale();
                    $currentLanguage = \App\Models\Language::where('code', $contentLangCode)->first();
                    $defaultLangId = 1;

                    // Group custom field values by custom_field_id
                    $grouped = $collection->item_custom_field_values->groupBy('custom_field_id');

                foreach ($grouped as $customFieldId => $fieldValues) {
                    $default = $fieldValues->firstWhere('language_id', $defaultLangId);
                    $translated = $currentLanguage ? $fieldValues->firstWhere('language_id', $currentLanguage->id) : null;

                    // Always use default for `custom_fields`
                    if ($default && $default->relationLoaded('custom_field') && !empty($default->custom_field)) {
                        $tempRow = $default->custom_field->toArray();
                        $tempRow['value'] = $default->custom_field->type === "fileinput"
                            ? (!empty($default->value) ? [url(Storage::url($default->value))] : [])
                            : (is_array($default->value) ? $default->value : json_decode($default->value, true));

                        $tempRow['custom_field_value'] = $default->toArray();
                        unset($tempRow['custom_field_value']['custom_field']);

                         $tempRow['translated_selected_values'] = [];
                        if (!empty($tempRow['value']) && isset($tempRow['translated_value'])) {
                            foreach ($tempRow['value'] as $val) {
                                $index = array_search($val, $tempRow['values'] ?? []);
                                $translatedVal = $tempRow['translated_value'][$index] ?? $val;
                                $tempRow['translated_selected_values'][] = $translatedVal;
                            }
                        }
                        $response[$key]['custom_fields'][] = $tempRow;
                    }

                // Use translated (from Content-Language), else fallback to default
                        $activeField = $translated ?? $default;

                        if ($activeField && $activeField->relationLoaded('custom_field') && !empty($activeField->custom_field)) {
                            $tempRow = $activeField->custom_field->toArray();
                            $tempRow['value'] = $activeField->custom_field->type === "fileinput"
                                ? (!empty($activeField->value) ? [url(Storage::url($activeField->value))] : [])
                                : (is_array($activeField->value) ? $activeField->value : json_decode($activeField->value, true));

                            $tempRow['custom_field_value'] = $activeField->toArray();
                            unset($tempRow['custom_field_value']['custom_field']);
                             $tempRow['translated_selected_values'] = [];
                                if (!empty($tempRow['value']) && isset($tempRow['translated_value'])) {
                                    foreach ($tempRow['value'] as $val) {
                                        $index = array_search($val, $tempRow['values'] ?? []);
                                        $translatedVal = $tempRow['translated_value'][$index] ?? $val;
                                        $tempRow['translated_selected_values'][] = $translatedVal;
                                    }
                                }
                            $response[$key]['translated_custom_fields'][] = $tempRow;
                        }

                    // 💡 New: Include all translated versions of the field
                    foreach ($fieldValues as $fieldValue) {
                        if ($fieldValue->relationLoaded('custom_field') && !empty($fieldValue->custom_field)) {
                            $tempRow = $fieldValue->custom_field->toArray();
                            $tempRow['value'] = $fieldValue->custom_field->type === "fileinput"
                                ? (!empty($fieldValue->value) ? [url(Storage::url($fieldValue->value))] : [])
                                : (is_array($fieldValue->value) ? $fieldValue->value : json_decode($fieldValue->value, true));

                            $tempRow['custom_field_value'] = $fieldValue->toArray();
                            unset($tempRow['custom_field_value']['custom_field']);

                            // Add language_id so you know which language this belongs to
                            $tempRow['language_id'] = $fieldValue->language_id;
                              $tempRow['translated_selected_values'] = [];
                            if (!empty($tempRow['value']) && isset($tempRow['translated_value'])) {
                                foreach ($tempRow['value'] as $val) {
                                    $index = array_search($val, $tempRow['values'] ?? []);
                                    $translatedVal = $tempRow['translated_value'][$index] ?? $val;
                                    $tempRow['translated_selected_values'][] = $translatedVal;
                                }
                            }
                            $response[$key]['all_translated_custom_fields'][] = $tempRow;
                        }
                            }
                        }

                        unset($response[$key]['item_custom_field_values']);
                    }



                /*** Item Offers ***/
                if ($collection->relationLoaded('item_offers') && Auth::check()) {
                    $response[$key]['is_already_offered'] = $collection->item_offers->where('item_id', $collection->id)->where('buyer_id', Auth::user()->id)->count() > 0;
                } else {
                    $response[$key]['is_already_offered'] = false;
                }

                /*** User Reports ***/
                if ($collection->relationLoaded('user_reports') && Auth::check()) {
                    $response[$key]['is_already_reported'] = $collection->user_reports->where('user_id', Auth::user()->id)->count() > 0;
                } else {
                    $response[$key]['is_already_reported'] = false;
                }

                if (Auth::check()) {
                    $response[$key]['is_purchased'] = $collection->sold_to==Auth::user()->id ? 1 : 0;
                } else {
                    $response[$key]['is_purchased'] = 0;
                }
                if ($collection->relationLoaded('job_applications') && Auth::check()) {
                    $response[$key]['is_already_job_applied'] = $collection->job_applications->where('item_id', $collection->id)->where('user_id', Auth::user()->id)->count() > 0;
                } else {
                    $response[$key]['is_already_job_applied'] = false;
                }
            }
            $featuredRows = [];
            $normalRows = [];

            foreach ($response as $key => $value) {
                // ... (Your existing code here)
                // Extracting is_feature condition and processing accordingly
                if ($value['is_feature']) {
                    $featuredRows[] = $value;
                } else {
                    $normalRows[] = $value;
                }
            }


            // Merge the featured rows first and then the normal rows
            $response = array_merge($featuredRows, $normalRows);
            $totalCount = count($response);
            if ($this->resource instanceof AbstractPaginator) {
                //If the resource has a paginated collection then we need to copy the pagination related params and actual item details data will be copied to data params
                return [
                    ...$this->resource->toArray(),
                    'data' => $response,
                    'total_item_count' => $totalCount,
                ];
            }

            return $response;


        } catch (Throwable $th) {
            throw $th;
        }
    }
}
