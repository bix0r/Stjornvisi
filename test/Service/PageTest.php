<?php
/**
 * Created by PhpStorm.
 * User: einarvalur
 * Date: 3/4/14
 * Time: 11:17 AM
 */

namespace Stjornvisi\Service;


require_once __DIR__.'/../ArrayDataSet.php';
require_once __DIR__.'/../PDOMock.php';

use \PDO;
use \PHPUnit_Extensions_Database_TestCase;
use Stjornvisi\ArrayDataSet;
use Stjornvisi\PDOMock;

class PageTest extends PHPUnit_Extensions_Database_TestCase{
    static private $pdo = null;
    private $conn = null;

	/**
	 * Get page.
	 * 'Page Not Found' will return FALSE
	 */
	public function testGet(){
		$service = new Page( self::$pdo );

		$result = $service->get('/category/page1');
		$this->assertInstanceOf('\stdClass',$result);

		$result = $service->get('hundur');
		$this->assertFalse( $result );


		$result = $service->getObject(1);
		$this->assertInstanceOf('\stdClass',$result);

		$result = $service->getObject(100);
		$this->assertFalse( $result );
	}

	/**
	 * Try to get page with no
	 * storage connection
	 * @expectedException Exception
	 */
	public function testGetException(){
		$service = new Page( new PDOMock() );
		$service->get('/category/page1');
	}

	/**
	 * Try to get page with no
	 * storage connection
	 * @expectedException Exception
	 */
	public function testGetObjectException(){
		$service = new Page( new PDOMock() );
		$service->getObject(1);
	}

	/**
	 * Update success.
	 */
	public function testUpdate(){
		$service = new Page( self::$pdo );
		$count = $service->update(1,array(
			'submit' => 'submit',
			'label' => 'l1',
			'body' => 'b1',
		));
		$this->assertEquals(1,$count);
	}

	/**
	 * Update invalid date.
	 * @expectedException Exception
	 */
	public function testUpdateInvalidDate(){
		$service = new Page( self::$pdo );
		$service->update(1,array(
			'submit' => 'submit',
			'label' => 'l1',
			'hundur' => 'b1',
		));
	}

	/**
	 * Update invalid date.
	 */
	public function testUpdateEntryNotFound(){
		$service = new Page( self::$pdo );
		$count = $service->update(100,array(
			'submit' => 'submit',
			'label' => 'l1',
			'body' => 'b1',
		));
		$this->assertEquals(0,$count);
	}


	/**
	 * Update, no connection
	 * @expectedException Exception
	 */
	public function testUpdateNoConnection(){
		$service = new Page( new PDOMock() );
		$count = $service->update(100,array(
			'submit' => 'submit',
			'label' => 'l1',
			'body' => 'b1',
		));
		$this->assertEquals(0,$count);
	}

    /**
     *
     */
    protected function setUp() {
        $conn=$this->getConnection();
        $conn->getConnection()->query("set foreign_key_checks=0");
        parent::setUp();
        $conn->getConnection()->query("set foreign_key_checks=1");
    }

    /**
     * @return \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection(){

        if( $this->conn === null ){
            if (self::$pdo == null){
                self::$pdo = new PDO(
                    'mysql:dbname=stjornvisi_test;host=127.0.0.1',
                    'root',
                    '',
                    array(
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    ));
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo);
        }

        return $this->conn;
    }

    /**
     * @return \PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet(){
        return new ArrayDataSet([
			'User' => [
				['id'=>1, 'name'=>'', 'passwd'=>'', 'email'=>'one@mail.com', 'title'=>'', 'created_date'=>date('Y-m-d H:i:s'), 'modified_date'=>date('Y-m-d H:i:s'), 'frequency'=>1, 'is_admin'=>1],
			],
            'Page' => [
                ['id'=>1,'label'=>'/category/page1','body'=>'b1','created'=>date('Y-m-d H:i:s'),'affected'=>date('Y-m-d H:i:s'),'editor_id'=>1],
				['id'=>2,'label'=>'/category/page2','body'=>'b1','created'=>date('Y-m-d H:i:s'),'affected'=>date('Y-m-d H:i:s'),'editor_id'=>1],
				['id'=>3,'label'=>'/category/page3','body'=>'b1','created'=>date('Y-m-d H:i:s'),'affected'=>date('Y-m-d H:i:s'),'editor_id'=>1],
            ],
        ]);
    }
} 