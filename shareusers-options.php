<?php
// *** HEADER ***
    // Javascript functions
    echo '<script type="text/javascript" src="'. $this->public_url. '/301a.js"></script>'. "\n";
    echo '<script type="text/javascript">'. "\n";
    echo '//<![CDATA['. "\n";
    echo 'function change_frequence(elem) {'. "\n";
    echo '    var update = document.getElementById("shareusers_update_now")'. "\n";
    echo '    update.disabled = (elem.value != "manually")'. "\n";
    echo '    var minutes = document.getElementById("shareusers_minutes")'. "\n";
    echo '    minutes.disabled = (elem.value != "every_n_minutes")'. "\n";
    echo '}'. "\n";
    echo 'function selectLinkStyle(elem) {'. "\n";
    echo '    switch (elem.name) {'. "\n";
    echo '        case "shareusers_class": document.getElementById("shareusers_style_class").checked = "checked"'. "\n";
    echo '                      break'. "\n";
    echo '        default:      document.getElementById("shareusers_style_user").checked = "checked"'. "\n";
    echo '                      break'. "\n";
    echo '    }'. "\n";
    echo '}'. "\n";
    echo '//]]>'. "\n";
    echo '</script>'. "\n";
    // Title, messages, form,...
    echo '<div class="wrap">';
    echo '<h2>'. __('ShareUsers Options', 'shareusers'). '</h2>';
    if ($_GET['updated'] == 'true') {
        echo '<div id="message" class="updated fade"><p>'. __('Options has been saved', 'shareusers');
        if (isset($this->update_res)) {
            echo '. '. ($this->update_res ? __('Keywords updated', 'shareusers') : __('Keywords not updated', 'shareusers'));
        }
        echo '</p></div>';
    }
    echo '<form method="post" action="options.php">';
    // This is not needed if the version 2.7 function settings_fields is used.
//    settings_fields('options');
    wp_nonce_field('update-options');
    echo '<input type="hidden" name="action" value="update" />';
    echo '<input type="hidden" name="page_options" value="shareusers_autolink,shareusers_newwindow,shareusers_frequence,shareusers_update_now,shareusers_minutes,shareusers_style,shareusers_class,shareusers_color" />';
    // This is not needed if the version 2.7 function settings_fields is used.
    echo '<table class="form-table">';
// *** HEADER ***

// *** PLUGIN CONFIGURATION ***
    echo '<tr valign="top">';
    echo '<th scope="row" colspan="2"><strong>'. __('Plugin configuration', 'shareusers'). '</strong></th>'. "\n";
    echo '</tr>';
    // Remote update frequence
    echo '<tr valign="top">';
    echo '<th scope="row">'. __('Remote update frequence', 'shareusers'). '</th>'. "\n";
    echo '<td>'. "\n";
    $current_value = get_option('shareusers_frequence');
    $values = array(
        'never'       => __('Never', 'shareusers'), 
        'only_once'       => __('Only once (on install)', 'shareusers'), 
        'manually'    => __('Manually', 'shareusers'). '&nbsp;<input id="shareusers_update_now" name="shareusers_update_now" type="checkbox" value="1"'. (($current_value == 'manually') ? '' : ' disabled="disabled"'). ' />&nbsp;'. __('Update now', 'shareusers'), 
        'once_a_day'      => __('Once a day (default and recommended)', 'shareusers'), 
        'every_n_minutes' => __('Every', 'shareusers'). '&nbsp;<input type="text" id="shareusers_minutes" name="shareusers_minutes" value="'. get_option('shareusers_minutes'). '" size="2"'. (($current_value == 'every_n_minutes') ? '' : ' disabled="disabled"'). ' />&nbsp;'. __('minutes', 'shareusers'), 
        'always'      => __('Always (NOT recommended, very slow)', 'shareusers'),
    );
    foreach ($values as $value => $label) {
        echo '<input type="radio" name="shareusers_frequence" value="'. $value. '"';
        echo ($current_value == $value) ? ' checked="checked"' : '';
        echo ' onclick="change_frequence(this)" />'. $label. '<br />'. "\n";
    }
    echo '</td>'. "\n";
    echo '</tr>';
    // Auto link
    echo '<tr valign="top">';
    echo '<th scope="row">'. __('Auto link', 'shareusers'). '</th>'. "\n";
    echo '<td><input name="shareusers_autolink" type="checkbox" value="1"';
    echo get_option('shareusers_autolink') ? ' checked="checked"' : '';
    echo ' />'. "\n";
    echo __('Link keywords automatically', 'shareusers'). "<br />\n";
    echo sprintf(__('Example: If you are suscribed to the keyword "WordPress" and the word "wordpress" appears in your blog, this function will replace "wordpress" with a link to the keyword "WordPress". (%s)', 'shareusers'), $this->_getKwLink('wordpress', NULL, $lang = 'en')). "\n";
    echo '</td>'. "\n";
    echo '</tr>';
