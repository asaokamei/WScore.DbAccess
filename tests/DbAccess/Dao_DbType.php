<?php
namespace tests\DbAccess;

use tests\DbAccess\Dao\User;
use WScore\DbAccess\Dba;
use WScore\DbAccess\Paginate;

class Dao_DbType extends \PHPUnit_Framework_TestCase
{
    /**
     * @var User
     */
    var $user;

    static function setupBeforeClass()
    {
        class_exists( 'WScore\DbAccess\Dba' );
        class_exists( 'WScore\DbAccess\DbAccess' );
        Dba::reset();
    }

    function setup()
    {
        throw new \Exception( 'WHAT?' );
    }

    function prepareTest( $dbType )
    {
        Dba::reset();
        /** @noinspection PhpIncludeInspection */
        Dba::config( include(__DIR__."/configs/{$dbType}-config.php" ) );
        $pdo = Dba::db();
        $sql = 'DROP TABLE IF EXISTS dao_user;';
        $pdo->query( $sql );
        /** @noinspection PhpIncludeInspection */
        $pdo->query( include(__DIR__."/configs/{$dbType}-create.php" ) );
        $this->user = User::forge();
    }
    
    function makeUserData( $idx=1 )
    {
        $data = [
            'name' => 'test-' . $idx ,
            'age'  => 30 + $idx,
            'gender' => 1 + $idx%2,
            'status' => 1 + $idx%3,
            'bday' => (new \DateTime('1989-01-01'))->add(new \DateInterval('P1D'))->format('Y-m-d'),
            'no_null' => 'not null test: ' . mt_rand(1000,9999),
        ];
        return $data;
    }
    
    function saveUser($count=10)
    {
        for( $i = 1; $i <= $count; $i ++ ) {
            $this->user->insert( $this->makeUserData($i) );
        }
    }
    
    /**
     * @test
     */
    function insert_data_and_select_it()
    {
        $user = $this->makeUserData();
        $id = $this->user->insert( $user );
        $this->assertEquals( 1, $id );
        
        // check if the data is loaded.
        $found = $this->user->load( $id )[0];
        $this->assertEquals( $user['name'], $found['name'] );
        $this->assertEquals( $user['no_null'], $found['no_null'] );
        
        // is created and updated at filled?
        $now = User::$now;
        $this->assertEquals( $now->format('Y-m-d H:i:s'), $found['created_at'] );
        $this->assertEquals( $now->format('Y-m-d'), $found['open_date'] );
        $this->assertEquals( $now->format('Y-m-d H:i:s'), $found['updated_at'] );

        $upTime = clone( $now );
        User::$now = $upTime->add(new \DateInterval('P1D') );
        $this->user->where( $this->user->user_id->eq($id) )->update( ['name'=>'updated'] );

        $found = $this->user->load( $id )[0];
        $this->assertEquals( 'updated', $found['name'] );
        $this->assertEquals( $now->format('Y-m-d H:i:s'), $found['created_at'] );
        $this->assertEquals( $now->format('Y-m-d'), $found['open_date'] );
        $this->assertEquals( $upTime->format('Y-m-d H:i:s'), $found['updated_at'] );
    }

    /**
     * @test
     */
    function select_update_and_delete()
    {
        $this->saveUser(10);
        $d = $this->user;
        // selecting gender is 1.
        $found = $d->where( $d->gender->eq(1) )->select();
        $this->assertEquals( 5, count( $found ) );
        foreach( $found as $user ) {
            $this->assertEquals( 1, $user['gender'] );
        }
        // selecting status is 1.
        $found = $d->load( 1, 'status' );
        $this->assertEquals( 3, count( $found ) );
        foreach( $found as $user ) {
            $this->assertEquals( 1, $user['status'] );
        }
        // updating status is 2.
        $d->where( $d->status->eq(1) )->update( ['status' => 9 ] );
        $found = $d->load( 1, 'status' );
        $this->assertEquals( 0, count( $found ) );
        $found = $d->load( 9, 'status' );
        $this->assertEquals( 3, count( $found ) );
        
        // deleting one of status 9.
        $id_to_del = $found[1]['user_id'];
        $d->delete( $id_to_del );
        $found = $d->load( 9, 'status' );
        $this->assertEquals( 2, count( $found ) );
    }

    /**
     * @test
     */
    function inserting_null_to_bday()
    {
        $user = $this->makeUserData();
        $user['bday'] = null;
        $id = $this->user->insert( $user );
        $this->assertEquals( 1, $id );
        $found = $this->user->load( $id )[0];
        $this->assertEquals( $user['name'], $found['name'] );
        $this->assertEquals( null, $found['bday'] );
    }

