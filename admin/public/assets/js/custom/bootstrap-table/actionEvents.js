window.languageEvents = {
    'click .edit_btn': function (e, value, row) {
        $('.filepond').filepond('removeFile')
        $("#edit_name").val(row.name);
        $("#edit_name_in_english").val(row.name_in_english);
        $("#edit_code").val(row.code);
        $("#edit_country_code").val(row.country_code);
        $("#edit_rtl_switch").prop('checked', row.rtl);
        $("#edit_rtl").val(row.rtl ? 1 : 0);
    },
    'click .language': function (e, value, row) {

    },
};

// window.SeoSettingEvents = {
//     'click .edit_btn': function (e, value, row) {
//         $('.filepond').filepond('removeFile')
//         $("#edit_page").val(row.page);
//         $("#edit_title").val(row.title);
//         $("#edit_description").val(row.description);
//         $("#edit_keywords").val(row.keywords);
//     }
// };
window.SeoSettingEvents = {
    'click .edit_btn': function (e, value, row) {
        $("#edit_page").val(row.page);
        $('.filepond').filepond('removeFile');

        if (row.image) {
            $('.filepond').filepond('addFile', row.image);
        }
        $("#edit_title_1").val(row.title ?? '');
        $("#edit_description_1").val(row.description ?? '');
        $("#edit_keywords_1").val(row.keywords ?? '');

        let translations = row.translations ?? [];
        translations.forEach(function (translation) {
            const langId = translation.language_id;
            $("#edit_title_" + langId).val(translation.title);
            $("#edit_description_" + langId).val(translation.description);
            $("#edit_keywords_" + langId).val(translation.keywords);
        });
    }
};

window.customFieldValueEvents = {
    'click .edit_btn': function (e, value, row) {
        $("#new_custom_field_value").val(row.value);
        $("#old_custom_field_value").val(row.value);
    }
}
window.verificationFieldValueEvents = {
    'click .edit_btn': function (e, value, row) {
        $("#new_verification_field_value").val(row.value);
        $("#old_verification_field_value").val(row.value);
    }
}


window.itemEvents = {
    'click .editdata': function (e, value, row) {
        let html = `<table class="table">
            <tr>
                <th width="10%">${trans("No.")}</th>
                <th width="25%" class="text-center">${trans("Image")}</th>
                <th width="25%">${trans("Name")}</th>
                <th width="40%">${trans("Value")}</th>
            </tr>`;
        $.each(row.custom_fields, function (key, value) {
            html += `<tr class="mb-2">
                <td>${key + 1}</td>
                <td class="text-center">
                <a class="image-popup-no-margins" href="${value.image}" >
                <img src=${value.image} height="30px" width="30px" style="border-radius:8px;" alt="" onerror="onErrorImage(event)">
                </a>
                </td>
                <td>${value.name}</td>`;

            if (value.type == "fileinput") {
                if (value.value != undefined) {
                    if (value.value?.value.match(/\.(jpg|jpeg|png|svg)$/i)) {
                        html += `<td><img src="${value.value?.value}" alt="Custom Field Files" class="w-25" onerror="onErrorImage(event)"></td>`
                    } else {
                        html += `<td><a target="_blank" href="${value.value?.value}">View File</a></td>`
                    }

                } else {
                    html += `<td></td>`
                }
            } else {
                html += `<td class="text-break">${value.value?.value || ''}</td>`
            }

            html += `</tr>`;
        });

        html += "</table>";
        $('#custom_fields').html(html)
    },

    'click .edit-status': function (e, value, row) {
        $('#status').val(row.status).trigger('change');
        $('#rejected_reason').val(row.rejected_reason);
    }
}

