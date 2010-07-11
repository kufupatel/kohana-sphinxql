<?php defined('SYSPATH') or die('No direct script access.');

/**
 * This file is part of SphinxQL for Kohana.
 *
 * Copyright (c) 2010, Deoxxa Development
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package kohana-sphinxql
 */

/**
 * Class for building queries to send to sphinx
 *
 * @package kohana-sphinxql
 * @author MasterCJ <mastercj@mastercj.net>
 * @version 0.1
 * @license http://mastercj.net/license.txt
 */
class Kohana_SphinxQL_Query {
	/**
	 * @var array The fields that are to be returned in the result set
	 */
	protected $_fields = array();
	/**
	 * @var string A string to be searched for in the indexes
	 */
	protected $_search = null;
	/**
	 * @var array A set of WHERE conditions
	 */
	protected $_wheres = array();
	/**
	 * @var array A set of ORDER clauses
	 */
	protected $_orders = array();
	/**
	 * @var array The indexes that are to be searched
	 */
	protected $_indexes = array();
	/**
	 * @var integer The offset to start returning results from
	 */
	protected $_offset = 0;
	/**
	 * @var integer The maximum number of results to return
	 */
	protected $_limit = 20;
	/**
	 * @var SphinxQL_Core A reference to a SphinxQL_Core object, used for the execute() function
	 */
	protected $_sphinx = false;

	/**
	 * Constructor
	 *
	 * @param SphinxQL_Core $sphinx
	 */
	public function __construct(SphinxQL_Core $sphinx) {
		$this->sphinx($sphinx);
	}

	/**
	 * Magic method, returns the result of build().
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->build();
	}

	/**
	 * Sets or gets the SphinxQL_Core object associated with this query.
	 * If you pass it nothing, it'll return $this->_sphinx
	 * If you pass it a SphinxQL_Core object, it'll return $this
	 * If you pass it anything else, it'll return false
	 *
	 * @return SphinxQL_Query|SphinxQL_Core|false $this or $this->_sphinx or error
	 */
	public function sphinx($sphinx=null) {
		if (is_a($sphinx, 'SphinxQL_Core')) {
			$this->_sphinx = $sphinx;
			return $this;
		} elseif($sphinx === null) {
			return $sphinx;
		}

		return false;
	}

	/**
	 * Builds the query string from the information you've given.
	 *
	 * @return string The resulting query
	 */
	public function build() {
		$fields = array();
		$wheres = array();
		$orders = array();
		$fields = array();
		$query = '';

		foreach ($this->_fields as $alias => $field) {
			$fields[] = sprintf("%s AS %s", $field, is_integer($alias) ? $field : $alias);
		} unset($field);

		if (is_string($this->_search)) {
			$wheres[] = sprintf("MATCH('%s')", addslashes($this->_search));
		}

		foreach ($this->_wheres as $where) {
			$wheres[] = sprintf("%s %s %s", $where['field'], $where['operator'], $where['value']);
		} unset($where);

		foreach ($this->_orders as $order) {
			$orders[] = sprintf("%s %s", $order['field'], $order['sort']);
		} unset($order);

		$query .= sprintf('SELECT %s ', count($fields) ? implode(', ', $fields) : '*');
		$query .= sprintf('FROM %s ', implode(',', $this->_indexes));
		if (count($wheres) > 0) { $query .= sprintf('WHERE %s ', implode(' AND ', $wheres)); }
		if (count($orders) > 0) { $query .= sprintf('ORDER BY %s ', implode(', ', $orders)); }
		$query .= sprintf("LIMIT %d, %d", $this->_offset, $this->_limit);
		while (substr($query, -1, 1) == ' ') { $query = substr($query, 0, -1); }

		return $query;
	}

	/**
	 * Adds an entry to the list of indexes to be searched.
	 *
	 * @param string The index to add
	 * @return SphinxQL_Query $this
	 */
	public function add_index($index) {
		if (is_string($index)) {
			array_push($this->_indexes, $index);
		}

		return $this;
	}

	/**
	 * Removes an entry from the list of indexes to be searched.
	 *
	 * @param string The index to remove
	 * @return SphinxQL_Query $this
	 */
	public function rem_index($index) {
		if (is_string($index)) { 
			while ($pos = array_search($index, $this->_indexes)) {
				unset($this->_indexes[$pos]);
			}
		}

		return $this;
	}