    /**
     * @test
     */
    function count_returns_number_and_query_still_works()
    {
        $this->saveUser(10);
        $d = $this->user;
        $count = $d->where( $d->gender->eq(1) )->count();
        $found = $d->select();
        $this->assertEquals( $count, count( $found ) );
        foreach( $found as $user ) {
            $this->assertEquals( 1, $user['gender'] );
        }
    }

    /**
     * @test
     */
    function scopeStatus_selects_by_status()
    {
        $this->saveUser(10);
        $d = $this->user;

        // selecting status is 1.
        $found = $d->status()->select();
        $this->assertEquals( 3, count( $found ) );
        foreach( $found as $user ) {
            $this->assertEquals( 1, $user['status'] );
        }
    }

    /**
     * @test
     */
    function useIterator()
    {
        $this->saveUser(10);
        $d = $this->user->status();
        
        $count = 0;
        foreach( $d as $user ) {
            $this->assertEquals( 1, $user['status'] );
            $count++;
        }
        $this->assertEquals( 3, $count );

        $d = $this->user->status();
        $stmt = $d->getIterator();
        $this->assertEquals( 'PDOStatement', get_class( $stmt ) );
    }

    /**
     * @test
     */
    function useEmptyIterator()
    {
        $count = 0;
        $d = $this->user->status();
        foreach( $d as $user ) {
            $this->assertEquals( 1, $user['status'] );
            $count++;
        }
        $this->assertEquals( 0, $count );
    }

    /**
     * @test
     */
    function update_using_magic_set()
    {
        $this->saveUser(10);
        $d = $this->user;
        // selecting gender is 1.
        $found = $d->where( $d->gender->eq(1) )->select();
        $this->assertEquals( 5, count( $found ) );
        foreach( $found as $user ) {
            $this->assertEquals( 1, $user['gender'] );
        }
        // updating status is 2.
        $d->status = 9;
        $d->where( $d->status->eq(1) )->update();
        $found = $d->load( 1, 'status' );
        $this->assertEquals( 0, count( $found ) );
        $found = $d->load( 9, 'status' );
        $this->assertEquals( 3, count( $found ) );
    }

    /**
     * @test
     */
    function limit_and_offset()
    {
        $this->saveUser(10);
        $d = $this->user;
        $d->order('user_id')->offset(3)->limit(2);
        $found = $d->select();
        $this->assertEquals( 2, count( $found ) );
        $this->assertEquals( 4, $found[0]['user_id'] );
        $this->assertEquals( 5, $found[1]['user_id'] );
    }

    /**
     * @test
     */
    function security()
    {
        $data = $this->makeUserData();
        $data['no_null'] = 'any\' OR \'x\'=\'x';
        $id = $this->user->insert( $data );
        $saved = $this->user->load($id);
        $this->assertEquals( $data['no_null'], $saved[0]['no_null'] );

        $data['no_null'] = "t'' OR ''t''=''t'";
        $id = $this->user->insert( $data );
        $saved = $this->user->load($id);
        $this->assertEquals( $data['no_null'], $saved[0]['no_null'] );

        $data['no_null'] = "\'' OR 1=1 --";
        $id = $this->user->insert( $data );
        $saved = $this->user->load($id);
        $this->assertEquals( $data['no_null'], $saved[0]['no_null'] );
    }

    /**
     * @test
     */
    function page()
    {
        // construct initial Query.
        $this->saveUser(10);
        $session = [];
        $pager = new Paginate( $session, '/test/' );
        $pager->set( 'perPage', 3 );
        $this->assertEquals( null, $pager->loadQuery() );

        // query with pagination.
        $user = $this->user->order('user_id');
        $pager->saveQuery( $user );
        $pager->countTotal( $user );

        // verify the queried result.
        $found1 = $user->select();
        $this->assertEquals( 3, count( $found1 ) );
        for( $i=0; $i< count($found1) ; $i++ ) {
            $this->assertEquals( $i+1, $found1[$i]['user_id'] );
        }

        // save session and restore.
        $session = serialize( $session );
        $session = unserialize( $session );

        // recall the query, then paginate to the next page.
        $pager = new Paginate( $session, '/test/' );
        $user2 = $pager->loadQuery(2);
        $this->assertEquals( 'tests\DbAccess\Dao\User', get_class($user2) );

        $found2 = $user2->select();
        $this->assertEquals( 3, count( $found2 ) );
        $this->assertNotEquals( $found1, $found2 );
        for( $i=0; $i< count($found2) ; $i++ ) {
            $this->assertEquals( $i+4, $found2[$i]['user_id'] );
        }
    }
}