window.packageEvents = {
    'click .edit_btn': function (e, value, row) {
        // Clear all translation fields

        $('#edit_price').val(row.price);
        $('#edit_discount_in_percentage').val(row.discount_in_percentage);
        $('#edit_final_price').val(row.final_price);
        $('#edit_name').val(row.name);
        $('#edit_description').val(row.description);
        if (row.translations && Array.isArray(row.translations)) {
            row.translations.forEach(function (trans) {
                const langId = trans.language_id;
                $('#edit_translations_' + langId + '_name').val(trans.name);
                $('#edit_translations_' + langId + '_description').val(trans.description);
            });
        }

        $('#edit_ios_product_id').val(row.ios_product_id);

        // Assuming 'id' is a variable containing the ID you are working with
        if (row.duration.toLowerCase() === "unlimited") {
            // "Unlimited" value, set unlimited duration
            // $('input[type="radio"][name="duration_type"][value="unlimited"]').prop('checked', true);
            $('#edit_duration_type_unlimited').prop('checked', true);
            $('#edit_durationLimit').val();
            $('#edit_limitation_for_duration').hide();
        } else {
            // Numeric value, set limited duration
            // $('input[type="radio"][name="duration_type"][value="limited"]').prop('checked', true);
            $('#edit_duration_type_limited').prop('checked', true);
            $('#edit_limitation_for_duration').show();
            $('#edit_durationLimit').val(row.duration);
        }


        if (row.item_limit.toLowerCase() === "unlimited") {
            // "Unlimited" value, set unlimited duration
            // $('input[type="radio"][name="item_limit_type"][value="unlimited"]').prop('checked', true);
            $('#edit_item_limit_type_unlimited').prop('checked', true);
            $('#edit_ForLimit').val();
            $('#edit_limitation_for_limit').hide();
        } else {
            // Numeric value, set limited duration
            // $('input[type="radio"][name="item_limit_type"][value="limited"]').prop('checked', true);
            $('#edit_item_limit_type_limited').prop('checked', true);
            $('#edit_limitation_for_limit').show();
            $('#edit_ForLimit').val(row.item_limit);
        }
    }
};

window.advertisementPackageEvents = {
    'click .edit_btn': function (e, value, row) {
        $('#edit_name').val(row.name);
        $('#edit_price').val(row.price);
        $('#edit_discount_in_percentage').val(row.discount_in_percentage);
        $('#edit_final_price').val(row.final_price);
        $("#edit_duration").val(row.duration);
        $('#edit_durationLimit').val(row.duration);
        $('#edit_ForLimit').val(row.item_limit);
        $('#edit_description').val(row.description);
         if (row.translations && Array.isArray(row.translations)) {
            row.translations.forEach(function (trans) {
                const langId = trans.language_id;
                $('#edit_translations_' + langId + '_name').val(trans.name);
                $('#edit_translations_' + langId + '_description').val(trans.description);
            });
        }

        $('#edit_ios_product_id').val(row.ios_product_id);
    }
};

window.reportReasonEvents = {
    'click .edit_btn': function (e, value, row) {
        let translations = row.translations ?? [];

        // Reset all language inputs first (clear old values)
        $("[id^=edit_reason_]").val("");

        // Set English reason (default)
        $("#edit_reason_1").val(row.reason);

        // Fill translations if available
        translations.forEach(function (translation) {
            const langId = translation.language_id;
            $("#edit_reason_" + langId).val(translation.reason);
        });

        // Set the form action URL if needed
        // $(".edit-form").attr("action", `/report-reasons/${row.id}`);
    }
}

window.featuredSectionEvents = {
    'click .edit_btn': function (e, value, row) {
        $('#edit_title').val(row.title);
        $('#edit_description').val(row.description);
        $('#edit_slug').val(row.slug);
        $('#edit_filter').val(row.filter).trigger('change');
        $('input[name="edit_style_app"][value="' + row.style + '"]').prop('checked', true);
        // 🧹 clear all translation inputs first
        $('[id^="edit_translations_"]').val('');
         if (row.translations && Array.isArray(row.translations)) {
            row.translations.forEach(function (trans) {
                const langId = trans.language_id;
                $('#edit_translations_' + langId + '_name').val(trans.name);
                $('#edit_translations_' + langId + '_description').val(trans.description);
            });
        }
        if (row.filter === "price_criteria") {
            $('.price_criteria').show();
            $('#edit_min_price').val(row.min_price);
            $('#edit_max_price').val(row.max_price);
        } else {
            $('.price_criteria').hide();
            $('#edit_min_price').val();
            $('#edit_max_price').val();
        }
        if (row.filter == "category_criteria") {
            $('.category_criteria').show();
            if (row.value != '') {
                $('#edit_category_id').val(row.value.split(',')).trigger('change');
            } else {
                $('#edit_category_id').val('').trigger('change');
            }
        } else {
            $('.category_criteria').hide();
            $('#edit_category_id').val('').trigger('change');
        }

        $('input[name="style"]').attr('checked', false);
        $('input[name="style"][value="' + row.style + '"]').attr('checked', true);
    }
};

