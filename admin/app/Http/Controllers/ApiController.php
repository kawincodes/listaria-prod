<?php

namespace App\Http\Controllers;


use App\Http\Resources\ItemCollection;
use App\Models\Area;
use App\Models\BlockUser;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Chat;
use App\Models\City;
use App\Models\ContactUs;
use App\Models\Country;
use App\Models\CustomField;
use App\Models\Faq;
use App\Models\Favourite;
use App\Models\FeaturedItems;
use App\Models\FeatureSection;
use App\Models\Item;
use App\Models\ItemCustomFieldValue;
use App\Models\ItemImages;
use App\Models\ItemOffer;
use App\Models\JobApplication;
use App\Models\Language;
use App\Models\Notifications;
use App\Models\NumberOtp;
use App\Models\Package;
use App\Models\PaymentConfiguration;
use App\Models\PaymentTransaction;
use App\Models\ReportReason;
use App\Models\SellerRating;
use App\Models\SeoSetting;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\SocialLogin;
use App\Models\State;
use App\Models\Tip;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Models\UserPurchasedPackage;
use App\Models\UserReports;
use App\Models\VerificationField;
use App\Models\VerificationFieldRequest;
use App\Models\VerificationFieldValue;
use App\Models\VerificationRequest;
use App\Services\CachingService;
use App\Services\FileService;
use App\Services\HelperService;
use App\Services\NotificationService;
use App\Services\Payment\PaymentService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Unique;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Twilio\Rest\Client as TwilioRestClient;
use Illuminate\Support\Str;
use Throwable;

class ApiController extends Controller {

    private string $uploadFolder;

    public function __construct() {
        $this->uploadFolder = 'item_images';
        if (array_key_exists('HTTP_AUTHORIZATION', $_SERVER) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $this->middleware('auth:sanctum');
        }
    }

    public function getSystemSettings(Request $request) {
        try {
            $query = Setting::select(['id', 'name', 'value', 'type']); // include 'id' to support translation loading

            if (!empty($request->type)) {
                $query->where('name', $request->type);
            }

            $settings = $query->with('translations')->get();

            $tempRow = [];

            foreach ($settings as $row) {
                if (in_array($row->name, [
                    'account_holder_name',
                    'bank_name',
                    'account_number',
                    'ifsc_swift_code',
                    'bank_transfer_status',
                    'place_api_key'
                ])) {
                    continue;
                }
                $tempRow[$row->name] = $row->translated_value ?? $row->value;
            }


            // --- determine current language ---
            $languageCode = $request->header('Content-Language') ?? app()->getLocale();
            $language = Language::where('code', $languageCode)->first();


            if (!$language) {
                $defaultLanguageCode = Setting::where('name', "default_language")->value('value');
                $language = Language::where('code', $defaultLanguageCode)->first();
            }


            $tempRow['demo_mode'] = config('app.demo_mode');
            $tempRow['languages'] = CachingService::getLanguages();
            $tempRow['admin'] = User::role('Super Admin')->select(['name', 'profile'])->first();


            // 👇 add current language info
            $tempRow['current_language'] = $language?->code ?? app()->getLocale();


            ResponseService::successResponse(__('Data Fetched Successfully'), $tempRow);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getSystemSettings");
            ResponseService::errorResponse();
        }
    }


    public function userSignup(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'type'          => 'required|in:email,google,phone,apple',
                'firebase_id'   => 'required',
                'country_code'  => 'nullable|string',
                'flag'          => 'boolean',
                'platform_type' => 'nullable|in:android,ios'
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            $type = $request->type;
            $firebase_id = $request->firebase_id;
            $socialLogin = SocialLogin::where('firebase_id', $firebase_id)->where('type', $type)->with('user', function ($q) {
                $q->withTrashed();
            })->whereHas('user', function ($q) {
                $q->role('User');
            })->first();
            if (!empty($socialLogin->user->deleted_at)) {
                ResponseService::errorResponse(__('User is deactivated. Please Contact the administrator'));
            }
            if (empty($socialLogin)) {
                DB::beginTransaction();
                if ($request->type == "phone") {
                    $unique['mobile'] = $request->mobile;
                } else {
                    $unique['email'] = $request->email;
                }
                $existingUser = User::withTrashed()->where($unique)->first();

                if ($existingUser && $existingUser->trashed()) {
                    // DB::rollBack();
                    ResponseService::errorResponse(__('Your account has been deactivated.'), null, config('constants.RESPONSE_CODE.DEACTIVATED_ACCOUNT'));
                }
                $user = User::updateOrCreate([...$unique], [
                    ...$request->all(),
                    'profile' => $request->hasFile('profile') ? $request->file('profile')->store('user_profile', 'public') : $request->profile,
                ]);
                SocialLogin::updateOrCreate([
                    'type'    => $request->type,
                    'user_id' => $user->id
                ], [
                    'firebase_id' => $request->firebase_id,
                ]);
                $user->assignRole('User');
                Auth::login($user);
                $auth = User::find($user->id);
                DB::commit();
            } else {
                Auth::login($socialLogin->user);
                $auth = Auth::user();
            }
            if (!$auth->hasRole('User')) {
                ResponseService::errorResponse(__('Invalid Login Credentials'), null, config('constants.RESPONSE_CODE.INVALID_LOGIN'));
            }

            if (!empty($request->fcm_id)) {
//                UserFcmToken::insertOrIgnore(['user_id' => $auth->id, 'fcm_token' => $request->fcm_id, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
                UserFcmToken::updateOrCreate(['fcm_token' => $request->fcm_id], ['user_id' => $auth->id, 'platform_type' => $request->platform_type, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
            }

            if (!empty($request->registration)) {
                //If registration is passed then don't create token
                $token = null;
            } else {
                $token = $auth->createToken($auth->name ?? '')->plainTextToken;
            }

            ResponseService::successResponse(__('User logged-in successfully'), $auth, ['token' => $token]);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, "API Controller -> Signup");
            ResponseService::errorResponse();
        }
    }

