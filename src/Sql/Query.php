<?php
namespace WScore\DbAccess\Sql;

class Query
{
    /**
     * @var Where
     */
    protected $where;

    /**
     * design decision: bind is kept inside Query.
     * A Query object must have one independent Bind object,
     * or contamination of variables occur.
     *
     * @var Bind
     */
    protected $bind;

    /**
     * @var string           name of database table
     */
    public $table;

    /**
     * @var string           name of id (primary key)
     */
    public $id_name;

    /**
     * @var array            join for table
     */
    public $join = [];

    /**
     * @var string|array     columns to select in array or string
     */
    public $columns = [];

    /**
     * @var array            values for insert/update in array
     */
    public $values = [];

    /**
     * @var string[]         such as distinct, for update, etc.
     */
    public $selFlags = [];

    /**
     * @var array            order by. [ [ order, dir ], [].. ]
     */
    public $order = [];

    /**
     * @var string           group by. [ group, group2, ...]
     */
    public $group = [];

    /**
     * @var string
     */
    public $having = [];

    /**
     * @var int
     */
    public $limit = null;

    /**
     * @var int
     */
    public $offset = 0;

    /**
     * @var string
     */
    public $returning;

    /**
     * @var string
     */
    public $tableAlias;

    // +----------------------------------------------------------------------+
    /**
     * @param Where $where
     * @param Bind  $bind
     */
    public function __construct( $where, $bind ) {
        $this->bind  = $bind;
        $where->setQuery( $this );
        $this->where = $where;
    }

    /**
     * @return Bind
     */
    public function bind() {
        return $this->bind;
    }

    /**
     * @param $value
     * @return callable
     */
    public static function raw( $value ) {
        return function() use( $value ) {
            return $value;
        };
    }

    /**
     * @return Where
     */
    public function where() {
        return $this->where;
    }

    /**
     * @return Where
     */
    public function getWhere() {
        return $this->where;
    }

    // +----------------------------------------------------------------------+
    //  Setting string, array, and data to build SQL statement.
    // +----------------------------------------------------------------------+
    /**
     * @param string $table
     * @param string $id_name
     * @return Query
     */
    public function table( $table, $id_name=null ) {
        $this->table   = $this->table = $table;
        $this->id_name = $id_name ?: null;
        return $this;
    }

    /**
     * @param $alias
     * @return $this
     */
    public function alias( $alias ) {
        $this->tableAlias = $alias;
        return $this;
    }

    /**
     * @param string $column
     * @param null|string $as
     * @return Query
     */
    public function column( $column, $as=null ) {
        if( $as ) {
            $this->columns[ $as ] = $column;
        } else {
            $this->columns[] = $column;
        }
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function value( $name, $value=null ) {
        if( is_array( $name ) ) {
            $this->values = $name;
        }
        elseif( func_num_args() > 1 ) {
            $this->values[ $name ] = $value;
        }
        return $this;
    }

    /**
     * @param string $order
     * @param string $sort
     * @return $this
     */
    public function order( $order, $sort='ASC' ) {
        $this->order[] = [ $order, $sort ];
        return $this;
    }

    /**
     * @param string $group
     * @return $this
     */
    public function group( $group ) {
        $this->group[] = $group;
        return $this;
    }

    /**
     * @param string $having
     * @return $this
     */
    public function having( $having ) {
        $this->having[] = $having;
        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function limit( $limit ) {
        $this->limit  = ( is_numeric( $limit ) ) ? $limit: null;
        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset( $offset ) {
        $this->offset = ( is_numeric( $offset ) ) ? $offset: 0;
        return $this;
    }

    /**
     * creates SELECT DISTINCT statement.
     * @return Query
     */
    public function distinct() {
        return $this->flag( 'DISTINCT' );
    }

    /**
     * creates SELECT for UPDATE statement.
     * @return Query
     */
    public function forUpdate() {
        return $this->flag( 'FOR UPDATE' );
    }

    /**
     * @param $flag
     * @return $this
     */
    public function flag( $flag ) {
        $this->selFlags[] = $flag;
        return $this;
    }

    /**
     * @param string $return
     * @return $this
     */
    public function returning( $return ) {
        $this->returning = $return;
        return $this;
    }
    // +----------------------------------------------------------------------+
}