<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 9-12-16
 * Time: 16:02
 */
#region Template
/**
 * This function sets the correct template file for events.
 *
 * @param $archive_template
 *
 * @return string
 */
function mp_ssv_events_template($archive_template)
{
    if (is_post_type_archive('events') && get_theme_support('materialize')) {
        $archive_template = plugin_dir_path(__FILE__) . 'archive-events.php';
    }
    return $archive_template;
}

add_filter('archive_template', 'mp_ssv_events_template');
#endregion

#region Save Event
/**
 * @param $post_ID
 * @param $post_after
 *
 * @return mixed
 */
function mp_ssv_events_save($post_ID, $post_after)
{
    if (get_post_type() != 'events') {
        return $post_ID;
    }
    $event = new Event($post_after);
    if ($event->isPublished() && !$event->isValid()) {
        wp_update_post(
            array(
                'ID'          => $post_ID,
                'post_status' => 'draft',
            )
        );
        update_option(SSV_Events::OPTION_PUBLISH_ERROR, true);
    }
    return $post_ID;
}

add_action('save_post', 'mp_ssv_events_save', 10, 2);
#endregion

#region Admin Notice
/**
 * This function displays the error message thrown by the Save or Update actions of an Event.
 */
function mp_ssv_events_admin_notice()
{
    $screen = get_current_screen();
    if ('events' != $screen->post_type || 'post' != $screen->base) {
        return;
    }
    if (get_option(SSV_Events::OPTION_PUBLISH_ERROR, false)) {
        ?>
        <div class="notice notice-error">
            <p>You cannot publish an event without a start date and time!</p>
        </div>
        <?php
    }
    update_option(SSV_Events::OPTION_PUBLISH_ERROR, false);
}

add_action('admin_notices', 'mp_ssv_events_admin_notice');
#endregion

#region Updated Messages
/**
 * @param string[] $messages is an array of messages displayed after an event is updated.
 *
 * @return string[] the messages.
 */
function mp_ssv_events_updated_messages($messages)
{
    global $post, $post_ID;
    if (get_option(SSV_Events::OPTION_PUBLISH_ERROR, false)) {

        $messages['events'] = array(
            0  => '',
            1  => sprintf(__('Event updated. <a href="%s">View Event</a>'), esc_url(get_permalink($post_ID))),
            2  => __('Custom field updated.'),
            3  => __('Custom field deleted.'),
            4  => __('Event updated.'),
            /* translators: %s: date and time of the revision */
            5  => isset($_GET['revision']) ? sprintf(
                __('Event restored to revision from %s'),
                wp_post_revision_title((int)$_GET['revision'], false)
            ) : false,
            6  => '', //Send a blank string to prevent it from posting that it has been published correctly.
            7  => __('Event saved.'),
            8  => sprintf(
                __('Event submitted. <a target="_blank" href="%s">Preview event</a>'),
                esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))
            ),
            9  => sprintf(
                __('Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>'),
                // translators: Publish box date format, see http://php.net/date
                date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)),
                esc_url(get_permalink($post_ID))
            ),
            10 => sprintf(
                __('Event draft updated. <a target="_blank" href="%s">Preview event</a>'),
                esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))
            ),
        );
    } else {
        $messages['events'] = array(
            0  => '',
            1  => sprintf(__('Event updated. <a href="%s">View Event</a>'), esc_url(get_permalink($post_ID))),
            2  => __('Custom field updated.'),
            3  => __('Custom field deleted.'),
            4  => __('Event updated.'),
            /* translators: %s: date and time of the revision */
            5  => isset($_GET['revision']) ? sprintf(
                __('Event restored to revision from %s'),
                wp_post_revision_title((int)$_GET['revision'], false)
            ) : false,
            6  => sprintf(__('Event published. <a href="%s">View event</a>'), esc_url(get_permalink($post_ID))),
            7  => __('Event saved.'),
            8  => sprintf(
                __('Event submitted. <a target="_blank" href="%s">Preview event</a>'),
                esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))
            ),
            9  => sprintf(
                __('Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>'),
                // translators: Publish box date format, see http://php.net/date
                date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)),
                esc_url(get_permalink($post_ID))
            ),
            10 => sprintf(
                __('Event draft updated. <a target="_blank" href="%s">Preview event</a>'),
                esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))
            ),
        );
    }

    return $messages;
}

