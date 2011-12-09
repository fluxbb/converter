<?php
	ob_start();
?>
			'o_cur_version'				=> FORUM_VERSION,
			'o_database_revision'		=> FORUM_DB_REVISION,
			'o_searchindex_revision'	=> FORUM_SI_REVISION,
			'o_parser_revision'			=> FORUM_PARSER_REVISION,
			'o_board_title'				=> 'My FluxBB Forum',
			'o_board_desc'				=> '<p><span>Unfortunately no one can be told what FluxBB is - you have to see it for yourself.</span></p>',
			'o_default_timezone'		=> 0,
			'o_time_format'				=> 'H:i:s',
			'o_date_format'				=> 'Y-m-d',
			'o_timeout_visit'			=> 30000,
			'o_timeout_online'			=> 3000,
			'o_redirect_delay'			=> 1,
			'o_show_version'			=> 0,
			'o_show_user_info'			=> 1,
			'o_show_post_count'			=> 1,
			'o_signatures'				=> 1,
			'o_smilies'					=> 1,
			'o_smilies_sig'				=> 1,
			'o_make_links'				=> 1,
//			'o_default_lang'			=> 'English', // No need to change this value
//			'o_default_style'			=> 'Air', // No need to change this value
			'o_default_user_group'		=> 4,
			'o_topic_review'			=> 15,
			'o_disp_topics_default'		=> 30,
			'o_disp_posts_default'		=> 25,
			'o_indent_num_spaces'		=> 4,
			'o_quote_depth'				=> 3,
			'o_quickpost'				=> 1,
			'o_users_online'			=> 1,
			'o_censoring'				=> 0,
			'o_ranks'					=> 1,
			'o_show_dot'				=> 0,
			'o_topic_views'				=> 1,
			'o_quickjump'				=> 1,
			'o_gzip'					=> 0,
			'o_additional_navlinks'		=> '',
			'o_report_method'			=> 0,
			'o_regs_report'				=> 0,
			'o_default_email_setting'	=> 1,
			'o_mailing_list'			=> 'user@example.com',
			'o_avatars'					=> 1,
//			'o_avatars_dir'				=> 'img/avatars', // No need to change this value
			'o_avatars_width'			=> 60,
			'o_avatars_height'			=> 60,
			'o_avatars_size'			=> 10240,
			'o_search_all_forums'		=> 1,
//			'o_base_url'				=> '', // No need to change this value
			'o_admin_email'				=> 'user@example.com',
			'o_webmaster_email'			=> 'user@example.com',
			'o_forum_subscriptions'		=> 1,
			'o_topic_subscriptions'		=> 1,
			'o_smtp_host'				=> '',
			'o_smtp_user'				=> '',
			'o_smtp_pass'				=> '',
			'o_smtp_ssl'				=> 0,
			'o_regs_allow'				=> 1,
			'o_regs_verify'				=> 0,
			'o_announcement'			=> 0,
			'o_announcement_message'	=> 'Enter your announcement here.',
			'o_rules'					=> 0,
			'o_rules_message'			=> 'Enter your rules here',
			'o_maintenance'				=> 0,
			'o_maintenance_message'		=> 'The forums are temporarily down for maintenance. Please try again in a few minutes.',
			'o_default_dst'				=> 0,
			'o_feed_type'				=> 2,
			'o_feed_ttl'				=> 0,
			'p_message_bbcode'			=> 1,
			'p_message_img_tag'			=> 1,
			'p_message_all_caps'		=> 1,
			'p_subject_all_caps'		=> 1,
			'p_sig_all_caps'			=> 1,
			'p_sig_bbcode'				=> 1,
			'p_sig_img_tag'				=> 0,
			'p_sig_length'				=> 400,
			'p_sig_lines'				=> 4,
			'p_allow_banned_email'		=> 1,
			'p_allow_dupe_email'		=> 0,
			'p_force_guest_email'		=> 1,
<?php

$code = ob_get_contents();
ob_end_clean();

preg_match_all('%/*\s*\'(.*?)\'\s*=>\s*(.*?),?\s*\n%', "\n".trim($code, "\n")."\n", $matches, PREG_SET_ORDER);


$select = array();
foreach ($matches as $match)
{
	$config[$match[1]] = $match[0];
}

ob_start();
?>
			'o_smtp_host' 			=> $old_config['smtp_host'].(!empty($old_config['smtp_host']) && !empty($old_config['smtp_port'])) ? ':'.$old_config['smtp_port'] : '',
			'o_smtp_user' 			=> $old_config['smtp_username'],
			'o_smtp_pass' 			=> $old_config['smtp_password'],
<?php

$new_code = ob_get_contents();
ob_end_clean();

preg_match_all('%/*\s*\'(.*?)\'\s*=>\s*(.*?),?\s*\n%', "\n".trim($new_code, "\n")."\n", $matches_new, PREG_SET_ORDER);

foreach ($matches_new as $match)
{
	$config_new[$match[1]] = $match[0];
}


require '../../fluxbb-1.4/cache/cache_config.php';
foreach ($config as $name => $val)
{
	if (isset($config_new[$name]))
//		echo substr($config[$name], 0, strpos($config[$name], '=>') + 3).'$old_config[\''.$name.'\'],'."\n";
		echo $config_new[$name];
	else
		echo $config[$name];
//	else
//	{
	//	$tab = 6 * 4 - strlen($name);
//		$tab = $tab / 4;
//		echo "\t\t\t'".$name."'".str_repeat("\t", $tab)."=> '".$val."',"."\n";
//	}
}
