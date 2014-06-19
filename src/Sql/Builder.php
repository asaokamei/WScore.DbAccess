<?php
namespace WScore\DbAccess\Sql;

class Builder
{
    /**
     * @var Bind
     */
    protected $bind;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var Query
     */
    protected $query;
    
    protected $select = [
        'flags',
        'column',
        'from',
        'tableAlias',
        'join',
        'where',
        'groupBy',
        'having',
        'orderBy',
    ];
    
    protected $insert = [
        'table',
        'insertCol',
        'insertVal'
    ];

    protected $update = [
        'table',
        'updateSet',
        'where',
    ];

    protected $delete = [
        'table',
        'where',
    ];

    // +----------------------------------------------------------------------+
    //  construction
    // +----------------------------------------------------------------------+
    /**
     * @param Bind   $bind
     * @param Quote  $quote
     */
    public function __construct( $bind, $quote )
    {
        $this->bind  = $bind;
        $this->quote = $quote;
    }

    /**
     * ugly if statements. replace this method with some other pattern.
     *
     * @param string $db
     */
    public function setDbType( $db )
    {
        if( $db == 'mysql' ) {
            
            $this->quote->setQuote('`');
            $this->select[] = 'limitOffset';
            $this->update[] = 'limit';
            
        } elseif( $db == 'pgsql' ) {
            
            $this->select[] = 'limit';
            $this->select[] = 'offset';
            $this->insert[] = 'returning';
            $this->update[] = 'returning';
            
        } elseif( $db == 'sqlite' ) {
            
        } else {

            $this->select[] = 'limit';
            $this->select[] = 'offset';
        }
    }

    /**
     * @param Query $query
     */
    protected function setQuery( $query ) {
        $this->query = $query;
        $this->bind  = $query->bind();
    }

    // +----------------------------------------------------------------------+
    //  convert to SQL statements.
    // +----------------------------------------------------------------------+
    /**
     * @param Query $query
     * @return string
     */
    public function toSelect( $query )
    {
        $this->setQuery( $query );
        $sql = 'SELECT' . $this->buildByList( $this->select );
        return $sql;
    }

    /**
     * @param Query $query
     * @return string
     */
    public function toInsert( $query )
    {
        $this->setQuery( $query );
        $sql = 'INSERT INTO' . $this->buildByList( $this->insert );
        return $sql;
    }

    /**
     * @param Query $query
     * @return string
     */
    public function toUpdate( $query )
    {
        $this->setQuery( $query );
        $sql = 'UPDATE' . $this->buildByList( $this->update );
        return $sql;
    }

    /**
     * @param Query $query
     * @return string
     */
    public function toDelete( $query )
    {
        $this->setQuery( $query );
        $sql = 'DELETE' . $this->buildByList( $this->delete );
        return $sql;
    }

    // +----------------------------------------------------------------------+
    //  builders
    // +----------------------------------------------------------------------+
    /**
     * @param $list
     * @return string
     */
    protected function buildByList( $list )
    {
        $statement = '';
        foreach( $list as $item ) {
            $method = 'build'.ucwords($item);
            if( $sql = $this->$method() ) {
                $statement .= ' ' . $sql;
            }
        }
        return $statement;
    }

    /**
     * @return string
     */
    protected function buildInsertCol() {
        $keys = array_keys( $this->query->values );
        $columns = [];
        foreach( $keys as $col ) {
            $columns[] = $this->quote->quote($col);
        }
        return '( '.implode( ', ', $columns ).' )';
    }

    /**
     * @return string
     */
    protected function buildInsertVal() {
        $columns = [];
        foreach( $this->query->values as $col => $val ) {
            $val = $this->bind->prepare( $val, $col );
            if( is_callable($val) ) {
                $columns[] = $val();
            } else {
                $columns[] = $val;
            }
        }
        return 'VALUES ( '.implode( ', ', $columns ).' )';
    }
    
    protected function buildUpdateSet() {
        $setter = [];
        foreach( $this->query->values as $col => $val ) {
            $val = $this->bind->prepare( $val, $col );
            if( is_callable($val) ) {
                $val = $val();
            }
            $col = $this->quote->quote($col);
            $setter[] = $this->quote->quote($col).'='.$val;
        }
        return 'SET '.implode( ', ', $setter );
    }
    
    /**
     * @return string
     */
    protected function buildFlags() {
        return $this->query->selFlags ? implode( ' ', $this->query->selFlags ) : '';
    }
    
    /**
     * @return string
     */
    protected function buildTable() {
        return $this->quote->quote( $this->query->table );
    }