add_filter('post_updated_messages', 'mp_ssv_events_updated_messages');
#endregion

#region Post Category
/**
 * This method initializes the post category functionality for Events
 */
function mp_ssv_events_post_category()
{

    $labels = array(
        'name'               => _x('Events', 'events'),
        'singular_name'      => _x('Event', 'events'),
        'add_new'            => _x('Add New', 'events'),
        'add_new_item'       => _x('Add New Event', 'events'),
        'edit_item'          => _x('Edit Event', 'events'),
        'new_item'           => _x('New Event', 'events'),
        'view_item'          => _x('View Event', 'events'),
        'search_items'       => _x('Search Events', 'events'),
        'not_found'          => _x('No Events found', 'events'),
        'not_found_in_trash' => _x('No Events found in Trash', 'events'),
        'parent_item_colon'  => _x('Parent Event:', 'events'),
        'menu_name'          => _x('Events', 'events'),
    );

    $args = array(
        'labels'              => $labels,
        'hierarchical'        => true,
        'description'         => 'Events filterable by category',
        'supports'            => array('title', 'editor', 'author', 'thumbnail', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'page-attributes'),
        'taxonomies'          => array('event_category'),
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-calendar-alt',
        'show_in_nav_menus'   => true,
        'publicly_queryable'  => true,
        'exclude_from_search' => false,
        'has_archive'         => true,
        'query_var'           => true,
        'can_export'          => true,
        'rewrite'             => true,
        'capability_type'     => 'post',
    );

    register_post_type('events', $args);
}

add_action('init', 'mp_ssv_events_post_category');
#endregion

#region Category Taxonomy
/**
 * This function registers a taxonomy for the categories.
 */
function mp_ssv_events_category_taxonomy()
{
    register_taxonomy(
        'event_category',
        'events',
        array(
            'hierarchical' => true,
            'label'        => 'Event Categories',
            'query_var'    => true,
            'rewrite'      => array(
                'slug'       => 'event_category',
                'with_front' => false,
            ),
        )
    );
}

add_action('init', 'mp_ssv_events_category_taxonomy');
#endregion

#region Meta Boxes
/**
 * This method adds the custom Meta Boxes
 */
function mp_ssv_events_meta_boxes()
{
    add_meta_box('ssv_events_registration', 'Registration', 'ssv_events_registration', 'events', 'side', 'default');
    add_meta_box('ssv_events_date', 'Date', 'ssv_events_date', 'events', 'side', 'default');
    add_meta_box('ssv_events_location', 'Location', 'ssv_events_location', 'events', 'side', 'default');
    add_meta_box('ssv_events_registration_fields', 'Registration Fields', 'ssv_events_registration_fields', 'events', 'advanced', 'default');
    add_meta_box('ssv_events_registrations', 'Registrations', 'ssv_events_registrations', 'events', 'advanced', 'default');
}

add_action('add_meta_boxes', 'mp_ssv_events_meta_boxes');

function ssv_events_registration()
{
    global $post;
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Enable Registration</th>
            <td>
                <select name="registration" title="Enable Registration">
                    <option value="disabled" <?= get_post_meta($post->ID, 'registration', true) == 'disabled' ? 'selected' : '' ?>>Disabled</option>
                    <option value="members_only" <?= get_post_meta($post->ID, 'registration', true) == 'members_only' ? 'selected' : '' ?>>Members Only</option>
                    <option value="everyone" <?= get_post_meta($post->ID, 'registration', true) == 'everyone' ? 'selected' : '' ?>>Everypne</option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}

function ssv_events_date()
{
    global $post;
    $start       = get_post_meta($post->ID, 'start', true);
    $start       = $start ?: get_post_meta($post->ID, 'start_date', true) . ' ' . get_post_meta($post->ID, 'start_time', true);
    $end         = get_post_meta($post->ID, 'end', true);
    $end         = $end ?: get_post_meta($post->ID, 'end_date', true) . ' ' . get_post_meta($post->ID, 'end_time', true);
    $placeholder = (new DateTime('now'))->format('Y-m-d H:i');
    ?>
    Start Date<br/>
    <input type="text" class="datetimepicker" name="start" value="<?= $start ?>" placeholder="<?= $placeholder ?>" title="Start Date" required><br/>
    End Date<br/>
    <input type="text" class="datetimepicker" name="end" value="<?= $end ?>" placeholder="<?= $placeholder ?>" title="End Date" required>
    <?php
}

