<?php
/**
 * Created by PhpStorm.
 * User: asao
 * Date: 2014/06/26
 * Time: 9:36
 */
namespace WScore\ScoreDB;

use PdoStatement;
use Traversable;

interface QueryInterface
{
    /**
     * @param array $data
     * @return int|bool
     */
    public function insert( $data = array() );

    /**
     * @param string $name
     * @return int
     */
    public function lastId( $name = null );

    /**
     * @param null|int $limit
     * @return array
     */
    public function select( $limit = null );

    /**
     * Retrieve an external iterator
     * @return Traversable|PdoStatement
     */
    public function getIterator();

    /**
     *
     */
    public function reset();

    /**
     * @param int $id
     * @param string $column
     * @return string
     */
    public function delete( $id = null, $column = null );

    /**
     * @return int
     */
    public function count();

    /**
     * @param array $data
     * @return PDOStatement
     */
    public function update( $data = array() );
}