	/**
	 * Adds a entry to the list of fields to return from the query.
	 *
	 * @param string Field to add
	 * @param string Alias for that field
	 * @return SphinxQL_Query $this
	 */
	public function add_field($field, $alias) {
		if (is_string($field) && is_string($alias)) {
			$this->_fields[$alias] = $field;
		}

		return $this;
	}

	/**
	 * Adds multiple entries at once to the list of fields to search.
	 *
	 * @param array Array of alias => field pairs to add
	 * @return SphinxQL_Query $this
	 */
	public function add_fields($array) {
		if (is_array($array)) {
			foreach ($array as $alias => $field) {
				$this->add_field($field, $alias);
			}
		}

		return $this;
	}

	/**
	 * Removes a field from the list of fields to search.
	 *
	 * @param string Alias of the field to remove
	 * @return SphinxQL_Query $this
	 */
	public function rem_field($alias) {
		if (is_string($alias) && array_key_exists($this->_fields, $alias)) { 
			unset($this->_fields[$alias]);
		}

		return $this;
	}

	/**
	 * Removes multiple fields at once from the list of fields to search.
	 *
	 * @param array List of aliases of fields to remove
	 * @return SphinxQL_Query $this
	 */
	public function rem_fields($array) {
		if (is_array($array)) {
			foreach ($array as $alias) {
				$this->rem_field($alias);
			}
		}

		return $this;
	}

	/**
	 * Sets the text to be matched against the index(es)
	 *
	 * @param string Text to be searched
	 * @return SphinxQL_Query $this
	 */
	public function search($search) {
		if (is_string($search)) { $this->_search = $search; }
		return $this;
	}

	/**
	 * Sets the offset for the query
	 *
	 * @param integer Offset
	 * @return SphinxQL_Query $this
	 */
	public function offset($offset) {
		if (is_integer($offset)) { $this->_offset = $offset; }
		return $this;
	}

	/**
	 * Sets the limit for the query
	 *
	 * @param integer Limit
	 * @return SphinxQL_Query $this
	 */
	public function limit($limit) {
		if (is_integer($limit)) { $this->_limit = $limit; }
		return $this;
	}

	/**
	 * Adds a WHERE condition to the query.
	 *
	 * @param string The field for the condition
	 * @param string The value to compare the field to
	 * @param string The operator (=, <, >, etc)
	 * @param string Whether or not to quote the value (for use with 'IN' operators mainly)
	 * @return SphinxQL_Query $this
	 */
	public function where($field, $value, $operator=null, $quote=true) {
		if (!in_array($operator, array('=', '!=', '>', '<', '>=', '<=', 'AND', 'NOT IN', 'IN'))) { $operator = '='; }
		if (!is_string($field)) { return false; }
		if (!is_string($value)) { return false; }
		$quote = ($quote === true) ? true : false;

		$this->_wheres[] = array('field' => $field, 'operator' => $operator, 'value' => $value, 'quote' => $quote);

		return $this;
	}

	/**
	 * Adds a WHERE <field> <not> IN (<value x>, <value y>, <value ...>) condition to the query, mainly used for MVAs.
	 *
	 * @param string The field for the condition
	 * @param array The values to compare the field to
	 * @param string Whether this is a match-all, match-any (default) or match-none condition
	 * @return SphinxQL_Query $this
	 */
	public function where_in($field, $values, $how='any') {
		if (!is_array($values)) { $values = array($values); }
		if ($how == 'all') {
			foreach ($values as $value) {
				$this->where_in($field, $value, 'any');
			}
		} elseif ($how == 'any') {
			$this->where($field, '('.implode(', ', $values).')', 'IN', false);
		} elseif ($how == 'none') {
			$this->where($field, '('.implode(', ', $values).')', 'NOT IN', false);
		}
		return $this;
	}

	/**
	 * Adds an ORDER condition to the query.
	 *
	 * @param string The field for the condition
	 * @param string The sort type (can be 'asc' or 'desc', capitals are also OK)
	 * @return SphinxQL_Query $this
	 */
	public function order($field, $sort) {
		if (is_string($field) && is_string($sort)) {
			$this->_orders[] = array('field' => $field, 'sort' => $sort);
		}
		return $this;
	}

	/**
	 * Executes the query and returns the results
	 *
	 * @return array Results of the query
	 */
	public function execute() {
		return $this->_sphinx->query($this);
	}
}

?>