function ssv_events_location()
{
    global $post;
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Location</th>
            <td><input type="text" name="location" value="<?php echo get_post_meta($post->ID, 'location', true); ?>" title="Location"/></td>
        </tr>
    </table>
    <?php
}

function ssv_events_registrations()
{
    global $post;
    global $wpdb;
    $event      = new Event($post);
    $table      = SSV_Events::TABLE_REGISTRATION;
    $sql        = "SELECT * FROM $table WHERE eventID = $post->ID";
    $rows       = $wpdb->get_results($sql);
    $fieldNames = $event->getRegistrationFieldNames();
    SSV_General::var_export($rows, true);
    ?>
    <table cellspacing="5" border="1">
        <tr>
            <?php foreach ($fieldNames as $fieldName): ?>
                <td><?= $fieldName ?></td>
            <?php endforeach; ?>
            <th>Status</th>
        </tr>
        <?php
        $i = 0;
        foreach ($rows as $row) {
            /** @var Registration $registration */
            $registration = Registration::getByID($row->ID);
            ?>
            <tr>
                <?php foreach ($fieldNames as $fieldName): ?>
                    <td><?= $registration->getMeta($fieldName) ?></td>
                <?php endforeach; ?>
                <td>
                    <input type="hidden" name="<?= $i ?>_post" value="<?= $post->ID ?>">
                    <input type="hidden" name="<?= $i ?>_action" value="edit">
                    <input type="hidden" name="<?= $i ?>_registrationID" value="<?= $registration->registrationID ?>">
                    <select name="<?= $i ?>_status">
                        <option value="pending" <?= $registration->status == 'pending' ? 'selected' : '' ?>>pending</option>
                        <option value="approved" <?= $registration->status == 'approved' ? 'selected' : '' ?>>approved</option>
                        <option value="denied" <?= $registration->status == 'denied' ? 'selected' : '' ?>>denied</option>
                    </select>
                </td>
            </tr>
            <?php
            $i++;
        }
        ?>
    </table>
    <?php
}

function ssv_events_registration_fields()
{
    echo Form::fromDatabase(false)->getEditor(false);
}

#endregion

#region Save Meta
/**
 * @param $post_id
 *
 * @return int the post_id
 */
function mp_ssv_events_save_meta($post_id)
{
    if (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }
    $i = 0;
    while (isset($_POST[$i . '_post'])) {
        global $wpdb;
        $wpdb->update(
            $table = SSV_Events::TABLE_REGISTRATION,
            array("registration_status" => SSV_General::sanitize($_POST[$i . '_status'])),
            array("ID" => SSV_General::sanitize($_POST[$i . '_registrationID'])),
            array('%s')
        );
        $i++;
    }
    if (isset($_POST['registration'])) {
        update_post_meta($post_id, 'registration', SSV_General::sanitize($_POST['registration']));
    }
    if (isset($_POST['start'])) {
        update_post_meta($post_id, 'start', SSV_General::sanitize($_POST['start']));
    }
    if (isset($_POST['end'])) {
        update_post_meta($post_id, 'end', SSV_General::sanitize($_POST['end']));
    }
    if (isset($_POST['location'])) {
        update_post_meta($post_id, 'location', SSV_General::sanitize($_POST['location']));
    }

    $registrationFields = Form::fromDatabase();
    $registrationIDs    = array();
    foreach ($registrationFields as $id => $field) {
        /** @var Field $field */
        if (!empty($field->title)) {
            update_post_meta($post_id, Field::PREFIX . $id, $field->toJSON());
            $registrationIDs[] = $id;
        } else {
            delete_post_meta($post_id, Field::PREFIX . $id);
        }
    }
    update_post_meta($post_id, Field::CUSTOM_FIELD_IDS_META, $registrationIDs);
    return $post_id;
}

add_action('save_post_events', 'mp_ssv_events_save_meta');
#endregion