<?php

namespace datadistillr\Drill;

use Error;
use Exception;
use datadistillr\Drill\Request\PluginData;
use datadistillr\Drill\Request\QueryData;
use datadistillr\Drill\Request\RequestData;
use datadistillr\Drill\ResultSet\Column;
use datadistillr\Drill\ResultSet\Plugin;
use datadistillr\Drill\ResultSet\Schema;
use datadistillr\Drill\ResultSet\Table;

/**
 * @package datadistillr\drill-sdk-php
 *
 * @author Charles Givre <cgivre@thedataist.com>
 * @author Tim Swagger <tim@datadistillr.com>
 */
class DrillConnection {

	// region Properties
	/**
	 * Hostname
	 * @var string $hostname
	 */
	protected string $hostname;

	/**
	 * Port number
	 * @var int $port
	 */
	protected int $port;

	/**
	 * User name
	 * @var string $username
	 */
	protected string $username;

	/**
	 * Password
	 * @var string $password
	 */
	protected string $password;

	/**
	 * Use SSH
	 * @var bool $ssl
	 */
	protected bool $ssl = false;

	/**
	 * Error Messages
	 * @var ?string $errorMessage
	 */
	protected ?string $errorMessage = null;

	/**
	 * Columns
	 * @var ?Column[] $columns
	 * @todo verify reasoning for this to be here.
	 */
	protected ?array $columns = null;

	/**
	 * Rows
	 * @var ?array $rows
	 * @todo verify reasoning for this to be here.
	 */
	protected ?array $rows = null;

	/**
	 * Cache of Plugins
	 * @var ?array $cachedPlugins
	 */
	protected ?array $cachedPlugins = null;

	/**
	 * Default schema
	 * @var ?string $defaultSchema
	 */
	protected ?string $defaultSchema = null;

	/**
	 * Row Limit
	 * @var int $rowLimit
	 */
	protected int $rowLimit;

	/**
	 * Cache of enabled plugins
	 * @var ?array $cachedEnabledPlugins
	 */
	protected ?array $cachedEnabledPlugins = null;

	/**
	 * Stack Trace
	 * @var ?string|array $stackTrace
	 */
	protected $stackTrace;

	// endregion
	// region Constructor

	/**
	 * DrillConnection constructor.
	 *
	 * @param string $host Drill instance Hostname
	 * @param int $argPort Port Number
	 * @param string $username Username [default: '']
	 * @param string $password Password [default: '']
	 * @param bool $ssl Use SSL/TLS Connection [default: false]
	 * @param int $rowLimit Row Limit [default: 10000]
	 */
	public function __construct(string $host, int $argPort, string $username = '', string $password = '', bool $ssl = true, int $rowLimit = 10000) {
		$this->hostname = $host;
		$this->port = $argPort;
		$this->username = $username;
		$this->password = $password;
		$this->ssl = $ssl;
		$this->rowLimit = $rowLimit;
//		$this->cachedEnabledPlugins = $this->getEnabledStoragePlugins();
	}

	// endregion
	// region Query Methods

	/**
	 * Executes a Drill query.
	 *
	 * @param string $query The query to run/execute
	 *
	 * @return ?Result Returns Result object if the query executed successfully, null otherwise.
	 * @throws Exception
	 */
	public function query(string $query): ?Result {

		$url = $this->buildUrl('query');

		$postData = new QueryData($query, $this->rowLimit);

		try {
			$response = $this->drillRequest($url, 'POST', $postData);
		} catch (Error $e) {
			throw new Exception($e->getMessage());
		}

		if (isset($response['errorMessage'])) {
			$this->errorMessage = $response['errorMessage'];
			$this->stackTrace = $response['stackTrace'] ?? '';
			throw new Exception("Error in query: {$query}");
		} else {
			return new Result($response, $query);
		}

	}

	// region Plugin Methods

