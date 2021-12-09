<?php

namespace thedataist\Drill;

use Error;
use Exception;
use phpDocumentor\Reflection\Project;

/**
 * @package Drill
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
	 * @var ?string $error_message
	 */
	protected ?string $error_message = null;

	/**
	 * Columns
	 * @var ?array $columns ;
	 */
	protected ?array $columns = null;

	/**
	 * Rows
	 * @var ?array $rows
	 */
	protected ?array $rows = null;

	/**
	 * Cache of Plugins
	 * @var ?array $cached_plugins
	 */
	protected ?array $cached_plugins = null;

	/**
	 * Default schema
	 * @var ?string $default_schema
	 */
	protected ?string $default_schema = null;

	/**
	 * Row Limit
	 * @var int $row_limit
	 */
	protected int $row_limit;

	/**
	 * Cache of enabled plugins
	 * @var ?array $cached_enabled_plugins
	 */
	protected ?array $cached_enabled_plugins = null;

	/**
	 * Stack Trace
	 * @var ?string|array $stack_trace
	 */
	protected $stack_trace;

	// endregion
	// region Constructor

	/**
	 * DrillConnection constructor.
	 *
	 * @param string $host Drill instance Hostname
	 * @param int $arg_port Port Number
	 * @param string $username Username [default: '']
	 * @param string $password Password [default: '']
	 * @param bool $ssl Use SSL/TLS Connection [default: false]
	 * @param int $row_limit Row Limit [default: 10000]
	 */
	public function __construct(string $host, int $arg_port, string $username = '', string $password = '', bool $ssl = true, int $row_limit = 10000) {
		$this->hostname = $host;
		$this->port = $arg_port;
		$this->username = $username;
		$this->password = $password;
		$this->ssl = $ssl;
		$this->row_limit = $row_limit;
//		$this->cached_enabled_plugins = $this->get_enabled_storage_plugins();
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

		$postData = [
			'queryType' => 'SQL',
			'query' => $query,
			'autoLimit' => $this->row_limit,
			'options' => [
				'drill.exec.http.rest.errors.verbose' => true
			]
		];

		try {
			$response = $this->drillRequest($url, 'POST', $postData);
		} catch (Error $e) {
			throw new Exception($e->getMessage());
		}

		if (isset($response['errorMessage'])) {
			$this->error_message = $response['errorMessage'];
			$this->stack_trace = $response['stackTrace'] ?? '';
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
		$url = $this->buildUrl('enable_plugin', $plugin);
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
		$url = $this->buildUrl('disable_plugin', $plugin);
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
	 */
	public function getPluginType(?string $plugin): ?string {
		if (! isset($plugin) || ! $this->isActive()) {
			return null;
		}
		print_r("Checking for type on {$plugin}\n");

		// Remove back ticks
		$plugin = str_replace('`', '', $plugin);

		$query = "SELECT `SCHEMA_NAME`, `TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMATA` WHERE `SCHEMA_NAME` LIKE '{$plugin}%' LIMIT 1";

		// Should only be one row
		$info = $this->query($query)->fetch();

		if (! isset($info)) {
			return null;
		}

		return strtolower($info->TYPE);
	}

	/**
	 * This function returns an array of all storage plugins.
	 *
	 * @return array The list of all storage plugins, empty array if none
	 */
	function get_all_storage_plugins(): array {
		$plugin_info = $this->get_storage_plugins();
		$all_plugins = [];
		$enabled_plugins = [];

		foreach ($plugin_info as $plugin) {
			$all_plugins[] = $plugin['name'];
			if ($plugin_info['config']['enabled'] == 1) {
				$enabled_plugins[] = $plugin['name'];
			}
		}

		$this->cached_plugins = $all_plugins;
		$this->cached_enabled_plugins = $enabled_plugins;

		return $all_plugins;
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
	function get_disabled_storage_plugins(): array {
		$plugin_info = $this->get_storage_plugins();
		$disabled_plugins = [];

		foreach ($plugin_info as $plugin) {
			if ($plugin['config']['enabled'] == 0) {
				$disabled_plugins[] = $plugin['name'];
			}
		}

		return $disabled_plugins;
	}

	/**
	 * Retrieves the cached list of enabled plugins.
	 *
	 * Theoretically you can reduce API calls with this method.
	 *
	 * @return array The list storage plugins as an associative array, empty array if none.
	 */
	function get_cached_enabled_plugins(): array {

		if (!isset($this->cached_enabled_plugins)) {
			$this->cached_enabled_plugins = $this->get_enabled_storage_plugins();
		}

		return $this->cached_enabled_plugins;
	}

	/**
	 * Retrieves a list of enabled storage plugins.
	 *
	 * @return array A list of enabled storage plugins. Empty array if none.
	 * @throws Exception
	 */
	public function get_enabled_storage_plugins(): array {
		if (!$this->isActive()) {
			return [];
		}
		$plugin_info = $this->get_storage_plugins();
		$enabled_plugins = [];
		foreach ($plugin_info as $plugin) {
			if (isset($plugin['config']['enabled']) && $plugin['config']['enabled']) {
				$enabled_plugins[] = $plugin;
			}
		}
		$this->cached_enabled_plugins = $enabled_plugins;
		return $enabled_plugins;
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
	function get_cached_plugins(): array {

		if (!isset($this->cached_plugins)) {
			$this->cached_plugins = $this->getStoragePlugins();
		}

		return $this->cached_plugins;
	}

	/**
	 * This function returns an array of configuration options for a given storage plugin.
	 *
	 * @param string $plugin The plain text name of the storage plugin.
	 *
	 * @return array Array containing all configuration options for the given plugin
	 * @throws Exception
	 */
	function get_storage_plugin_info(string $plugin): array {

		$url = $this->buildUrl('plugin-info', $plugin);

		return $this->drillRequest($url);
	}

	/**
	 * Save Storage Plugin. Creates or edits a storage plugin.
	 *
	 * @param string $plugin_name Storage Plugin Name
	 * @param array $config Config
	 * @return bool|null
	 * @throws Exception
	 */
	public function save_storage_plugin(string $plugin_name, array $config): ?bool {
		$url = $this->buildUrl('plugin-info', $plugin_name);

		$postData = [
			'name' => $plugin_name,
			'config' => $config
		];

		$response = $this->drillRequest($url, 'POST', $postData);

		if (isset($response['errorMessage'])
			|| isset($response['result']) && strtolower($response['result']) !== 'success'
			|| !isset($response['result'])) {
			$this->error_message = $response['errorMessage'] ?? $response['result'] ?? implode('. ', $response);
			$this->stack_trace = $response['stackTrace'] ?? '';
			throw new Exception("Unable to save storage plugin: " . print_r($config, true));
		} else {
			return true;
		}
	}

	/**
	 * Delete Storage Plugin
	 *
	 * @param string $plugin_name Storage Plugin Name
	 * @return bool|null
	 * @throws Exception
	 */
	public function delete_storage_plugin(string $plugin_name): ?bool {
		$url = $this->buildUrl('plugin-info', $plugin_name);

		$response = $this->drillRequest($url, 'DELETE');

		if (isset($response['errorMessage'])
			|| isset($response['result']) && strtolower($response['result']) !== 'success'
			|| !isset($response['result'])) {
			$this->error_message = $response['errorMessage'] ?? $response['result'] ?? implode('. ', $response);
			$this->stack_trace = $response['stackTrace'] ?? '';
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

		$raw_results = $this->query($query)->fetch_all();
		if (!$raw_results) {
			$this->error_message = 'Error retrieving schema names';
			return null;
		}

		$schemata = [];
		// TODO: strip tables as well.
		foreach ($raw_results as $result) {
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
			$tables = $this->query($sql)->fetch_all();

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
			$tables = $this->query($sql)->fetch_all();

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

		$view_names = [];
		$sql = "SELECT `TABLE_NAME` FROM INFORMATION_SCHEMA.views WHERE `table_schema`='{$plugin}.{$schema}'";
		$results = $this->query($sql)->fetch_all();

		foreach ($results as $result) {
			$view_names[] = $result['TABLE_NAME'];
		}
		return $view_names;
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
	 * @param string $table_name The table or file name
	 * @param ?string $pluginType Plugin Type [default: null]
	 *
	 * @return Column[] List of columns present
	 * @throws Exception
	 */
	public function getColumns(string $plugin, string $schema, string $table_name, ?string $pluginType = null): array {

		print_r("I got to Columns\n");
		if(! isset($pluginType)) {
			$pluginType = $this->getPluginType($plugin);
		}

		$filePath = "{$plugin}.{$schema}.{$table_name}";

		// Since MongoDB uses the ** notation, bypass that and query the data directly
		// TODO: Add API functionality here as well
		if ($pluginType === 'file' || $pluginType === 'mongo' || $pluginType === 'splunk') {

			$views = $this->getViewNames($plugin, $schema);

			if ($pluginType === 'mongo' || $pluginType === 'splunk') {
				$quoted_file_name = $this->formatDrillTable($filePath, false);
				$sql = "SELECT * FROM {$quoted_file_name} LIMIT 1";
			} else if (in_array($table_name, $views)) {
				$view_name = "`{$plugin}.{$schema}`.`{$table_name}`"; // NOTE: escape char ` may need to go around plugin and schema separately
				$sql = "SELECT * FROM {$view_name} LIMIT 1";
			} else {
				$quoted_file_name = $this->formatDrillTable($filePath, true);
				$sql = "SELECT * FROM {$quoted_file_name} LIMIT 1";
			}

			// TODO: process this to return Column[]
			return $this->query($sql)->get_schema();

//		} else if (str_contains($table_name, "SELECT")) { // replaced with regex, str_contains is >=PHP8.0
		} else if (preg_match('/SELECT/', $table_name)) {

			$sql = "SELECT * FROM {$table_name} LIMIT 1";

		} else {
			/*
			 * Case for everything else.
			 */
			$quoted_schema = $this->formatDrillTable($filePath, false);
			$sql = "DESCRIBE {$quoted_schema}";
		}

		$result = $this->query($sql)->fetch_all();

		$columns = [];
		foreach ($result as $row) {
			$data = [
				'plugin' => $plugin,
				'schema' => $schema,
				'table' => $table_name,
				'name' => $row['COLUMN_NAME'],
				'data_type' => $row['DATA_TYPE'],
				'is_nullable' => $row['IS_NULLABLE']
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
		print_r("Plugin Type: {$pluginType}\n");

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
			print_r("Filtered to JDBC\n");
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
	public function error_message(): string {
		return $this->error_message ?? '';
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
			case 'enable_plugin':
				$path = '/storage/' . $extra . '/enable/true';
				break;
			case 'disable_plugin':
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
	 * @param ?array $postData Post Data [default: null]
	 * @return array
	 *
	 * @throws Error
	 * @throws Exception
	 */
	private function drillRequest(string $url, string $requestType = 'GET', ?array $postData = null): array {

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
			print_r('curl Error: ' . $error);
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
	// endregion
}