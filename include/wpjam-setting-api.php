<?php
/* WPJAM OPTIONS 
** Version: 1.0
*/
function wpjam_option_page($labels, $title='', $type='default'){
	extract($labels);
	?>
	<div class="wrap">
	<?php if($type == 'tab'){ ?>
		<h2 class="nav-tab-wrapper">
	        <?php foreach ( $sections as $section_name => $section) { ?>
	            <a class="nav-tab" href='javascript:void();' id="tab-title-<?php echo $section_name; ?>"><?php echo $section['title']; ?></a>
	        <?php } ?>    
	    </h2>
		<form action="options.php" method="POST">
			<?php settings_fields( $option_group ); ?>
			<?php foreach ( $sections as $section_name => $section ) { ?>
	            <div id="tab-<?php echo $section_name; ?>" class="div-tab hidden">
	                <?php wpjam_do_settings_section($option_page, $section_name); ?>
	            </div>                      
	        <?php } ?>
			<input type="hidden" name="<?php echo $option_name;?>[current_tab]" id="current_tab" value="" />
			<?php submit_button(); ?>
		</form>
		<?php wpjam_option_tab_script($option_name);?>
	<?php }else{ ?>
		<?php if($title){?>
			<?php if(preg_match("/<[^<]+>/",$title,$m) != 0){ ?>
				<?php echo $title; ?>
			<?php } else { ?>
				<h2><?php echo $title; ?></h2>
			<?php } ?>
		<?php }?>
		<form action="options.php" method="POST">
			<?php settings_fields( $option_group ); ?>
			<?php do_settings_sections( $option_page ); ?>
			<?php submit_button(); ?>
		</form>
	<?php } ?>
	</div>
	<?php
}

function wpjam_get_checkbox_settings($labels){
	$sections = $labels['sections'];
	$checkbox_options = array();
	foreach ($sections as $section) {
		$fields = $section['fields'];
		foreach ($fields as $field_name => $field) {
			if($field['type'] == 'checkbox'){
				$checkbox_options[] = $field_name;
			}
		}
	}
	return $checkbox_options;
}

function wpjam_option_tab_script($option_name='',$htag='h2'){
	$current_tab = '';

	if($option_name){
		$option = get_option( $option_name );
		if(!empty($_GET['settings-updated'])){
			$current_tab = $option['current_tab'];
		}
	}
	?>
	<script type="text/javascript">
		jQuery('div.div-tab').hide();
	<?php if($current_tab){ ?>
		jQuery('#tab-title-<?php echo $current_tab; ?>').addClass('nav-tab-active');
		jQuery('#tab-<?php echo $current_tab; ?>').show();
		jQuery('#current_tab').val('<?php echo $current_tab; ?>');
	<?php } else{ ?>
		//设置第一个显示
		jQuery('<?php echo $htag; ?> a.nav-tab').first().addClass('nav-tab-active');
		jQuery('div.div-tab').first().show();
	<?php } ?>
		jQuery(document).ready(function(){
			jQuery('<?php echo $htag; ?> a.nav-tab').on('click',function(){
		        jQuery('<?php echo $htag; ?> a.nav-tab').removeClass('nav-tab-active');
		        jQuery(this).addClass('nav-tab-active');
		        jQuery('div.div-tab').hide();
		        jQuery('#'+jQuery(this)[0].id.replace('title-','')).show();
		        jQuery('#current_tab').val(jQuery(this)[0].id.replace('tab-title-',''));
		    });
		});
	</script>
<?php
}