    public function updateProfile(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'name'                  => 'nullable|string',
                'profile'               => 'nullable|mimes:jpg,jpeg,png|max:7168',
                'email'                 => 'nullable|email|unique:users,email,' . Auth::user()->id,
                'mobile'                => 'nullable|unique:users,mobile,' . Auth::user()->id,
                'fcm_id'                => 'nullable',
                'address'               => 'nullable',
                'show_personal_details' => 'boolean',
                'country_code'          => 'nullable|string'
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            $app_user = Auth::user();
            //Email should not be updated when type is google.
            $data = $app_user->type == "google" ? $request->except('email') : $request->all();

            if ($request->hasFile('profile')) {
                $data['profile'] = FileService::compressAndReplace($request->file('profile'), 'profile', $app_user->getRawOriginal('profile'));
            }

            if (!empty($request->fcm_id)) {
                UserFcmToken::updateOrCreate(['fcm_token' => $request->fcm_id], ['user_id' => $app_user->id, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
            }
            $data['show_personal_details'] = $request->show_personal_details;

            $app_user->update($data);
            ResponseService::successResponse(__('Profile Updated Successfully'), $app_user);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> updateProfile');
            ResponseService::errorResponse();
        }
    }

    public function getPackage(Request $request) {
        $validator = Validator::make($request->toArray(), [
            'platform' => 'nullable|in:android,ios',
            'type'     => 'nullable|in:advertisement,item_listing'
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $packages = Package::with('translations')->where('status', 1);

            if (Auth::check()) {
                $packages = $packages->with('user_purchased_packages', function ($q) {
                    $q->onlyActive();
                });
            }

            if (isset($request->platform) && $request->platform == "ios") {
                $packages->whereNotNull('ios_product_id');
            }

            if (!empty($request->type)) {
                $packages = $packages->where('type', $request->type);
            }
            $packages = $packages->orderBy('id', 'ASC')->get();

            $packages->map(function ($package) {
                if (Auth::check()) {
                    $package['is_active'] = count($package->user_purchased_packages) > 0;
                } else {
                    $package['is_active'] = false;
                }
                return $package;
            });
            ResponseService::successResponse(__('Data Fetched Successfully'), $packages);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getPackage");
            ResponseService::errorResponse();
        }
    }

    public function assignFreePackage(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'package_id' => 'required|exists:packages,id',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();

            $package = Package::where(['final_price' => 0, 'id' => $request->package_id])->firstOrFail();
            $activePackage = UserPurchasedPackage::where(['package_id' => $request->package_id, 'user_id' => Auth::user()->id])->first();
            if (!empty($activePackage)) {
                ResponseService::errorResponse(__('You already have purchased this package'));
            }

            UserPurchasedPackage::create([
                'user_id'     => $user->id,
                'package_id'  => $request->package_id,
                'start_date'  => Carbon::now(),
                'total_limit' => $package->item_limit == "unlimited" ? null : $package->item_limit,
                'end_date'    => $package->duration == "unlimited" ? null : Carbon::now()->addDays($package->duration)
            ]);
            ResponseService::successResponse(__('Package Purchased Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> assignFreePackage");
            ResponseService::errorResponse();
        }
    }

    public function getLimits(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'package_type' => 'required|in:item_listing,advertisement',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $setting = Setting::where('name', "free_ad_listing")->first()['value'];
            if ($setting == 1) {
                return ResponseService::successResponse(__('User is allowed to create Advertisement'));
            }
            $user_package = UserPurchasedPackage::onlyActive()->whereHas('package', function ($q) use ($request) {
                $q->where('type', $request->package_type);
            })->count();
            if ($user_package > 0) {
                ResponseService::successResponse(__('User is allowed to create Advertisement'));
            }
            ResponseService::errorResponse(__('User is not allowed to create Advertisement'), $user_package);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getLimits");
            ResponseService::errorResponse();
        }
    }

    public function addItem(Request $request) {
                // dd($request->toArray());
        try {
            $validator = Validator::make($request->all(), [
                'name'                 => 'required',
                'category_id'          => 'required|integer',
                'description'          => 'required',
                'latitude'             => 'required',
                'longitude'            => 'required',
                'address'              => 'required',
                'contact'              => 'numeric',
                'show_only_to_premium' => 'required|boolean',
                'video_link'           => 'nullable|url',
                'gallery_images'       => 'nullable|array|min:1',
                'gallery_images.*'     => 'nullable|mimes:jpeg,png,jpg|max:7168',
                'image'                => 'required|mimes:jpeg,png,jpg|max:7168',
                'country'              => 'required',
                'state'                => 'nullable',
                'city'                 => 'required',
                'custom_field_files'   => 'nullable|array',
                'custom_field_files.*' => 'nullable|mimes:jpeg,png,jpg,pdf,doc|max:7168',
                'slug'                 => 'nullable|regex:/^[a-z0-9-]+$/',
            ]);
           $translations = json_decode($request->input('translations', '{}'), true, 512, JSON_THROW_ON_ERROR);
                if (!empty($translations)) {
                foreach ($translations as $languageId => $translation) {
                    Validator::make($translation, [
                        'name'               => 'required|string|max:255',
                        'slug'               => 'nullable|regex:/^[a-z0-9-]+$/',
                        'description'        => 'nullable|string',
                        'address'            => 'nullable|string',
                        'video_link'         => 'nullable|url',
                        'rejected_reason'    => 'nullable|string',
                        'admin_edit_reason'  => 'nullable|string',
                        'city'  => 'nullable|string',
                        'country'  => 'nullable|string',
                        'state'  => 'nullable|string',
                    ])->validate();
                }
                }
            $category = Category::findOrFail($request->category_id);

            $isJobCategory = $category->is_job_category;
             $isPriceOptional = $category->price_optional;

            if ($isJobCategory || $isPriceOptional) {
                $validator = Validator::make($request->all(), [
                    'min_salary' => 'nullable|numeric|min:0',
                    'max_salary' => 'nullable|numeric|gte:min_salary',
                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    'price' => 'required|numeric|min:0',
                ]);
            }
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            DB::beginTransaction();
            $user = Auth::user();
            $user_package = UserPurchasedPackage::onlyActive()->whereHas('package', static function ($q) {
                $q->where('type', 'item_listing');
            })->where('user_id', $user->id)->first();
            $free_ad_listing = Setting::where('name', "free_ad_listing")->value('value') ?? 0;
            $auto_approve_item = Setting::where('name', "auto_approve_item")->value('value') ?? 0;
            if($auto_approve_item == 1 || $user->auto_approve_item == 1){
                $status = "approved";
            }else{
                $status = "review";
            }
            if ($free_ad_listing == 0 && empty($user_package)) {
                ResponseService::errorResponse(__('No Active Package found for Advertisement Creation'));
            }
            if($user_package){
                ++$user_package->used_limit;
                $user_package->save();
            }

            $slug = $request->input('slug');
            if (empty($slug)) {
                $slug = HelperService::generateRandomSlug();
            }
            $uniqueSlug = HelperService::generateUniqueSlug(new Item(), $slug);

            $data = [
                ...$request->all(),
                'name'        => $request->name,
                'slug'        => $uniqueSlug,
                'status'      => $status,
                'active'      => "deactive",
                'user_id'     => $user->id,
                'package_id'  => $user_package->package_id ?? '',
                'expiry_date' => $user_package->end_date ?? null
            ];
            if ($request->hasFile('image')) {
                $data['image'] = FileService::compressAndUpload($request->file('image'), $this->uploadFolder);
            }
            $item = Item::create($data);
            if (!empty($translations)) {
                foreach ($translations as $languageId => $translationData) {
                    // Optional: Check if language ID exists
                    if (Language::where('id', $languageId)->exists()) {
                        $item->translations()->create([
                            'language_id'        => $languageId,
                            'name'               => $translationData['name'],
                            'description'        => $translationData['description'] ?? '',
                            'address'            => $translationData['address'] ?? '',
                            'rejected_reason'    => $translationData['rejected_reason'] ?? null,
                            'admin_edit_reason'  => $translationData['admin_edit_reason'] ?? null,
                            'city'  => $translationData['city'] ?? '',
                            'state'  => $translationData['state'] ?? '',
                            'country'  => $translationData['country'] ?? '',
                        ]);
                    }
                }
            }

            if ($request->hasFile('gallery_images')) {
                $galleryImages = [];
                foreach ($request->file('gallery_images') as $file) {
                    $galleryImages[] = [
                        'image'      => FileService::compressAndUpload($file, $this->uploadFolder),
                        'item_id'    => $item->id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ];
                }

                if (count($galleryImages) > 0) {
                    ItemImages::insert($galleryImages);
                }
            }
            if ($request->custom_fields) {
                $itemCustomFieldValues = [];
                foreach (json_decode($request->custom_fields, true, 512, JSON_THROW_ON_ERROR) as $key => $custom_field) {
                    $itemCustomFieldValues[] = [
                        'item_id'         => $item->id,
                        'language_id'     => 1,
                        'custom_field_id' => $key,
                        'value'           => json_encode($custom_field, JSON_THROW_ON_ERROR),
                        'created_at'      => time(),
                        'updated_at'      => time()
                    ];
                }

                if (count($itemCustomFieldValues) > 0) {
                    ItemCustomFieldValue::insert($itemCustomFieldValues);
                }
            }

            if ($request->custom_field_files) {
                $itemCustomFieldValues = [];
                foreach ($request->custom_field_files as $key => $file) {
                    $itemCustomFieldValues[] = [
                        'item_id'         => $item->id,
                        'language_id'     => 1,
                        'custom_field_id' => $key,
                        'value'           => !empty($file) ? FileService::upload($file, 'custom_fields_files') : '',
                        'created_at'      => time(),
                        'updated_at'      => time()
                    ];
                }

                if (count($itemCustomFieldValues) > 0) {
                    ItemCustomFieldValue::insert($itemCustomFieldValues);
                }
            }
            // Handle Translated Custom Field Values
             if ($request->has('custom_field_translations')) {
                    $customFieldTranslations = json_decode($request->input('custom_field_translations'), true, 512, JSON_THROW_ON_ERROR);
                    $translatedEntries = [];

                    foreach ($customFieldTranslations as $languageId => $fieldsByCustomField) {
                        foreach ($fieldsByCustomField as $customFieldId => $translatedValue) {
                            $translatedEntries[] = [
                                'item_id'         => $item->id,
                                'custom_field_id' => $customFieldId,
                                'language_id'     => $languageId,
                                'value'           => json_encode($translatedValue, JSON_THROW_ON_ERROR),
                                'created_at'      => now(),
                                'updated_at'      => now(),
                            ];
                        }
                    }

                    if (!empty($translatedEntries)) {
                        ItemCustomFieldValue::insert($translatedEntries);
                    }
                }


            // Add where condition here
           $result = Item::with(
                'user:id,name,email,mobile,profile,country_code',
                'category:id,name,image,is_job_category,price_optional',
                'gallery_images:id,image,item_id',
                'featured_items',
                'favourites',
                'item_custom_field_values.custom_field.translations',
                'area',
                'translations'
            )->where('id', $item->id)->get();
            $result = new ItemCollection($result);

            DB::commit();
            ResponseService::successResponse(__('Advertisement Added Successfully'), $result);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, "API Controller -> addItem");
            ResponseService::errorResponse();
        }
    }

    public function getItem(Request $request) {
        $validator = Validator::make($request->all(), [
            'limit'         => 'nullable|integer',
            'offset'        => 'nullable|integer',
            'id'            => 'nullable',
            'custom_fields' => 'nullable',
            'slug'          => 'nullable|string',
            'category_id'   => 'nullable',
            'user_id'       => 'nullable',
            'min_price'     => 'nullable',
            'max_price'     => 'nullable',
            'sort_by'       => 'nullable|in:new-to-old,old-to-new,price-high-to-low,price-low-to-high,popular_items',
            'posted_since'  => 'nullable|in:all-time,today,within-1-week,within-2-week,within-1-month,within-3-month'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            //TODO : need to simplify this whole module
             $sql = Item::with('user:id,name,email,mobile,profile,created_at,is_verified,show_personal_details,country_code', 'category:id,name,image,is_job_category,price_optional',
             'gallery_images:id,image,item_id', 'featured_items', 'favourites', 'item_custom_field_values.custom_field.translations','area:id,name','job_applications','translations')
                ->withCount('featured_items')
                ->withCount('job_applications')
                ->select('items.*')
                ->when($request->id, function ($sql) use ($request) {
                    $sql->where('id', $request->id);
                })->when(($request->category_id), function ($sql) use ($request) {
                    $category = Category::where('id', $request->category_id)->with('children')->get();
                    $categoryIDS = HelperService::findAllCategoryIds($category);
                    return $sql->whereIn('category_id', $categoryIDS);
                })->when(($request->category_slug), function ($sql) use ($request) {
                    $category = Category::where('slug', $request->category_slug)->with('children')->get();
                    $categoryIDS = HelperService::findAllCategoryIds($category);
                    return $sql->whereIn('category_id', $categoryIDS);
                })->when((isset($request->min_price) || isset($request->max_price)), function ($sql) use ($request) {
                    $min_price = $request->min_price ?? 0;
                    $max_price = $request->max_price ?? Item::max('price');
                    return $sql->whereBetween('price', [$min_price, $max_price]);
                })->when($request->posted_since, function ($sql) use ($request) {
                    return match ($request->posted_since) {
                        "today" => $sql->whereDate('created_at', '>=', now()),
                        "within-1-week" => $sql->whereDate('created_at', '>=', now()->subDays(7)),
                        "within-2-week" => $sql->whereDate('created_at', '>=', now()->subDays(14)),
                        "within-1-month" => $sql->whereDate('created_at', '>=', now()->subMonths()),
                        "within-3-month" => $sql->whereDate('created_at', '>=', now()->subMonths(3)),
                        default => $sql
                    };
                })->when($request->country, function ($sql) use ($request) {
                    return $sql->where('country', $request->country);
                })->when($request->state, function ($sql) use ($request) {
                    return $sql->where('state', $request->state);
                })->when($request->city, function ($sql) use ($request) {
                    return $sql->where('city', $request->city);
                })->when($request->area_id, function ($sql) use ($request) {
                    return $sql->where('area_id', $request->area_id);
                })->when($request->user_id, function ($sql) use ($request) {
                    return $sql->where('user_id', $request->user_id);
                })->when($request->slug, function ($sql) use ($request) {
                    return $sql->where('slug', $request->slug);
                })->when($request->latitude && $request->longitude && $request->radius, function ($sql) use ($request) {
                    $latitude = $request->latitude;
                    $longitude = $request->longitude;
                    $radius = $request->radius;

                    // Calculate distance using Haversine formula
                    $haversine = "(6371 * acos(cos(radians($latitude))
                                    * cos(radians(latitude))
                                    * cos(radians(longitude)
                                    - radians($longitude))
                                    + sin(radians($latitude))
                                    * sin(radians(latitude))))";

                    $sql->select('items.*')
                        ->selectRaw("{$haversine} AS distance")
                        ->where('latitude', '!=', 0)
                        ->where('longitude', '!=', 0)
                        ->having('distance', '<', $radius)
                        ->orderBy('distance', 'asc');
                });


            //            // Other users should only get approved items
            //            if (!Auth::check()) {
            //                $sql->where('status', 'approved');
            //            }

            // Sort By

            if ($request->sort_by == "new-to-old") {
                $sql->orderBy('id', 'DESC');
            } elseif ($request->sort_by == "old-to-new") {
                $sql->orderBy('id', 'ASC');
            } elseif ($request->sort_by == "price-high-to-low") {
                $sql->orderBy('price', 'DESC');
            } elseif ($request->sort_by == "price-low-to-high") {
                $sql->orderBy('price', 'ASC');
            } elseif ($request->sort_by == "popular_items") {
                $sql->orderBy('clicks', 'DESC');
            } else {
                $sql->orderBy('id', 'DESC');
            }

            // Status
            if (!empty($request->status)) {
                if (in_array($request->status, array('review', 'approved', 'rejected', 'sold out', 'soft rejected', 'permanent rejected', 'resubmitted'))) {
                    $sql->where('status', $request->status)->getNonExpiredItems()->whereNull('deleted_at');
                } elseif ($request->status == 'inactive') {
                    //If status is inactive then display only trashed items
                    $sql->onlyTrashed()->getNonExpiredItems();
                } elseif ($request->status == 'featured') {
                    //If status is featured then display only featured items
                    $sql->where('status', 'approved')->has('featured_items')->getNonExpiredItems();
                } elseif ($request->status == 'expired') {
                    $sql->whereNotNull('expiry_date')
                          ->where('expiry_date', '<', Carbon::now())->whereNull('deleted_at');
                }
            }

            // Feature Section Filtration
            if (!empty($request->featured_section_id) || !empty($request->featured_section_slug)) {
                if (!empty($request->featured_section_id)) {
                    $featuredSection = FeatureSection::findOrFail($request->featured_section_id);
                } else {
                    $featuredSection = FeatureSection::where('slug', $request->featured_section_slug)->firstOrFail();
                }
                $sql = match ($featuredSection->filter) {
                    /*Note : Reorder function is used to clear out the previously applied order by statement*/
                    "price_criteria" => $sql->whereBetween('price', [$featuredSection->min_price, $featuredSection->max_price]),
                    "most_viewed" => $sql->reorder()->orderBy('clicks', 'DESC'),
                    "category_criteria" => (static function () use ($featuredSection, $sql) {
                        $category = Category::whereIn('id', explode(',', $featuredSection->value))->with('children')->get();
                        $categoryIDS = HelperService::findAllCategoryIds($category);
                        return $sql->whereIn('category_id', $categoryIDS);
                    })(),

                    //Added withCount here 2nd time because of some wierd issue
                    "most_liked" => $sql->reorder()->withCount('favourites')->orderBy('favourites_count', 'DESC'),
                };
            }


            if (!empty($request->search)) {
                $sql->search($request->search);
            }

            function removeBackslashesRecursive($data)
            {
                $cleaned = [];
                foreach ($data as $key => $value) {
                    $cleanKey = stripslashes($key);
                    if (is_array($value)) {
                        $cleaned[$cleanKey] = removeBackslashesRecursive($value);
                    } else {
                        $cleaned[$cleanKey] = stripslashes($value);
                    }
                }
                return $cleaned;
            }
            $cleanedParameters = removeBackslashesRecursive($request->all());
            if (!empty($cleanedParameters['custom_fields'])) {
                $customFields = $cleanedParameters['custom_fields'];
                foreach ($customFields as $customFieldId => $value) {
                    if (is_array($value)) {
                        foreach ($value as $arrayValue) {
                            $sql->join('item_custom_field_values as cf' . $customFieldId, function ($join) use ($customFieldId) {
                                    $join->on('items.id', '=', 'cf' . $customFieldId . '.item_id');
                                })
                                ->where('cf' . $customFieldId . '.custom_field_id', $customFieldId)
                                ->where('cf' . $customFieldId . '.value', 'LIKE', '%' . trim($arrayValue) . '%');
                        }
                    } else {
                        $sql->join('item_custom_field_values as cf' . $customFieldId, function ($join) use ($customFieldId) {
                                $join->on('items.id', '=', 'cf' . $customFieldId . '.item_id');
                            })
                            ->where('cf' . $customFieldId . '.custom_field_id', $customFieldId)
                            ->where('cf' . $customFieldId . '.value', 'LIKE', '%' . trim($value) . '%');
                    }
                }
                $sql->whereHas('item_custom_field_values', function ($query) use ($customFields) {
                    $query->whereIn('custom_field_id', array_keys($customFields));
                }, '=', count($customFields));
            }


            if (Auth::check()) {
                $sql->with(['item_offers' => function ($q) {
                    $q->where('buyer_id', Auth::user()->id);
                }, 'user_reports'         => function ($q) {
                    $q->where('user_id', Auth::user()->id);
                }]);

                $currentURI = explode('?', $request->getRequestUri(), 2);

                if ($currentURI[0] == "/api/my-items") { //TODO: This if condition is temporary fix. Need something better
                    $sql->where(['user_id' => Auth::user()->id])->withTrashed();
                } else {
                    $sql->where('status', 'approved')->has('user')->onlyNonBlockedUsers()->getNonExpiredItems();
                }
            } else {
                //  Other users should only get approved items
                $sql->where('status', 'approved')->getNonExpiredItems();
            }

            if (!empty($request->id)) {
                /*
                 * Collection does not support first OR find method's result as of now. It's a part of R&D
                 * So currently using this shortcut method get() to fetch the first data
                 */
                $result = $sql->get();
                if (count($result) == 0) {
                    ResponseService::errorResponse(__('No item Found'));
                }
            } else {
                if (!empty($request->limit)) {
                    $result = $sql->paginate($request->limit);
                } else {
                    $result = $sql->paginate();
                }

            }
            //                // Add three regular items
            //                for ($i = 0; $i < 3 && $regularIndex < $regularItemCount; $i++) {
            //                    $items->push($regularItems[$regularIndex]);
            //                    $regularIndex++;
            //                }
            //
            //                // Add one featured item if available
            //                if ($featuredIndex < $featuredItemCount) {
            //                    $items->push($featuredItems[$featuredIndex]);
            //                    $featuredIndex++;
            //                }
            //            }
            // Return success response with the fetched items

            ResponseService::successResponse(__('Advertisement Fetched Successfully'), new ItemCollection($result));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getItem");
            ResponseService::errorResponse();
        }
    }