    /**
     * @return string
     */
    protected function buildFrom() {
        return 'FROM '.$this->quote->quote( $this->query->table );
    }

    /**
     * @return string
     */
    protected function buildTableAlias() {
        return $this->query->tableAlias ? $this->quote->quote( $this->query->tableAlias ) : '';
    }

    /**
     * @return string
     */
    protected function buildJoin() {
        return '';
    }

    /**
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function buildColumn() {
        if( !$this->query->columns ) {
            return '*';
        }
        $columns = [];
        foreach( $this->query->columns as $alias => $col ) {
            $col = $this->quote->quote($col);
            if( !is_numeric( $alias ) ) {
                $col .= ' AS ' . $this->quote->quote($alias);
            }
            $columns[] = $col;
        }
        return implode( ', ', $columns );
    }

    /**
     * @return string
     */
    protected function buildGroupBy() {
        if( !$this->query->group ) return '';
        $group = $this->quote->map( $this->query->group );
        return $this->query->group ? 'GROUP BY '.implode( ', ', $group ) : '';
    }

    /**
     * TODO: This is NOT having clause. It is WRONG!!!
     *
     * @throws \LogicException
     * @return string
     */
    protected function buildHaving() {
        if( !$this->query->having ) return '';
        throw new \LogicException( 'Having not implemented, yet!!!' );
        /** @noinspection PhpUnreachableStatementInspection */
        $having = $this->quote->map( $this->query->having );
        return $this->query->having ? 'HAVING '.implode( ', ', $having ) : '';
    }

    /**
     * @return string
     */
    protected function buildOrderBy() {
        if( !$this->query->order ) return '';
        $sql = [];
        foreach( $this->query->order as $order ) {
            $sql[] = $this->quote->quote($order[0])." ".$order[1];
        }
        return 'ORDER BY ' . implode( ', ', $sql );
    }

    /**
     * @return string
     */
    protected function buildLimit() {
        if( is_numeric( $this->query->limit ) && $this->query->limit > 0 ) {
            return "LIMIT ".$this->query->limit;
        }
        return '';
    }

    /**
     * @return string
     */
    protected function buildOffset() {
        if( is_numeric( $this->query->offset ) && $this->query->offset > 0 ) {
            return "OFFSET ".$this->query->offset;
        }
        return '';
    }

    /**
     * @return string
     */
    protected function buildLimitOffset() {
        $sql = '';
        if ( $this->query->limit && $this->query->offset ) {
            $sql .= ' LIMIT ' . $this->query->offset . ' , ' . $this->query->limit;
        } elseif ( $this->query->limit ) {
            $sql .= ' LIMIT ' . $this->query->limit;
        }
        return $sql;
    }

    /**
     * @return string
     */
    protected function buildReturning() {
        return $this->query->returning ? 'RETURNING '.$this->query->returning:'';
    }

    // +----------------------------------------------------------------------+
    //  builders for where clause.
    // +----------------------------------------------------------------------+
    /**
     * @return string
     */
    protected function buildWhere()
    {
        $criteria = $this->query->getWhere();
        return $this->buildCriteria( $criteria );
    }

    /**
     * @param Where $criteria
     * @return string
     */
    public function buildCriteria( $criteria )
    {
        $where = $criteria->getCriteria();
        $sql   = '';
        foreach( $where as $w ) {
            if( is_array( $w ) ) {
                $sql .= $this->formWhere( $w['col'], $w['val'], $w['rel'], $w['op'] );
            } elseif( is_string( $w ) ) {
                $sql .= 'and ' .$w;
            }
        }
        $sql = trim( $sql );
        $sql = preg_replace( '/^(and|or) /i', '', $sql );
        return $sql ? 'WHERE '.$sql : '';
    }

    /**
     * @param string $col
     * @param string $val
     * @param string $rel
     * @param string $op
     * @return string
     */
    protected function formWhere( $col, $val, $rel, $op='AND' )
    {
        if( !$rel ) return '';
        $where = '';
        $rel = strtoupper( $rel );
        if( $rel == 'IN' || $rel == 'NOT IN' ) {
            $val = $this->bind->prepare( $val );
            $tmp = is_array( $val ) ? implode( ", ", $val ): "{$val}";
            $val = "( " . $tmp . " )";
        }
        elseif( $rel == 'BETWEEN' ) {
            $val = $this->bind->prepare( $val );
            $val = "{$val[0]} AND {$val[1]}";
        }
        else {
            $val = $this->bind->prepare( $val );
        }
        $col = $this->quote->quote($col);
        $where .= trim( "{$op} {$col} {$rel} {$val}" ) . ' ';
        return $where;
    }

    // +----------------------------------------------------------------------+
}