function wpjam_add_settings($labels, $defaults=array()){
	extract($labels);
	$defaults = apply_filters('wpjam_defaults', $defaults, $option_name);
	register_setting( $option_group, $option_name, $field_validate );

	$field_callback = empty($field_callback)?'wpjam_field_callback' : $field_callback;
	if($sections){
		foreach ($sections as $section_name => $section) {
			add_settings_section( $section_name, $section['title'], $section['callback'], $option_page );

			$fields = isset($section['fields'])?$section['fields']:(isset($section['fileds'])?$section['fileds']:''); // 尼玛写错英文单词的 fallback

			if($fields){
				foreach ($fields as $field_name=>$field) {
					$field['option']	= $option_name;
					$field['name']		= $field_name;

					$filed_title		= $field['title'];

					if(in_array($field['type'], array('text','password','select','datetime','textarea','checkbox'))){
						$filed_title = '<label for="'.$field_name.'">'.$filed_title.'</label>';
					}

					$field['default'] 	= ($defaults && isset($defaults[$field_name]))?$defaults[$field_name]:'';
					add_settings_field( 
						$field_name,
						$filed_title,		
						$field_callback,	
						$option_page, 
						$section_name,	
						$field
					);	
				}

			}
		}
	}
}

function wpjam_field_callback($args) {

	$wpjam_option	= get_option( $args['option'] );
	$type			= $args['type'];
	$field_name		= $args['name'];

	$value			= (isset($wpjam_option[$field_name]))?$wpjam_option[$field_name]:$args['default'];
	$field 			= $args['option'].'['.$field_name.']';

	$class			= ' class="'.(isset($args['class'])?$args['class']:(($type == 'textarea')?"large-text code":"regular-text")).'"';
	$list			= isset($args['step'])?' list="'.$args['step'].'"':'';

	switch ($type) {
		case 'text':
		case 'password':
		case 'url':
		case 'color':
		case 'url':
		case 'tel':
		case 'email':
		case 'month':
		case 'date':
		case 'datetime':
		case 'datetime-local':
		case 'week':
			echo '<input type="'.$type.'" id="'.$field_name.'" name="'.$field.'" value="'.$value.'" '.$class.' />';
			break;

		case 'range':
			$max	= isset($args['max'])?' max="'.$args['max'].'"':'';
			$min	= isset($args['min'])?' min="'.$args['min'].'"':'';
			$step	= isset($args['step'])?' step="'.$args['step'].'"':'';

			echo '<input type="'.$type.'" id="'.$field_name.'" name="'.$field.'" value="'.$value.'"'.$max.$min.$step.$list.' onchange="jQuery(\'#'.$field_name.'_span\').html(jQuery(\'#'.$field_name.'\').val());"  /> <span id="'.$field_name.'_span">'.$value.'</span>';
			break;

		case 'number':
			$max	= isset($args['max'])?' max="'.$args['max'].'"':'';
			$min	= isset($args['min'])?' min="'.$args['min'].'"':'';
			$step	= isset($args['step'])?' step="'.$args['step'].'"':'';
			echo '<input type="'.$type.'" id="'.$field_name.'" name="'.$field.'" value="'.$value.'"'.$class.$max.$min.$step.' />';
			break;

		case 'checkbox':
			echo '<input type="checkbox" id="'.$field_name.'" name="'.$field.'" value="1" '.checked("1",$value,false).' />';
			break;

		case 'textarea':
			$rows = isset($args['rows'])?$args['rows']:10;
			echo '<textarea id="'.$field_name.'" name="'.$field.'" rows="'.$rows.'" cols="50" '.$class.'>'.$value.'</textarea>';
			break;

		case 'select':
			echo '<select id="'.$field_name.'" name="'.$field.'">';
			foreach ($args['options'] as $option_value => $option_title){ 
				echo '<option value="'.$option_value.'" '.selected($option_value,$value,false).'>'.$option_title.'</option>';
			}
			echo '</select>';
			break;
		
		default:
			# code...
			break;
	}

	echo isset($args['description'])?($type == 'checkbox')?' <label for="'.$field_name.'">'.$args['description'].'</label>':'<br />'.$args['description']:'';
}

// 拷贝自 do_settings_sections 函数，用于 tab 显示选项。
function wpjam_do_settings_section($option_page, $section_name){
	global $wp_settings_sections, $wp_settings_fields;

	if ( ! isset( $wp_settings_sections[$option_page] ) )
		return;

	$section = $wp_settings_sections[$option_page][$section_name];

	if ( $section['title'] )
		echo "<h3>{$section['title']}</h3>\n";

	if ( $section['callback'] )
		call_user_func( $section['callback'], $section );

	if ( isset( $wp_settings_fields ) && isset( $wp_settings_fields[$option_page] ) && !empty($wp_settings_fields[$option_page][$section['id']] ) ){
		echo '<table class="form-table">';
		do_settings_fields( $option_page, $section['id'] );
		echo '</table>';
	}
}

