@foreach ($categories as $category)
    <div class="category">
        <div class="category-header">
            <label>
                <input type="checkbox" 
                       name="selected_categories[]" 
                       value="{{ $category->id }}" 
                       {{ in_array($category->id, $selected_categories) ? "checked" : "" }}>
                {{ $category->name }}
            </label>
            @if (!empty($category->subcategories))
                <i style="font-size:24px"
                   class="fas toggle-button {{ in_array($category->id, $selected_all_categories) ? 'open' : '' }}">
                   &#xf0da;
                </i>
            @endif
        </div>

        {{-- ✅ Same open/close logic applies recursively --}}
        <div class="subcategories" 
             style="display: {{ in_array($category->id, $selected_all_categories) ? 'block' : 'none' }};">
            @if (!empty($category->subcategories))
                @include('category.treeview', [
                    'categories' => $category->subcategories,
                    'selected_categories' => $selected_categories,
                    'selected_all_categories' => $selected_all_categories
                ])
            @endif
        </div>
    </div>
@endforeach