@extends('layouts.main')

@section('title')
    {{ __('Terms & Conditions') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <form action="{{ route('settings.store') }}" method="post" class="create-form-without-reset">
                @csrf
                <div class="card-body">
                    <div class="row form-group">
                        <div class="col-2 d-flex justify-content-end">
                            <a href="{{ route('public.terms-conditions') }}" target="_blank" class="col-sm-12 col-md-12 d-fluid btn icon btn-primary btn-sm rounded-pill">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                        </div>
                        <div class="col-md-12 mt-3">
                            <textarea id="tinymce_editor" name="terms_conditions" class="form-control col-md-7 col-xs-12" aria-label="tinymce_editor">{{ $settings['terms_conditions'] ?? '' }}</textarea>
                        </div>
                         @if($languages_translate->isNotEmpty())
                                <div class="col-md-12 mt-3">
                                    <hr>
                                    <h5>{{ __("Translations") . " (" . __("Optional") . ")" }}</h5>
                                </div>

                                @foreach($languages_translate as $language)
                                    <div class="col-md-12 mb-4 border p-3 rounded shadow-sm">
                                        <h6 class="mb-3 text-primary">
                                            {{ __("Translation for") }}: <strong>{{ $language->name }} ({{ $language->code }})</strong>
                                        </h6>

                                        <input type="hidden" name="translations[{{ $language->id }}][name]" value="terms_conditions">

                                        <div class="form-group">
                                            <label for="translation_{{ $language->id }}" class="form-label">
                                                {{ __("Translated Terms & Conditions") }}
                                            </label>
                                            <textarea class="form-control"
                                                    id="tinymce_editor"
                                                    name="translations[{{ $language->id }}][value]"
                                                    rows="4"
                                                    placeholder="{{ __('Terms & Conditions in') . ' ' . $language->name }}">
                                                {{ old("translations.{$language->id}.value", $translations['terms_conditions'][$language->id] ?? '') }}
                                            </textarea>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-primary me-1 mb-1" type="submit" name="submit">{{ __('Save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