    public function updateItem(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'                   => 'required',
            'name'                 => 'nullable',
            'slug'                 => 'regex:/^[a-z0-9-]+$/',
            'price'                => 'nullable',
            'description'          => 'nullable',
            'latitude'             => 'nullable',
            'longitude'            => 'nullable',
            'address'              => 'nullable',
            'contact'              => 'nullable',
            'image'                => 'nullable|mimes:jpeg,jpg,png|max:7168',
            'custom_fields'        => 'nullable',
            'custom_field_files'   => 'nullable|array',
            'custom_field_files.*' => 'nullable|mimes:jpeg,png,jpg,pdf,doc|max:7168',
            'gallery_images'       => 'nullable|array',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        DB::beginTransaction();

        try {

            $item = Item::owner()->findOrFail($request->id);
            $auto_approve_item = Setting::where('name', "auto_approve_edited_item")->value('value') ?? 0;
            if($auto_approve_item == 1){
                $status = "approved";
            }else{
                $status = "review";
            }

            $slug = $request->input('slug', $item->slug);
            $uniqueSlug = HelperService::generateUniqueSlug(new Item(), $slug,$request->id);

            $data = $request->all();
            $data['slug'] = $uniqueSlug;
            $data['status'] = $status;
            if ($request->hasFile('image')) {
                $data['image'] = FileService::compressAndReplace($request->file('image'), $this->uploadFolder, $item->getRawOriginal('image'));
            }

            $item->update($data);
            // Update or create item translations
            $translations = json_decode($request->input('translations', '{}'), true, 512, JSON_THROW_ON_ERROR);
            if (!empty($translations)) {
                foreach ($translations as $languageId => $translationData) {
                    if (Language::where('id', $languageId)->exists()) {
                        $item->translations()->updateOrCreate(
                            ['language_id' => $languageId],
                            [
                                'name'               => $translationData['name'],
                                'description'        => $translationData['description'] ?? '',
                                'address'            => $translationData['address'] ?? '',
                                'rejected_reason'    => $translationData['rejected_reason'] ?? null,
                                'admin_edit_reason'  => $translationData['admin_edit_reason'] ?? null,
                                'city'               => $translationData['city'] ?? '',
                                'state'              => $translationData['state'] ?? '',
                                'country'            => $translationData['country'] ?? '',
                            ]
                        );
                    }
                }
            }

            //Update Custom Field values for item
            if ($request->custom_fields) {
                $itemCustomFieldValues = [];
                foreach (json_decode($request->custom_fields, true, 512, JSON_THROW_ON_ERROR) as $key => $custom_field) {
                    $itemCustomFieldValues[] = [
                        'item_id'         => $item->id,
                        'custom_field_id' => $key,
                        'value'           => json_encode($custom_field, JSON_THROW_ON_ERROR),
                        'updated_at'      => time()
                    ];
                }

                if (count($itemCustomFieldValues) > 0) {
                    ItemCustomFieldValue::upsert($itemCustomFieldValues, ['item_id', 'custom_field_id'], ['value', 'updated_at']);
                }
            }

            //Add new gallery images
            if ($request->hasFile('gallery_images')) {
                $galleryImages = [];
                foreach ($request->file('gallery_images') as $file) {
                    $galleryImages[] = [
                        'image'      => FileService::compressAndUpload($file, $this->uploadFolder),
                        'item_id'    => $item->id,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ];
                }
                if (count($galleryImages) > 0) {
                    ItemImages::insert($galleryImages);
                }
            }

            if ($request->custom_field_files) {
                $itemCustomFieldValues = [];
                foreach ($request->custom_field_files as $key => $file) {
                    $value = ItemCustomFieldValue::where(['item_id' => $item->id, 'custom_field_id' => $key])->first();
                    if (!empty($value)) {
                        $file = FileService::replace($file, 'custom_fields_files', $value->getRawOriginal('value'));
                    } else {
                        $file = '';
                    }

                    $itemCustomFieldValues[] = [
                        'item_id'         => $item->id,
                        'language_id'         => 1,
                        'custom_field_id' => $key,
                        'value'           => $file,
                        'updated_at'      => time()
                    ];
                }

                if (count($itemCustomFieldValues) > 0) {
                    ItemCustomFieldValue::updateOrCreate(
                        ['item_id' => $item->id, 'custom_field_id' => $key],
                        ['value' => $file,'language_id' => 1, 'updated_at' => time()]
                    );
                }
            }
                // Update or insert custom field translations
            if ($request->has('custom_field_translations')) {
                $customFieldTranslations = json_decode($request->input('custom_field_translations'), true, 512, JSON_THROW_ON_ERROR);
                $translatedEntries = [];

                foreach ($customFieldTranslations as $languageId => $fieldsByCustomField) {
                    foreach ($fieldsByCustomField as $customFieldId => $translatedValue) {
                        $translatedEntries[] = [
                            'item_id'         => $item->id,
                            'custom_field_id' => $customFieldId,
                            'language_id'     => $languageId,
                            'value'           => json_encode($translatedValue, JSON_THROW_ON_ERROR),
                            'updated_at'      => now(),
                            'created_at'      => now(),
                        ];
                    }
                }

                if (!empty($translatedEntries)) {
                    // Ensure combination is unique
                    ItemCustomFieldValue::upsert(
                        $translatedEntries,
                        ['item_id', 'custom_field_id', 'language_id'], // unique keys
                        ['value', 'updated_at']
                    );
                }
            }

            //Delete gallery images
            if (!empty($request->delete_item_image_id)) {
                $item_ids = explode(',', $request->delete_item_image_id);
                foreach (ItemImages::whereIn('id', $item_ids)->get() as $itemImage) {
                    FileService::delete($itemImage->getRawOriginal('image'));
                    $itemImage->delete();
                }
            }


            $result = Item::with('user:id,name,email,mobile,profile,country_code', 'category:id,name,image,is_job_category,price_optional', 'gallery_images:id,image,item_id', 'featured_items', 'favourites', 'item_custom_field_values.custom_field.translations', 'area','translations')->where('id', $item->id)->get();
          /*
             * Collection does not support first OR find method's result as of now. It's a part of R&D
             * So currently using this shortcut method
            */
            $result = new ItemCollection($result);


            DB::commit();
            ResponseService::successResponse(__('Advertisement Fetched Successfully'), $result);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, "API Controller -> updateItem");
            ResponseService::errorResponse();
        }
    }

    public function deleteItem(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);
            if ($validator->fails()) {
                ResponseService::errorResponse($validator->errors()->first());
            }
            $item = Item::owner()->with('gallery_images')->withTrashed()->findOrFail($request->id);
            FileService::delete($item->getRawOriginal('image'));

            if (count($item->gallery_images) > 0) {
                foreach ($item->gallery_images as $key => $value) {
                    FileService::delete($value->getRawOriginal('image'));
                }
            }

            $item->forceDelete();
            ResponseService::successResponse(__('Advertisement Deleted Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> deleteItem");
            ResponseService::errorResponse();
        }
    }

    public function updateItemStatus(Request $request) {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer',
            'status'  => 'required|in:sold out,inactive,active,resubmitted',
            // 'sold_to' => 'required_if:status,==,sold out|integer'
            'sold_to' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $item = Item::owner()->whereNotIn('status', ['review', 'permanent rejected'])->withTrashed()->findOrFail($request->item_id);
            if ($item->status == 'permanent rejected' && $request->status == 'resubmitted') {
                ResponseService::errorResponse(__('This Advertisement is permanently rejected and cannot be resubmitted'));
            }
            if ($request->status == "inactive") {
                $item->delete();
            } else if ($request->status == "active") {
                $item->restore();
                $item->update(['status' => 'review']);
            } else if ($request->status == "sold out") {
                $item->update([
                    'status'  => 'sold out',
                    'sold_to' => $request->sold_to
                ]);
            } else {
                $item->update(['status' => $request->status]);
            }
            ResponseService::successResponse(__('Advertisement Status Updated Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemController -> updateItemStatus');
            ResponseService::errorResponse(__('Something Went Wrong'));
        }
    }

    public function getItemBuyerList(Request $request) {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $buyer_ids = ItemOffer::where('item_id', $request->item_id)->select('buyer_id')->pluck('buyer_id');
            $users = User::select(['id', 'name', 'profile'])->whereIn('id', $buyer_ids)->get();
            ResponseService::successResponse(__('Buyer List fetched Successfully'), $users);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemController -> updateItemStatus');
            ResponseService::errorResponse(__('Something Went Wrong'));
        }
    }

    public function getSubCategories(Request $request) {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $sql = Category::withCount(['subcategories' => function ($q) {
                $q->where('status', 1);
            }])->with('translations')->where(['status' => 1])->orderBy('sequence', 'ASC')
                ->with(['subcategories'          => function ($query) {
                    $query->where('status', 1)->orderBy('sequence', 'ASC')->with('translations')->withCount(['approved_items', 'subcategories' => function ($q) {
                        $q->where('status', 1);
                    }]); // Order subcategories by 'sequence'
                },
                 'subcategories.subcategories' => function ($query) {
                    $query->where('status', 1)->orderBy('sequence', 'ASC')->with('translations')->withCount(['approved_items', 'subcategories' => function ($q) {
                        $q->where('status', 1);
                    }]);
                },
                'subcategories.subcategories.subcategories' => function ($query) {
                    $query->where('status', 1)->orderBy('sequence', 'ASC')->with('translations')->withCount(['approved_items', 'subcategories' => function ($q) {
                        $q->where('status', 1);
                    }]);
                },'subcategories.subcategories.subcategories.subcategories' => function ($query) {
                    $query->where('status', 1)->orderBy('sequence', 'ASC')->with('translations')->withCount(['approved_items', 'subcategories' => function ($q) {
                        $q->where('status', 1);
                    }]);
                },'subcategories.subcategories.subcategories.subcategories.subcategories' => function ($query) {
                    $query->where('status', 1)->orderBy('sequence', 'ASC')->with('translations')->withCount(['approved_items', 'subcategories' => function ($q) {
                        $q->where('status', 1);
                    }]);
                }
            ]);
            if (!empty($request->category_id)) {
                $sql = $sql->where('parent_category_id', $request->category_id);
            } else if (!empty($request->slug)) {
                $parentCategory = Category::where('slug', $request->slug)->firstOrFail();
                $sql = $sql->where('parent_category_id', $parentCategory->id);
            } else {
                $sql = $sql->whereNull('parent_category_id');
            }

            $sql = $sql->paginate();
            $sql->map(function ($category) {
                $category->all_items_count = $category->all_items_count;
                return $category;
            });
            ResponseService::successResponse(null, $sql, ['self_category' => $parentCategory ?? null]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategories');
            ResponseService::errorResponse();
        }
    }

    public function getParentCategoryTree(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_category_id' => 'nullable|integer',
            'tree'              => 'nullable|boolean',
            'slug'              => 'nullable|string'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $sql = Category::with('translations')->when($request->child_category_id, function ($sql) use ($request) {
                $sql->where('id', $request->child_category_id);
            })
                ->when($request->slug, function ($sql) use ($request) {
                    $sql->where('slug', $request->slug);
                })
                ->firstOrFail()
                ->ancestorsAndSelf()->breadthFirst()->get();
            if ($request->tree) {
                $sql = $sql->toTree();
            }
            ResponseService::successResponse(null, $sql);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategories');
            ResponseService::errorResponse();
        }
    }

    public function getNotificationList() {
        try {
            $notifications = Notifications::with('item') // 👈 add relation here
                ->whereRaw("FIND_IN_SET(" . Auth::user()->id . ",user_id)")
                ->orWhere('send_to', 'all')
                ->orderBy('id', 'DESC')
                ->paginate();

            ResponseService::successResponse(__('Notification fetched successfully'), $notifications);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getNotificationList');
            ResponseService::errorResponse();
        }
    }

    public function getLanguages(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'language_code' => 'required',
                'type'          => 'nullable|in:app,web'
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $language = Language::where('code', $request->language_code)->firstOrFail();
            if ($request->type == "web") {
                $json_file_path = base_path('resources/lang/' . $request->language_code . '_web.json');
            } else {
                $json_file_path = base_path('resources/lang/' . $request->language_code . '_app.json');
            }

            if (!is_file($json_file_path)) {
                ResponseService::errorResponse(__('Language file not found'));
            }

            $json_string = file_get_contents($json_file_path);
            $json_data = json_decode($json_string, false, 512, JSON_THROW_ON_ERROR);

            if ($json_data == null) {
                ResponseService::errorResponse(__('Invalid JSON format in the language file'));
            }
            $language->file_name = $json_data;

            ResponseService::successResponse(__('Data Fetched Successfully'), $language);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getLanguages");
            ResponseService::errorResponse();
        }
    }

    public function appPaymentStatus(Request $request) {
        try {
            $paypalInfo = $request->all();
            if (!empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == "completed") {
                ResponseService::successResponse(__('Your Package will be activated within 10 Minutes'), $paypalInfo['txn_id']);
            } elseif (!empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == "authorized") {
                ResponseService::successResponse(__('Your Transaction is Completed. Ads wil be credited to your account within 30 minutes.'), $paypalInfo);
            } else {
                ResponseService::errorResponse(__('Payment Cancelled / Declined'), (isset($_GET)) ? $paypalInfo : "");
            }
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> appPaymentStatus");
            ResponseService::errorResponse();
        }
    }

    public function getPaymentSettings() {
        try {
            $result = PaymentConfiguration::select(['currency_code', 'payment_method', 'api_key', 'status'])->where('status', 1)->get();
            $response = [];
            foreach ($result as $payment) {
                $response[$payment->payment_method] = $payment->toArray();
            }
            $settings = Setting::whereIn('name', [
                'account_holder_name',
                'bank_name',
                'account_number',
                'ifsc_swift_code',
                'bank_transfer_status'
            ])->get();

            $bankDetails = [];
            foreach ($settings as $row) {
                $key = ($row->name === 'bank_transfer_status') ? 'status' : $row->name;
                $bankDetails[$key] = $row->value;
            }
            $response['bankTransfer'] = $bankDetails;
            ResponseService::successResponse(__('Data Fetched Successfully'), $response);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getPaymentSettings");
            ResponseService::errorResponse();
        }
    }

    public function getCustomFields(Request $request) {
        try {
            $customField = CustomField::with('translations')
                ->whereHas('custom_field_category', function ($q) use ($request) {
                    $q->whereIn('category_id', explode(',', $request->input('category_ids')));
                })
                ->where('status', 1)
                ->get()
                ->each(function ($field) {
                $field->translated_name;
                $field->translated_value;
            });
            ResponseService::successResponse(__('Data Fetched successfully'), $customField);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getCustomFields");
            ResponseService::errorResponse();
        }
    }

    public function makeFeaturedItem(Request $request) {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::commit();
            $user = Auth::user();
            Item::where('status', 'approved')->findOrFail($request->item_id);
            $user_package = UserPurchasedPackage::onlyActive()
                ->where(['user_id' => $user->id])
                ->with('package')
                ->whereHas('package', function ($q) {
                    $q->where(['type' => 'advertisement']);
                })
                ->first();

            if (!$user_package) {
                return ResponseService::errorResponse(__('You need to purchase a Featured Ad plan first.'));
            }
            $featuredItems = FeaturedItems::where(['item_id' => $request->item_id, 'package_id' => $user_package->package_id])->first();
            if (!empty($featuredItems)) {
                ResponseService::errorResponse(__('Advertisement is already featured'));
            }

            ++$user_package->used_limit;
            $user_package->save();

            FeaturedItems::create([
                'item_id'                   => $request->item_id,
                'package_id'                => $user_package->package_id,
                'user_purchased_package_id' => $user_package->id,
                'start_date'                => date('Y-m-d'),
                'end_date'                  => $user_package->end_date
            ]);

            DB::commit();
            ResponseService::successResponse(__('Featured Advertisement Created Successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, "API Controller -> createAdvertisement");
            ResponseService::errorResponse();
        }
    }

    public function manageFavourite(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'item_id' => 'required',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $favouriteItem = Favourite::where('user_id', Auth::user()->id)->where('item_id', $request->item_id)->first();
            if (empty($favouriteItem)) {
                $favouriteItem = new Favourite();
                $favouriteItem->user_id = Auth::user()->id;
                $favouriteItem->item_id = $request->item_id;
                $favouriteItem->save();
                ResponseService::successResponse(__('Advertisement added to Favourite'));
            } else {
                $favouriteItem->delete();
                ResponseService::successResponse(__('Advertisement remove from Favourite'));
            }
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> manageFavourite");
            ResponseService::errorResponse();
        }
    }

    public function getFavouriteItem(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer',
                'limit' => 'nullable|integer',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $favouriteItemIDS = Favourite::where('user_id', Auth::user()->id)->select('item_id')->pluck('item_id');
            $items = Item::whereIn('id', $favouriteItemIDS)
                ->with('user:id,name,email,mobile,profile,country_code', 'category:id,name,image,is_job_category','gallery_images:id,image,item_id', 'featured_items', 'favourites', 'item_custom_field_values.custom_field')->where('status','approved')->onlyNonBlockedUsers()->getNonExpiredItems()->paginate();

            ResponseService::successResponse(__('Data Fetched Successfully'), new ItemCollection($items));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getFavouriteItem");
            ResponseService::errorResponse();
        }
    }
    public function getSlider() {
        try {
            $rows = Slider::with(['model' => function (MorphTo $morphTo) {
                $morphTo->constrain([Category::class => function ($query) {
                    $query->withCount('subcategories');
                }]);
            }])
            // ->whereHas('model')
            ->where(function ($query) {
                $query->whereNull('model_type')
                      ->orWhere(function ($query) {
                          $query->whereHasMorph('model', [Category::class, Item::class], function ($subQuery) {
                              $subQuery->whereNotNull('id');
                          });
                      });
            })
            ->get();
            ResponseService::successResponse(null, $rows);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getSlider");
            ResponseService::errorResponse();
        }
    }

    public function getReportReasons(Request $request) {
        try {
            $report_reason = new ReportReason();
            if (!empty($request->id)) {
                $id = $request->id;
                $report_reason->where('id', '=', $id);
            }
            $result = $report_reason->paginate();
            $total = $report_reason->count();
            ResponseService::successResponse(__('Data Fetched Successfully'), $result, ['total' => $total]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getReportReasons");
            ResponseService::errorResponse();
        }
    }

    public function addReports(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'item_id'          => 'required',
                'report_reason_id' => 'required_without:other_message',
                'other_message'    => 'required_without:report_reason_id'
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $user = Auth::user();
            $report_count = UserReports::where('item_id', $request->item_id)->where('user_id', $user->id)->first();
            if ($report_count) {
                ResponseService::errorResponse(__('Already Reported'));
            }
            UserReports::create([
                ...$request->all(),
                'user_id'       => $user->id,
                'other_message' => $request->other_message ?? '',
            ]);
            ResponseService::successResponse(__('Report Submitted Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> addReports");
            ResponseService::errorResponse();
        }
    }

    public function setItemTotalClick(Request $request) {
        try {

            $validator = Validator::make($request->all(), [
                'item_id' => 'required',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            Item::findOrFail($request->item_id)->increment('clicks');
            ResponseService::successResponse(null, 'Update Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> setItemTotalClick");
            ResponseService::errorResponse();
        }
    }

    public function getFeaturedSection(Request $request) {
        try {
            $featureSection = FeatureSection::with('translations')->orderBy('sequence', 'ASC');

            if (isset($request->slug)) {
                $featureSection->where('slug', $request->slug);
            }
            $featureSection = $featureSection->get();
            $tempRow = array();
            $rows = array();

            foreach ($featureSection as $row) {
                 $items = Item::where('status', 'approved')->take(5)->with('user:id,name,email,mobile,profile,is_verified,show_personal_details,country_code', 'category:id,name,image,is_job_category,price_optional', 'gallery_images:id,image,item_id', 'featured_items', 'favourites', 'item_custom_field_values.custom_field.translations','job_applications','translations')->has('user')->getNonExpiredItems();
                if (isset($request->city)) {
                    $items = $items->where('city', $request->city);
                }

                if (isset($request->state)) {
                    $items = $items->where('state', $request->state);
                }

                if (isset($request->country)) {
                    $items = $items->where('country', $request->country);
                }

                if (isset($request->area_id)) {
                    $items = $items->where('area_id', $request->area_id);
                }
                if(isset($request->latitude) &&  isset($request->longitude) && isset($request->radius)){
                    $latitude = $request->latitude;
                    $longitude = $request->longitude;
                    $radius = $request->radius;

                    // Calculate distance using Haversine formula
                    $haversine = "(6371 * acos(cos(radians($latitude))
                                    * cos(radians(latitude))
                                    * cos(radians(longitude)
                                    - radians($longitude))
                                    + sin(radians($latitude))
                                    * sin(radians(latitude))))";

                    $items= $items->select('items.*')
                        ->selectRaw("{$haversine} AS distance")
                        ->where('latitude', '!=', 0)
                        ->where('longitude', '!=', 0)
                        ->having('distance', '<', $radius)
                        ->orderBy('distance', 'asc');
                }
                $items = match ($row->filter) {
                    "price_criteria" => $items->whereBetween('price', [$row->min_price, $row->max_price]),
                    "most_viewed" => $items->orderBy('clicks', 'DESC'),
                    "category_criteria" => (static function () use ($row, $items) {
                        $category = Category::whereIn('id', explode(',', $row->value))->with('children')->get();
                        $categoryIDS = HelperService::findAllCategoryIds($category);
                        return $items->whereIn('category_id', $categoryIDS)->orderBy('id', 'DESC');
                    })(),
                    "most_liked" => $items->withCount('favourites')->orderBy('favourites_count', 'DESC'),
                };
                if (Auth::check()) {
                    $items->with(['item_offers' => function ($q) {
                        $q->where('buyer_id', Auth::user()->id);
                    }, 'user_reports'           => function ($q) {
                        $q->where('user_id', Auth::user()->id);
                    }]);
                }
                $items = $items->get();
//                $tempRow[$row->id]['section_id'] = $row->id;
//                $tempRow[$row->id]['title'] = $row->title;
//                $tempRow[$row->id]['style'] = $row->style;

                $tempRow[$row->id] = $row;
                $tempRow[$row->id]['total_data'] = count($items);
                if (count($items) > 0) {
                    $tempRow[$row->id]['section_data'] = new ItemCollection($items);
                } else {
                    $tempRow[$row->id]['section_data'] = [];
                }

                $rows[] = $tempRow[$row->id];
            }
            ResponseService::successResponse(__('Data Fetched Successfully'), $rows);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getFeaturedSection");
            ResponseService::errorResponse();
        }
    }

    public function getPaymentIntent(Request $request) {

        $validator = Validator::make($request->all(), [
            'package_id'     => 'required',
            'payment_method' => 'required|in:Stripe,Razorpay,Paystack,PhonePe,FlutterWave,bankTransfer',
            'platform_type'  => 'required_if:payment_method,==,Paystack|string'
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {

            DB::beginTransaction();

            if ($request->payment_method !== 'bankTransfer') {
                $paymentConfigurations = PaymentConfiguration::where(['status' => 1, 'payment_method' => $request->payment_method])->first();
                if (empty($paymentConfigurations)) {
                    ResponseService::errorResponse(__('Payment is not Enabled'));
                }
            }else {
                $bankTransferEnabled = Setting::where('name', 'bank_transfer_status')->value('value');
                if ($bankTransferEnabled != 1) {
                    ResponseService::errorResponse(__('Bank Transfer is not enabled.'));
                }
            }

            $package = Package::whereNot('final_price', 0)->findOrFail($request->package_id);

            $purchasedPackage = UserPurchasedPackage::onlyActive()->where(['user_id' => Auth::user()->id, 'package_id' => $request->package_id])->first();
            if (!empty($purchasedPackage)) {
                ResponseService::errorResponse(__('You already have purchased this package'));
            }
            //if ($request->payment_method === 'bankTransfer') {
                $existingTransaction = PaymentTransaction::where('user_id', Auth::user()->id)
                    ->where('package_id', $request->package_id)
                    ->where('payment_gateway', $request->payment_method)
                    ->whereIn('payment_status',['pending','under review'])
                    ->exists();

                $methodName = $paymentMethodNames[$request->payment_method] ?? ucfirst($request->payment_method);

                if ($existingTransaction) {
                    return ResponseService::errorResponse("A $methodName transaction for this package already exists.");
                }
            //}
            $orderId = ($request->payment_method === 'bankTransfer') ? uniqid() . '-' . 'p' . '-' . $package->id : null;

            //Add Payment Data to Payment Transactions Table
            $paymentTransactionData = PaymentTransaction::create([
                'user_id'         => Auth::user()->id,
                'package_id'     => $request->package_id,
                'amount'          => $package->final_price,
                'payment_gateway' => ucfirst($request->payment_method),
                'payment_status'  => 'Pending',
                'order_id'        => $orderId
            ]);

            if ($request->payment_method === 'bankTransfer') {
                DB::commit();
                ResponseService::successResponse(__('Bank transfer initiated. Please complete the transfer and update the transaction.'), [
                    "payment_transaction_id" => $paymentTransactionData->id,
                    "payment_transaction"    => $paymentTransactionData
                ]);
            }

            $paymentIntent = PaymentService::create($request->payment_method)->createAndFormatPaymentIntent(round($package->final_price, 2), [
                'payment_transaction_id' => $paymentTransactionData->id,
                'package_id'             => $package->id,
                'user_id'                => Auth::user()->id,
                'email'                  => Auth::user()->email,
                'platform_type'          => $request->platform_type
            ]);
            $paymentTransactionData->update(['order_id' => $paymentIntent['id']]);

            $paymentTransactionData = PaymentTransaction::findOrFail($paymentTransactionData->id);
            // Custom Array to Show as response
            $paymentGatewayDetails = array(
                ...$paymentIntent,
                'payment_transaction_id' => $paymentTransactionData->id,
            );

            DB::commit();
            ResponseService::successResponse("", ["payment_intent" => $paymentGatewayDetails, "payment_transaction" => $paymentTransactionData]);
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getPaymentTransactions(Request $request) {
        $validator = Validator::make($request->all(), [
            'latest_only' => 'nullable|boolean',
            'page'       =>'nullable'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $paymentTransactions = PaymentTransaction::where('user_id', Auth::user()->id)->orderBy('id', 'DESC');
            if ($request->latest_only) {
                $paymentTransactions->where('created_at', '>', Carbon::now()->subMinutes(30)->toDateTimeString());
            }
            $paymentTransactions = $paymentTransactions->paginate();

            $paymentTransactions->getCollection()->transform(function ($data) {
                if ($data->payment_status == "pending") {
                    try {
                        $paymentIntent = PaymentService::create($data->payment_gateway)->retrievePaymentIntent($data->order_id);
                    } catch (Throwable) {
//                        PaymentTransaction::find($data->id)->update(['payment_status' => "failed"]);
                    }

                    if (!empty($paymentIntent) && $paymentIntent['status'] != "pending") {
                        PaymentTransaction::find($data->id)->update(['payment_status' => $paymentIntent['status'] ?? "failed"]);
                    }
                }
                $data->payment_reciept = $data->payment_reciept;
                return $data;
            });

            ResponseService::successResponse(__('Payment Transactions Fetched'), $paymentTransactions);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function createItemOffer(Request $request) {

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer',
            'amount'  => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $item = Item::approved()->notOwner()->findOrFail($request->item_id);
            $itemOffer = ItemOffer::updateOrCreate([
                'item_id'   => $request->item_id,
                'buyer_id'  => Auth::user()->id,
                'seller_id' => $item->user_id,
            ], ['amount' => $request->amount,]);

            $itemOffer = $itemOffer->load('seller:id,name,profile', 'buyer:id,name,profile', 'item:id,name,description,price,image');

            $fcmMsg = [
                'user_id'           => $itemOffer->buyer->id,
                'user_name'         => $itemOffer->buyer->name,
                'user_profile'      => $itemOffer->buyer->profile,
                'user_type'         => 'Buyer',
                'item_id'           => $itemOffer->item->id,
                'item_name'         => $itemOffer->item->name,
                'item_image'        => $itemOffer->item->image,
                'item_price'        => $itemOffer->item->price,
                'item_offer_id'     => $itemOffer->id,
                'item_offer_amount' => $itemOffer->amount,
                // 'type'              => $notificationPayload['message_type'],
                // 'message_type_temp' => $notificationPayload['message_type']
            ];
            /* message_type is reserved keyword in FCM so removed here*/
            unset($fcmMsg['message_type']);
            if ($request->has('amount') && $request->amount != 0) {
                $user_token = UserFcmToken::where('user_id', $item->user->id)->pluck('fcm_token')->toArray();
                $message = 'new offer is created by buyer';
                NotificationService::sendFcmNotification($user_token, 'New Offer', $message, "offer", $fcmMsg);
            }

            ResponseService::successResponse(__('Advertisement Offer Created Successfully'), $itemOffer,);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> createItemOffer");
            ResponseService::errorResponse();
        }
    }

    public function getChatList(Request $request) {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:seller,buyer'
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            //List of Blocked Users by Auth Users
            $authUserBlockList = BlockUser::where('user_id', Auth::user()->id)->pluck('blocked_user_id');

            $otherUserBlockList = BlockUser::where('blocked_user_id', Auth::user()->id)->pluck('user_id');
            $itemOffer = ItemOffer::with(['seller:id,name,profile', 'buyer:id,name,profile', 'item:id,name,description,price,image,status,deleted_at,sold_to,category_id,min_salary,max_salary',
            'item.category:id,name,image,is_job_category,price_optional',
            'item.review' => function ($q) {
                $q->where('buyer_id', Auth::user()->id);
            }])->whereHas('buyer', function ($query) {
                $query->whereNull('deleted_at');
            })
                ->with(['chat' => function ($query) {
                    $query->latest('updated_at')->select('updated_at', 'item_offer_id','is_read','sender_id');
                }])->withCount([
                    'chat as unread_chat_count' => function ($query) {
                        $query->where('is_read', 0)
                             ->where('sender_id', '!=', Auth::user()->id);
                    }
                ])
                ->orderBy('id', 'DESC');
            if ($request->type == "seller") {
                $itemOffer = $itemOffer->where('seller_id', Auth::user()->id);
            } elseif ($request->type == "buyer") {
                $itemOffer = $itemOffer->where('buyer_id', Auth::user()->id);
            }
            $itemOffer = $itemOffer->paginate();
            $totalUnreadChatCount = $itemOffer->sum('unread_chat_count');

            $itemOffer->getCollection()->transform(function ($value) use ($request, $authUserBlockList, $otherUserBlockList) {
                // Your code here
                if ($request->type == "seller") {
                    $userBlocked = $authUserBlockList->contains($value->buyer_id) || $otherUserBlockList->contains($value->seller_id);
                } elseif ($request->type == "buyer") {
                    $userBlocked = $authUserBlockList->contains($value->seller_id) || $otherUserBlockList->contains($value->buyer_id);
                }
                $value->item->is_purchased = 0;
                if ($value->item->sold_to == Auth::user()->id) {
                    $value->item->is_purchased = 1;
                }
                $tempReview = $value->item->review;

                unset($value->item->review);
                $value->item->review = $tempReview[0] ?? null;
                $value->user_blocked = $userBlocked ?? false;
                return $value;
            });

            $itemOffer->getCollection()->transform(function ($offer) {
                $offer['last_message_time'] = $offer->chat->isNotEmpty() ? $offer->chat->first()->updated_at : null;
                return $offer;
            });
            ResponseService::successResponse(__('Chat List Fetched Successfully'), $itemOffer,['total_unread_chat_count' => $totalUnreadChatCount]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getChatList");
            ResponseService::errorResponse();
        }
    }

    public function sendMessage(Request $request) {
        $validator = Validator::make($request->all(), [
            'item_offer_id' => 'required|integer',
            'message'       => (!$request->file('file') && !$request->file('audio')) ? "required" : "nullable",
            'file'          => 'nullable|mimes:jpg,jpeg,png|max:7168',
            'audio'         => 'nullable|mimetypes:audio/mpeg,video/webm,audio/ogg,video/mp4,audio/x-wav,text/plain|max:7168',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $user = Auth::user();
            //List of users that Auth user has blocked
            $authUserBlockList = BlockUser::where('user_id', $user->id)->get();

            //List of Other users that have blocked the Auth user
            $otherUserBlockList = BlockUser::where('blocked_user_id', $user->id)->get();

            $itemOffer = ItemOffer::with('item')->findOrFail($request->item_offer_id);
            if ($itemOffer->seller_id == $user->id) {
                //If Auth user is seller then check if buyer has blocked the user
                $blockStatus = $authUserBlockList->filter(function ($data) use ($itemOffer) {
                    return $data->user_id == $itemOffer->seller_id && $data->blocked_user_id == $itemOffer->buyer_id;
                });
                if (count($blockStatus) !== 0) {
                    ResponseService::errorResponse(__('You Cannot send message because You have blocked this user'));
                }

                $blockStatus = $otherUserBlockList->filter(function ($data) use ($itemOffer) {
                    return $data->user_id == $itemOffer->buyer_id && $data->blocked_user_id == $itemOffer->seller_id;
                });
                if (count($blockStatus) !== 0) {
                    ResponseService::errorResponse(__('You Cannot send message because other user has blocked you.'));
                }
            } else {
                //If Auth user is seller then check if buyer has blocked the user
                $blockStatus = $authUserBlockList->filter(function ($data) use ($itemOffer) {
                    return $data->user_id == $itemOffer->buyer_id && $data->blocked_user_id == $itemOffer->seller_id;
                });
                if (count($blockStatus) !== 0) {
                    ResponseService::errorResponse(__('You Cannot send message because You have blocked this user'));
                }

                $blockStatus = $otherUserBlockList->filter(function ($data) use ($itemOffer) {
                    return $data->user_id == $itemOffer->seller_id && $data->blocked_user_id == $itemOffer->buyer_id;
                });
                if (count($blockStatus) !== 0) {
                    ResponseService::errorResponse(__('You Cannot send message because other user has blocked you.'));
                }
            }
            $chat = Chat::create([
                'sender_id'     => Auth::user()->id,
                'item_offer_id' => $request->item_offer_id,
                'message'       => $request->message,
                'file'          => $request->hasFile('file') ? FileService::compressAndUpload($request->file('file'), 'chat') : '',
                'audio'         => $request->hasFile('audio') ? FileService::compressAndUpload($request->file('audio'), 'chat') : '',
                'is_read'       => 0
            ]);

            if ($itemOffer->seller_id == $user->id) {
                $receiver_id = $itemOffer->buyer_id;
                $userType = "Seller";
            } else {
                $receiver_id = $itemOffer->seller_id;
                $userType = "Buyer";
            }
            $notificationPayload = $chat->toArray();

            $unreadMessagesCount = Chat::where('item_offer_id', $itemOffer->id)
            ->where('is_read', 0)
            ->count();

            $fcmMsg = [
                ...$notificationPayload,
                'user_id'           => $user->id,
                'user_name'         => $user->name,
                'user_profile'      => $user->profile,
                'user_type'         => $userType,
                'item_id'           => $itemOffer->item->id,
                'item_name'         => $itemOffer->item->name,
                'item_image'        => $itemOffer->item->image,
                'item_price'        => $itemOffer->item->price,
                'item_offer_id'     => $itemOffer->id,
                'item_offer_amount' => $itemOffer->amount,
                'type'              => $notificationPayload['message_type'],
                'message_type_temp' => $notificationPayload['message_type'],
                'unread_count'      => $unreadMessagesCount
            ];
            /* message_type is reserved keyword in FCM so removed here*/
            unset($fcmMsg['message_type']);
            $receiverFCMTokens = UserFcmToken::where('user_id', $receiver_id)->pluck('fcm_token')->toArray();
            $notification = NotificationService::sendFcmNotification($receiverFCMTokens, 'Message', $request->message, "chat", $fcmMsg);

            DB::commit();
            ResponseService::successResponse(__('Message Fetched Successfully'), $chat, ['debug' => $notification]);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, "API Controller -> sendMessage");
            ResponseService::errorResponse();
        }
    }

    public function getChatMessages(Request $request) {
        $validator = Validator::make($request->all(), [
            'item_offer_id' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $itemOffer = ItemOffer::owner()->findOrFail($request->item_offer_id);
            $chat = Chat::where('item_offer_id', $itemOffer->id)->orderBy('created_at', 'DESC')->paginate();
            $authUserId = auth::user()->id;
            Chat::where('item_offer_id', $itemOffer->id)
                ->where('sender_id', '!=', $authUserId)
                ->whereIn('id', $chat->pluck('id'))
                ->update(['is_read' => '1']);
            ResponseService::successResponse(__('Messages Fetched Successfully'), $chat);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getChatMessages");
            ResponseService::errorResponse();
        }
    }

    public function deleteUser() {
        try {
            User::findOrFail(Auth::user()->id)->forceDelete();
            ResponseService::successResponse(__('User Deleted Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> deleteUser");
            ResponseService::errorResponse();
        }
    }

    public function inAppPurchase(Request $request) {
        $validator = Validator::make($request->all(), [
            'purchase_token' => 'required',
            'payment_method' => 'required|in:google,apple',
            'package_id'     => 'required|integer'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $package = Package::findOrFail($request->package_id);
            $purchasedPackage = UserPurchasedPackage::where(['user_id' => Auth::user()->id, 'package_id' => $request->package_id])->first();
            if (!empty($purchasedPackage)) {
                ResponseService::errorResponse(__('You already have purchased this package'));
            }

            PaymentTransaction::create([
                'user_id'         => Auth::user()->id,
                'amount'          => $package->final_price,
                'payment_gateway' => $request->payment_method,
                'order_id'        => $request->purchase_token,
                'payment_status'  => 'success',
            ]);

            UserPurchasedPackage::create([
                'user_id'     => Auth::user()->id,
                'package_id'  => $request->package_id,
                'start_date'  => Carbon::now(),
                'total_limit' => $package->item_limit == "unlimited" ? null : $package->item_limit,
                'end_date'    => $package->duration == "unlimited" ? null : Carbon::now()->addDays($package->duration)
            ]);
            ResponseService::successResponse(__('Package Purchased Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> inAppPurchase");
            ResponseService::errorResponse();
        }
    }

    public function blockUser(Request $request) {
        $validator = Validator::make($request->all(), [
            'blocked_user_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            BlockUser::create([
                'user_id'         => Auth::user()->id,
                'blocked_user_id' => $request->blocked_user_id,
            ]);
            ResponseService::successResponse(__('User Blocked Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> blockUser");
            ResponseService::errorResponse();
        }
    }

    public function unblockUser(Request $request) {
        $validator = Validator::make($request->all(), [
            'blocked_user_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            BlockUser::where([
                'user_id'         => Auth::user()->id,
                'blocked_user_id' => $request->blocked_user_id,
            ])->delete();
            ResponseService::successResponse(__('User Unblocked Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> unblockUser");
            ResponseService::errorResponse();
        }
    }

    public function getBlockedUsers() {
        try {
            $blockedUsers = BlockUser::where('user_id', Auth::user()->id)->pluck('blocked_user_id');
            $users = User::whereIn('id', $blockedUsers)->select(['id', 'name', 'profile'])->get();
            ResponseService::successResponse(__('User Unblocked Successfully'), $users);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> unblockUser");
            ResponseService::errorResponse();
        }
    }

    public function getTips() {
        try {
            $tips = Tip::select(['id', 'description'])->orderBy('sequence', 'ASC')->with('translations')->get();
            ResponseService::successResponse(__('Tips Fetched Successfully'), $tips);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getTips");
            ResponseService::errorResponse();
        }
    }

    public function getBlog(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'nullable|integer|exists:categories,id',
                'blog_id'     => 'nullable|integer|exists:blogs,id',
                'sort_by'     => 'nullable|in:new-to-old,old-to-new,popular',
                'views'     => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            if ($request->views == 1) {
                if (!empty($request->id)) {
                    Blog::where('id', $request->id)->increment('views');
                } elseif (!empty($request->slug)) {
                    Blog::where('slug', $request->slug)->increment('views');
                } else {
                    return ResponseService::errorResponse(__('ID or Slug is required to increment views'));
                }
            }
            $blogs = Blog::with('translations')->when(!empty($request->id), static function ($q) use ($request) {
                $q->where('id', $request->id);
                Blog::where('id', $request->id);
            })
                ->when(!empty($request->slug), function ($q) use ($request) {
                    $q->where('slug', $request->slug);
                    Blog::where('slug', $request->slug);
                })
                ->when(!empty($request->sort_by), function ($q) use ($request) {
                    if ($request->sort_by === 'new-to-old') {
                        $q->orderByDesc('created_at');
                    } elseif ($request->sort_by === 'old-to-new') {
                        $q->orderBy('created_at');
                    } else if ($request->sort_by === 'popular') {
                        $q->orderByDesc('views');
                    }
                })
                ->when(!empty($request->tag), function ($q) use ($request) {
                    $q->where(function ($query) use ($request) {
                        $query->where('tags', 'like', '%' . $request->tag . '%')
                            ->orWhereHas('translations', function ($translationQuery) use ($request) {
                                $translationQuery->where('tags', 'like', '%' . $request->tag . '%');
                            });
                    });
                })
                ->paginate();

            $otherBlogs = [];
           if (!empty($request->id) || !empty($request->slug)) {
                $otherBlogs = Blog::with('translations')
                    ->when(!empty($request->id), function ($q) use ($request) {
                        $q->where('id', '!=', $request->id);
                    })
                    ->when(!empty($request->slug), function ($q) use ($request) {
                        $q->where('slug', '!=', $request->slug);
                    })
                    ->orderByDesc('id')
                    ->limit(3)
                    ->get();
            }

            ResponseService::successResponse(__('Blogs fetched successfully'), $blogs, ['other_blogs' => $otherBlogs]);
        } catch (Throwable $th) {
            // Log and handle exceptions
            ResponseService::logErrorResponse($th, 'API Controller -> getBlog');
            ResponseService::errorResponse(__('Failed to fetch blogs'));
        }
    }

    public function getCountries(Request $request)
    {
        try {
            $searchQuery = $request->search ?? '';
$countries = Country::withCount('states')
    ->where(function ($query) use ($searchQuery) {
        $query->where('name', 'LIKE', "%{$searchQuery}%")
            ->orWhereHas('translations', function ($q) use ($searchQuery) {
                $q->where('name', 'LIKE', "%{$searchQuery}%");
            });
    })
    ->with(['translations.language:id,code'])
    ->orderBy('name', 'ASC')
    ->paginate();

// Map translations to include `language_code`
$countries->getCollection()->transform(function ($country) {
    if ($country->translations instanceof \Illuminate\Support\Collection) {
        $country->translations = $country->translations->map(function ($translation) {
            return [
                'id'            => $translation->id,
                'country_id'    => $translation->country_id,
                'language_id'   => $translation->language_id,
                'name'          => $translation->name,
                'language_code' => optional($translation->language)->code,
            ];
        });
    } else {
        // if somehow it's not a collection, fallback
        $country->translations = [];
    }

    return $country;
});


            ResponseService::successResponse(__('Countries Fetched Successfully'), $countries);

        } catch (Throwable $th) {
            // Log and handle any exceptions
            ResponseService::logErrorResponse($th, "API Controller -> getCountries");
            ResponseService::errorResponse(__('Failed to fetch countries'));
        }
    }

    public function getStates(Request $request) {
        $validator = Validator::make($request->all(), [
            'country_id' => 'nullable|integer',
            'search'     => 'nullable|string'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $searchQuery = $request->search ?? '';
            $statesQuery = State::withCount('cities')
                ->where('name', 'LIKE', "%{$searchQuery}%")
                ->orderBy('name', 'ASC');

            if (isset($request->country_id)) {
                $statesQuery->where('country_id', $request->country_id);
            }

            $states = $statesQuery->paginate();

            ResponseService::successResponse(__('States Fetched Successfully'), $states);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller->getStates");
            ResponseService::errorResponse(__('Failed to fetch states'));
        }
    }

public function getCities(Request $request)
{
    try {
        // Validate
        $validator = Validator::make($request->all(), [
            'state_id' => 'nullable|integer',
            'search'   => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        $searchQuery = $request->search ?? '';

        // Base query
        $citiesQuery = City::with('translations')
            ->withCount('areas')
            ->orderBy('cities.name', 'ASC'); // force main table for sorting

        // Search filter: main name OR translated name
        if ($searchQuery !== '') {
            $citiesQuery->where(function ($q) use ($searchQuery) {
                $q->where('cities.name', 'LIKE', "%{$searchQuery}%")
                  ->orWhereHas('translations', function ($t) use ($searchQuery) {
                      $t->where('name', 'LIKE', "%{$searchQuery}%");
                  });
            });
        }

        // State filter
        if ($request->filled('state_id')) {
            $citiesQuery->where('cities.state_id', $request->state_id);
        }

        // Pagination
        $cities = $citiesQuery->paginate();

        return ResponseService::successResponse(__('Cities Fetched Successfully'), $cities);

    } catch (\Throwable $th) {
        ResponseService::logErrorResponse($th, "API Controller->getCities");
        return ResponseService::errorResponse(__('Failed to fetch cities'));
    }
}

    public function getAreas(Request $request) {
        $validator = Validator::make($request->all(), [
            'city_id' => 'nullable|integer',
            'search'  => 'nullable'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $searchQuery = $request->search ?? '';
            $data = Area::with('translations')->search($searchQuery)->orderBy('name', 'ASC');
            if (isset($request->city_id)) {
                $data->where('city_id', $request->city_id);
            }

            $data = $data->paginate();
            ResponseService::successResponse(__('Area fetched Successfully'), $data);
        } catch (Throwable $th) {
            dd($th);
            ResponseService::logErrorResponse($th, 'API Controller -> getAreas');
            ResponseService::errorResponse();
        }
    }

    public function getFaqs() {
        try {
            $faqs = Faq::with('translations')->get();
            ResponseService::successResponse(__('FAQ Data fetched Successfully'), $faqs);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getFaqs');
            ResponseService::errorResponse(__('Failed to fetch Faqs'));
        }
    }

   public function getAllBlogTags()
    {
        try {
            $languageCode = request()->header('Content-Language') ?? app()->getLocale();

            $language = Language::select(['id', 'code', 'name'])
                ->where('code', $languageCode)
                ->first();

            if (!$language) {
                return ResponseService::errorResponse("Invalid language code");
            }

            $tagsMap = [];

            Blog::with(['translations' => function ($q) use ($language) {
                $q->where('language_id', $language->id);
            }])->chunk(100, function ($blogs) use (&$tagsMap) {
                foreach ($blogs as $blog) {
                    $defaultTagsRaw = $blog->tags;
                    $defaultTags = [];
                    if (!empty($defaultTagsRaw)) {
                        if (is_string($defaultTagsRaw)) {
                            $decoded = json_decode($defaultTagsRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && !empty($decoded)) {
                                $defaultTags = is_array($decoded) ? $decoded : [$decoded];
                            } else {
                                $defaultTags = array_map('trim', explode(',', $defaultTagsRaw));
                            }
                        } elseif (is_array($defaultTagsRaw)) {
                            $defaultTags = $defaultTagsRaw;
                        }
                    }
                    $translatedTagsRaw = $blog->translations->first()?->tags;
                    $translatedTags = [];
                    if (!empty($translatedTagsRaw)) {
                        if (is_string($translatedTagsRaw)) {
                            $decoded = json_decode($translatedTagsRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && !empty($decoded)) {
                                $translatedTags = is_array($decoded) ? $decoded : [$decoded];
                            } else {
                                $translatedTags = array_map('trim', explode(',', $translatedTagsRaw));
                            }
                        } elseif (is_array($translatedTagsRaw)) {
                            $translatedTags = $translatedTagsRaw;
                        }
                    }
                    foreach ($defaultTags as $index => $defaultTag) {
                        $translated = $translatedTags[$index] ?? $defaultTag;
                        $tagsMap[$defaultTag] = $translated;
                    }
                }
            });
            $result = [];
            foreach ($tagsMap as $defaultTag => $translatedTag) {
                $result[] = [
                    'label' => $translatedTag,
                    'value' => $defaultTag
                ];
            }

            ResponseService::successResponse("Blog Tags Retrieved Successfully", array_values($result));
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getAllBlogTags');
            return ResponseService::errorResponse("Failed to fetch Tags");
        }
    }

    public function storeContactUs(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'    => 'required',
            'email'   => 'required|email',
            'subject' => 'required',
            'message' => 'required'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            ContactUs::create($request->all());
            ResponseService::successResponse(__('Contact Us Stored Successfully'));

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> storeContactUs');
            ResponseService::errorResponse();
        }
    }

    public function addItemReview(Request $request) {
        $validator = Validator::make($request->all(), [
            'review'  => 'nullable|string',
            'ratings' => 'required|numeric|between:0,5',
            'item_id' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $item = Item::with('user')->notOwner()->findOrFail($request->item_id);
            if ($item->sold_to !== Auth::id()) {
                ResponseService::errorResponse(__('You can only review items that you have purchased.'));
            }
            if ($item->status !== 'sold out') {
                ResponseService::errorResponse(__("The item must be marked as 'sold out' before you can review it."));
            }
            $existingReview = SellerRating::where('item_id', $request->item_id)->where('buyer_id', Auth::id())->first();
            if ($existingReview) {
                ResponseService::errorResponse(__('You have already reviewed this item.'));
            }
            $review = SellerRating::create([
                'item_id'   => $request->item_id,
                'buyer_id'  => Auth::user()->id,
                'seller_id' => $item->user_id,
                'ratings'   => $request->ratings,
                'review'    => $request->review ?? '',
            ]);

            ResponseService::successResponse(__('Your review has been submitted successfully.'), $review);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> storeContactUs');
            ResponseService::errorResponse();
        }
    }

    public function getSeller(Request $request) {
        $request->validate([
            'id' => 'required|integer'
        ]);

        try {
            // Fetch seller by ID
            $seller = User::findOrFail($request->id);

            // Fetch seller ratings
            $ratings = SellerRating::where('seller_id', $seller->id)->with('buyer:id,name,profile')->paginate(10);
            $averageRating = $ratings->avg('ratings');

            // Response structure
            $response = [
                'seller'  => [
                    ...$seller->toArray(),
                    'average_rating' => $averageRating,
                ],
                'ratings' => $ratings,
            ];

            // Send success response
            ResponseService::successResponse(__('Seller Details Fetched Successfully'), $response);

        } catch (Throwable $th) {
            // Log and handle error response
            ResponseService::logErrorResponse($th, "API Controller -> getSeller");
            ResponseService::errorResponse();
        }
    }


    public function renewItem(Request $request)
    {
        try {
            $free_ad_listing = Setting::where('name', "free_ad_listing")->value('value') ?? 0;

            $rules = [
                'item_id' => 'required|exists:items,id',
            ];

            // If free ad listing is disabled, package_id must be required
            if ($free_ad_listing == 0) {
                $rules['package_id'] = 'required|exists:packages,id';
            } else {
                // If enabled, package_id is optional
                $rules['package_id'] = 'nullable|exists:packages,id';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();
            $item = Item::findOrFail($request->item_id);
            $rawStatus = $item->getAttributes()['status'];

            $package = null;
            $userPackage = null;

            // Only fetch package if provided or required
            if ($request->filled('package_id')) {
                $package = Package::where('id', $request->package_id)->firstOrFail();

                $userPackage = UserPurchasedPackage::onlyActive()
                    ->where([
                        'user_id'    => $user->id,
                        'package_id' => $package->id
                    ])->first();

                if (!$userPackage) {
                    return ResponseService::errorResponse(__('You have not purchased this package'));
                }
            }

            $currentDate = Carbon::now();

            // Check if item already active
            if (Carbon::parse($item->expiry_date)->gt($currentDate)) {
                return ResponseService::errorResponse(__('Advertisement has not expired yet, so it cannot be renewed'));
            }

            // Handle expiry date renewal
            if ($package) {
                // Renew using package duration
                if ($package->duration === 'unlimited') {
                    $item->expiry_date = null;
                } else {
                    $item->expiry_date = $currentDate->copy()->addDays((int) $package->duration);
                }

                // Increase usage
                ++$userPackage->used_limit;
                $userPackage->save();
            } else {
                // Free ad listing renewal logic
                // You can decide default renewal days here. Example: 30 days
                $item->expiry_date = $currentDate->copy()->addDays(30);
            }

            $item->status = $rawStatus;
            $item->save();

            return ResponseService::successResponse(__('Advertisement renewed successfully'), $item);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> renewItem");
            return ResponseService::errorResponse();
        }
    }

    public function getMyReview(Request $request) {
        try {
            $ratings = SellerRating::where('seller_id', Auth::user()->id)->with('seller:id,name,profile', 'buyer:id,name,profile', 'item:id,name,price,image,description')->paginate(10);
            $averageRating = $ratings->avg('ratings');
            $response = [
                'average_rating' => $averageRating,
                'ratings'        => $ratings,
            ];

            ResponseService::successResponse(__('Seller Details Fetched Successfully'), $response);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> getSeller");
            ResponseService::errorResponse();
        }
    }

    public function addReviewReport(Request $request) {
        $validator = Validator::make($request->all(), [
            'report_reason'    => 'required|string',
            'seller_review_id' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $ratings = SellerRating::where('seller_id', Auth::user()->id)->findOrFail($request->seller_review_id);
            $ratings->update([
                'report_status' => 'reported',
                'report_reason' => $request->report_reason
            ]);

            ResponseService::successResponse(__('Your report has been submitted successfully.'), $ratings);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> addReviewReport');
            ResponseService::errorResponse();
        }
    }


    public function getVerificationFields() {
        try {
            $fields = VerificationField::all();
            ResponseService::successResponse(__('Verification Field Fetched Successfully'), $fields);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, "API Controller -> addVerificationFieldValues");
            ResponseService::errorResponse();
        }
    }

    public function sendVerificationRequest(Request $request) {
        try {

            $validator = Validator::make($request->all(), [
                'verification_field'         => 'sometimes|array',
                'verification_field.*'       => 'sometimes',
                'verification_field_files'   => 'nullable|array',
                'verification_field_files.*' => 'nullable|mimes:jpeg,png,jpg,pdf,doc|max:7168',
                'verification_field_translations'   => 'nullable|json',

            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            DB::beginTransaction();

            $user = Auth::user();
            $verificationRequest = VerificationRequest::updateOrCreate([
                'user_id' => $user->id,
            ], ['status' => 'pending']);

            $user = auth()->user();
            if ($request->verification_field) {
                $itemCustomFieldValues = [];
                foreach ($request->verification_field as $id => $value) {
                    $itemCustomFieldValues[] = [
                        'user_id'                 => $user->id,
                        'verification_field_id'   => $id,
                        'verification_request_id' => $verificationRequest->id,
                        'value'                   => $value,
                        'created_at'              => now(),
                        'updated_at'              => now()
                    ];
                }
                if (count($itemCustomFieldValues) > 0) {
                    VerificationFieldValue::upsert($itemCustomFieldValues, ['user_id', 'verification_fields_id'], ['value', 'updated_at']);
                }
            }

            if ($request->verification_field_files) {
                $itemCustomFieldValues = [];
                foreach ($request->verification_field_files as $fieldId => $file) {
                    $itemCustomFieldValues[] = [
                        'user_id'                 => $user->id,
                        'verification_field_id'   => $fieldId,
                        'verification_request_id' => $verificationRequest->id,
                        'value'                   => !empty($file) ? FileService::upload($file, 'verification_field_files') : '',
                        'created_at'              => now(),
                        'updated_at'              => now()
                    ];
                }
                if (count($itemCustomFieldValues) > 0) {
                    VerificationFieldValue::upsert($itemCustomFieldValues, ['user_id', 'verification_field_id'], ['value', 'updated_at']);
                }
            }
            if ($request->has('verification_field_translations')) {
                    $fieldTranslations = json_decode($request->input('verification_field_translations'), true, 512, JSON_THROW_ON_ERROR);
                    $translatedEntries = [];

                    foreach ($fieldTranslations as $languageId => $fieldsById) {
                        foreach ($fieldsById as $fieldId => $translatedValue) {
                            $translatedEntries[] = [
                                'user_id'                 => $user->id,
                                'verification_field_id'   => $fieldId,
                                'verification_request_id' => $verificationRequest->id,
                                'language_id'             => $languageId,
                                'value'                   => is_array($translatedValue) ? implode(',', $translatedValue) : $translatedValue,
                                'created_at'              => now(),
                                'updated_at'              => now(),
                            ];
                        }
                    }

                    if (!empty($translatedEntries)) {
                        // upsert to avoid duplicates — if necessary
                        VerificationFieldValue::upsert(
                            $translatedEntries,
                            ['user_id', 'verification_field_id'],
                            ['value', 'updated_at','language_id']
                        );
                    }
                }

            DB::commit();

            ResponseService::successResponse(__('Verification request submitted successfully.'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> SendVerificationRequest");
            ResponseService::errorResponse();
        }
    }

    public function getVerificationRequest(Request $request)
    {
        try {
            $verificationRequest = VerificationRequest::with([
                'verification_field_values.verification_field.translations'
            ])->owner()->first();

            if (empty($verificationRequest)) {
                ResponseService::errorResponse("No Request found");
            }

            $response = $verificationRequest->toArray();
            $response['verification_fields'] = [];

            // Get current language for translation
            $contentLangCode = $request->header('Content-Language') ?? app()->getLocale();
            $currentLanguage = Language::where('code', $contentLangCode)->first();
            $currentLangId = $currentLanguage->id ?? 1;

            foreach ($verificationRequest->verification_field_values as $verificationFieldValue) {
                if (
                    $verificationFieldValue->relationLoaded('verification_field') &&
                    !empty($verificationFieldValue->verification_field)
                ) {

                    // if (empty($verificationFieldValue->language_id) || $verificationFieldValue->language_id = null) {
                    //     $verificationFieldValue->language_id = $currentLangId;
                    // }


                    $field = $verificationFieldValue->verification_field;
                    $tempRow = $field->toArray();

                    $rawValue = $verificationFieldValue->value;

                    // Normalize value to array
                    $normalizedValue = [];
                    if ($field->type === 'fileinput') {
                        $normalizedValue = !empty($rawValue) ? [url(Storage::url($rawValue))] : [];
                    } elseif (is_array($rawValue)) {
                        $normalizedValue = $rawValue;
                    } elseif (is_string($rawValue)) {
                        $decoded = json_decode($rawValue, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $normalizedValue = $decoded;
                        } else {
                            $normalizedValue = [$rawValue];
                        }
                    } elseif (!empty($rawValue)) {
                        $normalizedValue = [$rawValue];
                    }

                    // Set normalized value
                    $tempRow['value'] = array_map('trim', explode(',', $normalizedValue[0]));


                    // Set verification_field_value with normalized value
                    $tempRow['verification_field_value'] = $verificationFieldValue->toArray();
                    unset($tempRow['verification_field_value']['verification_field']);
                    $tempRow['verification_field_value']['value'] = $normalizedValue;
                    $tempRow['verification_field_value']['language_id'] = $verificationFieldValue->language_id;
                    // Handle translated_selected_values
                    $selected = [];
                    $type = $field->type ?? null;
                    $allPossibleValues = $field->values ?? [];

                    // Fetch translated values (if available)
                    $translatedValues = [];
                    if (!empty($field->translations)) {
                        $translation = collect($field->translations)->firstWhere('language_id', $currentLangId);
                        $translatedValues = $translation['value'] ?? [];
                    }
                    if (empty($translatedValues)) {
                        $translatedValues = $allPossibleValues;
                    }

                    if (in_array($type, ['checkbox', 'radio', 'dropdown'])) {
                        foreach ($normalizedValue as $val) {
                            $index = array_search($val, $allPossibleValues);
                            $translatedVal = ($index !== false && isset($translatedValues[$index]))
                                ? $translatedValues[$index]
                                : $val;
                            $selected[] = $translatedVal;
                        }
                    } elseif (in_array($type, ['textbox', 'number'])) {
                        $selected = $normalizedValue;
                    }
                    $tempRow['language_id'] = $verificationFieldValue->language_id;
                    $tempRow['translated_selected_values'] = $selected;
                    $response['verification_fields'][] = $tempRow;
                }
            }

            ResponseService::successResponse(__('Verification request fetched successfully.'), $response);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> SendVerificationRequest");
            ResponseService::errorResponse();
        }
    }


    public function seoSettings(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'page' => 'nullable',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $settings = new SeoSetting();
            if (!empty($request->page)) {
                $settings = $settings->where('page', $request->page);
            }

            $settings = $settings->get();
            ResponseService::successResponse(__('SEO settings fetched successfully.'), $settings);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> seoSettings");
            ResponseService::errorResponse();
        }
    }
    public function getCategories(Request $request) {
        $validator = Validator::make($request->all(), [
            'language_code' => 'nullable'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $categories  = Category::all();
            $languageCode = $request->get('language_code', 'en');

            $translator = new GoogleTranslate($languageCode);
            $categoriesJson = $categories->toJson();
            $translatedJson = $translator->translate($categoriesJson);
            $translatedCategories = json_decode($translatedJson, true);

        return ResponseService::successResponse(null, $translatedCategories);
            ResponseService::successResponse(null, $sql);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategories');
            ResponseService::errorResponse();
        }
    }
    public function bankTransferUpdate(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'payment_transection_id' => 'required|integer',
                'payment_receipt' => 'required|file|mimes:jpg,jpeg,png|max:7048',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }
            $transaction = PaymentTransaction::where('user_id', Auth::user()->id)->findOrFail($request->payment_transection_id);

            if (!$transaction) {
                return ResponseService::errorResponse(__('Transaction not found.'));
            }
            $receiptPath = !empty($request->file('payment_receipt'))
            ? FileService::upload($request->file('payment_receipt'),'bank-transfer')
            : '';
            $transaction->update([
                'payment_receipt' => $receiptPath,
                'payment_status' => 'under review',
            ]);

            return ResponseService::successResponse(__('Payment transaction updated successfully.'), $transaction);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> bankTransferUpdate");
            return ResponseService::errorResponse();
        }
    }
    public function getOtp(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'number' => 'required|string'
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            // Format the phone number properly
            $requestNumber = $request->number;
            $trimmedNumber = ltrim($requestNumber, '+');
            $toNumber = "+".$trimmedNumber;

            // Fetch Twilio credentials from settings
            $twilioSettings = Setting::whereIn('name', [
                'twilio_account_sid', 'twilio_auth_token', 'twilio_my_phone_number'
            ])->pluck('value', 'name');

            if (!$twilioSettings->all()) {
                return ResponseService::errorResponse(__('Twilio settings are missing. Please contact admin.'));
            }

            $sid = $twilioSettings['twilio_account_sid'];
            $token = $twilioSettings['twilio_auth_token'];
            $fromNumber = $twilioSettings['twilio_my_phone_number'];

            $client = new TwilioRestClient($sid, $token);

            // Validate phone number using Twilio Lookup API
            try {
                $client->lookups->v1->phoneNumbers($toNumber)->fetch();
            } catch (Throwable $e) {
                return ResponseService::errorResponse(__('Invalid phone number.'));
            }


            $existingOtp = NumberOtp::where('number', $toNumber)->where('expire_at', '>', now())->first();
            $otp = $existingOtp ? $existingOtp->otp : rand(100000, 999999);
            $expireAt = now()->addMinutes(10);

            NumberOtp::updateOrCreate(
                ['number' => $toNumber],
                ['otp' => $otp, 'expire_at' => $expireAt]
            );

            // Send OTP via Twilio
            $client->messages->create($toNumber, [
                'from' => $fromNumber,
                'body' => "Your OTP is: $otp. It expires in 10 minutes."
            ]);

            return ResponseService::successResponse(__('OTP sent successfully.'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "OTP Controller -> getOtp");
            return ResponseService::errorResponse();
        }
    }
    public function verifyOtp(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'number' => 'required|string',
                'otp' => 'required|numeric|digits:6',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $requestNumber = $request->number;
            $trimmedNumber = ltrim($requestNumber, '+');
            $toNumber = "+".$trimmedNumber;

            $otpRecord = NumberOtp::where('number', $toNumber)->first();

            if (!$otpRecord) {
                return ResponseService::errorResponse(__('OTP not found.'));
            }
            if (now()->isAfter($otpRecord->expire_at)) {
                return ResponseService::validationError(__('OTP has expired.'));
            }

            if ($otpRecord->attempts >= 3) {
                $otpRecord->delete();
                return ResponseService::validationError(__('OTP expired after 3 failed attempts.'));
            }

            if ($otpRecord->otp != $request->otp) {
                $otpRecord->increment('attempts');
                return ResponseService::validationError(__('Invalid OTP.'));
            }
            $otpRecord->delete();

            $user = User::where('mobile', $trimmedNumber)->where('type', 'phone')->first();

            if (!$user) {
                $user = User::create([
                    'mobile' => $trimmedNumber,
                    'type'   => 'phone',
                ]);

                $user->assignRole('User');
            }

            Auth::login($user);
            $auth = User::find(Auth::id());

            $token = $auth->createToken($auth->name ?? '')->plainTextToken;

        return ResponseService::successResponse(__('User logged-in successfully'), $auth, ['token' => $token]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "OTP Controller -> verifyOtp");
            return ResponseService::errorResponse();
        }
    }
    public function applyJob(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id'    => 'required',
            'full_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'mobile'     => 'required|string|max:20',
            'resume'     => 'nullable|file|mimes:pdf,doc,docx|max:7168',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $userId = Auth::id();
            $post = Item::approved()->notOwner()->findOrFail($request->item_id);
            $alreadyApplied = JobApplication::where('item_id', $request->item_id)
            ->where('user_id', $userId)
            ->exists();

            if ($alreadyApplied) {
                return ResponseService::validationError(__('You have already applied for this job.'));
            }
            $resumePath = null;
            if ($request->hasFile('resume')) {
                $resumePath = FileService::upload($request->resume, 'job_resume');
            }

            $application = JobApplication::create([
                'item_id'     => $post->id,
                'user_id'     => Auth::user()->id,
                'recruiter_id' => $post->user_id,
                'full_name'   => $request->full_name,
                'email'       => $request->email,
                'mobile'      => $request->mobile,
                'resume' => $resumePath
            ]);

            $user_token = UserFcmToken::where('user_id', $post->user_id)->pluck('fcm_token')->toArray();
            if (!empty($user_token)) {
                NotificationService::sendFcmNotification($user_token,'New Job Application',$request->full_name . ' applied for your job post: ' . $post->name,'job-application',['item_id' => $post->id]
                );
            }

            return ResponseService::successResponse(__('Application submitted successfully.'), $application);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> applyJob");
            return ResponseService::errorResponse();
        }
    }


    public function recruiterApplications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'nullable|integer',
            'page' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = Auth::user();

            $applications = JobApplication::where('recruiter_id', $user->id)
            ->with('user:id,name,email', 'item:id,name');
            if (!empty($request->item_id)) {
                $applications->where('item_id', $request->item_id);
            }

            $applications = $applications->latest()->paginate();
            return ResponseService::successResponse(__('Recruiter applications fetched'), $applications);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> recruiterApplications");
            return ResponseService::errorResponse();
        }
    }
    public function myJobApplications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'nullable|integer',
            'page' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = Auth::user();

            $applications = JobApplication::where('user_id', $user->id);

            if (!empty($request->item_id)) {
                $applications->where('item_id', $request->item_id);
            }

            $applications = $applications->with([
                    'item:id,name,user_id',
                    'recruiter:id,name,email',
                ])
            ->latest()
            ->paginate();

            return ResponseService::successResponse(__('Your job applications fetched'), $applications);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> myJobApplications");
            return ResponseService::errorResponse();
        }
    }
        public function updateJobStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:job_applications,id',
            'status' => 'required|in:accepted,rejected',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $user = Auth::user();
            $application = JobApplication::with('item')->findOrFail($request->job_id);

            if ($application->recruiter_id !== $user->id) {
                return ResponseService::errorResponse(__('Unauthorized to update this job status.'), 403);
            }

            $application->update(['status' => $request->status]);

            // Optional: Notify the applicant
            $user_token = UserFcmToken::where('user_id', $application->user_id)->pluck('fcm_token')->toArray();
            if (!empty($user_token)) {
                NotificationService::sendFcmNotification(
                    $user_token,
                    'Application ' . ucfirst($request->status),
                    "Your application for job post has been " . $request->status,
                    'application-status',
                    ['job_id' => $application->id]
                );
            }

            return ResponseService::successResponse(__('Application status updated.'), $application);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "API Controller -> updateJobStatus");
            return ResponseService::errorResponse();
        }
    }

  public function getLocationFromCoordinates(Request $request)
{
    $validator = Validator::make($request->all(), [
        'lat'      => 'nullable|numeric',
        'lng'      => 'nullable|numeric',
        'lang'     => 'nullable|string',
        'search'   => 'nullable|string',
        'place_id' => 'nullable|string'
    ]);

    if ($validator->fails()) {
        return ResponseService::validationError($validator->errors()->first());
    }

    try {
        $lat       = $request->lat;
        $lng       = $request->lng;
        $lang      = $request->lang ?? 'en';
        $search    = $request->search;
        $placeId   = $request->place_id;
        $mapProvider = Setting::where('name', 'map_provider')->value('value') ?? 'free_api';

        // Determine current language ID
        $contentLangCode = $request->header('Content-Language') ?? app()->getLocale();
        $currentLanguage = Language::where('code', $contentLangCode)->first();
        $currentLangId   = $currentLanguage->id ?? 1;

        /**
         * 🔍 Handle search query
         */
        if ($search) {
            if ($mapProvider === 'google_places') {
                $apiKey = Setting::where('name', 'place_api_key')->value('value');
                if (!$apiKey) {
                    return ResponseService::errorResponse(__('Google Maps API key not set'));
                }

                $response = Http::get("https://maps.googleapis.com/maps/api/place/autocomplete/json", [
                    'key'      => $apiKey,
                    'input'    => $search,
                    'language' => $lang,
                ]);

                return $response->successful()
                    ? ResponseService::successResponse(__('Location fetched from Google API'), $response->json())
                    : ResponseService::errorResponse(__('Failed to fetch from Google Maps API'));

            } else {
                // Search Areas with translations
                $areas = Area::with([
                        'translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'city.translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'city.state.translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'city.state.country.nametranslations' => fn($q) => $q->where('language_id', $currentLangId),
                    ])
                    ->where('name', 'like', "%{$search}%")
                    ->limit(10)
                    ->get();

                if ($areas->isNotEmpty()) {
                    return ResponseService::successResponse(__('Matching areas found'), $areas->map(function ($area) {
                        return [
                            'area_id'           => $area->id,
                            'area'              => $area->name,
                            'area_translation'  => optional($area->translations->first())->name ?? $area->name,
                            'city_id'           => optional($area->city)->id,
                            'city'              => optional($area->city)->name,
                            'city_translation'  => optional($area->city->translations->first())->name ?? optional($area->city)->name,
                            'state'             => optional($area->city->state)->name,
                            'state_translation' => optional($area->city->state->translations->first())->name ?? optional($area->city->state)->name,
                            'country'           => optional($area->city->state->country)->name,
                            'country_translation' => optional($area->city->state->country->nametranslations->first())->name ?? optional($area->city->state->country)->name,
                            'latitude'          => $area->latitude,
                            'longitude'         => $area->longitude,
                        ];
                    }));
                }

                // Search Cities with translations
                $cities = City::with([
                        'translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'state.translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'state.country.nametranslations' => fn($q) => $q->where('language_id', $currentLangId),
                    ])
                    ->where('name', 'like', "%{$search}%")
                    ->orWhereHas('state', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('state.country', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->limit(10)
                    ->get();

                if ($cities->isEmpty()) {
                    return ResponseService::errorResponse(__('No matching location found'));
                }

                return ResponseService::successResponse(__('Matching cities found'), $cities->map(function ($city) {
                    return [
                        'city_id'           => $city->id,
                        'city'              => $city->name,
                        'city_translation'  => optional($city->translations->first())->name ?? $city->name,
                        'state'             => optional($city->state)->name,
                        'state_translation' => optional($city->state->translations->first())->name ?? optional($city->state)->name,
                        'country'           => optional($city->state->country)->name,
                        'country_translation' => optional($city->state->country->nametranslations->first())->name ?? optional($city->state->country)->name,
                        'latitude'          => $city->latitude,
                        'longitude'         => $city->longitude,
                    ];
                }));
            }
        }

        /**
         * 📍 Get location by coordinates
         */
        if (!empty($lat) && !empty($lng)) {
            if ($mapProvider === 'google_places') {
                $apiKey = Setting::where('name', 'place_api_key')->value('value');
                if (!$apiKey) {
                    return ResponseService::errorResponse(__('Google Maps API key not set'));
                }

                $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
                    'latlng'   => "{$lat},{$lng}",
                    'key'      => $apiKey,
                    'language' => $lang
                ]);

                return $response->successful()
                    ? ResponseService::successResponse(__('Location fetched from Google API'), $response->json())
                    : ResponseService::errorResponse(__('Failed to fetch from Google Maps API'));

            } else {
                $closestCity = City::with([
                        'translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'state.translations' => fn($q) => $q->where('language_id', $currentLangId),
                        'state.country.nametranslations' => fn($q) => $q->where('language_id', $currentLangId),
                    ])
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->selectRaw("
                        id, name, latitude, longitude, state_id,
                        (6371 * acos(cos(radians(?))
                            * cos(radians(latitude))
                            * cos(radians(longitude) - radians(?))
                            + sin(radians(?))
                            * sin(radians(latitude)))) AS distance
                    ", [$lat, $lng, $lat])
                    ->orderBy('distance', 'asc')
                    ->first();

                if (!$closestCity) {
                    return ResponseService::errorResponse(__('No nearby city found'));
                }

                $closestArea = Area::with([
                        'translations' => fn($q) => $q->where('language_id', $currentLangId)
                    ])
                    ->where('city_id', $closestCity->id)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->selectRaw("
                        id, name, latitude, longitude, city_id,
                        (6371 * acos(cos(radians(?))
                            * cos(radians(latitude))
                            * cos(radians(longitude) - radians(?))
                            + sin(radians(?))
                            * sin(radians(latitude)))) AS distance
                    ", [$lat, $lng, $lat])
                    ->orderBy('distance', 'asc')
                    ->first();

                return ResponseService::successResponse(__('Location fetched from local database'), [
                    'city_id'           => $closestCity->id,
                    'city'              => $closestCity->name,
                    'city_translation'  => optional($closestCity->translations->first())->name ?? $closestCity->name,
                    'state'             => optional($closestCity->state)->name,
                    'state_translation' => optional($closestCity->state->translations->first())->name ?? optional($closestCity->state)->name,
                    'country'           => optional($closestCity->state->country)->name,
                    'country_translation' => optional($closestCity->state->country->nametranslations->first())->name ?? optional($closestCity->state->country)->name,
                    'area_id'           => optional($closestArea)->id,
                    'area'              => optional($closestArea)->name,
                    'area_translation' => optional($closestArea?->translations?->first())->name ?? $closestArea?->name,
                    'latitude'          => $closestCity->latitude,
                    'longitude'         => $closestCity->longitude,
                ]);
            }
        }

        /**
         * 🏷️ Handle place_id
         */
        if ($placeId) {
            if ($mapProvider === 'google_places') {
                $apiKey = Setting::where('name', 'place_api_key')->value('value');
                if (!$apiKey) {
                    return ResponseService::errorResponse(__('Google Maps API key not set'));
                }

                $url = "https://maps.googleapis.com/maps/api/geocode/json?place_id={$placeId}&key={$apiKey}&language={$lang}";
                $response = Http::get($url);

                return $response->successful()
                    ? ResponseService::successResponse(__('Location fetched from place_id'), $response->json())
                    : ResponseService::errorResponse(__('Failed to fetch from Google Maps API using place_id'));
            } else {
                return ResponseService::errorResponse(__('place_id is only supported with Google Maps provider'));
            }
        }

    } catch (\Throwable $th) {
        ResponseService::logErrorResponse($th, "API Controller -> getLocationFromCoordinates");
        return ResponseService::errorResponse(__('Failed to fetch location'));
    }
}

}
