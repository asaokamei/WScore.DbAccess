<?php
namespace WScore\ScoreDB\Hook;

use WScore\ScoreDB\Query;

class Scopes
{
    /**
     * @var array
     */
    protected $scopes = [];

    /**
     * @param object $scope
     */
    public function setScope( $scope )
    {
        $this->scopes[] = $scope;
    }

    /**
     * @param string $name
     * @param Query  $query
     * @param array  $args
     * @return $this
     */
    public function scope( $name, $query, $args )
    {
        foreach( $this->scopes as $scope ) {
            if( method_exists( $scope, $method = 'scope'.ucfirst($name) ) ) {
                $pass = array_merge([$query], $args);
                call_user_func_array( [$scope, $method], $pass );
                return $this;
            }
        }
        return false;
    }

}