<?php

class ssv_event_category extends WP_Widget
{

    #region Construct
    public function __construct()
    {
        $widget_ops = array(
            'classname'                   => 'widget_event_categories',
            'description'                 => __('A list or dropdown of event categories.'),
            'customize_selective_refresh' => true,
        );
        parent::__construct('event_categories', __('Event Categories'), $widget_ops);
    }
    #endregion

    #region Widget
    public function widget($args, $instance)
    {
        static $first_dropdown = true;

        $title = apply_filters('widget_title', empty($instance['title']) ? __('Event Categories') : $instance['title'], $instance, $this->id_base);

        $c = !empty($instance['count']) ? '1' : '0';
        $h = !empty($instance['hierarchical']) ? '1' : '0';
        $d = !empty($instance['dropdown']) ? '1' : '0';

        echo $args['before_widget'];
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $cat_args = array(
            'orderby'      => 'name',
            'show_count'   => $c,
            'hierarchical' => $h,
        );

        if ($d) {
            $dropdown_id    = ($first_dropdown) ? 'event_cat' : "{$this->id_base}-dropdown-{$this->number}";
            $first_dropdown = false;

            echo '<label class="screen-reader-text" for="' . esc_attr($dropdown_id) . '">' . $title . '</label>';

            $cat_args['show_option_none'] = __('Select Event Category');
            $cat_args['id'] = $dropdown_id;

            $taxonomy       = 'event_category';
            $tax_terms = get_terms($taxonomy);
            ?>
            <select id="<?php echo $dropdown_id; ?>" onchange="onEventCatChange()" title="Select Category">
                <option value="-1">Select Category</option>
                <?php
                $id = 0;
                foreach ($tax_terms as $tax_term) {
                    echo '<option value="' . $id . '">' . $tax_term->name . '</option>';
                    $id++;
                }
                ?>
            </select>
            <script type='text/javascript'>
                /* <![CDATA[ */
                function onEventCatChange() {
                    var dropdown = document.getElementById("<?php echo esc_js($dropdown_id); ?>");
                    if (dropdown.options[dropdown.selectedIndex].value > 0) {
                        location.href = "<?php echo home_url(); ?>/event_category/" + dropdown.options[dropdown.selectedIndex].text;
                    }
                }
                /* ]]> */
            </script>
            <?php
        } else {
            $taxonomy  = 'event_category';
            $tax_terms = get_terms($taxonomy);
            ?>
            <ul>
                <?php
                foreach ($tax_terms as $tax_term) {
                    echo '<li>' . '<a href="' . esc_attr(get_term_link($tax_term, $taxonomy)) . '" title="' . sprintf(__("View all posts in %s"), $tax_term->name) . '" ' . '>' . $tax_term->name . '</a></li>';
                }
                ?>
            </ul>
            <?php
        }

        echo $args['after_widget'];
    }
    #endregion

    #region Update
    public function update($new_instance, $old_instance)
    {
        $instance                 = $old_instance;
        $instance['title']        = SSV_General::sanitize($new_instance['title']);
        $instance['count']        = !empty($new_instance['count']) ? 1 : 0;
        $instance['hierarchical'] = !empty($new_instance['hierarchical']) ? 1 : 0;
        $instance['dropdown']     = !empty($new_instance['dropdown']) ? 1 : 0;

        return $instance;
    }
    #endregion

    #region Form
    public function form($instance)
    {
        //Defaults
        $instance     = wp_parse_args((array)$instance, array('title' => ''));
        $title        = SSV_General::sanitize($instance['title']);
        $count        = isset($instance['count']) ? (bool)$instance['count'] : false;
        $hierarchical = isset($instance['hierarchical']) ? (bool)$instance['hierarchical'] : false;
        $dropdown     = isset($instance['dropdown']) ? (bool)$instance['dropdown'] : false;
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>"/></p>

        <p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>" name="<?php echo $this->get_field_name('dropdown'); ?>"<?php checked($dropdown); ?> />
            <label for="<?php echo $this->get_field_id('dropdown'); ?>"><?php _e('Display as dropdown'); ?></label><br/>

            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>"<?php checked($count); ?> />
            <label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Show post counts'); ?></label><br/>

            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hierarchical'); ?>" name="<?php echo $this->get_field_name('hierarchical'); ?>"<?php checked($hierarchical); ?> />
            <label for="<?php echo $this->get_field_id('hierarchical'); ?>"><?php _e('Show hierarchy'); ?></label></p>
        <?php
    }
    #endregion

}

add_action('widgets_init', create_function('', 'return register_widget("ssv_event_category");'));