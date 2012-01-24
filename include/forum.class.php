<?php

/**
 * @copyright (C) 2012 FluxBB (http://fluxbb.org)
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package FluxBB
 */

class Forum
{
	public $db;
	public $fluxbb;

	protected $db_config;
	protected $forum_config;
	protected $path;

	/**
	 * Constructor
	 *
	 * @param array $forum_config
	 * 		Forum configuration
	 *
	 * @param type FluxBB $fluxbb
	 * 		FluxBB instance
	 */
	function __construct(array $forum_config, FluxBB $fluxbb)
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

			$this->path = rtrim($this->path, '/').'/';
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
		$class = get_class($this);
		return defined($class.'::CONVERTS_PASSWORD') && constant($class.'::CONVERTS_PASSWORD');
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
	 * Convert specified data to the UTF-8 charset
	 *
	 * @param string $str
	 */
	function convert_to_utf8($str)
	{
		return convert_to_utf8($str, $this->db_config['charset']);
	}

}
