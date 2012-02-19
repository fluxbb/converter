<?php

/**
 * @copyright (C) 2012 FluxBB (http://fluxbb.org)
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package FluxBB
 */

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.4.8');

define('FORUM_DB_REVISION', 15);
define('FORUM_SI_REVISION', 2);
define('FORUM_PARSER_REVISION', 2);

class miniBB_3_0 extends Forum
{
	// Will the passwords be converted?
	var $converts_password = true;

	var $steps = array(
		'bans'					=> array('banned', 'id'),
		'categories'			=> array('forums', 'DISTINCT forum_group'),
		'forums'				=> array('forums', 'forum_id'),
		'posts'					=> array('posts', 'post_id'),
		// 'topic_subscriptions'	=> array('topics_watch', 'topic_id'),
		'topics'				=> array('topics', 'topic_id'),
		'users'					=> array('users', 'user_id'),
	);

	function initialize()
	{
		$this->db->set_names('utf8');
	}

	/**
	 * Check whether specified database has valid current forum software structure
	 */
	function validate()
	{
		if (!$this->db->field_exists('banned', 'banip'))
			conv_error('Selected database does not contain valid miniBB installation');
	}

	function convert_bans()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'id, banip AS ip, banreason AS message',
			'FROM'		=> 'banned',
		)) or conv_error('Unable to fetch bans', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('bans', $this->db->num_rows($result));
		while ($cur_ban = $this->db->fetch_assoc($result))
		{
			$this->fluxbb->add_row('bans', $cur_ban);
		}
	}

	function convert_categories()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'forum_group AS cat_name',
			'FROM'		=> 'forums',
			'ORDER BY'	=> 'forum_id ASC',
			'GROUP BY'	=> 'forum_group'
		)) or conv_error('Unable to fetch categories', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('categories', $this->db->num_rows($result));
		$i = 1;
		while ($cur_cat = $this->db->fetch_assoc($result))
		{
			$cur_cat['disp_position'] = $cur_cat['id'] = $i++;
			$this->fluxbb->add_row('categories', $cur_cat);
		}
	}

	function convert_forums()
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'forum_id AS id, forum_name, forum_desc, forum_order AS disp_position, topics_count AS num_topics, posts_count AS num_posts, forum_group AS cat_id',
			'FROM'		=> 'forums',
			'ORDER BY'	=> 'forum_order ASC'
		)) or conv_error('Unable to fetch forums', __FILE__, __LINE__, $this->db->error());

		$categories = array();
		$result_cats = $this->fluxbb->db->query_build(array(
			'SELECT'	=> 'id, cat_name',
			'FROM'		=> 'categories',
		)) or conv_error('Unable to fetch FluxBB categories', __FILE__, __LINE__, $this->fluxbb->db->error());

		while ($cur_cat = $this->fluxbb->db->fetch_assoc($result_cats))
			$categories[$cur_cat['cat_name']] = $cur_cat['id'];

		conv_processing_message('forums', $this->db->num_rows($result));
		while ($cur_forum = $this->db->fetch_assoc($result))
		{
			$cur_forum['cat_id'] = $categories[$cur_forum['cat_id']];
			$cur_forum['forum_desc'] = $this->convert_message($cur_forum['forum_desc']);
			$cur_forum['last_post'] = strtotime($cur_forum['last_post']);

			if ($cur_forum['num_topics'] == 0)
				$cur_forum['last_post'] = $cur_forum['last_post_id'] = $cur_forum['last_poster'] = NULL;

			$this->fluxbb->add_row('forums', $cur_forum);
		}
	}

	function convert_posts($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'post_id AS id, poster_name AS poster, poster_id, post_time AS posted, poster_ip, post_text AS message, topic_id',
			'FROM'		=> 'posts',
			'WHERE'		=> 'post_id > '.$start_at,
			'ORDER BY'	=> 'post_id ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('posts', $this->db->num_rows($result), $start_at);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_post = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_post['id'];
			$cur_post['message'] = $this->convert_message($cur_post['message']);
			$cur_post['posted'] = strtotime($cur_post['posted']);
			$cur_post['poster_id'] = $this->uid2uid($cur_post['poster_id']);

			$this->fluxbb->add_row('posts', $cur_post);
		}

		return $this->redirect('posts', 'post_id', $start_at);
	}

	// function convert_topic_subscriptions()
	// {
	// 	$result = $this->db->query_build(array(
	// 		'SELECT'	=> 'DISTINCT user_id, topic_id',
	// 		'FROM'		=> 'topics_watch',
	// 	)) or conv_error('Unable to fetch topic subscriptions', __FILE__, __LINE__, $this->db->error());

	// 	conv_processing_message('topic subscriptions', $this->db->num_rows($result));
	// 	while ($cur_sub = $this->db->fetch_assoc($result))
	// 	{
	// 		$this->fluxbb->add_row('topic_subscriptions', $cur_sub);
	// 	}
	// }

	function convert_topics($start_at)
	{
		// TODO: first post id, last post id
		$result = $this->db->query_build(array(
			'SELECT'	=> 'topic_id AS id, topic_poster_name AS poster, topic_title AS subject, topic_time AS posted, topic_last_post_id AS last_post_id, topic_last_post_time AS last_post, topic_last_poster AS last_poster, topic_views AS num_views, posts_count-1 AS num_replies, IF(topic_status=1, 1, 0) AS closed, IF(sticky=1, 1, 0) AS sticky, forum_id',
			'FROM'		=> 'topics',
			'WHERE'		=> 'topic_id > '.$start_at,
			'ORDER BY'	=> 'topic_id ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch topics', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('topics', $this->db->num_rows($result), $start_at);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_topic = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_topic['id'];
			$cur_topic['subject'] = html_entity_decode($cur_topic['subject'], ENT_QUOTES, 'UTF-8');
			$cur_topic['posted'] = strtotime($cur_topic['posted']);

			$this->fluxbb->add_row('topics', $cur_topic);
		}

		return $this->redirect('topics', 'topic_id', $start_at);
	}

	function convert_users($start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 'user_id AS id, username, user_regdate AS registered, user_password AS password, user_email AS email, user_icq AS icq, user_website AS url, user_from AS location, user_viewemail AS email_setting, language, num_posts',
			'FROM'		=> 'users',
			'WHERE'		=> 'user_id > '.$start_at,
			'ORDER BY'	=> 'user_id ASC',
			'LIMIT'		=> PER_PAGE,
		)) or conv_error('Unable to fetch users', __FILE__, __LINE__, $this->db->error());

		conv_processing_message('users', $this->db->num_rows($result), $start_at);

		if (!$this->db->num_rows($result))
			return false;

		while ($cur_user = $this->db->fetch_assoc($result))
		{
			$start_at = $cur_user['id'];

			$cur_user['group_id'] = ($cur_user['id'] == '1') ? PUN_ADMIN : PUN_MEMBER;
			$cur_user['username'] = html_entity_decode($cur_user['username']);

			$cur_user['id'] = $this->uid2uid($cur_user['id']);

			$this->fluxbb->add_row('users', $cur_user);
		}

		return $this->redirect('users', 'user_id', $start_at);
	}


	/**
 	* Convert user id to FluxBB style
	 */
	function uid2uid($id)
	{
		static $last_uid;

		// id=0 is a guest user
		if ($id == 0)
			return 1;

		// id=1 is reserved for the guest user
		else if ($id == 1)
		{
			if (!isset($last_uid))
			{
				$result = $this->db->query_build(array(
					'SELECT'	=> 'MAX(user_id)',
					'FROM'		=> 'users',
				)) or conv_error('Unable to fetch last user id', __FILE__, __LINE__, $this->db->error());

				$last_uid = $this->db->result($result) + 1;
			}
			return $last_uid;
		}

		return $id;
	}


	/**
	 * Convert BBcode
	 */
	function convert_message($message)
	{
		static $patterns, $replacements;

		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

		if (!isset($patterns))
		{
			$patterns = array(
				'%<em>(.*?)</em>%i'												=>	'[i]$1[/i]',
				'%<strong>(.*?)</strong>%i'										=>	'[b]$1[/b]',
				'%<img src="(.*?)".* />%i'										=>	'[img]$1[/img]',
				'%<span style="color:\s*(#[a-zA-Z0-9]{3,6})\s*">(.*?)</span>%i'	=>	'[color=$1]$2[/color]',
				'%<a href="(.*?)".*>(.*?)</a>%i'								=>	'[url=$1]$2[/url]',
				'%[/?align(left|right|center)?]%i'								=>	'',
			);
		}

		$message = preg_replace(array_keys($patterns), array_values($patterns), $message);

		if (!isset($replacements))
		{
			$replacements = array(
				'<br />'		=> "\n",
				'[hl]'			=> '[code]',
				'[/hl]'			=> '[/code]',
			);
		}

		return $this->fluxbb->preparse_bbcode(str_replace(array_keys($replacements), array_values($replacements), $message), $errors);
	}
}
