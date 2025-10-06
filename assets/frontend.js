jQuery(document).ready(function ($) {
    let currentStep = 1;
    const totalSteps = 7;

    // Initialize multi-step form
    initMultiStepForm();

    function initMultiStepForm() {
        updateProgressBar();
        updateNavigationButtons();

        // Next button click
        $('.btn-next').click(function () {
            if (validateCurrentStep()) {
                nextStep();
            }
        });

        // Previous button click
        $('.btn-prev').click(function () {
            prevStep();
        });
    }

    function nextStep() {
        if (currentStep < totalSteps) {
            $('.form-step[data-step="' + currentStep + '"]').removeClass('active').addClass('slide-out');
            currentStep++;

            setTimeout(function () {
                $('.form-step').removeClass('slide-out slide-in');
                $('.form-step[data-step="' + currentStep + '"]').addClass('active slide-in');
                updateProgressBar();
                updateNavigationButtons();
                scrollToTop();
            }, 150);
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            $('.form-step[data-step="' + currentStep + '"]').removeClass('active').addClass('slide-out');
            currentStep--;

            setTimeout(function () {
                $('.form-step').removeClass('slide-out slide-in');
                $('.form-step[data-step="' + currentStep + '"]').addClass('active slide-in');
                updateProgressBar();
                updateNavigationButtons();
                scrollToTop();
            }, 150);
        }
    }

    function updateProgressBar() {
        const progressPercentage = (currentStep / totalSteps) * 100;
        $('.progress-fill').css('width', progressPercentage + '%');
        $('.current-step').text(currentStep);
        $('.total-steps').text(totalSteps);
    }

    function updateNavigationButtons() {
        // Show/hide previous button
        if (currentStep === 1) {
            $('.btn-prev').hide();
        } else {
            $('.btn-prev').show();
        }

        // Show/hide next/submit button
        if (currentStep === totalSteps) {
            $('.btn-next').hide();
            $('.btn-submit').show();
        } else {
            $('.btn-next').show();
            $('.btn-submit').hide();
        }
    }

    function scrollToTop() {
        $('.fisibul-form-container')[0].scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    function validateCurrentStep() {
        let isValid = true;
        const currentStepElement = $('.form-step[data-step="' + currentStep + '"]');

        // Clear previous errors
        currentStepElement.find('.error-message').remove();
        currentStepElement.find('.error').removeClass('error');


        // Validate required fields in current step
        currentStepElement.find('input[required], select[required], textarea[required]').each(function () {
            if (!$(this).val() || $(this).val().trim() === '') {
                $(this).addClass('error');
                isValid = false;
            }
        });

        // Step-specific validations
        switch (currentStep) {
            case 1: // Personal Information
                isValid = validatePersonalInfo(currentStepElement) && isValid;
                break;
            case 2: // Educational Background
                isValid = validateEducation(currentStepElement) && isValid;
                break;
            case 3: // Motivation
                isValid = validateMotivation(currentStepElement) && isValid;
                break;
            case 4: // Track & Skills
                isValid = validateTrackSkills(currentStepElement) && isValid;
                break;
            case 5: // Availability
                isValid = validateAvailability(currentStepElement) && isValid;
                break;
            case 7: // Declaration
                isValid = validateDeclaration(currentStepElement) && isValid;
                break;
        }

        // Update step header style
        if (isValid) {
            currentStepElement.find('.step-header').removeClass('error').addClass('valid');
        } else {
            currentStepElement.find('.step-header').removeClass('valid').addClass('error');
        }

        return isValid;
    }

    function validatePersonalInfo(stepElement) {
        let isValid = true;

        // Email validation
        const email = stepElement.find('#email').val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            stepElement.find('#email').addClass('error');
            stepElement.find('#email').after('<span class="error-message">Please enter a valid email address.</span>');
            isValid = false;
        }

        // Age validation (minimum 16 years)
        const dob = new Date(stepElement.find('#date_of_birth').val());
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
            age--;
        }

        if (stepElement.find('#date_of_birth').val() && age < 16) {
            stepElement.find('#date_of_birth').addClass('error');
            stepElement.find('#date_of_birth').after('<span class="error-message">You must be at least 16 years old to apply.</span>');
            isValid = false;
        }

        return isValid;
    }

    function validateEducation(stepElement) {
        let isValid = true;

        // Check if "Other" is selected and other field is filled
        if (stepElement.find('#education_level').val() === 'other' && !stepElement.find('#education_other').val()) {
            stepElement.find('#education_other').addClass('error');
            stepElement.find('#education_other').after('<span class="error-message">Please specify your education level.</span>');
            isValid = false;
        }

        return isValid;
    }

    function validateMotivation(stepElement) {
        let isValid = true;

        // Check if passion question is answered
        if (!stepElement.find('input[name="passion_content"]:checked').length) {
            stepElement.find('.radio-group').addClass('error');
            stepElement.find('.radio-group').after('<span class="error-message">Please select an option.</span>');
            isValid = false;
        }

        return isValid;
    }

    function validateTrackSkills(stepElement) {
        let isValid = true;

        // Check if at least one digital tool is selected
        const digitalTools = stepElement.find('input[name="digital_tools[]"]:checked');
        if (digitalTools.length === 0) {
            stepElement.find('.checkbox-group').addClass('error');
            stepElement.find('.checkbox-group').after('<span class="error-message">Please select at least one digital tool.</span>');
            isValid = false;
        }

        return isValid;
    }

    function validateAvailability(stepElement) {
        let isValid = true;

        // Check required radio buttons
        if (!stepElement.find('input[name="access_computer"]:checked').length) {
            stepElement.find('input[name="access_computer"]').closest('.form-group').addClass('error');
            isValid = false;
        }

        if (!stepElement.find('input[name="access_internet"]:checked').length) {
            stepElement.find('input[name="access_internet"]').closest('.form-group').addClass('error');
            isValid = false;
        }

        return isValid;
    }

    function validateDeclaration(stepElement) {
        let isValid = true;

        // Check declaration checkboxes
        const declarationRead = stepElement.find('input[name="declaration_read"]:checked');
        const declarationCommit = stepElement.find('input[name="declaration_commit"]:checked');

        if (declarationRead.length === 0 || declarationCommit.length === 0) {
            stepElement.find('.checkbox-group').addClass('error');
            stepElement.find('.checkbox-group').after('<span class="error-message">You must agree to both declarations.</span>');
            isValid = false;
        }

        return isValid;
    }

    // Show/hide conditional fields
    $('#education_level').change(function () {
        if ($(this).val() === 'other') {
            $('#education_other_group').slideDown();
            $('#education_other').prop('required', true);
        } else {
            $('#education_other_group').slideUp();
            $('#education_other').prop('required', false);
        }
    });

    // Show/hide portfolio link field
    $('input[name="has_portfolio"]').change(function () {
        if ($(this).val() === 'yes') {
            $('#portfolio_link_group').slideDown();
        } else {
            $('#portfolio_link_group').slideUp();
        }
    });

    // Show/hide heard about other field
    $('#heard_about').change(function () {
        if ($(this).val() === 'other') {
            $('#heard_other_group').slideDown();
            $('#heard_other').prop('required', true);
        } else {
            $('#heard_other_group').slideUp();
            $('#heard_other').prop('required', false);
        }
    });

    // Show/hide digital tools other field
    $('input[name="digital_tools[]"][value="other"]').change(function () {
        if ($(this).is(':checked')) {
            $('#digital_tools_other').slideDown();
        } else {
            $('#digital_tools_other').slideUp();
        }
    });

    // Form submission
    $('#fisibul-internship-form').submit(function (e) {
        e.preventDefault();

        // Final validation of all steps
        if (!validateAllSteps()) {
            return;
        }

        const submitBtn = $('.btn-submit');
        const loading = $('.loading');

        // Show loading state
        submitBtn.prop('disabled', true);
        loading.show();
        $('#form-message').hide();

        // Prepare form data
        let formData = new FormData(this);
        formData.append('action', 'submit_internship_form');
        formData.append('fisibul_nonce', fisibul_ajax.nonce);

        // Submit via AJAX
        $.ajax({
            url: fisibul_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                submitBtn.prop('disabled', false);
                loading.hide();

                if (response.success) {
                    $('#form-message').html('<div class="form-message success">' + response.data + '</div>').show();
                    $('#fisibul-internship-form')[0].reset();
                    // Reset to first step
                    currentStep = 1;
                    $('.form-step').removeClass('active');
                    $('.form-step[data-step="1"]').addClass('active');
                    updateProgressBar();
                    updateNavigationButtons();
                    // Hide conditional fields
                    $('#education_other_group, #portfolio_link_group, #heard_other_group, #digital_tools_other').hide();
                } else {
                    $('#form-message').html('<div class="form-message error">' + response.data + '</div>').show();
                }

                scrollToTop();
            },
            error: function () {
                submitBtn.prop('disabled', false);
                loading.hide();
                $('#form-message').html('<div class="form-message error">An error occurred. Please try again.</div>').show();
                scrollToTop();
            }
        });
    });

    function validateAllSteps() {
        let allValid = true;

        for (let step = 1; step <= totalSteps; step++) {
            currentStep = step;
            if (!validateCurrentStep()) {
                allValid = false;
                // Go to first invalid step
                if (allValid === false) {
                    $('.form-step').removeClass('active');
                    $('.form-step[data-step="' + step + '"]').addClass('active');
                    updateProgressBar();
                    updateNavigationButtons();
                    scrollToTop();
                    return false;
                }
            }
        }

        return allValid;
    }

    // Real-time validation feedback
    $('input, select, textarea').on('blur', function () {
        $(this).removeClass('error');
        $(this).siblings('.error-message').remove();

        if ($(this).prop('required') && (!$(this).val() || $(this).val().trim() === '')) {
            $(this).addClass('error');
        }

        // Email validation on blur
        if ($(this).attr('type') === 'email' && $(this).val()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test($(this).val())) {
                $(this).addClass('error');
                $(this).after('<span class="error-message">Please enter a valid email address.</span>');
            }
        }
    });

    // Auto-save form data to prevent data loss
    const formId = 'fisibul-internship-form';
    const storageKey = 'fisibul_form_data';
    const stepKey = 'fisibul_current_step';

    // Save form data and current step
    function saveFormData() {
        const formData = {};
        $('#' + formId + ' input, #' + formId + ' select, #' + formId + ' textarea').each(function () {
            const name = $(this).attr('name');
            const type = $(this).attr('type');

            if (type === 'checkbox' || type === 'radio') {
                if ($(this).is(':checked')) {
                    if (formData[name]) {
                        if (Array.isArray(formData[name])) {
                            formData[name].push($(this).val());
                        } else {
                            formData[name] = [formData[name], $(this).val()];
                        }
                    } else {
                        formData[name] = $(this).val();
                    }
                }
            } else {
                formData[name] = $(this).val();
            }
        });

        sessionStorage.setItem(storageKey, JSON.stringify(formData));
        sessionStorage.setItem(stepKey, currentStep);
    }

    // Load form data and restore step
    function loadFormData() {
        const savedData = sessionStorage.getItem(storageKey);
        const savedStep = sessionStorage.getItem(stepKey);

        if (savedData) {
            try {
                const formData = JSON.parse(savedData);

                Object.keys(formData).forEach(function (name) {
                    const element = $('#' + formId + ' [name="' + name + '"]');
                    const type = element.attr('type');

                    if (type === 'checkbox' || type === 'radio') {
                        if (Array.isArray(formData[name])) {
                            formData[name].forEach(function (value) {
                                $('#' + formId + ' [name="' + name + '"][value="' + value + '"]').prop('checked', true);
                            });
                        } else {
                            $('#' + formId + ' [name="' + name + '"][value="' + formData[name] + '"]').prop('checked', true);
                        }
                    } else {
                        element.val(formData[name]);
                    }
                });

                // Trigger change events to show conditional fields
                $('#education_level, input[name="has_portfolio"], #heard_about').trigger('change');
                $('input[name="digital_tools[]"][value="other"]').trigger('change');
            } catch (e) {
                console.log('Error loading saved form data:', e);
            }
        }

        // Restore step
        if (savedStep && savedStep > 1) {
            currentStep = parseInt(savedStep);
            $('.form-step').removeClass('active');
            $('.form-step[data-step="' + currentStep + '"]').addClass('active');
            updateProgressBar();
            updateNavigationButtons();
        }
    }

    // Auto-save every 10 seconds
    setInterval(saveFormData, 10000);

    // Save on form input and step change
    $('#' + formId).on('input change', function () {
        saveFormData();
    });

    $('.btn-next, .btn-prev').on('click', function () {
        setTimeout(saveFormData, 100);
    });

    // Load saved data on page load
    loadFormData();

    // Clear saved data on successful submission
    $(document).ajaxSuccess(function (event, xhr, settings) {
        if (settings.data && settings.data.has('submit_internship_form')) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                sessionStorage.removeItem(storageKey);
                sessionStorage.removeItem(stepKey);
            }
        }
    });

    // Keyboard navigation
    $(document).keydown(function (e) {
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'SELECT') {
            if (e.keyCode === 37 && currentStep > 1) { // Left arrow
                e.preventDefault();
                prevStep();
            } else if (e.keyCode === 39 && currentStep < totalSteps) { // Right arrow
                e.preventDefault();
                if (validateCurrentStep()) {
                    nextStep();
                }
            }
        }
    });
});