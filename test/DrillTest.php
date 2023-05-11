<?php

namespace datadistillr\Drill;

use Exception;
use PHPUnit\Framework\TestCase;

use ReflectionClass;


final class DrillTest extends TestCase
{
  protected static bool $USE_LOCAL_DRILL = True;
  protected static string $host;
  protected static int $port;
  protected static string $username;
  protected static string $password;
  protected static bool $ssl;
  protected static int $row_limit;

  public function setup() : void {
    if (self::$USE_LOCAL_DRILL) {
      self::$host = 'localhost';
      self::$port = 8047;
      self::$username = '';
      self::$password = '';
      self::$ssl = false;
      self::$row_limit = 10000;
    } else {
      self::$host = 'drill.dev.datadistillr.io';
      self::$port = 443;
      self::$username = '';
      self::$password = '';
      self::$ssl = true;
      self::$row_limit = 10000;
    }
  }

	public function testConnection() {
    $this->setup();
    $dh = new DrillConnection(self::$host, self::$port, self::$username, self::$password, self::$ssl, self::$row_limit);
		$active = $dh->isActive();
		$this->assertEquals(true, $active);
	}

	public function testBadConnection() {
    $this->setup();
    try {
			$dh = new DrillConnection(self::$host, 8048);
			$active = $dh->isActive();
		} catch(Exception $e) {
			$active = false;
		} finally {
			$this->assertEquals(false, $active);
		}
	}

	public function testQuery() {
    $this->setup();
		$dh = new DrillConnection(self::$host, self::$port, self::$username, self::$password, self::$ssl, self::$row_limit);
		$result = $dh->query('SELECT * FROM `cp`.`employee.json` LIMIT 7');


		$this->assertEmpty($dh->errorMessage());

		$fieldCount = $result->fieldCount();
		$this->assertEquals(17, $fieldCount);
    $this->assertEquals(7, count($result->getRows()));
	}

	public function testPathsOnJDBC() {
    $this->setup();
    $dh = new DrillConnection(self::$host, self::$port, self::$username, self::$password, self::$ssl, self::$row_limit);

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

  public function testElasticSearchTree() {
    $this->setup();
    $dh = new DrillConnection(self::$host, self::$port, self::$username, self::$password, self::$ssl, self::$row_limit);

    $result = $dh->getNestedTree('elastic', 'search-ddrtest');
    $this->assertCount(15, $result);
    print_r($result);
  }

	public function testPlugins() {
    $this->setup();
    $dh = new DrillConnection(self::$host, self::$port, self::$username, self::$password, self::$ssl, self::$row_limit);
		$plugins = $dh->getAllStoragePlugins();
		$this->assertEquals(17, count($plugins));

		$enabledPlugins = $dh->getEnabledStoragePlugins();
		$this->assertEquals(4, count($enabledPlugins));
	}

	public function testErrorMessage() {
    $this->setup();
    $dh = new DrillConnection(self::$host, self::$port, self::$username, self::$password, self::$ssl, self::$row_limit);
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
    $this->setup();
    $dh = new DrillConnection(self::$host, self::$port, self::$username, self::$password, self::$ssl, self::$row_limit);
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
    $this->setup();
    $dh = new DrillConnection(self::$host, self::$port, self::$username, self::$password, self::$ssl, self::$row_limit);
		$this->assertContains('dfs.root', $dh->getSchemaNames());
	}

	public function testGetPluginType() {
    $this->setup();
    $startTime = new \DateTime();
		echo 'Start getPluginType(): ' . $startTime->format('Y-m-d H:i:s v') . "\n";
		$dh = new DrillConnection(self::$host, self::$port, self::$username, self::$password, self::$ssl, self::$row_limit);
		$plugin_type = $dh->getPluginType('mariadb');
		$this->assertEquals('jdbc', $plugin_type);
		$plugin_type = $dh->getPluginType('mdubs_postgres');
		$this->assertEquals('jdbc', $plugin_type);
		$endTime = new \DateTime();
		echo 'End getPluginType(): ' . $endTime->format('Y-m-d H:i:s v') . "\n";
		echo 'Total time: ' . $startTime->diff($endTime)->format('%Y-%m-%d %H:%i:%s %F') . "\n";
	}

	public function testGetTableNames() {
    $this->setup();
    // TODO: validate this
		$dh = new DrillConnection(self::$host, self::$port, self::$username, self::$password, self::$ssl, self::$row_limit);
		print_r($dh->getTableNames('dfs', 'test'));
		$this->assertTrue(true);
	}

	public function testGetColumns() {
    $this->setup();
    $dh = new DrillConnection(self::$host, self::$port, self::$username, self::$password, self::$ssl, self::$row_limit);
		// $x = $dh->getNestedTree('dfs', 'test', 'Dummy Customers-1.xlsx');
    $x = $dh->getColumns('dfs', 'test', 'Dummy Customers-1.xlsx');
		print_r($x);
		$this->assertTrue(true);
	}

	public function testCleanDataTypeName() {
    $this->setup();
    $this->assertEquals("DECIMAL", Result::cleanDataTypeName("DECIMAL(3, 4)"));
		$this->assertEquals("FLOAT8", Result::cleanDataTypeName("FLOAT8"));
		$this->assertEquals("CHAR", Result::cleanDataTypeName("CHAR(30)"));
	}

  public function testNestedFields() {
    $this->setup();

    $db = new DrillConnection(self::$host, self::$port, self::$username, self::$password, self::$ssl, self::$row_limit);

    $tables = $db->getNestedTree('dfs', 'test', 'demo_data_1.xlsx');
    print_r($tables);

    //$tables = $db->getNestedTree('dfs', 'test', 'demo_data_1.xlsx', 'data');
    //print_r($tables);

    //$tables = $db->getNestedTree('dfs', 'kushi', 'CMS.mdb', 'Address');
    //print_r($tables);

    $tables = $db->getNestedTree('dfs', 'kushi', 'CMS.mdb');
    print_r($tables);

    //$tables = $db->getNestedTree('dfs', 'test', 'scalar.h5');
    //print_r($tables);

    //$tables = $db->getNestedTree('dfs', 'test', 'scalar.h5', 'datatype', 's10');
    //print_r($tables);
  }
}
