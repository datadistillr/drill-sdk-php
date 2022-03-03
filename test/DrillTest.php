<?php

namespace datadistillr\Drill;

use Exception;
use PHPUnit\Framework\TestCase;

use ReflectionClass;


class DrillTest extends TestCase {
	
//	protected $host = 'localhost';
//	protected $port = 8047;
//	protected $username = '';
//	protected $password = '';
//	protected $ssl = false;
//	protected $row_limit = 10000;

	protected $host = 'drill.dev.datadistillr.io';
	protected $port = 443;
	protected $username = '';
	protected $password = '';
	protected $ssl = true;
	protected $row_limit = 10000;

	public function testConnection() {
		$dh = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$active = $dh->isActive();
		$this->assertEquals(true, $active);
	}

	public function testBadConnection() {
		try {
			$dh = new DrillConnection($this->host, 8048);
			$active = $dh->isActive();
		} catch(Exception $e) {
			$active = false;
		} finally {
			$this->assertEquals(false, $active);
		}

	}

	public function testQuery() {

		$dh = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$result = $dh->query('SELECT * FROM `cp`.`employee.json` LIMIT 7');


		$this->assertEmpty($dh->errorMessage());

		$fieldCount = $result->fieldCount();
		$this->assertEquals(16, $fieldCount);
	}

	public function testPathsOnJDBC() {
		$dh = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);

		// TODO: test this on Postgres
		// TODO: test this with MsSql

		// Schema
		$result = $dh->getNestedTree('mariadb');
		$this->assertCount(15, $result);

		$result = $dh->getNestedTree('postgres');
		$this->assertCount(4, $result);
		print_r($result);

		// Tables
		$result = $dh->getNestedTree('mariadb', 'yelp');
		$this->assertCount(4, $result);

		$result = $dh->getNestedTree('postgres', 'pg_catalog');
		$this->assertCount(251, $result);
		print_r($result);

		// Columns
		$result = $dh->getNestedTree('mariadb', 'yelp', 'Users');
		$this->assertCount(7, $result);
		print_r($result);
	}

	public function testPlugins() {
		$dh = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$plugins = $dh->getAllStoragePlugins();
		$this->assertEquals(17, count($plugins));

		$enabledPlugins = $dh->getEnabledStoragePlugins();
		$this->assertEquals(4, count($enabledPlugins));
	}

	public function testErrorMessage() {
    $dh = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
    try {
		$result = $dh->query("SELECT CAST('abc' AS INT) FROM (VALUES(1))");
	} catch (Exception $e) {
		// Do something with $e
	}
    $this->assertNotEmpty($dh->errorMessage());
    $this->assertStringStartsWith("Unexpected exception during fragment initialization",
      $dh->errorMessage());
  }

	public function testFormatTable() {
		$dh = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$reflector = new ReflectionClass( 'datadistillr\Drill\DrillConnection' );
		$fdtMethod = $reflector->getMethod('formatDrillTable');
		$fdtMethod->setAccessible(true);

		// TODO: test this on Postgres

		$file_with_workspace = $fdtMethod->invokeArgs($dh, ['dfs', 'tmp.data.csv', true]);
		$this->assertEquals('`dfs`.`tmp`.`data.csv`', $file_with_workspace);

		$file_with_workspace2 = $fdtMethod->invokeArgs($dh, ['dfs', 'tmp.my.directory/data.csv', true]);
		$this->assertEquals('`dfs`.`tmp`.`my.directory/data.csv`', $file_with_workspace2);

		$file_without_workspace = $fdtMethod->invokeArgs($dh, ['dfs', 'data.csv', true]);
		$this->assertEquals('`dfs`.`data.csv`', $file_without_workspace);

		$file_without_workspace2 = $fdtMethod->invokeArgs($dh, ['dfs', 'my.directory/data.csv', true]);
		$this->assertEquals('`dfs`.`my.directory/data.csv`', $file_without_workspace2);

		$file_with_workspace_and_backticks = $fdtMethod->invokeArgs($dh, ['dfs', '`tmp`.`data.csv`', true]);
		$this->assertEquals('`dfs`.`tmp`.`data.csv`', $file_with_workspace_and_backticks);

		$db_2_part = $fdtMethod->invokeArgs($dh, ['mysql', 'sales', false]);
		$this->assertEquals('`mysql`.`sales`', $db_2_part);

		$db_3_part = $fdtMethod->invokeArgs($dh, ['mysql','sales.customers', false]);
		$this->assertEquals('`mysql`.`sales`.`customers`', $db_3_part);
	}

	public function testSchemaNames() {
		$dh = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$this->assertContains('dfs.root', $dh->getSchemaNames());
	}

	public function testGetPluginType() {
		$startTime = new \DateTime();
		echo 'Start getPluginType(): ' . $startTime->format('Y-m-d H:i:s v') . "\n";
		$dh = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$plugin_type = $dh->getPluginType('mariadb');
		$this->assertEquals('jdbc', $plugin_type);
		$plugin_type = $dh->getPluginType('mdubs_postgres');
		$this->assertEquals('jdbc', $plugin_type);
		$endTime = new \DateTime();
		echo 'End getPluginType(): ' . $endTime->format('Y-m-d H:i:s v') . "\n";
		echo 'Total time: ' . $startTime->diff($endTime)->format('%Y-%m-%d %H:%i:%s %F') . "\n";
	}

	public function testGetTableNames() {
		// TODO: validate this
		$dh = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		print_r($dh->getTableNames('dfs', 'test'));
		$this->assertTrue(true);
	}

	public function testGetColumns() {
		$dh = new DrillConnection($this->host, $this->port, $this->username, $this->password, $this->ssl, $this->row_limit);
		$x = $dh->getColumns('dfs', 'test', 'Dummy Customers-1.xlsx');
		print_r($x);
		$this->assertTrue(true);
	}

	public function testCleanDataTypeName() {
		$this->assertEquals("DECIMAL", Result::cleanDataTypeName("DECIMAL(3, 4)"));
		$this->assertEquals("FLOAT8", Result::cleanDataTypeName("FLOAT8"));
		$this->assertEquals("CHAR", Result::cleanDataTypeName("CHAR(30)"));
	}
}
