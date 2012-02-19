<?php

/**
 * @copyright (C) 2012 FluxBB (http://fluxbb.org)
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package FluxBB
 */

class Forum
{
	/**
	 * @var wrapper_mysql Database instance
	 */
	var $db;

	/**
	 * @var array Database configuration
	 */
	var $db_config;

	/**
	 * @var FluxBB FluxBB instance
	 */
	var $fluxbb;

	/**
	 * @var array Current forum configuration
	 * 		For example: array('type' = 'phpBB_3_0', 'path' => '../')
	 */
	var $forum_config;

	/**
	 * @var string Full path to the forum root directory
	 */
	var $path;

	/**
	 * Constructor
	 *
	 * @param array $forum_config
	 * 		Forum configuration
	 *
	 * @param type FluxBB $fluxbb
	 * 		FluxBB instance
	 */
	function Forum($forum_config, $fluxbb)
	{
		$this->forum_config = $forum_config;

		if (!empty($forum_config['path']))
		{
			// Check whether it is absolute path
			if (strpos($forum_config['path'], '/') === 0 || strpos($forum_config['path'], ':') === 1)
				$this->path = $forum_config['path'];

			// Or relative
			else
				$this->path = PUN_ROOT.$forum_config['path'];

			$this->path = realpath(rtrim($this->path, '/')).'/';
			conv_log('Will convert avatars', true);
			conv_log('Forum path: '.$this->path, true);
		}
		else
			conv_log('Warning: avatars will not be converted due to missing path', true);

		$this->fluxbb = $fluxbb;
	}

	/**
	 * Connect to the old forum database
	 *
	 * @param array $db_config
	 */
	function connect_database($db_config)
	{
		$this->db_config = $db_config;

		$this->db = connect_database($db_config);

		$this->initialize();
	}

	/**
	 * Close database connection
	 */
	function close_database()
	{
		if (!isset($this->db))
			return false;

		$this->db->end_transaction();
		$this->db->close();
	}

	/**
	 * Check whether converter is able to convert password for the current forum
	 */
	function converts_password()
	{
		$const = get_class($this).'::CONVERTS_PASSWORD';
		return defined($const) && constant($const);
	}

	/**
	 * Check whether current table has more rows - if yes, redirect to the next page of the current stage
	 *
	 * @param string $old_table
	 * @param string $old_field
	 * @param integer $start_at
	 */
	function redirect($old_table, $old_field, $start_at)
	{
		$result = $this->db->query_build(array(
			'SELECT'	=> 1,
			'FROM'		=> $this->db->escape($old_table),
			'WHERE'		=> $this->db->escape($old_field).' > '.intval($start_at),
			'LIMIT'		=> 1,
		)) or conv_error('Unable to fetch num rows', __FILE__, __LINE__, $this->db->error());

		if ($this->db->num_rows($result))
			return $start_at;

		return false;
	}

	/**
	 * Fetch row count for each (or one) tables
	 *
	 * @param string $table
	 * @return type
	 */
	function fetch_item_count($table = null)
	{
		global $session;

		// First we look for whether we have this data stored in $session
		if (isset($session['forum_item_count']))
			$tables = $session['forum_item_count'];

		// When not found, get item count and save in $session for futher use
		else
		{
			$tables = array();
			foreach ($this->steps as $cur_step => $table_info)
			{
				$count = 0;

				if (is_numeric($table_info))
					$count = $table_info;

				else if (is_array($table_info))
				{
					$query = array(
						'SELECT'	=> 'COUNT('.$this->db->escape($table_info[1]).')',
						'FROM'		=> $this->db->escape($table_info[0])
					);
					if (isset($table_info[2]))
						$query['WHERE'] = $table_info[2];

					$result = $this->db->query_build($query) or conv_error('Unable to fetch num rows for '.$cur_step, __FILE__, __LINE__, $this->db->error());
					$count = $this->db->result($result);
				}

				$tables[$cur_step] = $count;
			}
			$session['forum_item_count'] = $tables;
		}

		if (isset($table))
			return isset($tables[$table]) ? $tables[$table] : -1;

		return $tables;
	}

	/**
	 * Convert specified data to the UTF-8 charset
	 *
	 * @param string $str
	 */
	function convert_to_utf8($str)
	{
		return convert_to_utf8($str, $this->db_config['charset']);
	}

}
