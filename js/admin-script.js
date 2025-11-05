/**
 * Modern Admin JavaScript for Approval Plugin
 */

jQuery(document).ready(function($) {

    // ========================================
    // Modal Functionality
    // ========================================
    var modal = $('#approval-modal');
    var modalBody = $('#approval-modal-body');
    var closeBtn = $('.approval-close');

    // Close modal when clicking the X
    closeBtn.on('click', function() {
        modal.fadeOut(300);
    });

    // Close modal when clicking outside of it
    $(window).on('click', function(event) {
        if (event.target == modal[0]) {
            modal.fadeOut(300);
        }
    });

    // Close modal on ESC key
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27 && modal.is(':visible')) {
            modal.fadeOut(300);
        }
    });

    // ========================================
    // View Request Details (AJAX)
    // ========================================
    $(document).on('click', '.view-details', function(e) {
        e.preventDefault();

        var requestId = $(this).data('id');

        // Show modal with loading state
        modalBody.html('<div class="loading-spinner"><span class="dashicons dashicons-update spinning"></span> Loading...</div>');
        modal.fadeIn(300);

        // AJAX request to get details
        $.ajax({
            url: approval_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'approval_get_request_details',
                request_id: requestId,
                nonce: approval_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    modalBody.html(response.data.html);
                } else {
                    modalBody.html('<div class="error-message"><p>' + (response.data.message || 'Error loading request details.') + '</p></div>');
                }
            },
            error: function() {
                modalBody.html('<div class="error-message"><p>An error occurred while loading request details.</p></div>');
            }
        });
    });

    // ========================================
    // Quick Approve/Reject from Table
    // ========================================
    $(document).on('click', '.quick-approve, .quick-reject', function(e) {
        e.preventDefault();

        var button = $(this);
        var requestId = button.data('id');
        var isApprove = button.hasClass('quick-approve');
        var status = isApprove ? 'approved' : 'rejected';
        var action = isApprove ? 'approve' : 'reject';

        // Confirmation
        if (!confirm('Are you sure you want to ' + action + ' this request?')) {
            return;
        }

        // Disable button
        button.prop('disabled', true).text(isApprove ? 'Approving...' : 'Rejecting...');

        // AJAX request
        $.ajax({
            url: approval_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'approval_update_request',
                request_id: requestId,
                status: status,
                admin_notes: '',
                nonce: approval_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotification(response.data.message, 'success');

                    // Reload page after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data.message || 'Failed to update request.', 'error');
                    button.prop('disabled', false).text(isApprove ? 'Approve' : 'Reject');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
                button.prop('disabled', false).text(isApprove ? 'Approve' : 'Reject');
            }
        });
    });

    // ========================================
    // Modal Approve/Reject
    // ========================================
    $(document).on('click', '.modal-approve, .modal-reject', function(e) {
        e.preventDefault();

        var button = $(this);
        var requestId = button.data('id');
        var isApprove = button.hasClass('modal-approve');
        var status = isApprove ? 'approved' : 'rejected';
        var adminNotes = $('#modal-admin-notes').val();

        // Disable buttons
        $('.modal-approve, .modal-reject').prop('disabled', true);
        button.html('<span class="dashicons dashicons-update spinning"></span> Processing...');

        // AJAX request
        $.ajax({
            url: approval_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'approval_update_request',
                request_id: requestId,
                status: status,
                admin_notes: adminNotes,
                nonce: approval_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    modalBody.html('<div class="success-message" style="text-align: center; padding: 40px;"><span class="dashicons dashicons-yes-alt" style="font-size: 64px; color: #10b981; width: 64px; height: 64px;"></span><h3>' + response.data.message + '</h3><p>Refreshing page...</p></div>');

                    // Reload page after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data.message || 'Failed to update request.', 'error');
                    $('.modal-approve, .modal-reject').prop('disabled', false);
                    button.html(isApprove ? '<span class="dashicons dashicons-yes"></span> Approve' : '<span class="dashicons dashicons-no"></span> Reject');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
                $('.modal-approve, .modal-reject').prop('disabled', false);
                button.html(isApprove ? '<span class="dashicons dashicons-yes"></span> Approve' : '<span class="dashicons dashicons-no"></span> Reject');
            }
        });
    });

    // ========================================
    // Bulk Actions
    // ========================================
    $('#cb-select-all').on('change', function() {
        var checked = $(this).prop('checked');
        $('input[name="request_ids[]"]').prop('checked', checked);
    });

    // Form submission validation
    $('#approval-bulk-form').on('submit', function(e) {
        var action = $('#bulk-action-selector').val();
        var checkedCount = $('input[name="request_ids[]"]:checked').length;

        if (action === '-1') {
            e.preventDefault();
            alert('Please select an action.');
            return false;
        }

        if (checkedCount === 0) {
            e.preventDefault();
            alert('Please select at least one request.');
            return false;
        }

        var actionText = action === 'approve' ? 'approve' : 'reject';
        if (!confirm('Are you sure you want to ' + actionText + ' ' + checkedCount + ' request(s)?')) {
            e.preventDefault();
            return false;
        }
    });

    // ========================================
    // Notification System
    // ========================================
    function showNotification(message, type) {
        // Remove existing notifications
        $('.approval-notification').remove();

        var icon = type === 'success' ? 'yes-alt' : 'warning';
        var bgColor = type === 'success' ? '#10b981' : '#ef4444';

        var notification = $('<div class="approval-notification" style="position: fixed; top: 32px; right: 20px; background: ' + bgColor + '; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 999999; display: flex; align-items: center; gap: 10px; animation: slideInRight 0.3s ease;"><span class="dashicons dashicons-' + icon + '"></span><span>' + message + '</span></div>');

        $('body').append(notification);

        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    // ========================================
    // Row Actions
    // ========================================
    $('.row-actions').css('visibility', 'visible');

    // ========================================
    // Add CSS animation
    // ========================================
    var style = $('<style>@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }</style>');
    $('head').append(style);

    // ========================================
    // Show bulk update success message
    // ========================================
    var urlParams = new URLSearchParams(window.location.search);
    var bulkUpdated = urlParams.get('bulk_updated');

    if (bulkUpdated) {
        showNotification(bulkUpdated + ' request(s) updated successfully!', 'success');

        // Remove parameter from URL
        var newUrl = window.location.pathname + '?page=approval-requests';
        var status = urlParams.get('status');
        if (status) {
            newUrl += '&status=' + status;
        }
        window.history.replaceState({}, document.title, newUrl);
    }

    // ========================================
    // Keyboard Shortcuts
    // ========================================
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + R for refresh (allow default)
        // ESC to close modal (handled above)

        // Alt + A to select all
        if (e.altKey && e.keyCode === 65) {
            e.preventDefault();
            $('#cb-select-all').prop('checked', true).trigger('change');
        }
    });

    // ========================================
    // Initialize tooltips
    // ========================================
    if (typeof $.fn.tooltip === 'function') {
        $('[title]').tooltip({
            position: {
                my: 'center bottom-10',
                at: 'center top'
            }
        });
    }

    // ========================================
    // Smooth scrolling for status filters
    // ========================================
    $('.subsubsub a').on('click', function() {
        $('html, body').animate({
            scrollTop: 0
        }, 300);
    });

    // ========================================
    // Table row highlighting
    // ========================================
    $('.approval-requests-table tbody tr').hover(
        function() {
            $(this).css('box-shadow', '0 2px 8px rgba(0,0,0,0.08)');
        },
        function() {
            $(this).css('box-shadow', 'none');
        }
    );

    // ========================================
    // Auto-refresh indicator (optional)
    // ========================================
    var autoRefreshEnabled = false;
    var autoRefreshInterval;

    // You can add a button to toggle auto-refresh if needed
    // This is just a placeholder for future enhancement

    // ========================================
    // Statistics Cards Animation on Page Load
    // ========================================
    $('.approval-stat-card').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        }).delay(index * 100).animate({
            'opacity': '1'
        }, {
            duration: 500,
            step: function(now) {
                $(this).css('transform', 'translateY(' + (20 - (now * 20)) + 'px)');
            }
        });
    });

    // ========================================
    // Number Counter Animation for Stats
    // ========================================
    function animateNumber(element, target) {
        var current = 0;
        var increment = target / 30;
        var timer = setInterval(function() {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }

            if (element.text().indexOf('%') > -1 || element.text().indexOf('h') > -1) {
                var suffix = element.text().match(/[%h]/)[0];
                element.text(Math.round(current) + suffix);
            } else {
                element.text(Math.round(current));
            }
        }, 30);
    }

    // Animate stat numbers on page load
    setTimeout(function() {
        $('.stat-number').each(function() {
            var text = $(this).text();
            var number = parseInt(text);
            if (!isNaN(number)) {
                $(this).text('0');
                animateNumber($(this), number);
            }
        });
    }, 300);

    // ========================================
    // Search Functionality
    // ========================================
    $('#approval-search-input').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();

        $('.approval-requests-table tbody tr').each(function() {
            if ($(this).hasClass('no-search-results')) {
                return;
            }

            var rowText = $(this).text().toLowerCase();

            if (rowText.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Show "no results" message if all rows are hidden
        var visibleRows = $('.approval-requests-table tbody tr:visible:not(.no-search-results)').length;
        if (visibleRows === 0 && searchTerm !== '') {
            if ($('.no-search-results').length === 0) {
                $('.approval-requests-table tbody').append(
                    '<tr class="no-search-results"><td colspan="8" style="text-align: center; padding: 60px 20px; color: #9ca3af;"><div style="text-align: center;"><span class="dashicons dashicons-search" style="font-size: 64px; width: 64px; height: 64px; margin-bottom: 15px; opacity: 0.5;"></span><br><strong style="font-size: 16px; display: block; margin-bottom: 8px;">No requests found</strong><span style="font-size: 14px;">Try adjusting your search terms</span></div></td></tr>'
                );
            }
        } else {
            $('.no-search-results').remove();
        }
    });
});
