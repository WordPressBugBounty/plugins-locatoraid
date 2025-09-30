<?php if (! defined('ABSPATH')) exit; // Exit if accessed directly
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2016, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2016, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */

/**
 * Query Builder Class
 *
 * This is the platform-independent base Query Builder implementation class.
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/database/
 */

if( ! class_exists('HC_Database_Query_Builder') ){

class HC_Database_Query_Builder {
	public $dbprefix		= '';
	public $char_set		= 'utf8';
	public $swap_pre		= '';
	public $db_debug		= FALSE;
	public $db_debug_level	= 'low';
	public $benchmark		= 0;
	public $bind_marker		= '?';
	public $save_queries		= TRUE;
	public $queries			= array();
	public $query_times		= array();
	public $trans_enabled		= TRUE;
	public $trans_strict		= TRUE;
	protected $_trans_depth		= 0;
	protected $_trans_status	= TRUE;
	protected $_trans_failure	= FALSE;
	public $cache_on		= FALSE;
	public $cachedir		= '';
	public $cache_autodel		= FALSE;
	public $CACHE;
	protected $_protect_identifiers		= TRUE;
	protected $_reserved_identifiers	= array('*');
	protected $_escape_char = '`';
	protected $_like_escape_str = " ESCAPE '%s' ";
	protected $_like_escape_chr = '!';
	protected $_random_keyword = array('RAND()', 'RAND(%d)');
	protected $_count_string = 'SELECT COUNT(*) AS ';
	protected $return_delete_sql		= FALSE;
	protected $reset_delete_data		= FALSE;
	protected $qb_select			= array();
	protected $qb_distinct			= FALSE;
	protected $qb_from			= array();
	protected $qb_join			= array();
	protected $qb_join_tables	= array();
	protected $qb_where			= array();
	protected $qb_groupby			= array();
	protected $qb_having			= array();
	protected $qb_keys			= array();
	protected $qb_limit			= FALSE;
	protected $qb_offset			= FALSE;
	protected $qb_orderby			= array();
	protected $qb_set			= array();
	protected $qb_aliased_tables		= array();
	protected $qb_where_group_started	= FALSE;
	protected $qb_where_group_count		= 0;
	protected $qb_caching				= FALSE;
	protected $qb_cache_exists			= array();
	protected $qb_cache_select			= array();
	protected $qb_cache_from			= array();
	protected $qb_cache_join			= array();
	protected $qb_cache_where			= array();
	protected $qb_cache_groupby			= array();
	protected $qb_cache_having			= array();
	protected $qb_cache_orderby			= array();
	protected $qb_cache_set				= array();
	protected $qb_no_escape 			= array();
	protected $qb_cache_no_escape			= array();

	public function select($select = '*', $escape = NULL)
	{
		if (is_string($select))
		{
			$select = explode(',', $select);
		}

		// If the escape value was not set, we will base it on the global setting
		is_bool($escape) OR $escape = $this->_protect_identifiers;

		foreach ($select as $val)
		{
			$val = trim($val);

			if ($val !== '')
			{
				$this->qb_select[] = $val;
				$this->qb_no_escape[] = $escape;

				if ($this->qb_caching === TRUE)
				{
					$this->qb_cache_select[] = $val;
					$this->qb_cache_exists[] = 'select';
					$this->qb_cache_no_escape[] = $escape;
				}
			}
		}

		return $this;
	}

	public function select_max($select = '', $alias = '')
	{
		return $this->_max_min_avg_sum($select, $alias, 'MAX');
	}

	public function select_min($select = '', $alias = '')
	{
		return $this->_max_min_avg_sum($select, $alias, 'MIN');
	}

	public function select_avg($select = '', $alias = '')
	{
		return $this->_max_min_avg_sum($select, $alias, 'AVG');
	}

	public function select_sum($select = '', $alias = '')
	{
		return $this->_max_min_avg_sum($select, $alias, 'SUM');
	}

	protected function _max_min_avg_sum($select = '', $alias = '', $type = 'MAX')
	{
		if ( ! is_string($select) OR $select === '')
		{
			$this->display_error('db_invalid_query');
		}

		$type = strtoupper($type);

		if ( ! in_array($type, array('MAX', 'MIN', 'AVG', 'SUM')))
		{
			show_error('Invalid function type: '.$type);
		}

		if ($alias === '')
		{
			$alias = $this->_create_alias_from_table(trim($select));
		}

		$sql = $type.'('.$this->protect_identifiers(trim($select)).') AS '.$this->escape_identifiers(trim($alias));

		$this->qb_select[] = $sql;
		$this->qb_no_escape[] = NULL;

		if ($this->qb_caching === TRUE)
		{
			$this->qb_cache_select[] = $sql;
			$this->qb_cache_exists[] = 'select';
		}

		return $this;
	}

	protected function _create_alias_from_table($item)
	{
		if (strpos($item, '.') !== FALSE)
		{
			$item = explode('.', $item);
			return end($item);
		}

		return $item;
	}

	public function distinct($val = TRUE)
	{
		$this->qb_distinct = is_bool($val) ? $val : TRUE;
		return $this;
	}

	public function from($from)
	{
		foreach ((array) $from as $val)
		{
			if (strpos($val, ',') !== FALSE)
			{
				foreach (explode(',', $val) as $v)
				{
					$v = trim($v);
					$this->_track_aliases($v);

					$this->qb_from[] = $v = $this->protect_identifiers($v, TRUE, NULL, FALSE);

					if ($this->qb_caching === TRUE)
					{
						$this->qb_cache_from[] = $v;
						$this->qb_cache_exists[] = 'from';
					}
				}
			}
			else
			{
				$val = trim($val);

				// Extract any aliases that might exist. We use this information
				// in the protect_identifiers to know whether to add a table prefix
				$this->_track_aliases($val);

				$this->qb_from[] = $val = $this->protect_identifiers($val, TRUE, NULL, FALSE);

				if ($this->qb_caching === TRUE)
				{
					$this->qb_cache_from[] = $val;
					$this->qb_cache_exists[] = 'from';
				}
			}
		}

		return $this;
	}

	public function join($table, $cond, $type = '', $escape = NULL)
	{
		if ($type !== '')
		{
			$type = strtoupper(trim($type));

			if ( ! in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'), TRUE))
			{
				$type = '';
			}
			else
			{
				$type .= ' ';
			}
		}

		// Extract any aliases that might exist. We use this information
		// in the protect_identifiers to know whether to add a table prefix
		$this->_track_aliases($table);

		is_bool($escape) OR $escape = $this->_protect_identifiers;

		if ( ! $this->_has_operator($cond))
		{
			$cond = ' USING ('.($escape ? $this->escape_identifiers($cond) : $cond).')';
		}
		elseif ($escape === FALSE)
		{
			$cond = ' ON '.$cond;
		}
		else
		{
			// Split multiple conditions
			if (preg_match_all('/\sAND\s|\sOR\s/i', $cond, $joints, PREG_OFFSET_CAPTURE))
			{
				$conditions = array();
				$joints = $joints[0];
				array_unshift($joints, array('', 0));

				for ($i = count($joints) - 1, $pos = strlen($cond); $i >= 0; $i--)
				{
					$joints[$i][1] += strlen($joints[$i][0]); // offset
					$conditions[$i] = substr($cond, $joints[$i][1], $pos - $joints[$i][1]);
					$pos = $joints[$i][1] - strlen($joints[$i][0]);
					$joints[$i] = $joints[$i][0];
				}
			}
			else
			{
				$conditions = array($cond);
				$joints = array('');
			}

			$cond = ' ON ';
			for ($i = 0, $c = count($conditions); $i < $c; $i++)
			{
				$operator = $this->_get_operator($conditions[$i]);
				$cond .= $joints[$i];
				$cond .= preg_match("/(\(*)?([\[\]\w\.'-]+)".preg_quote($operator)."(.*)/i", $conditions[$i], $match)
					? $match[1].$this->protect_identifiers($match[2]).$operator.$this->protect_identifiers($match[3])
					: $conditions[$i];
			}
		}

		// Do we want to escape the table name?
		if ($escape === TRUE)
		{
			$table = $this->protect_identifiers($table, TRUE, NULL, FALSE);
		}

		// Assemble the JOIN statement
		$this->qb_join[] = $join = $type.'JOIN '.$table.$cond;
		$this->qb_join_tables[] = $table;

		if ($this->qb_caching === TRUE)
		{
			$this->qb_cache_join[] = $join;
			$this->qb_cache_exists[] = 'join';
		}

		return $this;
	}

	public function where($key, $value = NULL, $escape = NULL)
	{
		return $this->_wh('qb_where', $key, $value, 'AND ', $escape);
	}

	public function or_where($key, $value = NULL, $escape = NULL)
	{
		return $this->_wh('qb_where', $key, $value, 'OR ', $escape);
	}

	protected function _wh($qb_key, $key, $value = NULL, $type = 'AND ', $escape = NULL)
	{
		$qb_cache_key = ($qb_key === 'qb_having') ? 'qb_cache_having' : 'qb_cache_where';

		if ( ! is_array($key))
		{
			$key = array($key => $value);
		}

		// If the escape value was not set will base it on the global setting
		is_bool($escape) OR $escape = $this->_protect_identifiers;

		foreach ($key as $k => $v)
		{
			$prefix = (count($this->$qb_key) === 0 && count($this->$qb_cache_key) === 0)
				? $this->_group_get_type('')
				: $this->_group_get_type($type);

			if ($v !== NULL)
			{
				if ($escape === TRUE)
				{
					$v = ' '.$this->escape($v);
				}

				if ( ! $this->_has_operator($k))
				{
					$k .= ' = ';
				}
			}
			elseif ( ! $this->_has_operator($k))
			{
				// value appears not to have been set, assign the test to IS NULL
				$k .= ' IS NULL';
			}
			elseif (preg_match('/\s*(!?=|<>|IS(?:\s+NOT)?)\s*$/i', $k, $match, PREG_OFFSET_CAPTURE))
			{
				$k = substr($k, 0, $match[0][1]).($match[1][0] === '=' ? ' IS NULL' : ' IS NOT NULL');
			}

			$this->{$qb_key}[] = array('condition' => $prefix.$k.$v, 'escape' => $escape);
			if ($this->qb_caching === TRUE)
			{
				$this->{$qb_cache_key}[] = array('condition' => $prefix.$k.$v, 'escape' => $escape);
				$this->qb_cache_exists[] = substr($qb_key, 3);
			}

		}

		return $this;
	}

	public function where_in($key = NULL, $values = NULL, $escape = NULL)
	{
		return $this->_where_in($key, $values, FALSE, 'AND ', $escape);
	}

	public function or_where_in($key = NULL, $values = NULL, $escape = NULL)
	{
		return $this->_where_in($key, $values, FALSE, 'OR ', $escape);
	}

	public function where_not_in($key = NULL, $values = NULL, $escape = NULL)
	{
		return $this->_where_in($key, $values, TRUE, 'AND ', $escape);
	}

	public function or_where_not_in($key = NULL, $values = NULL, $escape = NULL)
	{
		return $this->_where_in($key, $values, TRUE, 'OR ', $escape);
	}

	protected function _where_in($key = NULL, $values = NULL, $not = FALSE, $type = 'AND ', $escape = NULL)
	{
		if ($key === NULL OR $values === NULL)
		{
			return $this;
		}

		if ( ! is_array($values))
		{
			$values = array($values);
		}

		is_bool($escape) OR $escape = $this->_protect_identifiers;

		$not = ($not) ? ' NOT' : '';

		if ($escape === TRUE)
		{
			$where_in = array();
			foreach ($values as $value)
			{
				$where_in[] = $this->escape($value);
			}
		}
		else
		{
			$where_in = array_values($values);
		}

		$prefix = (count($this->qb_where) === 0 && count($this->qb_cache_where) === 0)
			? $this->_group_get_type('')
			: $this->_group_get_type($type);

		$where_in = array(
			'condition' => $prefix.$key.$not.' IN('.implode(', ', $where_in).')',
			'escape' => $escape
		);

		$this->qb_where[] = $where_in;
		if ($this->qb_caching === TRUE)
		{
			$this->qb_cache_where[] = $where_in;
			$this->qb_cache_exists[] = 'where';
		}

		return $this;
	}

	public function like($field, $match = '', $side = 'both', $escape = NULL)
	{
		return $this->_like($field, $match, 'AND ', $side, '', $escape);
	}

	public function not_like($field, $match = '', $side = 'both', $escape = NULL)
	{
		return $this->_like($field, $match, 'AND ', $side, 'NOT', $escape);
	}

	public function or_like($field, $match = '', $side = 'both', $escape = NULL)
	{
		return $this->_like($field, $match, 'OR ', $side, '', $escape);
	}

	public function or_not_like($field, $match = '', $side = 'both', $escape = NULL)
	{
		return $this->_like($field, $match, 'OR ', $side, 'NOT', $escape);
	}

	protected function _like($field, $match = '', $type = 'AND ', $side = 'both', $not = '', $escape = NULL)
	{
		if ( ! is_array($field))
		{
			$field = array($field => $match);
		}

		is_bool($escape) OR $escape = $this->_protect_identifiers;
		// lowercase $side in case somebody writes e.g. 'BEFORE' instead of 'before' (doh)
		$side = strtolower($side);

		foreach ($field as $k => $v)
		{
			$prefix = (count($this->qb_where) === 0 && count($this->qb_cache_where) === 0)
				? $this->_group_get_type('') : $this->_group_get_type($type);

			if ($escape === TRUE)
			{
				$v = $this->escape_like_str($v);
			}

			if ($side === 'none')
			{
				$like_statement = "{$prefix} {$k} {$not} LIKE '{$v}'";
			}
			elseif ($side === 'before')
			{
				$like_statement = "{$prefix} {$k} {$not} LIKE '%{$v}'";
			}
			elseif ($side === 'after')
			{
				$like_statement = "{$prefix} {$k} {$not} LIKE '{$v}%'";
			}
			else
			{
				$like_statement = "{$prefix} {$k} {$not} LIKE '%{$v}%'";
			}

			// some platforms require an escape sequence definition for LIKE wildcards
			if ($escape === TRUE && $this->_like_escape_str !== '')
			{
				$like_statement .= sprintf($this->_like_escape_str, $this->_like_escape_chr);
			}

			$this->qb_where[] = array('condition' => $like_statement, 'escape' => $escape);
			if ($this->qb_caching === TRUE)
			{
				$this->qb_cache_where[] = array('condition' => $like_statement, 'escape' => $escape);
				$this->qb_cache_exists[] = 'where';
			}
		}

		return $this;
	}

	public function group_start($not = '', $type = 'AND ')
	{
		$type = $this->_group_get_type($type);

		$this->qb_where_group_started = TRUE;
		$prefix = (count($this->qb_where) === 0 && count($this->qb_cache_where) === 0) ? '' : $type;
		$where = array(
			'condition' => $prefix.$not.str_repeat(' ', ++$this->qb_where_group_count).' (',
			'escape' => FALSE
		);

		$this->qb_where[] = $where;
		if ($this->qb_caching)
		{
			$this->qb_cache_where[] = $where;
		}

		return $this;
	}

	public function or_group_start()
	{
		return $this->group_start('', 'OR ');
	}

	public function not_group_start()
	{
		return $this->group_start('NOT ', 'AND ');
	}

	public function or_not_group_start()
	{
		return $this->group_start('NOT ', 'OR ');
	}

	public function group_end()
	{
		$this->qb_where_group_started = FALSE;
		$where = array(
			'condition' => str_repeat(' ', $this->qb_where_group_count--).')',
			'escape' => FALSE
		);

		$this->qb_where[] = $where;
		if ($this->qb_caching)
		{
			$this->qb_cache_where[] = $where;
		}

		return $this;
	}

	protected function _group_get_type($type)
	{
		if ($this->qb_where_group_started)
		{
			$type = '';
			$this->qb_where_group_started = FALSE;
		}

		return $type;
	}

	public function group_by($by, $escape = NULL)
	{
		is_bool($escape) OR $escape = $this->_protect_identifiers;

		if (is_string($by))
		{
			$by = ($escape === TRUE)
				? explode(',', $by)
				: array($by);
		}

		foreach ($by as $val)
		{
			$val = trim($val);

			if ($val !== '')
			{
				$val = array('field' => $val, 'escape' => $escape);

				$this->qb_groupby[] = $val;
				if ($this->qb_caching === TRUE)
				{
					$this->qb_cache_groupby[] = $val;
					$this->qb_cache_exists[] = 'groupby';
				}
			}
		}

		return $this;
	}

	public function having($key, $value = NULL, $escape = NULL)
	{
		return $this->_wh('qb_having', $key, $value, 'AND ', $escape);
	}

	public function or_having($key, $value = NULL, $escape = NULL)
	{
		return $this->_wh('qb_having', $key, $value, 'OR ', $escape);
	}

	public function order_by($orderby, $direction = '', $escape = NULL)
	{
		$direction = strtoupper(trim($direction));

		if ($direction === 'RANDOM')
		{
			$direction = '';

			// Do we have a seed value?
			$orderby = ctype_digit((string) $orderby)
				? sprintf($this->_random_keyword[1], $orderby)
				: $this->_random_keyword[0];
		}
		elseif (empty($orderby))
		{
			return $this;
		}
		elseif ($direction !== '')
		{
			$direction = in_array($direction, array('ASC', 'DESC'), TRUE) ? ' '.$direction : '';
		}

		is_bool($escape) OR $escape = $this->_protect_identifiers;

		if ($escape === FALSE)
		{
			$qb_orderby[] = array('field' => $orderby, 'direction' => $direction, 'escape' => FALSE);
		}
		else
		{
			$qb_orderby = array();
			foreach (explode(',', $orderby) as $field)
			{
				$qb_orderby[] = ($direction === '' && preg_match('/\s+(ASC|DESC)$/i', rtrim($field), $match, PREG_OFFSET_CAPTURE))
					? array('field' => ltrim(substr($field, 0, $match[0][1])), 'direction' => ' '.$match[1][0], 'escape' => TRUE)
					: array('field' => trim($field), 'direction' => $direction, 'escape' => TRUE);
			}
		}

		$this->qb_orderby = array_merge($this->qb_orderby, $qb_orderby);
		if ($this->qb_caching === TRUE)
		{
			$this->qb_cache_orderby = array_merge($this->qb_cache_orderby, $qb_orderby);
			$this->qb_cache_exists[] = 'orderby';
		}

		return $this;
	}

	public function limit($value, $offset = 0)
	{
		is_null($value) OR $this->qb_limit = (int) $value;
		empty($offset) OR $this->qb_offset = (int) $offset;

		return $this;
	}

	public function offset($offset)
	{
		empty($offset) OR $this->qb_offset = (int) $offset;
		return $this;
	}

	protected function _limit($sql)
	{
		return $sql.' LIMIT '.($this->qb_offset ? $this->qb_offset.', ' : '').$this->qb_limit;
	}

	public function set($key, $value = '', $escape = NULL)
	{
		$key = $this->_object_to_array($key);

		if ( ! is_array($key))
		{
			$key = array($key => $value);
		}

		is_bool($escape) OR $escape = $this->_protect_identifiers;

		foreach ($key as $k => $v)
		{
			$this->qb_set[$this->protect_identifiers($k, FALSE, $escape)] = ($escape)
				? $this->escape($v) : $v;
		}

		return $this;
	}

	public function get_compiled_select($table = '', $reset = TRUE)
	{
		if ($table !== '')
		{
			$this->_track_aliases($table);
			$this->from($table);
		}

		$select = $this->_compile_select();

		if ($reset === TRUE)
		{
			$this->_reset_select();
		}

		return $select;
	}

	protected function _insert_batch($table, $keys, $values)
	{
		return 'INSERT INTO '.$table.' ('.implode(', ', $keys).') VALUES '.implode(', ', $values);
	}

	public function set_insert_batch($key, $value = '', $escape = NULL)
	{
		$key = $this->_object_to_array_batch($key);

		if ( ! is_array($key))
		{
			$key = array($key => $value);
		}

		is_bool($escape) OR $escape = $this->_protect_identifiers;

		$keys = array_keys($this->_object_to_array(current($key)));
		sort($keys);

		foreach ($key as $row)
		{
			$row = $this->_object_to_array($row);
			if (count(array_diff($keys, array_keys($row))) > 0 OR count(array_diff(array_keys($row), $keys)) > 0)
			{
				// batch function above returns an error on an empty array
				$this->qb_set[] = array();
				return;
			}

			ksort($row); // puts $row in the same order as our keys

			if ($escape !== FALSE)
			{
				$clean = array();
				foreach ($row as $value)
				{
					$clean[] = $this->escape($value);
				}

				$row = $clean;
			}

			$this->qb_set[] = '('.implode(',', $row).')';
		}

		foreach ($keys as $k)
		{
			$this->qb_keys[] = $this->protect_identifiers($k, FALSE, $escape);
		}

		return $this;
	}

	public function get_compiled_insert($table = '', $reset = TRUE)
	{
		if ($this->_validate_insert($table) === FALSE)
		{
			return FALSE;
		}

		$sql = $this->_insert(
			$this->protect_identifiers(
				$this->qb_from[0], TRUE, NULL, FALSE
			),
			array_keys($this->qb_set),
			array_values($this->qb_set)
		);

		if ($reset === TRUE)
		{
			$this->_reset_write();
		}

		return $sql;
	}

	protected function _validate_insert($table = '')
	{
		if (count($this->qb_set) === 0)
		{
			return ($this->db_debug) ? $this->display_error('db_must_use_set') : FALSE;
		}

		if ($table !== '')
		{
			$this->qb_from[0] = $table;
		}
		elseif ( ! isset($this->qb_from[0]))
		{
			return ($this->db_debug) ? $this->display_error('db_must_set_table') : FALSE;
		}

		return TRUE;
	}

	protected function _replace($table, $keys, $values)
	{
		return 'REPLACE INTO '.$table.' ('.implode(', ', $keys).') VALUES ('.implode(', ', $values).')';
	}

	protected function _from_tables()
	{
		return implode(', ', $this->qb_from);
	}

	public function get_compiled_update($table = '', $reset = TRUE)
	{
		// Combine any cached components with the current statements
		$this->_merge_cache();

		if ($this->_validate_update($table) === FALSE)
		{
			return FALSE;
		}

		$sql = $this->_update($this->qb_from[0], $this->qb_set);

		if ($reset === TRUE)
		{
			$this->_reset_write();
		}

		return $sql;
	}

	protected function _validate_update($table)
	{
		if (count($this->qb_set) === 0)
		{
			return ($this->db_debug) ? $this->display_error('db_must_use_set') : FALSE;
		}

		if ($table !== '')
		{
			$this->qb_from = array($this->protect_identifiers($table, TRUE, NULL, FALSE));
		}
		elseif ( ! isset($this->qb_from[0]))
		{
			return ($this->db_debug) ? $this->display_error('db_must_set_table') : FALSE;
		}

		return TRUE;
	}

	protected function _update_batch($table, $values, $index)
	{
		$ids = array();
		foreach ($values as $key => $val)
		{
			$ids[] = $val[$index];

			foreach (array_keys($val) as $field)
			{
				if ($field !== $index)
				{
					$final[$field][] = 'WHEN '.$index.' = '.$val[$index].' THEN '.$val[$field];
				}
			}
		}

		$cases = '';
		foreach ($final as $k => $v)
		{
			$cases .= $k." = CASE \n"
				.implode("\n", $v)."\n"
				.'ELSE '.$k.' END, ';
		}

		$this->where($index.' IN('.implode(',', $ids).')', NULL, FALSE);

		return 'UPDATE '.$table.' SET '.substr($cases, 0, -2).$this->_compile_wh('qb_where');
	}

	public function set_update_batch($key, $index = '', $escape = NULL)
	{
		$key = $this->_object_to_array_batch($key);

		if ( ! is_array($key))
		{
			// @todo error
		}

		is_bool($escape) OR $escape = $this->_protect_identifiers;

		foreach ($key as $k => $v)
		{
			$index_set = FALSE;
			$clean = array();
			foreach ($v as $k2 => $v2)
			{
				if ($k2 === $index)
				{
					$index_set = TRUE;
				}

				$clean[$this->protect_identifiers($k2, FALSE, $escape)] = ($escape === FALSE) ? $v2 : $this->escape($v2);
			}

			if ($index_set === FALSE)
			{
				return $this->display_error('db_batch_missing_index');
			}

			$this->qb_set[] = $clean;
		}

		return $this;
	}

	protected function _truncate($table)
	{
		return 'TRUNCATE '.$table;
	}

	public function get_compiled_delete($table = '', $reset = TRUE)
	{
		$this->return_delete_sql = TRUE;
		$sql = $this->delete($table, '', NULL, $reset);
		$this->return_delete_sql = FALSE;
		return $sql;
	}

	public function delete($table = '', $where = '', $limit = NULL, $reset_data = TRUE)
	{
		// Combine any cached components with the current statements
		$this->_merge_cache();

		if ($table === '')
		{
			if ( ! isset($this->qb_from[0]))
			{
				return ($this->db_debug) ? $this->display_error('db_must_set_table') : FALSE;
			}

			$table = $this->qb_from[0];
		}
		elseif (is_array($table))
		{
			empty($where) && $reset_data = FALSE;

			foreach ($table as $single_table)
			{
				$this->delete($single_table, $where, $limit, $reset_data);
			}

			return;
		}
		else
		{
			$table = $this->protect_identifiers($table, TRUE, NULL, FALSE);
		}

		if ($where !== '')
		{
			$this->where($where);
		}

		if ( ! empty($limit))
		{
			$this->limit($limit);
		}

		if (count($this->qb_where) === 0)
		{
			return ($this->db_debug) ? $this->display_error('db_del_must_use_where') : FALSE;
		}

		$sql = $this->_delete($table);
		if ($reset_data)
		{
			$this->_reset_write();
		}

		return ($this->return_delete_sql === TRUE) ? $sql : $this->query($sql);
	}

	protected function _delete($table)
	{
		// $sql = 'DELETE FROM '.$table.$this->_compile_wh('qb_where')
			// .($this->qb_limit ? ' LIMIT '.$this->qb_limit : '');

		if (count($this->qb_join) > 0){
			$sql = 'DELETE ' . $table;
			foreach( $this->qb_join_tables as $jt ){
				$sql .= ', ' . $jt;
			}
			$sql .= ' FROM '.$table;
		}
		else {
			$sql = 'DELETE FROM '.$table;
		}

	// Write the "JOIN" portion of the query
		if (count($this->qb_join) > 0){
			$sql .= "\n".implode("\n", $this->qb_join);
		}

		$sql .= $this->_compile_wh('qb_where')
			.($this->qb_limit ? ' LIMIT '.$this->qb_limit : '');

		return $sql;
	}

	public function dbprefix($table = '')
	{
		if ($table === '')
		{
			$this->display_error('db_table_name_required');
		}

		return $this->dbprefix.$table;
	}

	public function set_prefix($prefix = '')
	{
		$this->dbprefix = $prefix;
		return $this;
	}
	public function prefix(){
		return $this->dbprefix;
	}

	protected function _track_aliases($table)
	{
		if (is_array($table))
		{
			foreach ($table as $t)
			{
				$this->_track_aliases($t);
			}
			return;
		}

		// Does the string contain a comma?  If so, we need to separate
		// the string into discreet statements
		if (strpos($table, ',') !== FALSE)
		{
			return $this->_track_aliases(explode(',', $table));
		}

		// if a table alias is used we can recognize it by a space
		if (strpos($table, ' ') !== FALSE)
		{
			// if the alias is written with the AS keyword, remove it
			$table = preg_replace('/\s+AS\s+/i', ' ', $table);

			// Grab the alias
			$table = trim(strrchr($table, ' '));

			// Store the alias, if it doesn't already exist
			if ( ! in_array($table, $this->qb_aliased_tables))
			{
				$this->qb_aliased_tables[] = $table;
			}
		}
	}

	protected function _compile_select($select_override = FALSE)
	{
		// Combine any cached components with the current statements
		$this->_merge_cache();

		// Write the "select" portion of the query
		if ($select_override !== FALSE)
		{
			$sql = $select_override;
		}
		else
		{
			$sql = ( ! $this->qb_distinct) ? 'SELECT ' : 'SELECT DISTINCT ';

			if (count($this->qb_select) === 0)
			{
				$sql .= '*';
			}
			else
			{
				// Cycle through the "select" portion of the query and prep each column name.
				// The reason we protect identifiers here rather than in the select() function
				// is because until the user calls the from() function we don't know if there are aliases
				foreach ($this->qb_select as $key => $val)
				{
					$no_escape = isset($this->qb_no_escape[$key]) ? $this->qb_no_escape[$key] : NULL;
					$this->qb_select[$key] = $this->protect_identifiers($val, FALSE, $no_escape);
				}

				$sql .= implode(', ', $this->qb_select);
			}
		}

		// Write the "FROM" portion of the query
		if (count($this->qb_from) > 0)
		{
			$sql .= "\nFROM ".$this->_from_tables();
		}

		// Write the "JOIN" portion of the query
		if (count($this->qb_join) > 0)
		{
			$sql .= "\n".implode("\n", $this->qb_join);
		}

		$sql .= $this->_compile_wh('qb_where')
			.$this->_compile_group_by()
			.$this->_compile_wh('qb_having')
			.$this->_compile_order_by(); // ORDER BY

		// LIMIT
		if ($this->qb_limit)
		{
			return $this->_limit($sql."\n");
		}

		return $sql;
	}

	public function get_compiled_where( $reset = TRUE )
	{
		$return = $this->_compile_wh('qb_where');
		$return = trim($return);
		if( substr($return, 0, strlen('WHERE ')) == 'WHERE ' ){
			$return = substr($return, strlen('WHERE '));
		}

		if ($reset === TRUE){
			$this->_reset_select();
		}

		return $return;
	}

	protected function _compile_wh($qb_key)
	{
		if (count($this->$qb_key) > 0)
		{
			for ($i = 0, $c = count($this->$qb_key); $i < $c; $i++)
			{
				// Is this condition already compiled?
				if (is_string($this->{$qb_key}[$i]))
				{
					continue;
				}
				elseif ($this->{$qb_key}[$i]['escape'] === FALSE)
				{
					$this->{$qb_key}[$i] = $this->{$qb_key}[$i]['condition'];
					continue;
				}

				// Split multiple conditions
				$conditions = preg_split(
					'/((?:^|\s+)AND\s+|(?:^|\s+)OR\s+)/i',
					$this->{$qb_key}[$i]['condition'],
					-1,
					PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
				);

				for ($ci = 0, $cc = count($conditions); $ci < $cc; $ci++)
				{
					if (($op = $this->_get_operator($conditions[$ci])) === FALSE
						OR ! @preg_match('/^(\(?)(.*)('.preg_quote($op, '/').')\s*(.*(?<!\)))?(\)?)$/i', $conditions[$ci], $matches))
					{
						continue;
					}

					// $matches = array(
					//	0 => '(test <= foo)',	/* the whole thing */
					//	1 => '(',		/* optional */
					//	2 => 'test',		/* the field name */
					//	3 => ' <= ',		/* $op */
					//	4 => 'foo',		/* optional, if $op is e.g. 'IS NULL' */
					//	5 => ')'		/* optional */
					// );

					if ( ! empty($matches[4]))
					{
						$this->_is_literal($matches[4]) OR $matches[4] = $this->protect_identifiers(trim($matches[4]));
						$matches[4] = ' '.$matches[4];
					}

					$conditions[$ci] = $matches[1].$this->protect_identifiers(trim($matches[2]))
						.' '.trim($matches[3]).$matches[4].$matches[5];
				}

				$this->{$qb_key}[$i] = implode('', $conditions);
			}

			return ($qb_key === 'qb_having' ? "\nHAVING " : "\nWHERE ")
				.implode("\n", $this->$qb_key);
		}

		return '';
	}

	protected function _compile_group_by()
	{
		if (count($this->qb_groupby) > 0)
		{
			for ($i = 0, $c = count($this->qb_groupby); $i < $c; $i++)
			{
				// Is it already compiled?
				if (is_string($this->qb_groupby[$i]))
				{
					continue;
				}

				$this->qb_groupby[$i] = ($this->qb_groupby[$i]['escape'] === FALSE OR $this->_is_literal($this->qb_groupby[$i]['field']))
					? $this->qb_groupby[$i]['field']
					: $this->protect_identifiers($this->qb_groupby[$i]['field']);
			}

			return "\nGROUP BY ".implode(', ', $this->qb_groupby);
		}

		return '';
	}

	protected function _compile_order_by()
	{
		if (is_array($this->qb_orderby) && count($this->qb_orderby) > 0)
		{
			for ($i = 0, $c = count($this->qb_orderby); $i < $c; $i++)
			{
				if ($this->qb_orderby[$i]['escape'] !== FALSE && ! $this->_is_literal($this->qb_orderby[$i]['field']))
				{
					$this->qb_orderby[$i]['field'] = $this->protect_identifiers($this->qb_orderby[$i]['field']);
				}

				$this->qb_orderby[$i] = $this->qb_orderby[$i]['field'].$this->qb_orderby[$i]['direction'];
			}

			return $this->qb_orderby = "\nORDER BY ".implode(', ', $this->qb_orderby);
		}
		elseif (is_string($this->qb_orderby))
		{
			return $this->qb_orderby;
		}

		return '';
	}

	protected function _object_to_array($object)
	{
		if ( ! is_object($object))
		{
			return $object;
		}

		$array = array();
		foreach (get_object_vars($object) as $key => $val)
		{
			// There are some built in keys we need to ignore for this conversion
			if ( ! is_object($val) && ! is_array($val) && $key !== '_parent_name')
			{
				$array[$key] = $val;
			}
		}

		return $array;
	}

	protected function _object_to_array_batch($object)
	{
		if ( ! is_object($object))
		{
			return $object;
		}

		$array = array();
		$out = get_object_vars($object);
		$fields = array_keys($out);

		foreach ($fields as $val)
		{
			// There are some built in keys we need to ignore for this conversion
			if ($val !== '_parent_name')
			{
				$i = 0;
				foreach ($out[$val] as $data)
				{
					$array[$i++][$val] = $data;
				}
			}
		}

		return $array;
	}

	public function start_cache()
	{
		$this->qb_caching = TRUE;
		return $this;
	}

	public function stop_cache()
	{
		$this->qb_caching = FALSE;
		return $this;
	}

	public function flush_cache()
	{
		$this->_reset_run(array(
			'qb_cache_select'		=> array(),
			'qb_cache_from'			=> array(),
			'qb_cache_join'			=> array(),
			'qb_cache_where'		=> array(),
			'qb_cache_groupby'		=> array(),
			'qb_cache_having'		=> array(),
			'qb_cache_orderby'		=> array(),
			'qb_cache_set'			=> array(),
			'qb_cache_exists'		=> array(),
			'qb_cache_no_escape'	=> array()
		));

		return $this;
	}

	protected function _merge_cache()
	{
		if (count($this->qb_cache_exists) === 0)
		{
			return;
		}
		elseif (in_array('select', $this->qb_cache_exists, TRUE))
		{
			$qb_no_escape = $this->qb_cache_no_escape;
		}

		foreach (array_unique($this->qb_cache_exists) as $val) // select, from, etc.
		{
			$qb_variable	= 'qb_'.$val;
			$qb_cache_var	= 'qb_cache_'.$val;
			$qb_new 	= $this->$qb_cache_var;

			for ($i = 0, $c = count($this->$qb_variable); $i < $c; $i++)
			{
				if ( ! in_array($this->{$qb_variable}[$i], $qb_new, TRUE))
				{
					$qb_new[] = $this->{$qb_variable}[$i];
					if ($val === 'select')
					{
						$qb_no_escape[] = $this->qb_no_escape[$i];
					}
				}
			}

			$this->$qb_variable = $qb_new;
			if ($val === 'select')
			{
				$this->qb_no_escape = $qb_no_escape;
			}
		}

		// If we are "protecting identifiers" we need to examine the "from"
		// portion of the query to determine if there are any aliases
		if ($this->_protect_identifiers === TRUE && count($this->qb_cache_from) > 0)
		{
			$this->_track_aliases($this->qb_from);
		}
	}

	protected function _is_literal($str)
	{
		$str = trim($str);

		if (empty($str) OR ctype_digit($str) OR (string) (float) $str === $str OR in_array(strtoupper($str), array('TRUE', 'FALSE'), TRUE))
		{
			return TRUE;
		}

		static $_str;

		if (empty($_str))
		{
			$_str = ($this->_escape_char !== '"')
				? array('"', "'") : array("'");
		}

		return in_array($str[0], $_str, TRUE);
	}

	public function reset_query()
	{
		$this->_reset_select();
		$this->_reset_write();
		return $this;
	}

	protected function _reset_run($qb_reset_items)
	{
		foreach ($qb_reset_items as $item => $default_value)
		{
			$this->$item = $default_value;
		}
	}

	protected function _reset_select()
	{
		$this->_reset_run(array(
			'qb_select'		=> array(),
			'qb_from'		=> array(),
			'qb_join'		=> array(),
			'qb_join_tables'	=> array(),
			'qb_where'		=> array(),
			'qb_groupby'		=> array(),
			'qb_having'		=> array(),
			'qb_orderby'		=> array(),
			'qb_aliased_tables'	=> array(),
			'qb_no_escape'		=> array(),
			'qb_distinct'		=> FALSE,
			'qb_limit'		=> FALSE,
			'qb_offset'		=> FALSE
		));
	}

	protected function _reset_write()
	{
		$this->_reset_run(array(
			'qb_set'	=> array(),
			'qb_from'	=> array(),
			'qb_join'	=> array(),
			'qb_where'	=> array(),
			'qb_orderby'	=> array(),
			'qb_keys'	=> array(),
			'qb_limit'	=> FALSE
		));
	}

	protected function _version()
	{
		return 'SELECT VERSION() AS ver';
	}

	public function trans_off()
	{
		$this->trans_enabled = FALSE;
	}

	public function trans_strict($mode = TRUE)
	{
		$this->trans_strict = is_bool($mode) ? $mode : TRUE;
	}

	public function trans_start($test_mode = FALSE)
	{
		if ( ! $this->trans_enabled)
		{
			return FALSE;
		}

		return $this->trans_begin($test_mode);
	}

	public function trans_complete()
	{
		if ( ! $this->trans_enabled)
		{
			return FALSE;
		}

		// The query() function will set this flag to FALSE in the event that a query failed
		if ($this->_trans_status === FALSE OR $this->_trans_failure === TRUE)
		{
			$this->trans_rollback();

			// If we are NOT running in strict mode, we will reset
			// the _trans_status flag so that subsequent groups of
			// transactions will be permitted.
			if ($this->trans_strict === FALSE)
			{
				$this->_trans_status = TRUE;
			}

			log_message('debug', 'DB Transaction Failure');
			return FALSE;
		}

		return $this->trans_commit();
	}

	public function trans_status()
	{
		return $this->_trans_status;
	}

	public function trans_begin($test_mode = FALSE)
	{
		if ( ! $this->trans_enabled)
		{
			return FALSE;
		}
		// When transactions are nested we only begin/commit/rollback the outermost ones
		elseif ($this->_trans_depth > 0)
		{
			$this->_trans_depth++;
			return TRUE;
		}

		// Reset the transaction failure flag.
		// If the $test_mode flag is set to TRUE transactions will be rolled back
		// even if the queries produce a successful result.
		$this->_trans_failure = ($test_mode === TRUE);

		if ($this->_trans_begin())
		{
			$this->_trans_depth++;
			return TRUE;
		}

		return FALSE;
	}

	public function trans_commit()
	{
		if ( ! $this->trans_enabled OR $this->_trans_depth === 0)
		{
			return FALSE;
		}
		// When transactions are nested we only begin/commit/rollback the outermost ones
		elseif ($this->_trans_depth > 1 OR $this->_trans_commit())
		{
			$this->_trans_depth--;
			return TRUE;
		}

		return FALSE;
	}

	public function trans_rollback()
	{
		if ( ! $this->trans_enabled OR $this->_trans_depth === 0)
		{
			return FALSE;
		}
		// When transactions are nested we only begin/commit/rollback the outermost ones
		elseif ($this->_trans_depth > 1 OR $this->_trans_rollback())
		{
			$this->_trans_depth--;
			return TRUE;
		}

		return FALSE;
	}

	public function compile_binds($sql, $binds)
	{
		if (empty($binds) OR empty($this->bind_marker) OR strpos($sql, $this->bind_marker) === FALSE)
		{
			return $sql;
		}
		elseif ( ! is_array($binds))
		{
			$binds = array($binds);
			$bind_count = 1;
		}
		else
		{
			// Make sure we're using numeric keys
			$binds = array_values($binds);
			$bind_count = count($binds);
		}

		// We'll need the marker length later
		$ml = strlen($this->bind_marker);

		// Make sure not to replace a chunk inside a string that happens to match the bind marker
		if ($c = preg_match_all("/'[^']*'/i", $sql, $matches))
		{
			$c = preg_match_all('/'.preg_quote($this->bind_marker, '/').'/i',
				str_replace($matches[0],
					str_replace($this->bind_marker, str_repeat(' ', $ml), $matches[0]),
					$sql, $c),
				$matches, PREG_OFFSET_CAPTURE);

			// Bind values' count must match the count of markers in the query
			if ($bind_count !== $c)
			{
				return $sql;
			}
		}
		elseif (($c = preg_match_all('/'.preg_quote($this->bind_marker, '/').'/i', $sql, $matches, PREG_OFFSET_CAPTURE)) !== $bind_count)
		{
			return $sql;
		}

		do
		{
			$c--;
			$escaped_value = $this->escape($binds[$c]);
			if (is_array($escaped_value))
			{
				$escaped_value = '('.implode(',', $escaped_value).')';
			}
			$sql = substr_replace($sql, $escaped_value, $matches[0][$c][1], $ml);
		}
		while ($c !== 0);

		return $sql;
	}

	public function is_write_type($sql)
	{
		return (bool) preg_match('/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|TRUNCATE|LOAD|COPY|ALTER|RENAME|GRANT|REVOKE|LOCK|UNLOCK|REINDEX)\s/i', $sql);
	}

	public function escape($str)
	{
		if (is_array($str))
		{
			$str = array_map(array(&$this, 'escape'), $str);
			return $str;
		}
		elseif (is_string($str) OR (is_object($str) && method_exists($str, '__toString')))
		{
			return "'".$this->escape_str($str)."'";
		}
		elseif (is_bool($str))
		{
			return ($str === FALSE) ? 0 : 1;
		}
		elseif ($str === NULL)
		{
			return 'NULL';
		}

		return $str;
	}

	public function escape_str($str, $like = FALSE)
	{
		if (is_array($str))
		{
			foreach ($str as $key => $val)
			{
				$str[$key] = $this->escape_str($val, $like);
			}

			return $str;
		}

		$str = $this->_escape_str($str);

		// escape LIKE condition wildcards
		if ($like === TRUE)
		{
			// return str_replace(
				// array($this->_like_escape_chr, '%', '_'),
				// array($this->_like_escape_chr.$this->_like_escape_chr, $this->_like_escape_chr.'%', $this->_like_escape_chr.'_'),
				// $str
			// );
		}

		return $str;
	}

	public function escape_like_str($str)
	{
		return $this->escape_str($str, TRUE);
	}

	protected function _escape_str($str)
	{
		return str_replace("'", "''", hc_remove_invisible_characters($str));
	}

	public function escape_identifiers($item)
	{
		if ($this->_escape_char === '' OR empty($item) OR in_array($item, $this->_reserved_identifiers))
		{
			return $item;
		}
		elseif (is_array($item))
		{
			foreach ($item as $key => $value)
			{
				$item[$key] = $this->escape_identifiers($value);
			}

			return $item;
		}
		// Avoid breaking functions and literal values inside queries
		elseif (ctype_digit($item) OR $item[0] === "'" OR ($this->_escape_char !== '"' && $item[0] === '"') OR strpos($item, '(') !== FALSE)
		{
			return $item;
		}

		static $preg_ec = array();

		if (empty($preg_ec))
		{
			if (is_array($this->_escape_char))
			{
				$preg_ec = array(
					preg_quote($this->_escape_char[0], '/'),
					preg_quote($this->_escape_char[1], '/'),
					$this->_escape_char[0],
					$this->_escape_char[1]
				);
			}
			else
			{
				$preg_ec[0] = $preg_ec[1] = preg_quote($this->_escape_char, '/');
				$preg_ec[2] = $preg_ec[3] = $this->_escape_char;
			}
		}

		foreach ($this->_reserved_identifiers as $id)
		{
			if (strpos($item, '.'.$id) !== FALSE)
			{
				return preg_replace('/'.$preg_ec[0].'?([^'.$preg_ec[1].'\.]+)'.$preg_ec[1].'?\./i', $preg_ec[2].'$1'.$preg_ec[3].'.', $item);
			}
		}

		return preg_replace('/'.$preg_ec[0].'?([^'.$preg_ec[1].'\.]+)'.$preg_ec[1].'?(\.)?/i', $preg_ec[2].'$1'.$preg_ec[3].'$2', $item);
	}

	public function insert_string($table, $data)
	{
		$fields = $values = array();

		foreach ($data as $key => $val)
		{
			$fields[] = $this->escape_identifiers($key);
			$values[] = $this->escape($val);
		}

		return $this->_insert($this->protect_identifiers($table, TRUE, NULL, FALSE), $fields, $values);
	}

	protected function _insert($table, $keys, $values)
	{
		return 'INSERT INTO '.$table.' ('.implode(', ', $keys).') VALUES ('.implode(', ', $values).')';
	}

	public function update_string($table, $data, $where)
	{
		if (empty($where))
		{
			return FALSE;
		}

		$this->where($where);

		$fields = array();
		foreach ($data as $key => $val)
		{
			$fields[$this->protect_identifiers($key)] = $this->escape($val);
		}

		$sql = $this->_update($this->protect_identifiers($table, TRUE, NULL, FALSE), $fields);
		$this->_reset_write();
		return $sql;
	}

	protected function _update($table, $values)
	{
		foreach ($values as $key => $val)
		{
			$valstr[] = $key.' = '.$val;
		}

		return 'UPDATE '.$table.' SET '.implode(', ', $valstr)
			.$this->_compile_wh('qb_where')
			.$this->_compile_order_by()
			.($this->qb_limit ? ' LIMIT '.$this->qb_limit : '');
	}

	protected function _has_operator($str)
	{
		return (bool) preg_match('/(<|>|!|=|\sIS NULL|\sIS NOT NULL|\sEXISTS|\sBETWEEN|\sLIKE|\sIN\s*\(|\s)/i', trim($str));
	}

	protected function _get_operator($str)
	{
		static $_operators;

		if (empty($_operators))
		{
			$_les = ($this->_like_escape_str !== '')
				? '\s+'.preg_quote(trim(sprintf($this->_like_escape_str, $this->_like_escape_chr)), '/')
				: '';
			$_operators = array(
				'\s*(?:<|>|!)?=\s*',             // =, <=, >=, !=
				'\s*<>?\s*',                     // <, <>
				'\s*>\s*',                       // >
				'\s+IS NULL',                    // IS NULL
				'\s+IS NOT NULL',                // IS NOT NULL
				'\s+EXISTS\s*\(.*\)',        // EXISTS(sql)
				'\s+NOT EXISTS\s*\(.*\)',    // NOT EXISTS(sql)
				'\s+BETWEEN\s+',                 // BETWEEN value AND value
				'\s+IN\s*\(.*\)',            // IN(list)
				'\s+NOT IN\s*\(.*\)',        // NOT IN (list)
				'\s+LIKE\s+\S.*('.$_les.')?',    // LIKE 'expr'[ ESCAPE '%s']
				'\s+NOT LIKE\s+\S.*('.$_les.')?' // NOT LIKE 'expr'[ ESCAPE '%s']
			);

		}

		return preg_match('/'.implode('|', $_operators).'/i', $str, $match)
			? $match[0] : FALSE;
	}

	public function protect_identifiers($item, $prefix_single = FALSE, $protect_identifiers = NULL, $field_exists = TRUE)
	{
		if ( ! is_bool($protect_identifiers))
		{
			$protect_identifiers = $this->_protect_identifiers;
		}

		if (is_array($item))
		{
			$escaped_array = array();
			foreach ($item as $k => $v)
			{
				$escaped_array[$this->protect_identifiers($k)] = $this->protect_identifiers($v, $prefix_single, $protect_identifiers, $field_exists);
			}

			return $escaped_array;
		}

		// This is basically a bug fix for queries that use MAX, MIN, etc.
		// If a parenthesis is found we know that we do not need to
		// escape the data or add a prefix. There's probably a more graceful
		// way to deal with this, but I'm not thinking of it -- Rick
		//
		// Added exception for single quotes as well, we don't want to alter
		// literal strings. -- Narf
		if (strcspn($item, "()'") !== strlen($item))
		{
			return $item;
		}

		// Convert tabs or multiple spaces into single spaces
		$item = preg_replace('/\s+/', ' ', trim($item));

		// If the item has an alias declaration we remove it and set it aside.
		// Note: strripos() is used in order to support spaces in table names
		if ($offset = strripos($item, ' AS '))
		{
			$alias = ($protect_identifiers)
				? substr($item, $offset, 4).$this->escape_identifiers(substr($item, $offset + 4))
				: substr($item, $offset);
			$item = substr($item, 0, $offset);
		}
		elseif ($offset = strrpos($item, ' '))
		{
			$alias = ($protect_identifiers)
				? ' '.$this->escape_identifiers(substr($item, $offset + 1))
				: substr($item, $offset);
			$item = substr($item, 0, $offset);
		}
		else
		{
			$alias = '';
		}

		// Break the string apart if it contains periods, then insert the table prefix
		// in the correct location, assuming the period doesn't indicate that we're dealing
		// with an alias. While we're at it, we will escape the components
		if (strpos($item, '.') !== FALSE)
		{
			$parts = explode('.', $item);

			// Does the first segment of the exploded item match
			// one of the aliases previously identified? If so,
			// we have nothing more to do other than escape the item
			//
			// NOTE: The ! empty() condition prevents this method
			//       from breaking when QB isn't enabled.
			if ( ! empty($this->qb_aliased_tables) && in_array($parts[0], $this->qb_aliased_tables))
			{
				if ($protect_identifiers === TRUE)
				{
					foreach ($parts as $key => $val)
					{
						if ( ! in_array($val, $this->_reserved_identifiers))
						{
							$parts[$key] = $this->escape_identifiers($val);
						}
					}

					$item = implode('.', $parts);
				}

				return $item.$alias;
			}

			// Is there a table prefix defined in the config file? If not, no need to do anything
			if ($this->dbprefix !== '')
			{
				// We now add the table prefix based on some logic.
				// Do we have 4 segments (hostname.database.table.column)?
				// If so, we add the table prefix to the column name in the 3rd segment.
				if (isset($parts[3]))
				{
					$i = 2;
				}
				// Do we have 3 segments (database.table.column)?
				// If so, we add the table prefix to the column name in 2nd position
				elseif (isset($parts[2]))
				{
					$i = 1;
				}
				// Do we have 2 segments (table.column)?
				// If so, we add the table prefix to the column name in 1st segment
				else
				{
					$i = 0;
				}

				// This flag is set when the supplied $item does not contain a field name.
				// This can happen when this function is being called from a JOIN.
				if ($field_exists === FALSE)
				{
					$i++;
				}

				// Verify table prefix and replace if necessary
				if ($this->swap_pre !== '' && strpos($parts[$i], $this->swap_pre) === 0)
				{
					$parts[$i] = preg_replace('/^'.$this->swap_pre.'(\S+?)/', $this->dbprefix.'\\1', $parts[$i]);
				}
				// We only add the table prefix if it does not already exist
				elseif (strpos($parts[$i], $this->dbprefix) !== 0)
				{
					$parts[$i] = $this->dbprefix.$parts[$i];
				}

				// Put the parts back together
				$item = implode('.', $parts);
			}

			if ($protect_identifiers === TRUE)
			{
				$item = $this->escape_identifiers($item);
			}

			return $item.$alias;
		}

		// Is there a table prefix? If not, no need to insert it
		if ($this->dbprefix !== '')
		{
			// Verify table prefix and replace if necessary
			if ($this->swap_pre !== '' && strpos($item, $this->swap_pre) === 0)
			{
				$item = preg_replace('/^'.$this->swap_pre.'(\S+?)/', $this->dbprefix.'\\1', $item);
			}
			// Do we prefix an item with no segments?
			elseif ($prefix_single === TRUE && strpos($item, $this->dbprefix) !== 0)
			{
				$item = $this->dbprefix.$item;
			}
		}

		if ($protect_identifiers === TRUE && ! in_array($item, $this->_reserved_identifiers))
		{
			$item = $this->escape_identifiers($item);
		}

		return $item.$alias;
	}

	function display_error($error = '')
	{
		$heading = 'A Database Error Occurred';
		$message = array();
		$message[] = $error;

		$trace = debug_backtrace();
		foreach ($trace as $call){
			if (isset($call['file']) && strpos($call['file'], 'database') === FALSE){
				// Found it - use a relative path for safety
				$safe_file_name = str_replace(array(), '', $call['file']);
				$safe_file_name = basename( $safe_file_name );
				// $message[] = 'Filename: ' . $safe_file_name;
				// $message[] = 'Line Number: '. $call['line'];
				break;
			}
		}

		echo hc_show_detailed_error($heading, $message, 500);
		// exit;
	}
}
}