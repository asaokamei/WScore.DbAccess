<?php
namespace WScore\ScoreDB\Relation;

use WScore\ScoreDB\Dao;
use WScore\ScoreDB\Entity\EntityAbstract;

/**
 * Created by PhpStorm.
 * User: asao
 * Date: 2014/10/18
 * Time: 11:16
 */
class HasMany implements RelationInterface
{
    /**
     * @var Dao
     */
    protected $sourceDao;

    /**
     * @var string
     */
    protected $sourceCol;

    /**
     * @var EntityAbstract
     */
    protected $entity;

    /**
     * @var string
     */
    protected $targetName;

    /**
     * @var string
     */
    protected $targetCol;

    /**
     * @var EntityAbstract[]
     */
    protected $target = [];

    /**
     * @param Dao            $sourceDao
     * @param Dao|string     $targetName
     * @param EntityAbstract $entity
     */
    public function __construct( $sourceDao, $targetName, $entity )
    {
        $this->sourceDao  = $sourceDao;
        $this->sourceCol  = $sourceDao->getKeyName();
        $this->targetName = $targetName;
        $this->targetCol  = $this->sourceCol;
        $this->entity     = $entity;
    }

    /**
     * @return EntityAbstract[]
     */
    public function get()
    {
        /** @var Dao $targetName */
        $targetName   = $this->targetName;
        $sourceKey    = $this->entity->_getRaw( $this->sourceCol );
        $this->target = $targetName::fetch( $sourceKey, $this->targetCol );
        return $this->target;
    }

    /**
     * @param EntityAbstract|EntityAbstract[] $target
     * @return $this|RelationInterface
     */
    public function link( $target )
    {
        if( $target instanceof EntityAbstract ) {
            $target = [ $target ];
        }
        if( !$sourceKey    = $this->entity->_getRaw( $this->sourceCol ) ) {
            throw new \RuntimeException( 'lazy relation not supported' );
        }
        $targetCol = $this->targetCol;
        foreach( $target as $tgt ) {
            $tgt->$targetCol = $sourceKey;
            $this->target[] = $tgt;
        }
        return $this;
    }

    /**
     * @param EntityAbstract $target
     * @return $this|RelationInterface
     */
    public function unlink( $target=null )
    {
        $targetCol = $this->targetCol;
        $target->$targetCol = null;
        
        return $this;
    }
}