window.staffEvents = {
    'click .edit_btn': function (e, value, row) {
        $('#edit_role').val(row.roles[0].id);
        $('#edit_name').val(row.name);
        $('#edit_email').val(row.email);
    }
}
window.verificationfeildEvents = {
    'click .edit_btn': function (e, value, row) {
        $('#edit_name').val(row.name);
        $('#edit_is_required').val(row.is_required)
    }
}

window.userEvents = {
    'click .assign_package': function (e, value, row) {
        $("#user_id").val(row.id);
        $('.package_type').prop('checked', false);

        // $('#item-listing-package-div').hide();
        // $('#advertisement-package-div').hide();

        $('#advertisement-package').attr('required', false);
        $('#item-listing-package').attr('required', false);

        $('#package_details').hide();
        $('.payment').hide();
        $('.cheque').hide();
    }
}

// window.faqEvents = {
//     'click .edit_btn': function (e, value, row) {
//         $('#edit_question').val(row.question);
//         $('#edit_answer').val(row.answer);
//     }
// }

window.faqEvents = {
    'click .edit_btn': function (e, value, row) {
        let updateUrl = "{{ url('admin/faq') }}/" + row.id;
        $('.edit-form').attr('action', updateUrl);
        $("[id^=edit_question_]").val("");
        $("[id^=edit_answer_]").val("");
        $('#edit_faq_id').val(row.id);
        $("#edit_question_1").val(row.question);
        $("#edit_answer_1").val(row.answer);
        let translations = row.translations ?? [];
        translations.forEach(function (translation) {
            const langId = translation.language_id;
            $("#edit_question_" + langId).val(translation.question);
            $("#edit_answer_" + langId).val(translation.answer);
        });
    }
};