function wpjam_get_setting($option, $setting_name){
	if(isset($option[$setting_name])){
		return str_replace("\r\n", "\n", $option[$setting_name]);
	}else{
		return '';
	}
}

function wpjam_get_option($option_name, $defaults = array()){
	$option = get_option( $option_name );
	if($option){
		return $option;
	}else{
		$defaults = apply_filters($option_name.'_defaults', $defaults);
		return wp_parse_args($option, $defaults);
	}
}

function wpjam_get_column($column_key, $column_name, $style = ''){
	return '<th scope="col" id="'.$column_key.'" class="manage-column column-'.$column_key.'" style="'.$style.'">'.$column_name.'</th>';
}

function wpjam_get_sortable_column($column_key, $column_name, $style=''){

	$orderby		= isset($_GET['orderby'])?$_GET['orderby']:'';
	$order 			= isset($_GET['order'])?$_GET['order']:'desc';

	$base_url = remove_query_arg(array('orderby','order','paged'), wpjam_get_current_page_url());

	if($orderby == $column_key){
		$class = 'sorted '.$order;
		$order = ($order == 'desc')?'asc':'desc';
		$url   = $base_url.'&orderby='.$column_key.'&order='.$order;
	}else{
		$class = 'sortable asc';
		$url   = $base_url.'&orderby='.$column_key.'&order=desc';
	} 

	return '<th scope="col" id="'.$column_key.'" class="manage-column column-'.$column_key.' '.$class.'" style="'.$style.'"><a href="'.$url.'"><span>'.$column_name.'</span><span class="sorting-indicator"></a></th>';
}

function wpjam_admin_pagenavi($total_count, $number_per_page=50){

	$current_page = isset($_GET['paged'])?$_GET['paged']:1;

	$base_url = remove_query_arg(array('paged'), wpjam_get_current_page_url());

	$total_pages	= ceil($total_count/$number_per_page);

	$first_page_url	= $base_url.'&amp;paged=1';
	$last_page_url	= $base_url.'&amp;paged='.$total_pages;
	
	if($current_page > 1 && $current_page < $total_pages){
		$prev_page		= $current_page-1;
		$prev_page_url	= $base_url.'&amp;paged='.$prev_page;

		$next_page		= $current_page+1;
		$next_page_url	= $base_url.'&amp;paged='.$next_page;
	}elseif($current_page == 1){
		$prev_page_url	= '#';
		$first_page_url	= '#';
		if($total_pages > 1){
			$next_page		= $current_page+1;
			$next_page_url	= $base_url.'&amp;paged='.$next_page;
		}else{
			$next_page_url	= '#';
		}
	}elseif($current_page == $total_pages){
		$prev_page		= $current_page-1;
		$prev_page_url	= $base_url.'&amp;paged='.$prev_page;
		$next_page_url	= '#';
		$last_page_url	= '#';
	}
	?>
	<div class="tablenav-pages">
		<span class="displaying-num"><?php /*每页 <?php echo $number_per_page;?> 个项目，*/?>共 <?php echo $total_count;?> 个项目</span>
		<span class="pagination-links">
			<a class="first-page <?php if($current_page==1) echo 'disabled'; ?>" title="前往第一页" href="<?php echo $first_page_url;?>">«</a>
			<a class="prev-page <?php if($current_page==1) echo 'disabled'; ?>" title="前往上一页" href="<?php echo $prev_page_url;?>">‹</a>
			<span class="paging-input">第 <?php echo $current_page;?> 页，共 <span class="total-pages"><?php echo $total_pages; ?></span> 页</span>
			<a class="next-page <?php if($current_page==$total_pages) echo 'disabled'; ?>" title="前往下一页" href="<?php echo $next_page_url;?>">›</a>
			<a class="last-page <?php if($current_page==$total_pages) echo 'disabled'; ?>" title="前往最后一页" href="<?php echo $last_page_url;?>">»</a>
		</span>
	</div>
	<br class="clear">
	<?php
}

