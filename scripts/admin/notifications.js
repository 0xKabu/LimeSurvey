/**
 * Notifcation system for admin
 *
 * @since 2017-08-02
 * @author Olle Haerstedt
 */

$(document).ready(function() {

    /**
     * Load widget HTML and inject it
     * @param {object} that The notification link
     * @return
     */
    function updateNotificationWidget(that) {
        console.log('updateNotificationWidget begin');
        // Update notification widget
        $.ajax({
            url: $(that).data('update-url'),
            method: 'GET'
        }).done(function(response) {

            $('#notification-li').replaceWith(response);

            // Re-bind onclick
            initNotification();
        });
    }

    /**
     * Tell system that notification is read
     * @param {object} that The notification link
     * @return
     */
    function notificationIsRead(that) {
        $.ajax({
            url: $(that).data('read-url'),
            method: 'GET',
        }).done(function(response) {
            // Fetch new HTML for menu widget
            updateNotificationWidget(that);
        });

    }

    /**
     * Fetch notification as JSON and show modal
     * @param {object} that The notification link
     * @param {url} URL to fetch notification as JSON
     * @return
     */
    function showNotificationModal(that, url) {
        $.ajax({
            url: url,
            method: 'GET',
        }).done(function(response) {

            var response = JSON.parse(response);
            var not = response.result;

            $('#admin-notification-modal .modal-title').html(not.title);
            $('#admin-notification-modal .modal-body-text').html(not.message);
            $('#admin-notification-modal .modal-content').addClass('panel-' + not.modal_class);
            $('#admin-notification-modal').modal();
            $('#admin-notification-modal').on('hidden.bs.modal', function(e) {
                notificationIsRead(that);
            });
        });
    }

    /**
     * Event function run when notification is clicked
     * @param {object} that The notification link
     * @param {url} URL to fetch notification as JSON
     * @return
     */
    function notificationClicked(that, url) {
        showNotificationModal(that, url);
    }

    /**
     * Bind onclick and stuff
     * @return
     */
    function initNotification() {
        console.log('initNotification begin');
        $('.admin-notification-link').each(function(nr, that) {

            var url = $(that).data('url');
            var type = $(that).data('type');
            console.log('type', type);

            // Important notifications are shown as pop-up on load
            if (type == 'important') {
                showNotificationModal(that, url);
                return false;  // Stop loop
            }

            // Bind click to notification in drop-down
            $(that).on('click', function() {
                notificationClicked(that, url);
            });

        });
    }

    initNotification();

});
