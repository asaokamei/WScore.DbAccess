<?php
namespace WScore\DbAccess\Sql;

/**
 * Class Where
 * @package WScore\DbAccess\Sql
 *
 * @method Where ne( $value )
 * @method Where lt( $value )
 * @method Where le( $value )
 * @method Where gt( $value )
 * @method Where ge( $value )
 */
class Where
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @var array
     */
    protected $where = array();

    /**
     * @var string
     */
    protected $column;

    protected $methods = [
        'ne'      => '!=',
        'lt'      => '<',
        'gt'      => '>',
        'le'      => '<=',
        'ge'      => '>=',
    ];

    // +----------------------------------------------------------------------+
    //  managing objects.
    // +----------------------------------------------------------------------+
    /**
     */
    public function __construct()
    {
    }
    
    /**
     * @param Query $q
     */
    public function setQuery( $q ) {
        $this->query = $q;
        $this->bind  = $q->bind();
    }

    /**
     * @return Query
     */
    public function q() {
        return $this->query;
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call( $method, $args )
    {
        if( isset( $this->methods[$method] ) ) {
            if( in_array( $method, ['isNull', 'notNull'] ) ) {
                return $this->where( $this->column, null, $this->methods[$method] );
            } else {
                return $this->where( $this->column, $args[0], $this->methods[$method] );
            }
        }
        return call_user_func_array( [$this->query, $method ], $args );
    }

    /**
     * @return array
     */
    public function getCriteria() {
        return $this->where;
    }
    // +----------------------------------------------------------------------+
    //  setting columns.
    // +----------------------------------------------------------------------+

    /**
     * set where statement with values properly prepared/quoted.
     *
     * @param string $col
     * @param string $val
     * @param string $rel
     * @return Where
     */
    public function where( $col, $val, $rel = '=' )
    {
        $where          = array( 'col' => $col, 'val' => $val, 'rel' => $rel, 'op' => 'AND' );
        $this->where[ ] = $where;
        return $this;
    }

    /**
     * @param string $name
     * @return Where
     */
    public function __get( $name ) {
        return $this->col( $name );
    }

    /**
     * @param string $col
     * @return Where
     */
    public function col( $col )
    {
        $this->column = $col;
        return $this;
    }

    // +----------------------------------------------------------------------+
    //  where clause.
    // +----------------------------------------------------------------------+
    /**
     * @param string|array $val
     * @return Where
     */
    public function id( $val )
    {
        if ( is_array( $val ) ) {
            return $this->col( $this->query->id_name )->in( $val );
        }
        return $this->where( $this->query->id_name, $val, '=' );
    }

    /**
     * @param $val
     * @return Where
     */
    public function eq( $val )
    {
        if ( is_array( $val ) ) {
            return $this->in( $val );
        }
        return $this->where( $this->column, $val, '=' );
    }

    /**
     * @param array $values
     * @return Where
     */
    public function in( $values )
    {
        if( !is_array($values ) ) {
            $values = func_get_args();
        }
        return $this->where( $this->column, $values, 'IN' );
    }

    /**
     * @param $values
     * @return Where
     */
    public function notIn( $values)
    {
        if( !is_array($values ) ) {
            $values = func_get_args();
        }
        return $this->in( $this->column, $values, 'NOT IN' );
    }

    /**
     * @param $val1
     * @param $val2
     * @return Where
     */
    public function between( $val1, $val2 )
    {
        return $this->where( $this->column, [$val1, $val2], "BETWEEN" );
    }

    /**
     * @return Where
     */
    public function isNull()
    {
        return $this->where( $this->column, false, 'IS NULL' );
    }

    /**
     * @return Where
     */
    public function notNull()
    {
        return $this->where( $this->column, false, 'IS NOT NULL' );
    }

    /**
     * @param $val
     * @return Where
     */
    public function like( $val )
    {
        return $this->where( $this->column, $val, 'LIKE' );
    }

    /**
     * @param $val
     * @return Where
     */
    public function contain( $val )
    {
        return $this->like( "%{$val}%" );
    }

    /**
     * @param $val
     * @return Where
     */
    public function startWith( $val )
    {
        return $this->like( "{$val}%" );
    }

    /**
     * @param $val
     * @return Where
     */
    public function endWith( $val )
    {
        return $this->like( "%{$val}" );
    }

}