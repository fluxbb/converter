<?php

class FluxBB
{
	var $db;
	var $db_type;
	var $schemas;

	function FluxBB($db, $db_type)
	{
		$db->set_names('utf8');

		$this->db = $db;
		$this->db_type = $db_type;

		$this->schemas = array();
	}

	function init_bans()
	{
		$this->schemas['bans'] = array(
			'FIELDS'		=> array(
				'id'			=> array(
					'datatype'		=> 'SERIAL',
					'allow_null'	=> false
				),
				'username'		=> array(
					'datatype'		=> 'VARCHAR(200)',
					'allow_null'	=> true
				),
				'ip'			=> array(
					'datatype'		=> 'VARCHAR(255)',
					'allow_null'	=> true
				),
				'email'			=> array(
					'datatype'		=> 'VARCHAR(80)',
					'allow_null'	=> true
				),
				'message'		=> array(
					'datatype'		=> 'VARCHAR(255)',
					'allow_null'	=> true
				),
				'expire'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> true
				),
				'ban_creator'	=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				)
			),
			'PRIMARY KEY'	=> array('id')
		);
		
		$this->db->create_table('bans', $this->schemas['bans']);
	}
	
	function init_categories()
	{
		$this->schemas['categories'] = array(
			'FIELDS'		=> array(
				'id'			=> array(
					'datatype'		=> 'SERIAL',
					'allow_null'	=> false
				),
				'cat_name'		=> array(
					'datatype'		=> 'VARCHAR(80)',
					'allow_null'	=> false,
					'default'		=> '\'New Category\''
				),
				'disp_position'	=> array(
					'datatype'		=> 'INT(10)',
					'allow_null'	=> false,
					'default'		=> '0'
				)
			),
			'PRIMARY KEY'	=> array('id')
		);
	
		$this->db->create_table('categories', $this->schemas['categories']);
	}
	
	function init_censoring()
	{
		$this->schemas['censoring'] = array(
			'FIELDS'		=> array(
				'id'			=> array(
					'datatype'		=> 'SERIAL',
					'allow_null'	=> false
				),
				'search_for'	=> array(
					'datatype'		=> 'VARCHAR(60)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				),
				'replace_with'	=> array(
					'datatype'		=> 'VARCHAR(60)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				)
			),
			'PRIMARY KEY'	=> array('id')
		);
	
		$this->db->create_table('censoring', $this->schemas['censoring']);
	}
	
	function init_config()
	{
		$this->schemas['config'] = array(
			'FIELDS'		=> array(
				'conf_name'		=> array(
					'datatype'		=> 'VARCHAR(255)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				),
				'conf_value'	=> array(
					'datatype'		=> 'TEXT',
					'allow_null'	=> true
				)
			),
			'PRIMARY KEY'	=> array('conf_name')
		);
	
		$this->db->create_table('config', $this->schemas['config']);
	}
	
	function init_forum_perms()
	{
		$this->schemas['forum_perms'] = array(
			'FIELDS'		=> array(
				'group_id'		=> array(
					'datatype'		=> 'INT(10)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'forum_id'		=> array(
					'datatype'		=> 'INT(10)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'read_forum'	=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'post_replies'	=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'post_topics'	=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				)
			),
			'PRIMARY KEY'	=> array('group_id', 'forum_id')
		);
	
		$this->db->create_table('forum_perms', $this->schemas['forum_perms']);
	}
	
	function init_forums()
	{
		$this->schemas['forums'] = array(
			'FIELDS'		=> array(
				'id'			=> array(
					'datatype'		=> 'SERIAL',
					'allow_null'	=> false
				),
				'forum_name'	=> array(
					'datatype'		=> 'VARCHAR(80)',
					'allow_null'	=> false,
					'default'		=> '\'New forum\''
				),
				'forum_desc'	=> array(
					'datatype'		=> 'TEXT',
					'allow_null'	=> true
				),
				'redirect_url'	=> array(
					'datatype'		=> 'VARCHAR(100)',
					'allow_null'	=> true
				),
				'moderators'	=> array(
					'datatype'		=> 'TEXT',
					'allow_null'	=> true
				),
				'num_topics'	=> array(
					'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'num_posts'		=> array(
					'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'last_post'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> true
				),
				'last_post_id'	=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> true
				),
				'last_poster'	=> array(
					'datatype'		=> 'VARCHAR(200)',
					'allow_null'	=> true
				),
				'sort_by'		=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'disp_position'	=> array(
					'datatype'		=> 'INT(10)',
					'allow_null'	=> false,
					'default'		=>	'0'
				),
				'cat_id'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=>	'0'
				)
			),
			'PRIMARY KEY'	=> array('id')
		);
	
		$this->db->create_table('forums', $this->schemas['forums']);
	}
	
	function init_groups()
	{
		$this->schemas['groups'] = array(
			'FIELDS'		=> array(
				'g_id'						=> array(
					'datatype'		=> 'SERIAL',
					'allow_null'	=> false
				),
				'g_title'					=> array(
					'datatype'		=> 'VARCHAR(50)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				),
				'g_user_title'				=> array(
					'datatype'		=> 'VARCHAR(50)',
					'allow_null'	=> true
				),
				'g_moderator'				=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'g_mod_edit_users'			=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'g_mod_rename_users'		=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'g_mod_change_passwords'	=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'g_mod_ban_users'			=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'g_read_board'				=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'g_view_users'				=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'g_post_replies'			=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'g_post_topics'				=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'g_edit_posts'				=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'g_delete_posts'			=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'g_delete_topics'			=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'g_set_title'				=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'g_search'					=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'g_search_users'			=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'g_send_email'				=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'g_post_flood'				=> array(
					'datatype'		=> 'SMALLINT(6)',
					'allow_null'	=> false,
					'default'		=> '30'
				),
				'g_search_flood'			=> array(
					'datatype'		=> 'SMALLINT(6)',
					'allow_null'	=> false,
					'default'		=> '30'
				),
				'g_email_flood'				=> array(
					'datatype'		=> 'SMALLINT(6)',
					'allow_null'	=> false,
					'default'		=> '60'
				)
			),
			'PRIMARY KEY'	=> array('g_id')
		);
	
		$this->db->create_table('groups', $this->schemas['groups']);
	}
	
	function init_online()
	{
		$this->schemas['online'] = array(
			'FIELDS'		=> array(
				'user_id'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'ident'			=> array(
					'datatype'		=> 'VARCHAR(200)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				),
				'logged'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'idle'			=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'last_post'			=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> true
				),
				'last_search'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> true
				),
			),
			'UNIQUE KEYS'	=> array(
				'user_id_ident_idx'	=> array('user_id', 'ident')
			),
			'INDEXES'		=> array(
				'ident_idx'		=> array('ident'),
				'logged_idx'	=> array('logged')
			),
			'ENGINE'		=> 'HEAP'
		);
	
		if ($this->db_type == 'mysql' || $this->db_type == 'mysqli' || $this->db_type == 'mysql_innodb' || $this->db_type == 'mysqli_innodb')
		{
			$this->schemas['online']['UNIQUE KEYS']['user_id_ident_idx'] = array('user_id', 'ident(25)');
			$this->schemas['online']['INDEXES']['ident_idx'] = array('ident(25)');
		}
	
		if ($this->db_type == 'mysql_innodb' || $this->db_type == 'mysqli_innodb')
			$this->schemas['online']['ENGINE'] = 'InnoDB';
	
		$this->db->create_table('online', $this->schemas['online']);
	}
	
	function init_posts()
	{
		$this->schemas['posts'] = array(
			'FIELDS'		=> array(
				'id'			=> array(
					'datatype'		=> 'SERIAL',
					'allow_null'	=> false
				),
				'poster'		=> array(
					'datatype'		=> 'VARCHAR(200)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				),
				'poster_id'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'poster_ip'		=> array(
					'datatype'		=> 'VARCHAR(39)',
					'allow_null'	=> true
				),
				'poster_email'	=> array(
					'datatype'		=> 'VARCHAR(80)',
					'allow_null'	=> true
				),
				'message'		=> array(
					'datatype'		=> 'TEXT',
					'allow_null'	=> true
				),
				'hide_smilies'	=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'posted'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'edited'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> true
				),
				'edited_by'		=> array(
					'datatype'		=> 'VARCHAR(200)',
					'allow_null'	=> true
				),
				'topic_id'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				)
			),
			'PRIMARY KEY'	=> array('id'),
			'INDEXES'		=> array(
				'topic_id_idx'	=> array('topic_id'),
				'multi_idx'		=> array('poster_id', 'topic_id')
			)
		);
	
		$this->db->create_table('posts', $this->schemas['posts']);
	}
	
	function init_ranks()
	{
		$this->schemas['ranks'] = array(
			'FIELDS'		=> array(
				'id'			=> array(
					'datatype'		=> 'SERIAL',
					'allow_null'	=> false
				),
				'rank'			=> array(
					'datatype'		=> 'VARCHAR(50)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				),
				'min_posts'		=> array(
					'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				)
			),
			'PRIMARY KEY'	=> array('id')
		);
	
		$this->db->create_table('ranks', $this->schemas['ranks']);
	}
	
	function init_reports()
	{
		$this->schemas['reports'] = array(
			'FIELDS'		=> array(
				'id'			=> array(
					'datatype'		=> 'SERIAL',
					'allow_null'	=> false
				),
				'post_id'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'topic_id'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'forum_id'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'reported_by'	=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'created'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'message'		=> array(
					'datatype'		=> 'TEXT',
					'allow_null'	=> true
				),
				'zapped'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> true
				),
				'zapped_by'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> true
				)
			),
			'PRIMARY KEY'	=> array('id'),
			'INDEXES'		=> array(
				'zapped_idx'	=> array('zapped')
			)
		);
	
		$this->db->create_table('reports', $this->schemas['reports']);
	}
	
	function init_search_cache()
	{
		$this->schemas['search_cache'] = array(
			'FIELDS'		=> array(
				'id'			=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'ident'			=> array(
					'datatype'		=> 'VARCHAR(200)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				),
				'search_data'	=> array(
					'datatype'		=> 'TEXT',
					'allow_null'	=> true
				)
			),
			'PRIMARY KEY'	=> array('id'),
			'INDEXES'		=> array(
				'ident_idx'	=> array('ident')
			)
		);
	
		if ($this->db_type == 'mysql' || $this->db_type == 'mysqli' || $this->db_type == 'mysql_innodb' || $this->db_type == 'mysqli_innodb')
			$this->schemas['search_cache']['INDEXES']['ident_idx'] = array('ident(8)');
	
		$this->db->create_table('search_cache', $this->schemas['search_cache']);
	}
	
	function init_search_matches()
	{
		$this->schemas['search_matches'] = array(
			'FIELDS'		=> array(
				'post_id'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'word_id'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'subject_match'	=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				)
			),
			'INDEXES'		=> array(
				'word_id_idx'	=> array('word_id'),
				'post_id_idx'	=> array('post_id')
			)
		);
	
		$this->db->create_table('search_matches', $this->schemas['search_matches']);
	}
	
	function init_search_words()
	{
		$this->schemas['search_words'] = array(
			'FIELDS'		=> array(
				'id'			=> array(
					'datatype'		=> 'SERIAL',
					'allow_null'	=> false
				),
				'word'			=> array(
					'datatype'		=> 'VARCHAR(20)',
					'allow_null'	=> false,
					'default'		=> '\'\'',
					'collation'		=> 'bin'
				)
			),
			'PRIMARY KEY'	=> array('word'),
			'INDEXES'		=> array(
				'id_idx'	=> array('id')
			)
		);
	
		if ($this->db_type == 'sqlite')
		{
			$this->schemas['search_words']['PRIMARY KEY'] = array('id');
			$this->schemas['search_words']['UNIQUE KEYS'] = array('word_idx'	=> array('word'));
		}
	
		$this->db->create_table('search_words', $this->schemas['search_words']);
	}
	
	function init_subscriptions()
	{
		$this->schemas['subscriptions'] = array(
			'FIELDS'		=> array(
				'user_id'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'topic_id'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				)
			),
			'PRIMARY KEY'	=> array('user_id', 'topic_id')
		);
	
		$this->db->create_table('subscriptions', $this->schemas['subscriptions']);
	}
	
	function init_topics()
	{
		$this->schemas['topics'] = array(
			'FIELDS'		=> array(
				'id'			=> array(
					'datatype'		=> 'SERIAL',
					'allow_null'	=> false
				),
				'poster'		=> array(
					'datatype'		=> 'VARCHAR(200)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				),
				'subject'		=> array(
					'datatype'		=> 'VARCHAR(255)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				),
				'posted'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'first_post_id'	=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'last_post'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'last_post_id'	=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'last_poster'	=> array(
					'datatype'		=> 'VARCHAR(200)',
					'allow_null'	=> true
				),
				'num_views'		=> array(
					'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'num_replies'	=> array(
					'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'closed'		=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'sticky'		=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'moved_to'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> true
				),
				'forum_id'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				)
			),
			'PRIMARY KEY'	=> array('id'),
			'INDEXES'		=> array(
				'forum_id_idx'		=> array('forum_id'),
				'moved_to_idx'		=> array('moved_to'),
				'last_post_idx'		=> array('last_post'),
				'first_post_id_idx'	=> array('first_post_id')
			)
		);
	
		$this->db->create_table('topics', $this->schemas['topics']);
	}
	
	function init_users()
	{
		$this->schemas['users'] = array(
			'FIELDS'		=> array(
				'id'				=> array(
					'datatype'		=> 'SERIAL',
					'allow_null'	=> false
				),
				'group_id'			=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '3'
				),
				'username'			=> array(
					'datatype'		=> 'VARCHAR(200)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				),
				'password'			=> array(
					'datatype'		=> 'VARCHAR(40)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				),
				'email'				=> array(
					'datatype'		=> 'VARCHAR(80)',
					'allow_null'	=> false,
					'default'		=> '\'\''
				),
				'title'				=> array(
					'datatype'		=> 'VARCHAR(50)',
					'allow_null'	=> true
				),
				'realname'			=> array(
					'datatype'		=> 'VARCHAR(40)',
					'allow_null'	=> true
				),
				'url'				=> array(
					'datatype'		=> 'VARCHAR(100)',
					'allow_null'	=> true
				),
				'jabber'			=> array(
					'datatype'		=> 'VARCHAR(80)',
					'allow_null'	=> true
				),
				'icq'				=> array(
					'datatype'		=> 'VARCHAR(12)',
					'allow_null'	=> true
				),
				'msn'				=> array(
					'datatype'		=> 'VARCHAR(80)',
					'allow_null'	=> true
				),
				'aim'				=> array(
					'datatype'		=> 'VARCHAR(30)',
					'allow_null'	=> true
				),
				'yahoo'				=> array(
					'datatype'		=> 'VARCHAR(30)',
					'allow_null'	=> true
				),
				'location'			=> array(
					'datatype'		=> 'VARCHAR(30)',
					'allow_null'	=> true
				),
				'signature'			=> array(
					'datatype'		=> 'TEXT',
					'allow_null'	=> true
				),
				'disp_topics'		=> array(
					'datatype'		=> 'TINYINT(3) UNSIGNED',
					'allow_null'	=> true
				),
				'disp_posts'		=> array(
					'datatype'		=> 'TINYINT(3) UNSIGNED',
					'allow_null'	=> true
				),
				'email_setting'		=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'notify_with_post'	=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'auto_notify'		=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'show_smilies'		=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'show_img'			=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'show_img_sig'		=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'show_avatars'		=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'show_sig'			=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '1'
				),
				'timezone'			=> array(
					'datatype'		=> 'FLOAT',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'dst'				=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'time_format'		=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'date_format'		=> array(
					'datatype'		=> 'TINYINT(1)',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'language'			=> array(
					'datatype'		=> 'VARCHAR(25)',
					'allow_null'	=> false,
					'default'		=> '\'English\''
				),
				'style'				=> array(
					'datatype'		=> 'VARCHAR(25)',
					'allow_null'	=> false,
					'default'		=> '\'Oxygen\''
				),
				'num_posts'			=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'last_post'			=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> true
				),
				'last_search'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> true
				),
				'last_email_sent'	=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> true
				),
				'registered'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'registration_ip'	=> array(
					'datatype'		=> 'VARCHAR(39)',
					'allow_null'	=> false,
					'default'		=> '\'0.0.0.0\''
				),
				'last_visit'		=> array(
					'datatype'		=> 'INT(10) UNSIGNED',
					'allow_null'	=> false,
					'default'		=> '0'
				),
				'admin_note'		=> array(
					'datatype'		=> 'VARCHAR(30)',
					'allow_null'	=> true
				),
				'activate_string'	=> array(
					'datatype'		=> 'VARCHAR(80)',
					'allow_null'	=> true
				),
				'activate_key'		=> array(
					'datatype'		=> 'VARCHAR(8)',
					'allow_null'	=> true
				),
			),
			'PRIMARY KEY'	=> array('id'),
			'INDEXES'		=> array(
				'registered_idx'	=> array('registered'),
				'username_idx'		=> array('username')
			)
		);
	
		if ($this->db_type == 'mysql' || $this->db_type == 'mysqli' || $this->db_type == 'mysql_innodb' || $this->db_type == 'mysqli_innodb')
			$this->schemas['users']['INDEXES']['username_idx'] = array('username(8)');
	
		$this->db->create_table('users', $this->schemas['users']);
	}

	function add_row($table, $data)
	{
		$fields = array_keys($this->schemas[$table]['FIELDS']);
		$keys = array_keys($data);
		$diff = array_diff($fields, $keys);

		if (count($fields) != count($keys) || !empty($diff))
			error('Field list doesn\'t match for '.$table.' table.', __FILE__, __LINE__);

		foreach ($data as $key => $value)
			$data[$key] = $value === null ? 'NULL' : '\''.$this->db->escape($value).'\'';

		$result = $this->db->query_build(array(
			'INSERT'	=> implode(', ', array_keys($data)),
			'INTO'		=> $table,
			'VALUES'	=> implode(', ', array_values($data)),
		));

		// TODO: Check the query was successful
	}

	function pass_hash($str)
	{
		if (function_exists('sha1'))	// Only in PHP 4.3.0+
			return sha1($str);
		else if (function_exists('mhash'))	// Only if Mhash library is loaded
			return bin2hex(mhash(MHASH_SHA1, $str));
		else
			return md5($str);
	}
	
	function random_pass($len)
	{
		static $chars;
		
		if (!isset($chars))
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$key = '';
		for ($i = 0;$i < $len;$i++)
			$key .= substr($chars, (mt_rand() % strlen($chars)), 1);

		return $key;
	}
}