window.areaEvents = {
    'click .edit_btn': function (e, value, row) {
        $('#edit_name').val(row.name);
        $('#edit_country').val(row.country_id);
        $('#edit_state').val(row.state_id);
        $('#edit_city').val(row.city_id);
        $('#edit_latitude').val(row.latitude);
        $('#edit_longitude').val(row.longitude);

        // Initialize map after modal is shown
        $('#editModal').on('shown.bs.modal', function () {
            // Get coordinates from the row data
            const lat = parseFloat(row.latitude) || 0;
            const lng = parseFloat(row.longitude) || 0;

            // Initialize map with current coordinates
            const editMap = window.mapUtils.initializeMap('edit_map', lat, lng);

            // Create a marker at the current position
            let currentMarker = L.marker([lat, lng], {
                draggable: true
            }).addTo(editMap);

            // Update coordinates when marker is dragged
            currentMarker.on('dragend', function(event) {
                const position = event.target.getLatLng();
                $('#edit_latitude').val(position.lat);
                $('#edit_longitude').val(position.lng);
            });

            // Update marker position and coordinates when map is clicked
            editMap.on('click', function(e) {
                const position = e.latlng;
                currentMarker.setLatLng(position);
                $('#edit_latitude').val(position.lat);
                $('#edit_longitude').val(position.lng);
            });
        });

        // Clean up when modal is hidden
        $('#editModal').on('hidden.bs.modal', function () {
            window.mapUtils.removeMap('edit_map');
            $(this).off('shown.bs.modal');
            $(this).off('hidden.bs.modal');
        });
    }
}
window.cityEvents = {
    'click .edit_btn': function (e, value, row) {
        $('#edit_country').val(row.country_id);
        $('#edit_state').val(row.state_id);
        $('#edit_name').val(row.name);
        $('#edit_latitude').val(row.latitude);
        $('#edit_longitude').val(row.longitude);

        // Initialize map after modal is shown
        $('#editModal').on('shown.bs.modal', function () {
            // Get coordinates from the row data
            const lat = parseFloat(row.latitude) || 0;
            const lng = parseFloat(row.longitude) || 0;

            // Initialize map with current coordinates
            const editMap = window.mapUtils.initializeMap('edit_map', lat, lng);

            // Create a marker at the current position
            let currentMarker = L.marker([lat, lng], {
                draggable: true
            }).addTo(editMap);

            // Update coordinates when marker is dragged
            currentMarker.on('dragend', function(event) {
                const position = event.target.getLatLng();
                $('#edit_latitude').val(position.lat);
                $('#edit_longitude').val(position.lng);
            });

            // Update marker position and coordinates when map is clicked
            editMap.on('click', function(e) {
                const position = e.latlng;
                currentMarker.setLatLng(position);
                $('#edit_latitude').val(position.lat);
                $('#edit_longitude').val(position.lng);
            });
        });

        // Clean up when modal is hidden
        $('#editModal').on('hidden.bs.modal', function () {
            window.mapUtils.removeMap('edit_map');
            $('#edit_map').html('');
            $(this).off('shown.bs.modal');
            $(this).off('hidden.bs.modal');
        });
    }
}
window.verificationEvents = {
    'click .view-verification-fields': function (e, value, row) {
        let tabs = '<ul class="nav nav-tabs" role="tablist">';
        let content = '<div class="tab-content mt-3">';

        $.each(row.languages, function (index, lang) {
            let activeClass = index === 0 ? 'active' : '';
            let showClass   = index === 0 ? 'show active' : '';

            // Tab header
            tabs += `
                <li class="nav-item">
                    <button class="nav-link ${activeClass}" data-bs-toggle="tab" data-bs-target="#lang-${lang.id}">
                        ${lang.name}
                    </button>
                </li>
            `;

            // Tab body
            content += `<div class="tab-pane fade ${showClass}" id="lang-${lang.id}">`;
            content += `<table class="table">
                <tr>
                    <th width="10%">${trans("No.")}</th>
                    <th width="25%">${trans("Name")}</th>
                    <th width="65%">${trans("Value")}</th>
                </tr>`;

            let count = 1;
            console.log(row.verification_field_values);
            $.each(row.verification_field_values, function (key, field) {
                // ✅ Filter based on language for this tab
                let showField = false;
                if (lang.id === 1 && (field.language_id === null || field.language_id === 1)) {
                    showField = true;
                } else if (field.language_id === lang.id) {
                    showField = true;
                }

                if (showField) {
                    let fieldName = field.verification_field.name;
                    let fieldValue = field.value;

                    let displayValue = '';
                    if (fieldValue) {
                        if (typeof fieldValue === 'string' && fieldValue.includes('verification_field_files')) {
                            displayValue = `<a class='text-decoration-underline' href='${fieldValue}' target='_blank'>${trans('Click Here')}</a>`;
                        } else {
                            displayValue = Array.isArray(fieldValue) ? fieldValue.join(', ') : fieldValue;
                        }
                    } else {
                        displayValue = trans('No value provided');
                    }

                    content += `<tr>
                        <td>${count}</td>
                        <td>${fieldName}</td>
                        <td class="text-break">${displayValue}</td>
                    </tr>`;
                    count++;
                }
            });

            content += `</table></div>`;
        });

        tabs += '</ul>';
        content += '</div>';

        $('#verification_fields').html(tabs + content);
        $('#editModal').modal('show');
    },

    'click .edit_btn': function (e, value, row) {
        $('#status').val(row.status).trigger('change');
        $('#rejection_reason').val(row.rejection_reason);
    }
};
window.reviewReportEvents = {
    'click .edit-status': function (e, value, row) {
        $('#report_status').val(row.report_status).trigger('change');
        $('#report_rejected_reason').val(row.report_rejected_reason);
    }
}
