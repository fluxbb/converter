<?php

class FluxBB
{
	var $db;
	var $db_type;

	function FluxBB($db, $db_type)
	{
		$db->set_names('utf8');

		$this->db = $db;
		$this->db_type = $db_type;

		$this->schemas = array();
	}

	function add_row($table, $data, $ignore_column_count = false)
	{
	//	$fields = array_keys($this->schemas[$table]['FIELDS']);
//		$keys = array_keys($data);
//		$diff = array_diff($fields, $keys);

//		if (!$ignore_column_count && (count($fields) != count($keys) || !empty($diff)))
//			error('Field list doesn\'t match for '.$table.' table.', __FILE__, __LINE__);

		foreach ($data as $key => $value)
			$data[$key] = $value === null ? 'NULL' : '\''.$this->db->escape($value).'\'';

		$result = $this->db->query_build(array(
			'INSERT'	=> implode(', ', array_keys($data)),
			'INTO'		=> $table,
			'VALUES'	=> implode(', ', array_values($data)),
		)) or error('Unable to insert values', __FILE__, __LINE__, $this->db->error());

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