function wpjam_admin_display_fields($fields, $fields_type = 'table'){
	$new_fields = array();
	foreach($fields as $key => $field){ 
		$type		= $field['type'];
		$value		= $field['value'];

		$class		= isset($field['class'])?$field['class']:'regular-text';
		$description= (isset($field['description']))?($type == 'checkbox')?' <label for="'.$key.'">'.$field['description'].'</label>':'<p>'.$field['description']:'</p>';

		$title 	= isset($field['title'])?$field['title']:$field['name'];
		$label 	= '<label for="'.$key.'">'.$title.'</label>';
		

		switch ($type) {
			case 'text':
			case 'password':
			case 'hidden':
			case 'url':
			case 'color':
			case 'url':
			case 'tel':
			case 'email':
			case 'month':
			case 'date':
			case 'datetime':
			case 'datetime-local':
			case 'week':
				$new_field_html = '<input name="'.$key.'" id="'. $key.'" type="'.$type.'"  value="'.esc_attr($value).'" class="'.$class.'" />';
				break;

			case 'range':
				$max	= isset($args['max'])?' max="'.$args['max'].'"':'';
				$min	= isset($args['min'])?' min="'.$args['min'].'"':'';
				$step	= isset($args['step'])?' step="'.$args['step'].'"':'';

				$new_field_html ='<input name="'.$key.'" id="'. $key.'" type="'.$type.'"  value="'.esc_attr($value).'"'.$max.$min.$step.$list.' class="'.$class.'" onchange="jQuery(\'#'.$key.'_span\').html(jQuery(\'#'.$key.'\').val());"  /> <span id="'.$key.'_span">'.$value.'</span>';
				break;

			case 'number':
				$max	= isset($args['max'])?' max="'.$args['max'].'"':'';
				$min	= isset($args['min'])?' min="'.$args['min'].'"':'';
				$step	= isset($args['step'])?' step="'.$args['step'].'"':'';

				$new_field_html = '<input name="'.$key.'" id="'. $key.'" type="'.$type.'"  value="'.esc_attr($value).'" class="'.$class.'"'.$max.$min.$step.' />';
				break;

			case 'checkbox':
				$new_field_html = '<input name="'.$key.'" id="'. $key.'" type="checkbox"  value="'.esc_attr($value).'" '.$field['checked'].' />';
				break;

			case 'textarea':

				$rows = isset($field['rows'])?$field['rows']:6;
				$new_field_html = '<textarea name="'.$key.'" id="'. $key.'" rows="'.$rows.'" cols="50"  class="'.$class.' code" >'.esc_attr($value).'</textarea>';
				break;

			case 'select':

				$new_field_html  = '<select name="'.$key.'" id="'. $key.'">';
				foreach ($field['options'] as $option_value => $option_title){ 
					$new_field_html .= '<option value="'.$option_value.'" '.selected($option_value,$value,false).'>'.$option_title.'</option>';
				}
				$new_field_html .= '</select>';
				
				break;

			case 'radio':
				$new_field_html  = '';
				foreach ($field['options'] as $option_value => $option_title) {
					$new_field_html  .= '<p><input name="'.$key.'" type="radio" id="'.$key.'" value="'.$option_value .'" '.checked($option_value,$value,false).' /> '.$option_title.'</p>';
				}
				break;

			case 'image':
				$new_field_html = '<input name="'.$key.'" id="'.$key.'" type="text"  value="'.esc_attr($value).'" class="'.$class.'" /><input type="botton" class="wpjam_upload button" style="width:80px;" value="选择图片">';
                break;
            case 'mulit_image':
            case 'multi_image':
            	$new_field_html  = '';
                if(is_array($value)){
                    foreach($value as $image_key=>$image){
                        if(!empty($image)){
                        	$new_field_html .= '<span><input type="text" name="'.$key.'[]" id="'. $key.'" value="'.esc_attr($image).'"  class="'.$class.'" /><a href="javascript:;" class="button del_image">删除</a></span>';
                        }
                    }
                }
                $new_field_html  = '<span><input type="text" name="'.$key.'[]" id="'.$key.'" value="" class="'.$class.'" /><input type="botton" class="wpjam_mulit_upload button" style="width:110px;" value="选择图片[多选]" title="按住Ctrl点击鼠标左键可以选择多张图片"></span>';
                break;
            case 'mulit_text':
            case 'multi_text':
            	$new_field_html  = '';
                if(is_array($value)){
                    foreach($value as $text_key=>$item){
                        if(!empty($item)){
                        	$new_field_html .= '<span><input type="text" name="'.$key.'[]" id="'. $key.'" value="'.esc_attr($item).'"  class="'.$class.'" /><a href="javascript:;" class="button del_image">删除</a></span>';
                        }
                    }
                }
                $new_field_html  = '<span><input type="text" name="'.$key.'[]" id="'.$key.'" value="" class="'.$class.'" /><a class="wpjam_mulit_text button">添加选项</a></span>';
                break;

            case 'file':
            	$new_field_html  = '<input type="file" name="'.$key.'" id="'. $key.'" />'.'已上传：'.wp_get_attachment_link($value);
                break;
			
			default:
				$new_field_html = '<input name="'.$key.'" id="'. $key.'" type="text"  value="'.esc_attr($value).'" class="'.$class.'" />';
				break;
		}
		$new_fields[$key] = array('title'=>$title, 'label'=>$label, 'html'=>$new_field_html.$description);
	}
	
	?>
	<?php if($fields_type == 'list'){ ?>
	<ul>
	<?php foreach ($new_fields as $key=>$field) { ?>
		<li><?php echo $field['label']; ?> <?php echo $field['html']; ?> </li>
	<?php } ?>
	</ul>
	<?php } elseif($fields_type == 'table'){ ?>
	<table class="form-table" cellspacing="0">
		<tbody>
		<?php foreach ($new_fields as $key=>$field) { ?>
			<tr valign="top" id="tr_<?php echo $key; ?>">
			<?php if($field['title']) { ?>
				<th scope="row"><?php echo $field['label']; ?></th>
				<td><?php echo $field['html']; ?></td>
			<?php } else { ?>
				<td colspan="2"><?php echo $field['html']; ?></td>
			<?php } ?>
			</tr>
		<?php } ?>
		</tbody>
	</table>

	<?php } elseif($fields_type == 'tr') { ?>
		<?php foreach ($new_fields as $key=>$field) { ?>

			<tr id="tr_<?php echo $key; ?>">
			<?php if($field['title']) { ?>
				<th scope="row"><?php echo $field['label']; ?></th>
				<td><?php echo $field['html']; ?></td>
			<?php } else { ?>
				<td colspan="2"><?php echo $field['html']; ?></td>
			<?php } ?>
			</tr>
		<?php } ?>
	<?php } ?>
	<?php
}

