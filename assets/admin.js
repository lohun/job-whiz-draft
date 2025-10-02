jQuery(document).ready(function ($) {

    // Application Details Modal
    window.viewApplication = function (applicationId) {
        showLoading();

        $.ajax({
            url: fisibul_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_application_details',
                application_id: applicationId,
                nonce: fisibul_admin_ajax.nonce
            },
            success: function (response) {
                hideLoading();

                if (response.success) {
                    displayApplicationDetails(response.data);
                    $('#application-modal').show();
                } else {
                    alert('Error loading application details: ' + response.data);
                }
            },
            error: function () {
                hideLoading();
                alert('Error loading application details. Please try again.');
            }
        });
    };

    function displayApplicationDetails(app) {
        let modalHtml = `
            <div class="modal-header">
                <h2>Application Details - ${app.first_name} ${app.last_name}</h2>
                <span class="close">&times;</span>
            </div>
            <div id="modal-body">
                <div class="application-detail">
                    <h3>Personal Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Full Name:</span>
                        <span class="detail-value">${app.first_name} ${app.middle_name || ''} ${app.last_name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">${app.email}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value">${app.phone}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date of Birth:</span>
                        <span class="detail-value">${app.date_of_birth}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value">${app.address}</span>
                    </div>
                </div>
                
                <div class="application-detail">
                    <h3>Educational Background</h3>
                    <div class="detail-row">
                        <span class="detail-label">Education Level:</span>
                        <span class="detail-value">${app.education_level}${app.education_other ? ' - ' + app.education_other : ''}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Institution:</span>
                        <span class="detail-value">${app.institution}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Course of Study:</span>
                        <span class="detail-value">${app.course_study}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Level:</span>
                        <span class="detail-value">${app.level || 'Not specified'}</span>
                    </div>
                </div>
                
                <div class="application-detail">
                    <h3>Track & Skills</h3>
                    <div class="detail-row">
                        <span class="detail-label">Preferred Track:</span>
                        <span class="detail-value">${app.preferred_track}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Experience Level:</span>
                        <span class="detail-value">${app.experience_level}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Digital Tools:</span>
                        <span class="detail-value">${app.digital_tools}</span>
                    </div>
                </div>
                
                <div class="application-detail">
                    <h3>Motivation</h3>
                    <div class="detail-row">
                        <span class="detail-label">Passion for Content:</span>
                        <span class="detail-value">${app.passion_content}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Interest Reason:</span>
                        <span class="detail-value">${app.interest_reason}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Career Goals:</span>
                        <span class="detail-value">${app.career_goals}</span>
                    </div>
                </div>
                
                <div class="application-detail">
                    <h3>Availability & Resources</h3>
                    <div class="detail-row">
                        <span class="detail-label">Access to Computer:</span>
                        <span class="detail-value">${app.access_computer}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Access to Internet:</span>
                        <span class="detail-value">${app.access_internet}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Availability:</span>
                        <span class="detail-value">${app.availability}</span>
                    </div>
                </div>
                
                <div class="application-detail">
                    <h3>Portfolio & Experience</h3>
                    <div class="detail-row">
                        <span class="detail-label">Has Portfolio:</span>
                        <span class="detail-value">${app.has_portfolio}</span>
                    </div>
                    ${app.portfolio_link ? `
                    <div class="detail-row">
                        <span class="detail-label">Portfolio Link:</span>
                        <span class="detail-value"><a href="${app.portfolio_link}" target="_blank">${app.portfolio_link}</a></span>
                    </div>
                    ` : ''}
                    ${app.previous_experience ? `
                    <div class="detail-row">
                        <span class="detail-label">Previous Experience:</span>
                        <span class="detail-value">${app.previous_experience}</span>
                    </div>
                    ` : ''}
                </div>
                
                <div class="application-detail">
                    <h3>Additional Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Heard About:</span>
                        <span class="detail-value">${app.heard_about}${app.heard_other ? ' - ' + app.heard_other : ''}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Full Name (Signature):</span>
                        <span class="detail-value">${app.full_name_signature}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Submission Date:</span>
                        <span class="detail-value">${new Date(app.submission_date).toLocaleString()}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="status-${app.status}">${app.status.charAt(0).toUpperCase() + app.status.slice(1)}</span>
                        </span>
                    </div>
                </div>
            </div>
        `;

        $('#application-modal .modal-content').html(modalHtml);
    }

    // Close modal
    $(document).on('click', '.close, #application-modal', function (e) {
        if (e.target === this) {
            $('#application-modal').hide();
        }
    });

    // Prevent modal from closing when clicking inside
    $(document).on('click', '.modal-content', function (e) {
        e.stopPropagation();
    });

    // Close modal with Escape key
    $(document).keyup(function (e) {
        if (e.keyCode === 27) { // Escape key
            $('#application-modal').hide();
        }
    });

    // Export functionality
    $('#export-form').on('submit', function (e) {
        e.preventDefault();
        showLoading('Preparing export...');

        // Create a temporary form to submit the export request
        const form = $('<form>', {
            'method': 'POST',
            'action': fisibul_admin_ajax.ajax_url
        });

        // Add form fields
        $(this).find('input, select').each(function () {
            form.append($('<input>', {
                'type': 'hidden',
                'name': $(this).attr('name'),
                'value': $(this).val()
            }));
        });

        // Add action and nonce
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'action',
            'value': 'export_applications'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'export_nonce',
            'value': fisibul_admin_ajax.nonce
        }));

        // Submit form
        form.appendTo('body').submit();

        // Hide loading after a short delay
        setTimeout(function () {
            hideLoading();
        }, 3000);
    });

    // Bulk actions for applications
    $('#bulk-action-selector-top, #bulk-action-selector-bottom').change(function () {
        const action = $(this).val();
        const selectedIds = [];

        $('input[name="application[]"]:checked').each(function () {
            selectedIds.push($(this).val());
        });

        if (action && selectedIds.length > 0) {
            if (confirm(`Are you sure you want to ${action} ${selectedIds.length} application(s)?`)) {
                performBulkAction(action, selectedIds);
            }
        }

        $(this).val('');
    });

    function performBulkAction(action, ids) {
        showLoading(`Performing ${action} action...`);

        $.ajax({
            url: fisibul_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bulk_update_applications',
                bulk_action: action,
                application_ids: ids,
                nonce: fisibul_admin_ajax.nonce
            },
            success: function (response) {
                hideLoading();

                if (response.success) {
                    location.reload(); // Refresh the page to show updated data
                } else {
                    alert('Error performing bulk action: ' + response.data);
                }
            },
            error: function () {
                hideLoading();
                alert('Error performing bulk action. Please try again.');
            }
        });
    }

    // Filter and search functionality
    $('#application-search').on('input', debounce(function () {
        const searchTerm = $(this).val().toLowerCase();
        filterApplications(searchTerm);
    }, 300));

    $('#status-filter, #track-filter').change(function () {
        const searchTerm = $('#application-search').val().toLowerCase();
        filterApplications(searchTerm);
    });

    function filterApplications(searchTerm) {
        const statusFilter = $('#status-filter').val();
        const trackFilter = $('#track-filter').val();

        $('tbody tr').each(function () {
            const $row = $(this);
            const name = $row.find('td:nth-child(2)').text().toLowerCase();
            const email = $row.find('td:nth-child(3)').text().toLowerCase();
            const status = $row.find('td:nth-child(6) span').text().toLowerCase();
            const track = $row.find('td:nth-child(5)').text().toLowerCase();

            let show = true;

            // Text search
            if (searchTerm && !name.includes(searchTerm) && !email.includes(searchTerm)) {
                show = false;
            }

            // Status filter
            if (statusFilter && statusFilter !== 'all' && !status.includes(statusFilter)) {
                show = false;
            }

            // Track filter
            if (trackFilter && trackFilter !== 'all' && !track.includes(trackFilter)) {
                show = false;
            }

            $row.toggle(show);
        });
    }

    // Statistics update
    function updateStatistics() {
        $.ajax({
            url: fisibul_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_application_statistics',
                nonce: fisibul_admin_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    const stats = response.data;
                    $('.stat-box:nth-child(1) h3').text(stats.total);
                    $('.stat-box:nth-child(2) h3').text(stats.pending);
                    $('.stat-box:nth-child(3) h3').text(stats.approved);
                    $('.stat-box:nth-child(4) h3').text(stats.rejected);
                }
            }
        });
    }

    // Auto-refresh statistics every 30 seconds
    setInterval(updateStatistics, 30000);

    // Utility functions
    function showLoading(message = 'Loading...') {
        if ($('.loading-overlay').length === 0) {
            $('body').append(`
                <div class="loading-overlay">
                    <div style="text-align: center;">
                        <div class="loading-spinner"></div>
                        <p style="margin-top: 15px; font-weight: 600;">${message}</p>
                    </div>
                </div>
            `);
        }
    }

    function hideLoading() {
        $('.loading-overlay').remove();
    }

    function debounce(func, wait, immediate) {
        let timeout;
        return function () {
            const context = this, args = arguments;
            const later = function () {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    // Initialize tooltips
    $('[title]').each(function () {
        $(this).tooltip({
            position: {
                my: "center bottom-20", at: "center top", using: function (position, feedback) {
                    $(this).css(position);
                    $("<div>")
                        .addClass("arrow")
                        .addClass(feedback.vertical)
                        .addClass(feedback.horizontal)
                        .appendTo(this);
                }
            }
        });
    });

    // Initialize date pickers if available
    if ($.fn.datepicker) {
        $('input[type="date"]').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }
});