// *** PLUGIN CONFIGURATION ***

// *** LINK OPTIONS ***
    echo '<tr valign="top">';
    echo '<th scope="row" colspan="2"><strong>'. __('Link options', 'shareusers'). '</strong></th>'. "\n";
    echo '</tr>';
    // Links style
    echo '<tr valign="top">';
    echo '<th scope="row">'. __('Link style', 'shareusers'). '</th>'. "\n";
    echo '<td>'. "\n";
    echo '<input type="radio" id="shareusers_style_none" name="shareusers_style" value="none"';
    echo (get_option('shareusers_style') == 'none') ? ' checked="checked"' : '';
    echo ' />'. __('No style', 'shareusers'). "<br />\n";
	echo '<input type="radio" id="shareusers_style_class" name="shareusers_style" value="class"';
    echo (get_option('shareusers_style') == 'class') ? ' checked="checked"' : '';
    echo ' />'. __('Apply this', 'shareusers'). ' <i>class</i>: ';
    echo '<input type="text" id="shareusers_class" name="shareusers_class" value="'. get_option('shareusers_class'). '" size="15" onclick="selectLinkStyle(this)" onchange="selectLinkStyle(this)" /><br />'. "\n";
    echo '<input type="radio" id="shareusers_style_user" name="shareusers_style" value="user"';
    echo (get_option('shareusers_style') == 'user') ? ' checked="checked"' : '';
    echo ' />'. __('Define the style', 'shareusers'). "<br />\n";
	echo '    <div style="margin: 5px 25px;">'. "\n";
	echo '        '. __('Link color', 'shareusers'). ': ';
    echo '<input type="text" id="shareusers_color" name="shareusers_color" value="'. get_option('shareusers_color'). '" size="7" maxlength="7" onclick="selectLinkStyle(this)" onchange="selectLinkStyle(this)" />&nbsp;';
    echo '<img id="shareusers_color_trigger" src="'. $this->public_url. '/color_sel.gif" alt="" onclick="selectLinkStyle(this);showColorGrid3(\'shareusers_color\', \'none\')" />';
    echo '<div id="colorpicker301" class="colorpicker301"></div></div>'. "\n";
	echo '    <div style="margin: 5px 25px;">'. "\n";
    echo '        <input type="checkbox" id="shareusers_underline" name="shareusers_underline" value="1"';
    echo get_option('shareusers_underline') ? ' checked="checked"' : '';
    echo ' onclick="selectLinkStyle(this)" onchange="selectLinkStyle(this)" />'. __('Underline', 'shareusers'). "\n";
    echo '    </div>'. "\n";
    echo '</td>'. "\n";
    echo '</tr>';
    // Open links in new window
    echo '<tr valign="top">';
    echo '<th scope="row">'. __('Link destination', 'shareusers'). '</th>'. "\n";
    echo '<td><input name="shareusers_newwindow" type="checkbox" value="1"';
    echo get_option('shareusers_newwindow') ? ' checked="checked"' : '';
    echo ' />'. "\n";
    echo __('Open links in new window', 'shareusers'). "\n";
    echo '</td>'. "\n";
    echo '</tr>';
// *** LINK OPTIONS ***

// *** FOOTER ***
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" class="button-primary" value="'. __('Save Changes', 'shareusers'). '" />';
    echo '</p>';
    echo '</form>';
    echo '</div>';
// *** FOOTER ***