function wpjam_confim_delete_script(){
	?>
	<script type="text/javascript">
	jQuery(function(){
		jQuery('span.delete a').click(function(){
			return confirm('确定要删除吗?'); 
		}); 
	});
	</script> 
	<?php
}

// 获取自定义字段设置
function wpjam_get_post_options(){
    $wpjam_options = apply_filters('wpjam_options', array());
    return $wpjam_options;
}

//输出自定义字段表单
add_action('admin_head', 'wpjam_post_options_box');
function wpjam_post_options_box() {
   	$wpjam_options = wpjam_get_post_options();
    if($wpjam_options){
    	foreach($wpjam_options as $meta_box=>$wpjam_option){
    		$context	= isset($wpjam_option['context'])?$wpjam_option['context']:'normal';
    		$priority	= isset($wpjam_option['priority'])?$wpjam_option['priority']:'high';
    		if($wpjam_option['post_types'] == null){
    			global $pagenow;
				if($pagenow != 'post.php' && $pagenow != 'post-new.php'){
					return;
				}
    			add_meta_box($meta_box, $wpjam_option['name'], 'wpjam_post_options_callback', null, $context, $priority, array('meta_box'=>$meta_box));
    		}else{
    			foreach($wpjam_option['post_types'] as $post_type){
		        	add_meta_box($meta_box, $wpjam_option['name'], 'wpjam_post_options_callback', $post_type, 'normal', 'high', array('meta_box'=>$meta_box));
		        }
    		}
	    }
    }
}

