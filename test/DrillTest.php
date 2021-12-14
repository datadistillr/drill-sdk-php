<?php

namespace datadistillr\Drill;

use Exception;
use PHPUnit\Framework\TestCase;

use datadistillr\Drill\DrillConnection;
use datadistillr\Drill\Result;
use ReflectionClass;


class DrillTest extends TestCase {
	
	protected $host = 'localhost';
	protected $port = 8047;
	protected $username = '';
	protected $password = '';
	protected $ssl = false;
	protected $row_limit = 10000;
	
	protected $drill = null;

	public function testConnection() {
		$this->drill = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$active = $this->drill->isActive();
		$this->assertEquals(true, $active);
	}

	public function testBadConnection() {
		try {
			$this->baddrill = new DrillConnection($this->host, 8048);
			$active = $this->baddrill->isActive();
		} catch(Exception $e) {
			$active = false;
		} finally {
			$this->assertEquals(false, $active);
		}

	}

	public function testQuery() {

		$this->drill = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$result = $this->drill->query('SELECT * FROM `cp`.`employee.json` LIMIT 7');


		$this->assertEmpty($this->drill->errorMessage());

		$fieldCount = $result->fieldCount();
		$this->assertEquals(16, $fieldCount);
	}

	public function testPathsOnJDBC() {
		$this->drill = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);

		// Schema
		$result = $this->drill->getNestedTree('mariadb');
		$this->assertCount(7, $result);
		print_r($result);

		// Tables
		$result = $this->drill->getNestedTree('mariadb', 'yelp');
		$this->assertCount(4, $result);
		print_r($result);

		// Columns
		$result = $this->drill->getNestedTree('mariadb', 'yelp', 'Users');
		$this->assertCount(7, $result);
		print_r($result);
	}

	public function testPlugins() {
		$d = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$plugins = $d->getAllStoragePlugins();
		$this->assertEquals(15, count($plugins));

		$enabledPlugins = $d->getEnabledStoragePlugins();
		$this->assertEquals(6, count($enabledPlugins));
	}

	public function testErrorMessage() {
    $this->drill = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
    $result = $this->drill->query("SELECT CAST('abc' AS INT) FROM (VALUES(1))");
    $this->assertNotEmpty($this->drill->errorMessage());
    $this->assertStringStartsWith("Unexpected exception during fragment initialization",
      $this->drill->errorMessage());
  }

	public function testFormatTable() {
		$d = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$reflector = new ReflectionClass( 'DrillConnection' );
		$fdtMethod = $reflector->getMethod('formatDrillTable');
		$fdtMethod->setAccessible(true);

		$file_with_workspace = $fdtMethod->invokeArgs($this->drill, ["dfs.test.data.csv", true]);
		$this->assertEquals("dfs.`test`.`data.csv`", $file_with_workspace);

		$file_without_workspace = $fdtMethod->invokeArgs($this->drill, ["dfs.test.csv", true]);
		$this->assertEquals("dfs.`test.csv`", $file_without_workspace);

		$file_with_workspace_and_backticks = $fdtMethod->invokeArgs($this->drill, ["`dfs`.`test`.`data.csv`", true]);
		$this->assertEquals("dfs.`test`.`data.csv`", $file_with_workspace_and_backticks);

		$db_2_part = $fdtMethod->invokeArgs($this->drill, ["mysql.sales", false]);
		$this->assertEquals("`mysql`.`sales`", $db_2_part);

		$db_3_part = $fdtMethod->invokeArgs($this->drill, ["mysql.sales.customers", false]);
		$this->assertEquals("`mysql`.`sales`.`customers`", $db_3_part);
	}

	public function testSchemaNames() {
		$d = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		print_r($d->get_schema_names());
		$this->assertTrue(true);
	}

	public function testGetPluginType() {
		$d = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$plugin_type = $d->get_plugin_type("dfs");
		$this->assertEquals("file", $plugin_type);
	}

	public function testGetTableNames() {
		$d = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		print_r($d->get_table_names('dfs', 'test'));
		$this->assertTrue(true);
	}

	public function testGetColumns() {
		$d = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$x = $d->get_columns("Dummy Customers-1.xlsx", "dfs.test");
		print_r($x);
		$this->assertTrue(true);
	}

	public function testCleanDataTypeName() {
		$this->assertEquals("DECIMAL", Result::clean_data_type_name("DECIMAL(3, 4)"));
		$this->assertEquals("FLOAT8", Result::clean_data_type_name("FLOAT8"));
		$this->assertEquals("CHAR", Result::clean_data_type_name("CHAR(30)"));
	}
}
