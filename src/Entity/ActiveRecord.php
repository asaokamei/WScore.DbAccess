<?php
namespace WScore\ScoreDB\Entity;

use WScore\ScoreDB\Dao;

/**
 * Class ActiveRecord
 * @package WScore\ScoreDB\Entity
 *
 * A generic Active Record type entity object.
 *
 * set fetch mode to PDO::FETCH_CLASS in PDOStatement when
 * retrieving data as EntityObject.
 *
 */
class ActiveRecord
{
    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var array
     */
    protected $original_data = array();

    /**
     * @var Dao
     */
    protected $dao;

    /**
     * check if this entity object is fetched from db.
     * the $this->data is filled before constructor is called.
     *
     * @var bool
     */
    protected $isFetched = false;

    /**
     * @var bool   set to true to disable db access (save and delete).
     */
    protected $immuneDbAccess = false;

    /**
     * allow to set/alter values via magic __set method.
     *
     * @var bool
     */
    protected $modsBySet = true;

    // +----------------------------------------------------------------------+
    //  constructors and managing values
    // +----------------------------------------------------------------------+
    /**
     * @param Dao $dao
     */
    public function __construct( $dao )
    {
        $this->dao       = $dao;
        $this->modsBySet = false;
        if( !empty($this->data) ) {
            $this->isFetched = true;
            $this->original_data = $this->data;
        }
    }

    // +----------------------------------------------------------------------+
    //  database access
    // +----------------------------------------------------------------------+
    /**
     * @return mixed
     */
    public function getKey()
    {
        $key = $this->dao->getKeyName();
        return $this->__get($key);
    }

    /**
     * saves to database.
     * updates if fetched, inserted if it's a new entity.
     *
     * @throws \BadMethodCallException
     * @return $this
     */
    public function save()
    {
        if( $this->isImmune() ) {
            throw new \BadMethodCallException();
        }
        if( $this->isFetched ) {
            $modified = $this->_getModified();
            $this->dao->key( $this->getKey() );
            $this->dao->update( $modified );
        } else {
            $this->dao->insert( $this->data );
        }
        return $this;
    }

    /**
     * deletes
     *
     * @throws \BadMethodCallException
     * @return $this
     */
    public function delete()
    {
        if( $this->isImmune() ) {
            throw new \BadMethodCallException();
        }
        if( $this->isFetched ) {
            $this->dao->update( $this->getKey() );
        }
        return $this;
    }

    /**
     * disable save/delete to database.
     *
     * @param bool $immune
     * @return $this
     */
    public function immune($immune=true)
    {
        $this->immuneDbAccess = $immune;
        return $this;
    }

    /**
     * check if the entity object is immunized.
     *
     * @return bool
     */
    public function isImmune()
    {
        return $this->immuneDbAccess;
    }

    /**
     * check if the entity object is fetched from database.
     *
     * @return bool
     */
    public function isFetched()
    {
        return $this->isFetched;
    }

    // +----------------------------------------------------------------------+
    //  property accessor
    // +----------------------------------------------------------------------+
    /**
     * @param array $data
     * @return $this
     */
    public function fill( $data )
    {
        $data = $this->dao->filterFillable($data);
        foreach( $data as $key => $value ) {
            $this->__set( $key, $value );
        }
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function __get( $key )
    {
        $value = $this->_getRaw($key);
        if( $this->dao ) {
            $value = $this->dao->mutate( $key, $value );
        }
        return $value;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @throws \InvalidArgumentException
     */
    public function __set( $key, $value )
    {
        if( !$this->modsBySet ) {
            throw new \InvalidArgumentException( "Cannot modify property in Entity object" );
        }
        if( $this->dao ) {
            $value = $this->dao->muteBack( $key, $value );
        }
        $this->data[$key] = $value;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function __isset( $key )
    {
        return isset( $this->data[$key] );
    }

    /**
     * @param mixed $key
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __unset( $key )
    {
        if( isset( $this->data[$key]) ) unset( $this->data[$key] );
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function _getRaw( $key )
    {
        return $this->__isset( $key ) ? $this->data[$key] : null;
    }

    /**
     * @return array
     */
    public function _getModified()
    {
        $modified = array();
        foreach ( $this->data as $key => $value ) {
            if ( !array_key_exists( $key, $this->original_data ) || $value !== $this->original_data[ $key ] ) {
                $modified[ $key ] = $value;
            }
        }
        return $modified;
    }

    // +----------------------------------------------------------------------+
}