function wpjam_post_options_callback( $post, $meta_box){
    if(isset($meta_box['args']['meta_box'])){
        $meta_box = $meta_box['args']['meta_box'];
    } else{
        $meta_box = '';
    }
    $wpjam_options = wpjam_get_post_options();
    foreach ($wpjam_options[$meta_box]['fields'] as $key => $wpjam_field) {
        if(isset($_REQUEST[$key])){
            $value  = $_REQUEST[$key];
        }else{
            $value = get_post_meta($post->ID, $key, true);
        }
        if($wpjam_field['type'] == 'checkbox'){
        	$wpjam_options[$meta_box]['fields'][$key]['value'] = 1;
        	$wpjam_options[$meta_box]['fields'][$key]['checked'] = ($value)?'checked':'';;
        }else{
        	$wpjam_options[$meta_box]['fields'][$key]['value'] = $value;
        }
    }
    $fields_type = (isset($wpjam_options[$meta_box]['context']) && $wpjam_options[$meta_box]['context'] == 'side')?'list':'table';
    wpjam_admin_display_fields($wpjam_options[$meta_box]['fields'] , $fields_type);
    ?>
    <script type="text/javascript">
        jQuery(function(){
            jQuery("form#post").attr('enctype','multipart/form-data');
        });
    </script>
<?php
}

//保存自定义字段
add_action('save_post', 'wpjam_save_post_options', 999);
function wpjam_save_post_options($post_id){
    // to prevent metadata or custom fields from disappearing...
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
        return $post_id;

    $post = get_post($post_id);
    $wpjam_options = wpjam_get_post_options();
    foreach ($wpjam_options as $meta_box => $wpjam_group) {
        if($wpjam_group['post_types'] == null || in_array($post->post_type, $wpjam_group['post_types'])){
            foreach($wpjam_group['fields'] as $key=>$wpjam_field){
                switch($wpjam_field['type']){
                    case 'file':
                        if($_POST['wpjam_delete_field'][$key]){
                            delete_post_meta($post_id,$key,$_POST['wpjam_delete_field'][$key]);
                        }
                        if(isset($_FILES[$key]) && $_FILES[$key]){
                            require_once(ABSPATH . 'wp-admin/includes/admin.php');
                            $attachment_id=media_handle_upload($key,$post_id);
                            if(!is_wp_error($attachment_id)){
                                update_post_meta($post_id,$key,$attachment_id);
                            }
                            unset($attachment_id);
                        }
                        break;
                    case 'checkbox':
                    	//xxx特殊设置，防止在前台修改此值
                        if(is_admin()){
                        	if(isset($_POST[$key])){
                                update_post_meta($post_id,$key,$_POST[$key]);
                            }else{
                            	if(get_post_meta($post_id, $key, true)){
									delete_post_meta($post_id, $key);
								}
                            }
                        }
                        break;
                    case 'mulit_image':
                        if(isset($_POST[$key]) && is_array($_POST[$key])){
                            //删除空图片
                            foreach($_POST[$key] as $image_key=>$image_value){
                                if(empty($image_value))
                                    unset($_POST[$key][$image_key]);
                            }
                            update_post_meta($post_id,$key,$_POST[$key]);
                        }
                        break;
                    case 'mulit_text':
                        if(isset($_POST[$key]) && is_array($_POST[$key])){
                            foreach($_POST[$key] as $multiple_text_key=>$item_value){
                                if(empty($item_value))
                                    unset($_POST[$key][$multiple_text_key]);
                            }
                            update_post_meta($post_id,$key,$_POST[$key]);
                        }
                        break;
                    default:
                        if(isset($_POST[$key]) && $_POST[$key]){
                            update_post_meta($post_id,$key,$_POST[$key]);
                        }else{
                        	if(get_post_meta($post_id, $key, true)){
								delete_post_meta($post_id, $key);
							}
                        }
                }
            }
        }
    }
}