	/**
	 * Enable Selected Plugin
	 *
	 * @param string $plugin Plugin name
	 * @return bool True if plugin successfully enabled, false otherwise.
	 * @throws Exception
	 */
	public function enablePlugin(string $plugin): bool {
		$url = $this->buildUrl('enablePlugin', $plugin);
		$result = $this->drillRequest($url);

		if (isset($result['result']) && $result['result'] === 'success') {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Disable Selected Plugin
	 *
	 * @param string $plugin Plugin name
	 * @return bool True if plugin successfully disabled, false otherwise.
	 * @throws Exception
	 */
	public function disablePlugin(string $plugin): bool {
		$url = $this->buildUrl('disablePlugin', $plugin);
		$result = $this->drillRequest($url);

		if (isset($result['result']) && $result['result'] === 'success') {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Identify plugin type
	 *
	 * @param ?string $plugin Plugin name
	 *
	 * @return ?string The plugin type, or null on error
	 * @throws Exception
	 * @todo This does not always return the plugin type.  Need better query
	 */
	public function getPluginType(?string $plugin): ?string {
		if (! isset($plugin) || ! $this->isActive()) {
			return null;
		}

		// Remove back ticks
		$plugin = str_replace('`', '', $plugin);

		$query = "SELECT `SCHEMA_NAME`, `TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMATA` WHERE `SCHEMA_NAME` LIKE '{$plugin}%' LIMIT 1";

		// Should only be one row
		$info = $this->query($query)->fetch();

		if (! isset($info) || ! isset($info->TYPE)) {
			return null;
		}

		return strtolower($info->TYPE);
	}

	/**
	 * This function returns an array of all storage plugins.
	 *
	 * @return array The list of all storage plugins, empty array if none
	 */
	function getAllStoragePlugins(): array {
		$pluginInfo = $this->getStoragePlugins();
		$allPlugins = [];
		$enabledPlugins = [];

		foreach ($pluginInfo as $plugin) {
			$allPlugins[] = $plugin['name'];
			if ($pluginInfo['config']['enabled'] == 1) {
				$enabledPlugins[] = $plugin['name'];
			}
		}

		$this->cachedPlugins = $allPlugins;
		$this->cachedEnabledPlugins = $enabledPlugins;

		return $allPlugins;
	}

	/**
	 * Retrieves an associative array of storage plugins.
	 *
	 * It will have all configuration options for the plugins.
	 *
	 * @return array The list storage plugins as an associative array, empty array if none
	 * @throws Exception
	 */
	public function getStoragePlugins(): array {

		$url = $this->buildUrl('storage');
		return $this->drillRequest($url);
	}

	/**
	 * Retrieves a list of storage plugins which are disabled.
	 *
	 * @return array List of disabled storage plugins, empty array if none
	 */
	function getDisabledStoragePlugins(): array {
		$pluginInfo = $this->getStoragePlugins();
		$disabledPlugins = [];

		foreach ($pluginInfo as $plugin) {
			if ($plugin['config']['enabled'] == 0) {
				$disabledPlugins[] = $plugin['name'];
			}
		}

		return $disabledPlugins;
	}

	/**
	 * Retrieves the cached list of enabled plugins.
	 *
	 * Theoretically you can reduce API calls with this method.
	 *
	 * @return array The list storage plugins as an associative array, empty array if none.
	 */
	function getCachedEnabledPlugins(): array {

		if (!isset($this->cachedEnabledPlugins)) {
			$this->cachedEnabledPlugins = $this->getEnabledStoragePlugins();
		}

		return $this->cachedEnabledPlugins;
	}

	/**
	 * Retrieves a list of enabled storage plugins.
	 *
	 * @return array A list of enabled storage plugins. Empty array if none.
	 * @throws Exception
	 */
	public function getEnabledStoragePlugins(): array {
		if (!$this->isActive()) {
			return [];
		}
		$pluginInfo = $this->getStoragePlugins();
		$enabledPlugins = [];
		foreach ($pluginInfo as $plugin) {
			if (isset($plugin['config']['enabled']) && $plugin['config']['enabled']) {
				$enabledPlugins[] = $plugin;
			}
		}
		$this->cachedEnabledPlugins = $enabledPlugins;
		return $enabledPlugins;
	}

	/**
	 * Checks if the connection is active.
	 *
	 * @return bool Returns true if the connection to Drill is active, false if not.
	 */
	public function isActive(): bool {
		$protocol = $this->ssl ? 'https://' : 'http://';

		$result = get_headers($protocol . $this->hostname . ':' . $this->port);

		return isset($result[1]);
	}

	/**
	 * Retrieves the cached list of plugins.
	 *
	 * Theoretically you can reduce API calls with this method.
	 *
	 * @return array The list storage plugins as an associative array, empty array if none.
	 */
	public function getCachedPlugins(): array {

		if (!isset($this->cachedPlugins)) {
			$this->cachedPlugins = $this->getStoragePlugins();
		}

		return $this->cachedPlugins;
	}

	/**
	 * Retrieve Stack Trace
	 *
	 * @return ?array Array of Stack Trace Results
	 */
	public function getStackTrace(): ?array {
		return $this->stackTrace;
	}

	/**
	 * This function returns an array of configuration options for a given storage plugin.
	 *
	 * @param string $plugin The plain text name of the storage plugin.
	 *
	 * @return array Array containing all configuration options for the given plugin
	 * @throws Exception
	 */
	function getStoragePluginInfo(string $plugin): array {

		$url = $this->buildUrl('plugin-info', $plugin);

		return $this->drillRequest($url);
	}

	/**
	 * Save Storage Plugin. Creates or edits a storage plugin.
	 *
	 * @param string $pluginName Storage Plugin Name
	 * @param array $config Config
	 * @return bool|null
	 * @throws Exception
	 */
	public function saveStoragePlugin(string $pluginName, array $config): ?bool {
		$url = $this->buildUrl('plugin-info', $pluginName);

		$postData = new PluginData($pluginName, $config);

		$response = $this->drillRequest($url, 'POST', $postData);

		if (isset($response['errorMessage'])
			|| isset($response['result']) && strtolower($response['result']) !== 'success'
			|| !isset($response['result'])) {
			$this->errorMessage = $response['errorMessage'] ?? $response['result'] ?? implode('. ', $response);
			$this->stackTrace = $response['stackTrace'] ?? '';
			throw new Exception("Unable to save storage plugin: " . print_r($config, true));
		} else {
			return true;
		}
	}

	/**
	 * Delete Storage Plugin
	 *
	 * @param string $pluginName Storage Plugin Name
	 * @return bool|null
	 * @throws Exception
	 */
	public function deleteStoragePlugin(string $pluginName): ?bool {
		$url = $this->buildUrl('plugin-info', $pluginName);

		$response = $this->drillRequest($url, 'DELETE');

		if (isset($response['errorMessage'])
			|| isset($response['result']) && strtolower($response['result']) !== 'success'
			|| !isset($response['result'])) {
			$this->errorMessage = $response['errorMessage'] ?? $response['result'] ?? implode('. ', $response);
			$this->stackTrace = $response['stackTrace'] ?? '';
			throw new Exception("Unable to delete storage plugin.");
		} else {
			return true;
		}
	}
	// endregion

	// region Schema Methods

	/**
	 * Retrieves list of enabled schemata names
	 *
	 * @param ?string $plugin Optional plugin name [default: null]
	 * @param bool $stripPlugin Strip Plugin name from returned values [default: false]
	 * @return ?array A list of enabled schemata, null on error
	 * @throws Exception
	 */
	public function getSchemaNames(?string $plugin = null, bool $stripPlugin = false): ?array {

		if (!$this->isActive()) {
			return null;
		}

		$query = 'SHOW DATABASES';
		if(isset($plugin)) {
			$query .= " WHERE `SCHEMA_NAME` LIKE '{$plugin}.%'";
		}

		$rawResults = $this->query($query)->getRows();
		if (!$rawResults) {
			$this->errorMessage = 'Error retrieving schema names';
			return null;
		}

		$schemata = [];
		// TODO: strip tables as well.
		foreach ($rawResults as $result) {
			$schema = $result['SCHEMA_NAME'];
			if ($schema != 'cp.default' &&
				$schema != 'INFORMATION_SCHEMA' &&
				$schema != 'information_schema' &&
				$schema != 'dfs.default' &&
				$schema != 'sys') {
				$schemata[] = $schema;
			}
		}

		if($stripPlugin) {
			foreach($schemata as &$schema) {
				$schema = preg_replace("/^{$plugin}\./", '', $schema);
			}
		}

		return $schemata;
	}

	/**
	 * Retrieves an organized/tree listing of schema names
	 *
	 * @param ?string $plugin Optional plugin name [default: null]
	 * @param bool $stripPlugin Strip Plugin name from returned values [default: false]
	 * @return ?Schema[] An organized list of schema names
	 * @throws Exception
	 */
	public function getSchema(?string $plugin = null, bool $stripPlugin = false): ?array {
		$nameList = $this->getSchemaNames($plugin, $stripPlugin);
		if (!isset($nameList)) {
			// error getting schema names;
			return null;
		}

		$schemata = [];
		$hasDot = '/\./';

		foreach ($nameList as &$name) {
			// Skip plugin entries, we will pick them up elsewhere
			if (!preg_match($hasDot, $name)) {
				continue;
			}

			$nameSplit = preg_split($hasDot, $name);
			if (!isset($schemata[$nameSplit[0]])) {
				// Create plugin entry if not exist
				$schemata[$nameSplit[0]] = new Plugin(['name' => $nameSplit[0]]);
			}

			$schemata[$nameSplit[0]]->schemas[] = new Schema(['plugin' => $nameSplit[0], 'name' => $nameSplit[1]]);
		}

		return $schemata;
	}

	// endregion
	// region Table Methods

	/**
	 * Retrieves an array of tables names
	 *
	 * @param string $plugin The plain text name of the storage plugin.
	 * @param string $schema The input schema
	 * @param ?string $pluginType Plugin type [default: null]
	 *
	 * @return Table[] List of tables. Empty array if there are none
	 * @throws Exception
	 */
	public function getTables(string $plugin, string $schema, ?string $pluginType = null): array {
		$tables = [];

		foreach ($this->getTableNames($plugin, $schema, $pluginType) as $tableName) {
			$tables[] = new Table(['name' => $tableName, 'schema' => $schema]);
		}

		return $tables;
	}

	/**
	 * Retrieves a list of Drill tables which exist in a Drill schema.
	 *
	 * For a file system, this is essentially a list of files in a given
	 * workspace.  For a relational database, this corresponds to the list
	 * of tables in the database.
	 *
	 * @param string $plugin The plain text name of the storage plugin.
	 * @param string $schema The input schema
	 * @param ?string $pluginType Plugin type [default: null]
	 *
	 * @return array List of table names
	 * @throws Exception
	 */
	public function getTableNames(string $plugin, string $schema, ?string $pluginType = null): array {
		$cleanPlugin = str_replace('`', '', $plugin);
		$cleanSchema = str_replace('`', '', $schema);

		if(!isset($pluginType)) {
			$pluginType = $this->getPluginType($cleanPlugin);
		}
		$tableNames = [];

		$cleanSchema = $cleanPlugin . '.' . $cleanSchema;

		if ($pluginType === 'file') {
			$sql = "SELECT `FILE_NAME` FROM `INFORMATION_SCHEMA`.`files` WHERE `SCHEMA_NAME` = '{$cleanSchema}' AND `IS_FILE` = true";
			$tables = $this->query($sql)->getRows();

			foreach ($tables as $table) {
				if (strpos($table['FILE_NAME'], 'view.drill')) {
					// Skip views.  Use the get_view_names() method instead
					continue;
				} else {
					$tableNames[] = $table['FILE_NAME'];
				}
			}
		} else {
			$sql = "SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA` = '{$cleanSchema}'";
			$tables = $this->query($sql)->getRows();

			foreach ($tables as $table) {
				if (strpos($table['TABLE_NAME'], 'view.drill')) {
					$tableName = str_replace('view.drill', '', $table['TABLE_NAME']);
				} else {
					$tableName = $table['TABLE_NAME'];
				}
				$tableNames[] = $tableName;
			}
		}

		return $tableNames;
	}

	/**
	 * Retrieve the View Names
	 *
	 * @param string $plugin Plugin/Datasource to retrieve view names from
	 * @param string $schema Schema to retrieve view names from
	 *
	 * @return ?array List of names or null if error
	 * @throws Exception
	 */
	public function getViewNames(string $plugin, string $schema): ?array {
		if (!$this->isActive()) {
			return null;
		}

		$viewNames = [];
		$sql = "SELECT `TABLE_NAME` FROM INFORMATION_SCHEMA.views WHERE `table_schema`='{$plugin}.{$schema}'";
		$results = $this->query($sql)->getRows();

		foreach ($results as $result) {
			$viewNames[] = $result['TABLE_NAME'];
		}
		return $viewNames;
	}

	// endregion
	// region Column Methods

	/**
	 * Retrieves the columns present in a given data source.
	 *
	 * Drill schema discovery behaves differently for the different plugin types.
	 * If the data is a file, API, MongoDB or Splunk, we have to execute a
	 * `SELECT *` query with a LIMIT 1 to identify the columns and data types.
	 *
	 * If the data is a database, we can use the DESCRIBE TABLE command to access schema information.
	 *
	 * @param string $plugin The plugin name
	 * @param string $schema The schema name
	 * @param string $tableName The table or file name
	 * @param ?string $pluginType Plugin Type [default: null]
	 *
	 * @return Column[] List of columns present
	 * @throws Exception
	 */
	public function getColumns(string $plugin, string $schema, string $tableName, ?string $pluginType = null): array {

		if(! isset($pluginType)) {
			$pluginType = $this->getPluginType($plugin);
		}

		$filePath = "{$plugin}.{$schema}.{$tableName}";

		// Since MongoDB uses the ** notation, bypass that and query the data directly
		// TODO: Add API functionality here as well
		if ($pluginType === 'file' || $pluginType === 'mongo' || $pluginType === 'splunk') {

			$views = $this->getViewNames($plugin, $schema);

			if ($pluginType === 'mongo' || $pluginType === 'splunk') {
				$quotedFileName = $this->formatDrillTable($filePath, false);
				$sql = "SELECT * FROM {$quotedFileName} LIMIT 1";
			} else if (in_array($tableName, $views)) {
				$viewName = "`{$plugin}.{$schema}`.`{$tableName}`"; // NOTE: escape char ` may need to go around plugin and schema separately
				$sql = "SELECT * FROM {$viewName} LIMIT 1";
			} else {
				$quotedFileName = $this->formatDrillTable($filePath, true);
				$sql = "SELECT * FROM {$quotedFileName} LIMIT 1";
			}

			// TODO: process this to return Column[]
			return $this->query($sql)->getSchema();

//		} else if (str_contains($table_name, "SELECT")) { // replaced with regex, str_contains is >=PHP8.0
		} else if (preg_match('/SELECT/', $tableName)) {

			$sql = "SELECT * FROM {$tableName} LIMIT 1";

		} else {
			/*
			 * Case for everything else.
			 */
			$quotedSchema = $this->formatDrillTable($filePath, false);
			$sql = "DESCRIBE {$quotedSchema}";
		}

		$result = $this->query($sql)->getRows();

		$columns = [];
		foreach ($result as $row) {
			$data = [
				'plugin' => $plugin,
				'schema' => $schema,
				'table' => $tableName,
				'name' => $row['COLUMN_NAME'],
				'dataType' => $row['DATA_TYPE'],
				'isNullable' => $row['IS_NULLABLE']
			];

			$columns[] = new Column($data);
		}
		return $columns;
	}

	// endregion
	// region Path Method(s)

	/**
	 * Get nested tree based on path
	 *
	 * This method allows you to pass in the path segments and it will attempt to figure out
	 * how to return the nested schema/tables/columns, etc based on the type of Storage Plugin
	 *
	 * @param string $plugin The plugin name
	 * @param string ...$pathItems The path to the required tree
	 * @throws Exception
	 * @todo Currently only works for JDBC Connection Types, Needs to be expanded for Files
	 */
	public function getNestedTree(string $plugin, ...$pathItems): array {
		$pluginType = $this->getPluginType($plugin);

		$itemCount = count($pathItems);

		if($pluginType === 'file') {
//			$filePath = '';
//			$count = 0;
//			foreach($pathItems as $path) {
//				if($count++ > 0) {
//					$filePath .= '.';
//				}
//
//				$filePath .= "`{$path}`";
//			}
//			$filePath = $plugin . '.' . $filePath;
//
//			$results = $this->query("SHOW FILES IN {$filePath}")->fetch_assoc();

			throw new \Exception('Unsupported format');

		} elseif($pluginType === 'jdbc') {

			if($itemCount < 1) {
				$list = $this->getSchemaNames($plugin, true);
				$results = [];

				foreach($list as $name) {
					$results[] = new Schema(['plugin'=>$plugin, 'name'=>$name]);
				}
			}
			elseif($itemCount == 1) {
				$results = $this->getTables($plugin, $pathItems[0], $pluginType);
			}
			elseif($itemCount == 2) {
				$results = $this->getColumns($plugin, $pathItems[0], $pathItems[1], $pluginType);
			}

		} elseif($pluginType === 'mongo' || $pluginType === 'elastic') {
			if($itemCount < 1) {
				$results = $this->getTables($plugin, $pathItems[0], $pluginType);
			}
			elseif($itemCount == 1) {
				$results = $this->getColumns($plugin, $pathItems[0], $pathItems[1], $pluginType);
			}
		}

		return $results;

	}

	// endregion
	// endregion
	// region Management Methods

	/**
	 * Format Drill Table
	 *
	 * @param string $schema Schema name
	 * @param bool $isFile Schema/DB is a file
	 *
	 * @return string
	 */
	protected function formatDrillTable(string $schema, bool $isFile): string {
		$formattedSchema = '';

		$numDots = substr_count($schema, '.');
		$schema = str_replace('`', '', $schema);

		// For files, the last section will be the file extension
		$schemaParts = explode('.', $schema);

		if ($isFile && $numDots == 3) {
			// Case for file and workspace
			$plugin = $schemaParts[0];
			$workspace = $schemaParts[1];
			$table = "{$schemaParts[2]}.{$schemaParts[3]}";
			$formattedSchema = "{$plugin}.`{$workspace}`.`{$table}`";
		} elseif ($isFile && $numDots == 2) {
			// Case for File and no workspace
			$plugin = $schemaParts[0];
			$formattedSchema = "`{$plugin}`.`{$schemaParts[1]}`.`{$schemaParts[2]}`";
		} else {
			// Case for everything else
			foreach ($schemaParts as $part) {
				$quotedPart = "`{$part}`";
				if (strlen($formattedSchema) > 0) {
					$formattedSchema = "{$formattedSchema}.{$quotedPart}";
				} else {
					$formattedSchema = $quotedPart;
				}
			}
		}
		return $formattedSchema;
	}

	/**
	 * Retrieves the error message from the most recent query.
	 *
	 * @return string The error message from the most recent query, an empty string if undefined.
	 */
	public function errorMessage(): string {
		return $this->errorMessage ?? '';
	}

	// endregion
	// region Private Methods

	/**
	 * Build URL
	 *
	 * @param string $function The Function to be called [default: '']
	 * @param string $extra Any extra to be included [default: '']
	 *
	 * @return string The completed URL
	 */
	private function buildUrl(string $function = '', string $extra = ''): string {

		$protocol = $this->ssl ? 'https://' : 'http://';

		switch ($function) {
			case 'query':
				$path = '/query.json';
				break;
			case 'storage':
				$path = '/storage.json';
				break;
			case 'plugin-info':
				$path = '/storage/' . $extra . '.json';
				break;
			case 'enablePlugin':
				$path = '/storage/' . $extra . '/enable/true';
				break;
			case 'disablePlugin':
				$path = '/storage/' . $extra . '/enable/false';
				break;
			default:
				$path = '';
		}

		return $protocol . $this->hostname . ':' . $this->port . $path;
	}

	/**
	 * Initiate Request to Drill Server
	 *
	 * @param string $url Request Endpoint URL
	 * @param string $requestType HTTP Request type [default: GET]
	 *    Options GET, DELETE, POST
	 * @param ?RequestData $postData Post Data [default: null]
	 * @return array
	 *
	 * @throws Error
	 * @throws Exception
	 */
	private function drillRequest(string $url, string $requestType = 'GET', ?RequestData $postData = null): array {

		$curlOptions = [
			CURLOPT_CUSTOMREQUEST => $requestType
		];

		$curlHeaders = [
			'Content-Type: application/json'
		];

		switch ($requestType) {
			case 'GET':
				$curlOptions[CURLOPT_HEADER] = 0;
				break;
			case 'POST':
				$curlOptions[CURLOPT_POST] = true;
				$curlOptions[CURLOPT_POSTFIELDS] = json_encode($postData);
			case 'DELETE':
				$curlOptions[CURLOPT_HTTPHEADER] = $curlHeaders;
				break;
			default:
				throw new Exception('Invalid Request Type');
		}

		$curlOptions[CURLOPT_RETURNTRANSFER] = true;

		$ch = curl_init($url);
		curl_setopt_array($ch, $curlOptions);

		$response = curl_exec($ch);

		if ($error = curl_error($ch)) {
			curl_close($ch);

			throw new Error($error);
		}

		curl_close($ch);

		$result = json_decode($response, true);
		if (isset($result)) {
			return $result;
		}

		return [];
	}



	// endregion

	// region Deprecated Methods

	/**
	 * Checks if the connection is active.
	 *
	 * @return bool Returns true if the connection to Drill is active, false if not.
	 * @deprecated v0.5.6 use isActive()
	 */
	public function is_active(): bool {
		return $this->isActive();
	}

	/**
	 * Identify plugin type
	 *
	 * @param ?string $plugin Plugin name
	 *
	 * @return ?string The plugin type, or null on error
	 * @throws Exception
	 * @deprecated v0.5.6 use getPluginType()
	 */
	function get_plugin_type(?string $plugin): ?string {
		return $this->getPluginType($plugin);
	}

	/**
	 * This function returns an array of all storage plugins.
	 *
	 * @return array The list of all storage plugins, empty array if none
	 * @deprecated v0.5.7 use getAllStoragePlugins()
	 */
	function get_all_storage_plugins(): array {
		return $this->getAllStoragePlugins();
	}

	/**
	 * Retrieves a list of storage plugins which are disabled.
	 *
	 * @return array List of disabled storage plugins, empty array if none
	 * @deprecated v0.5.7 use getDisabledStoragePlugins()
	 */
	function get_disabled_storage_plugins(): array {
		return $this->getDisabledStoragePlugins();
	}

	/**
	 * Retrieves the cached list of enabled plugins.
	 *
	 * Theoretically you can reduce API calls with this method.
	 *
	 * @return array The list storage plugins as an associative array, empty array if none.
	 * @deprecated v0.5.7 use getCachedEnabledPlugins()
	 */
	function get_cached_enabled_plugins(): array {
		return $this->getCachedEnabledPlugins();
	}

	/**
	 * Retrieves a list of enabled storage plugins.
	 *
	 * @return array A list of enabled storage plugins. Empty array if none.
	 * @throws Exception
	 * @deprecated v0.5.7 use getEnabledStoragePlugins()
	 */
	public function get_enabled_storage_plugins(): array {
		return $this->getEnabledStoragePlugins();
	}

	/**
	 * This function returns an array of configuration options for a given storage plugin.
	 *
	 * @param string $plugin The plain text name of the storage plugin.
	 *
	 * @return array Array containing all configuration options for the given plugin
	 * @throws Exception
	 * @deprecated v0.5.7 use getStoragePluginInfo()
	 */
	function get_storage_plugin_info(string $plugin): array {
		return $this->getStoragePluginInfo($plugin);
	}

	/**
	 * Save Storage Plugin. Creates or edits a storage plugin.
	 *
	 * @param string $plugin_name Storage Plugin Name
	 * @param array $config Config
	 * @return bool|null
	 * @throws Exception
	 * @deprecated v0.5.7 use saveStoragePlugin()
	 */
	public function save_storage_plugin(string $plugin_name, array $config): ?bool {
		return $this->saveStoragePlugin($plugin_name, $config);
	}

	/**
	 * Delete Storage Plugin
	 *
	 * @param string $plugin_name Storage Plugin Name
	 * @return bool|null
	 * @throws Exception
	 * @deprecated v0.5.7 use deleteStoragePlugin()
	 */
	public function delete_storage_plugin(string $plugin_name): ?bool {
		return $this->deleteStoragePlugin($plugin_name);
	}

	/**
	 * Retrieves the cached list of plugins.
	 *
	 * Theoretically you can reduce API calls with this method.
	 *
	 * @return array The list storage plugins as an associative array, empty array if none.
	 * @deprecated v0.5.7 use getCachedPlugins()
	 */
	function get_cached_plugins(): array {
		return $this->getCachedPlugins();
	}

	/**
	 * Retrieves list of enabled schemata names
	 *
	 * @return ?array A list of enabled schemata, null on error
	 * @throws Exception
	 * @deprecated v0.5.6 use getSchemaNames()
	 */
	function get_schema_names(): ?array {
		return $this->getSchemaNames();
	}

	/**
	 * Retrieves an organized/tree listing of schema names
	 *
	 * @return ?Schema[] An organized list of schema names
	 * @throws Exception
	 * @deprecated v0.5.6 use getSchema()
	 */
	public function get_schema(): ?array {
		return $this->getSchema();
	}

	/**
	 * Retrieves a list of Drill tables which exist in a Drill schema.
	 *
	 * For a file system, this is essentially a list of files in a given
	 * workspace.  For a relational database, this corresponds to the list
	 * of tables in the database.
	 *
	 * @param string $plugin The plain text name of the storage plugin.
	 * @param string $schema The input schema
	 *
	 * @return array List of table names
	 * @throws Exception
	 * @deprecated v0.5.6 use getTableNames()
	 */
	function get_table_names(string $plugin, string $schema): array {
		return $this->getTableNames($plugin, $schema);
	}

	/**
	 * Retrieves an array of tables names
	 *
	 * @param string $plugin The plain text name of the storage plugin.
	 * @param string $schema The input schema
	 *
	 * @return Table[] List of tables. Empty array if there are none
	 * @throws Exception
	 * @deprecated v0.5.6 use getTables()
	 */
	function get_tables(string $plugin, string $schema): array {
		return $this->getTables($plugin, $schema);
	}

	/**
	 * Retrieve the View Names
	 *
	 * @param string $plugin Plugin/Datasource to retrieve view names from
	 * @param string $schema Schema to retrieve view names from
	 *
	 * @return ?array List of names or null if error
	 * @throws Exception
	 * @deprecated v0.5.6 use getViewNames()
	 */
	function get_view_names(string $plugin, string $schema): ?array {
		return $this->getViewNames($plugin, $schema);
	}

	/**
	 * Retrieves the columns present in a given data source.
	 *
	 * Drill schema discovery behaves differently for the different plugin types.
	 * If the data is a file, API, MongoDB or Splunk, we have to execute a
	 * `SELECT *` query with a LIMIT 1 to identify the columns and data types.
	 *
	 * If the data is a database, we can use the DESCRIBE TABLE command to access schema information.
	 *
	 * @param string $plugin The plugin name
	 * @param string $schema The schema name
	 * @param string $table_name The table or file name
	 *
	 * @return Column[] List of columns present
	 * @throws Exception
	 * @deprecated v0.5.6 use getColumns()
	 */
	function get_columns(string $plugin, string $schema, string $table_name): array {
		return $this->getColumns($plugin, $schema, $table_name);
	}

	/**
	 * Format Drill Table
	 *
	 * @param string $schema Schema name
	 * @param bool $is_file Schema/DB is a file
	 *
	 * @return string
	 * @deprecated v0.5.6 use formatDrillTable()
	 */
	function format_drill_table(string $schema, bool $is_file): string {
		return $this->formatDrillTable($schema, $is_file);
	}

	/**
	 * Initiate GET Request to Drill server
	 *
	 * @param string $url Full URL Request to Drill Server
	 * @return array Associative array
	 * @throws Exception
	 * @deprecated v0.5.6
	 */
	private function get_request(string $url): array {
		return $this->drillRequest($url);
	}

	/**
	 * Initiate POST Request to Drill server
	 *
	 * @param string $url Full URL Request to Drill Server
	 * @param array $postData Associative array of data
	 * @return array returns associative array
	 * @throws Exception
	 * @deprecated v0.5.6
	 */
	private function post_request(string $url, array $postData): array {
		return $this->drillRequest($url, 'POST', $postData);
	}

	/**
	 * Initiate DELETE Request to Drill server
	 *
	 * @param string $url Full URL Request to Drill Server
	 * @return array returns associative array
	 * @throws Exception
	 * @deprecated v0.5.6 use drillRequest()
	 */
	private function delete_request(string $url): array {
		return $this->drillRequest($url, 'DELETE');
	}

	/**
	 * Enable Selected Plugin
	 *
	 * @param string $plugin Plugin name
	 * @return bool True if plugin successfully enabled, false otherwise.
	 * @throws Exception
	 * @deprecated v0.5.6 use enablePlugin()
	 */
	function enable_plugin(string $plugin): bool {
		return $this->enablePlugin($plugin);
	}

	/**
	 * Disable Selected Plugin
	 *
	 * @param string $plugin Plugin name
	 * @return bool True if plugin successfully disabled, false otherwise.
	 * @throws Exception
	 * @deprecated v0.5.6 use disablePlugin()
	 */
	function disable_plugin(string $plugin): bool {
		return $this->disablePlugin($plugin);
	}

	/**
	 * Retrieves an associative array of storage plugins.
	 *
	 * It will have all configuration options for the plugins.
	 *
	 * @return array The list storage plugins as an associative array, empty array if none
	 * @throws Exception
	 * @deprecated v0.5.6 use getStoragePlugins()
	 */
	function get_storage_plugins(): array {
		return $this->getStoragePlugins();
	}

	/**
	 * Retrieves the error message from the most recent query.
	 *
	 * @return string The error message from the most recent query, an empty string if undefined.
	 * @deprecated v0.5.7 use errorMessage()
	 */
	public function error_message(): string {
		return $this->errorMessage();
	}
	// endregion
}