add_action('admin_footer', 'wpjam_upload_image_script');
function wpjam_upload_image_script(){
	global $pagenow;

	if($pagenow != 'post.php' && $pagenow != 'post-new.php'){
		return;
	}

	$wpjam_options = wpjam_get_post_options();
	$post_type = isset($_GET['post_type'])?$_GET['post_type']:'post';

	$need_js = 0;

	foreach ($wpjam_options as $key => $wpjam_option) {
		if($wpjam_option['post_types'] == null){
			$need_js = 1;
			break;
		}
		if( in_array($post_type, $wpjam_option['post_types']) ){
			$need_js = 1;
			break;
		}
	}

	if(!$need_js){
		return;
	}
	wp_enqueue_media();
    ?>
    <script type="text/javascript">
        jQuery(function($){
            //上传单个图片
            $('.wpjam_upload').on("click",function(e) {
                var obj = $(this);
                e.preventDefault();
                var custom_uploader = wp.media({
                    title: '插入选项缩略图',
                    button: {
                        text: '选择图片'
                    },
                    multiple: false  // Set this to true to allow multiple files to be selected
                })
                    .on('select', function() {
                        var attachment = custom_uploader.state().get('selection').first().toJSON();
                        //var dataobj = '<img src="'+attachment.url+'"><div class="close">X</div>';
                        obj.prev("input").val(attachment.url)
                        obj.after(dataobj).hide();
                        $('.media-modal-close').trigger('click');
                    })
                    .open();
            });
            // 添加多个选项
            var item =  '';

            $('body').on('click', 'a.wpjam_mulit_text', function(){
            	var value = $(this).prev().val();
            	var name = $(this).prev().attr("name");
            	var option = '<span><input type="text" name="'+name+'" value="'+value+'" style="width:70%;" /><a href="javascript:;" class="button del_image">删除</a></span>';
            	$(this).parent().before(option);
            	$(this).prev().val('');
				return false;
            });

            //上传多个图片
            var html = '';
            $('body').on('click', '.wpjam_mulit_upload', function(e) {
                var position = $(this).prev("input");
                var key_name = position.attr('name');
                var custom_uploader;
                var obj = $(this);
                var ids = new Array();
                e.preventDefault();
                if (custom_uploader) {
                    custom_uploader.open();
                    return;
                }
                custom_uploader = wp.media.frames.file_frame = wp.media({
                    title: 'Choose Image',
                    button: {
                        text: 'Choose Image'
                    },
                    multiple: true
                }).on('select', function() {
                    var data = custom_uploader.state().get('selection');
                    data.map( function( data ) {
                        data = data.toJSON();
                        value = data.url;
                        // console.log(data);
                        html = '<span><input type="text" name="'+key_name+'" value="'+value+'" style="width:70%;"  /><a href="javascript:;" class="button del_image">删除</a></span>';
                        position.before(html);
                    });
                    response = ids.join(",");
                    obj.prev().val(response);
                    $('.media-modal-close').trigger('click');
                }).open();

                return false;
            });
            //  删除图片
            $('body').on('click', '.del_image', function(){
                $(this).parent().fadeOut(1000, function(){
                    $(this).remove();
                });
            });

            return false;
        });
    </script>
<?php
}

// 获取当前页面 url
function wpjam_get_current_page_url(){
    $ssl        = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true:false;
    $sp         = strtolower($_SERVER['SERVER_PROTOCOL']);
    $protocol   = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port       = $_SERVER['SERVER_PORT'];
    $port       = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
    $host       = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
    return $protocol . '://' . $host . $port . $_SERVER['REQUEST_URI'];
}