<?php

namespace datadistillr\Drill;

use datadistillr\Drill\Logs\Logger;
use datadistillr\Drill\Logs\LogType;
use datadistillr\Drill\Request\AccessTokenData;
use datadistillr\Drill\Request\OAuthTokenData;
use datadistillr\Drill\Request\RefreshTokenData;
use datadistillr\Drill\Request\RequestFunction;
use datadistillr\Drill\Request\RequestType;
use datadistillr\Drill\Request\RequestUrl;
use datadistillr\Drill\Response\ClusterResponse;
use datadistillr\Drill\Response\ConfirmationResponse;
use datadistillr\Drill\Response\OptionsListResponse;
use datadistillr\Drill\Response\ProfileResponse;
use datadistillr\Drill\Response\ProfilesResponse;
use datadistillr\Drill\Response\QueryResponse;
use datadistillr\Drill\Response\RawResponse;
use datadistillr\Drill\Response\Response;
use datadistillr\Drill\Request\PluginData;
use datadistillr\Drill\Request\QueryData;
use datadistillr\Drill\Request\RequestData;
use datadistillr\Drill\Response\StatusMetricsResponse;
use datadistillr\Drill\Response\StatusResponse;
use datadistillr\Drill\Response\StatusThreadsResponse;
use datadistillr\Drill\Response\StoragePluginListResponse;
use datadistillr\Drill\Response\StoragePluginResponse;
use datadistillr\Drill\ResultSet\Column;
use datadistillr\Drill\ResultSet\Plugin;
use datadistillr\Drill\ResultSet\Schema;
use datadistillr\Drill\ResultSet\Table;
use Error;
use Exception;
use function PHPUnit\Framework\isEmpty;

/**
 * @package datadistillr\drill-sdk-php
 *
 * @author Charles Givre <cgivre@thedataist.com>
 * @author Tim Swagger <tim@datadistillr.com>
 */
class DrillConnection {

	use Logger;

	// region Properties
	/**
	 * File Workspace path depth
	 * @const WORKSPACE_DEPTH
	 */
	const WORKSPACE_DEPTH = 1;

	/**
	 * File directory path depth
	 * @const DIRECTORY_DEPTH
	 */
	const DIRECTORY_DEPTH = 2;

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
	 * @param RequestFunction $function Function to run [Default: RequestFunction::Query]
	 *
	 * @return ?Result Returns Result object if the query executed successfully, null otherwise.
	 * @throws Exception
	 */
	public function query(string $query, RequestFunction $function = RequestFunction::Query): ?Result {

		$url = new RequestUrl($function, $this->hostname, $this->port, $this->ssl);

		$postData = new QueryData($query, $this->rowLimit);

		try {
			$response = $this->drillRequest($url, $postData);
		} catch (Error $e) {
			throw new Exception($e->getMessage());
		}

		if (isset($response->errorMessage)) {
			$this->logMessage(LogType::Error, $response->errorMessage);
			$this->logMessage(LogType::StackTrace, isset($response->stackTrace) ? print_r($response->stackTrace, true) : '');
			throw new Exception("Error in query: {$query}");
		} else {
			$result = new Result($response, $query, $url);
			$this->logMessage(LogType::Query, 'Query Result: '. print_r($result, true));
			return $result;
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
		$url = new RequestUrl(RequestFunction::EnablePlugin, $this->hostname, $this->port, $this->ssl, $plugin);

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

		$url = new RequestUrl(RequestFunction::DisablePlugin, $this->hostname, $this->port, $this->ssl, $plugin);

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
	 * @param ?string $pluginName Plugin name
	 *
	 * @return ?string The plugin type, or null on error
	 * @throws Exception
	 * @todo This does not always return the plugin type.  Need better query
	 */
	public function getPluginType(?string $pluginName): ?string {
		$this->logMessage(LogType::Query, 'Starting Plugin Type check');
		if (! isset($pluginName)) {
			return null;
		}

		$plugin = $this->getStoragePlugin($pluginName);

		if (! isset($plugin) || ! isset($plugin->config->type)) {
			$this->logMessage(LogType::Error, 'Plugin Type check errored.  No Plugin.');
			return null;
		}

		$this->logMessage(LogType::Query, 'Plugin Type check complete.  Type: ' . $plugin->config->type);
		return strtolower($plugin->config->type);
	}

	/**
	 * This function returns an array of all storage plugins.
	 *
	 * @return array The list of all storage plugins, empty array if none
	 */
	public function getAllStoragePlugins(): array {
		$pluginInfo = $this->getStoragePlugins();
		$allPlugins = [];
		$enabledPlugins = [];

		foreach ($pluginInfo as $plugin) {
			$allPlugins[] = $plugin->name;
			if (isset($plugin->config->enabled) && $plugin->config->enabled) {
				$enabledPlugins[] = $plugin->name;
			}
		}

		$this->cachedPlugins = $allPlugins;
		$this->cachedEnabledPlugins = $enabledPlugins;

		return $allPlugins;
	}

	/**
	 * This function returns an array of configuration options for a given storage plugin.
	 *
	 * @param string $plugin The plain text name of the storage plugin.
	 *
	 * @return ?StoragePluginResponse containing all configuration options for the given plugin
	 * @throws Exception
	 */
	function getStoragePlugin(string $plugin): ?StoragePluginResponse {
		$this->logMessage(LogType::Request, 'Starting retrieval of StoragePlugin: ' . $plugin);

		$url = new RequestUrl(RequestFunction::PluginInfo, $this->hostname, $this->port, $this->ssl, $plugin);

		return new StoragePluginResponse($this->drillRequest($url));
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
		$this->logMessage(LogType::Request, 'Starting Request to get Storage Plugins');

		$url = new RequestUrl(RequestFunction::Storage, $this->hostname, $this->port, $this->ssl);
		$storage = $this->drillRequest($url);

		$this->logMessage(LogType::Request, 'Ending Request to get Storage Plugins');
		return $storage->plugins;
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
        $this->logMessage(LogType::Debug, 'Starting saveStoragePlugin');

        $url = new RequestUrl(RequestFunction::CreatePlugin, $this->hostname, $this->port, $this->ssl, $pluginName);

		$postData = new PluginData($pluginName, $config);

        try {
            $response = $this->drillRequest($url, $postData);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        if (isset($response->errorMessage)) {
            $this->logMessage(LogType::Error, $response->errorMessage);
            $this->logMessage(LogType::StackTrace, $response->stackTrace ?? '');
            throw new Exception("Error saving storage plugin $pluginName: " . print_r($config, true));
        } else {
            $this->logMessage(LogType::Debug, 'Ending saveStoragePlugin');
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
        $this->logMessage(LogType::Debug, 'Starting deleteStoragePlugin');

        $url = new RequestUrl(RequestFunction::DeletePlugin, $this->hostname, $this->port, $this->ssl, $pluginName);

        try {
            $response = $this->drillRequest($url);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        if (isset($response->errorMessage)) {
            $this->logMessage(LogType::Error, $response->errorMessage);
            $this->logMessage(LogType::StackTrace, $response->stackTrace ?? '');
            throw new Exception("Error deleting storage plugin $pluginName.");
        } else {
            $this->logMessage(LogType::Debug, 'Ending deleteStoragePlugin');
            return true;
        }
	}

	/**
	 * Add User Alias
	 *
	 * @param string $alias Alias Name
	 * @param string $dataSourceName Data Source Name
	 * @param string $userName User Name
	 * return bool Returns true if successful, throws an error if failed.
	 * @throws Exception
	 */
	public function addUserStorageAlias(string $alias, string $dataSourceName, string $userName): bool {
		$this->logMessage(LogType::Debug, 'Starting addUserAlias');

		$url = new RequestUrl(RequestFunction::Query, $this->hostname, $this->port, $this->ssl);

		$query = "CREATE OR REPLACE ALIAS {$alias} FOR STORAGE `{$dataSourceName}` AS USER '{$userName}'";
		$postData = new QueryData($query);

		try {
			$response = $this->drillRequest($url, $postData);
		} catch (Error $e) {
			throw new Exception($e->getMessage());
		}

		if (isset($response->errorMessage)) {
			$this->logMessage(LogType::Error, $response->errorMessage);
			$this->logMessage(LogType::StackTrace, isset($response->stackTrace) ? print_r($response->stackTrace, true) : '');
			throw new Exception("Error in query: {$query}");
		} else {
			$result = new Result($response, $query, $url);
			$this->logMessage(LogType::Debug, 'Ending addUserAlias');
			return true;
		}
	}

	/**
	 * Delete User Alias
	 *
	 * @param string $alias Alias Name
	 * @param string $dataSourceName Data Source Name
	 * @param bool $all Drop All Aliases. If false, $userName is required [default: false]
	 * @param ?string $userName User Name
	 * return bool Returns true if successful, throws an error if failed.
	 * @throws Exception
	 */
	public function deleteUserStorageAlias(string $alias, string $dataSourceName, bool $all = false, ?string $userName = null): bool {
		$this->logMessage(LogType::Debug, 'Starting deleteUserAlias');

		if(! $all && ! isset($userName)) {
			throw new Exception('$userName required to drop Storage alias when not dropping all');
		}

		$url = new RequestUrl(RequestFunction::Query, $this->hostname, $this->port, $this->ssl);

		if($all) {
			$query = "DROP ALL ALIASES FOR STORAGE {$dataSourceName}";
		} else {
			$query = "DROP ALIAS {$alias} FOR STORAGE AS USER '{$userName}'";
		}

		$postData = new QueryData($query);

		try {
			$response = $this->drillRequest($url, $postData);
		} catch (Error $e) {
			throw new Exception($e->getMessage());
		}

		if (isset($response->errorMessage)) {
			$this->logMessage(LogType::Error, $response->errorMessage);
			$this->logMessage(LogType::StackTrace, isset($response->stackTrace) ? print_r($response->stackTrace, true) : '');
			throw new Exception("Error in query: {$query}");
		} else {
			$result = new Result($response, $query, $url);
			$this->logMessage(LogType::Debug, 'Ending deleteUserAlias');
			return true;
		}
	}



    /**
     * Update Refresh Token. Updates an HTTP storage plugin's OAuth refresh token.
     *
     * @param string $pluginName Storage Plugin Name
     * @param string $refreshToken Refresh Token
     * @return bool|null
     * @throws Exception
     */
    public function updateRefreshToken(string $pluginName, string $refreshToken): ?bool {
        $this->logMessage(LogType::Debug, 'Starting updateRefreshToken');
        $url = new RequestUrl(RequestFunction::UpdateRefreshToken, $this->hostname, $this->port, $this->ssl, $pluginName);

        $postData = new RefreshTokenData($refreshToken);

        try {
            $response = $this->drillRequest($url, $postData);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        if (isset($response->errorMessage)) {
            $this->logMessage(LogType::Error, $response->errorMessage);
            $this->logMessage(LogType::StackTrace, $response->stackTrace ?? '');
            throw new Exception("Error saving refresh token.");
        } else {
            $this->logMessage(LogType::Debug, 'Ending updateRefreshToken');
            return true;
        }
    }

    /**
     * Update Access Token. Updates an HTTP storage plugin's OAuth access token.
     *
     * @param string $pluginName Storage Plugin Name
     * @param string $accessToken Access Token
     * @return bool|null
     * @throws Exception
     */
    public function updateAccessToken(string $pluginName, string $accessToken): ?bool {
        $this->logMessage(LogType::Debug, 'Starting updateAccessToken');
        $url = new RequestUrl(RequestFunction::UpdateAccessToken, $this->hostname, $this->port, $this->ssl, $pluginName);

        $postData = new AccessTokenData($accessToken);

        try {
            $response = $this->drillRequest($url, $postData);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        if (isset($response->errorMessage)) {
            $this->logMessage(LogType::Error, $response->errorMessage);
            $this->logMessage(LogType::StackTrace, $response->stackTrace ?? '');
            throw new Exception("Error saving access token.");
        } else {
            $this->logMessage(LogType::Debug, 'Ending updateAccessToken');
            return true;
        }
    }

    /**
     * Update OAuth Tokens. Updates an HTTP storage plugin's OAuth tokens.
     *
     * @param string $pluginName Storage Plugin Name
     * @param string $accessToken Access Token
     * @param string $refreshToken Refresh Token
     * @return bool|null
     * @throws Exception
     */
    public function updateOAuthTokens(string $pluginName, string $accessToken, string $refreshToken): ?bool {
        $this->logMessage(LogType::Debug, 'Starting updateOAuthTokens');

        $url = new RequestUrl(RequestFunction::UpdateOAuthTokens, $this->hostname, $this->port, $this->ssl, $pluginName);

        $postData = new OAuthTokenData($accessToken, $refreshToken);

        try {
            $response = $this->drillRequest($url, $postData);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        if (isset($response->errorMessage)) {
            $this->logMessage(LogType::Error, $response->errorMessage);
            $this->logMessage(LogType::StackTrace, $response->stackTrace ?? '');
            throw new Exception("Error saving OAuth tokens.");
        } else {
            $this->logMessage(LogType::Debug, 'Ending updateOAuthTokens');
            return true;
        }
    }

    /**
	 * Retrieves a list of storage plugins which are disabled.
	 *
	 * @return array List of disabled storage plugins, empty array if none
	 * @throws Exception
	 */
	public function getDisabledStoragePlugins(): array {
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
			if (isset($plugin->config->enabled) && $plugin->config->enabled) {
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


	// endregion

	// region Schema Methods

	/**
	 * Retrieves list of enabled schemata names
	 *
	 * @param ?string $pluginName Optional plugin name [default: null]
	 * @param bool $stripPlugin Strip Plugin name from returned values [default: false]
	 * @return ?array A list of enabled schemata, null on error
	 * @throws Exception
	 */
	public function getSchemaNames(?string $pluginName = null, bool $stripPlugin = false): ?array {
		$this->logMessage(LogType::Query, 'Starting getSchemaNames');

		if (! $this->isActive()) {
			return null;
		}

		$query = 'SHOW DATABASES';
		if(isset($pluginName)) {
			// fix and escape underscore characters
			$escapedPluginName = preg_replace('/_/', '\_', $pluginName);
			$query .= " WHERE `SCHEMA_NAME` LIKE '{$escapedPluginName}%' escape '\'"; // This should have a period (.) after the plugin name {$pluginName}.%  but it has been removed to address a bug in Drill
			unset($escapedPluginName);
		}

		$rawResults = $this->query($query)->getRows();
		if (!$rawResults) {
			$this->errorMessage = 'Error retrieving schema names';
			$this->logMessage(LogType::Error, 'Error retrieving schema names');
			return null;
		}

		$schemata = [];
		// TODO: strip tables as well.
		foreach ($rawResults as $result) {
			$schema = $result->SCHEMA_NAME;
			if (str_starts_with($schema, $pluginName.'.' )) { // NOTE: this technically replaces all the prior checks, however it is a bit of a hack and should be removed when drill is fixed.
				$schemata[] = $schema;
			}
		}

		if($stripPlugin) {
			foreach($schemata as &$schema) {
				$schema = preg_replace("/^{$pluginName}\./", '', $schema);
			}
		}

		$this->logMessage(LogType::Query, 'Ending getSchemaNames');
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
		$this->logMessage(LogType::Query, 'Starting getTableNames');

		$cleanPlugin = str_replace('`', '', $plugin);
		$cleanSchema = str_replace('`', '', $schema);

		if(!isset($pluginType)) {
			$pluginType = $this->getPluginType($cleanPlugin);
		}
		$tableNames = [];

		$cleanSchema = $cleanPlugin . ($cleanSchema != '' ? '.' . $cleanSchema : '');

		if ($pluginType === 'file') {
			$sql = "SELECT `FILE_NAME` FROM `INFORMATION_SCHEMA`.`files` WHERE `SCHEMA_NAME` = '{$cleanSchema}' AND `IS_FILE` = true";
			$this->logMessage(LogType::Query, $sql);

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
			$this->logMessage(LogType::Query, $sql);

			$tables = $this->query($sql)->getRows();

			foreach ($tables as $table) {
				if (strpos($table->TABLE_NAME, 'view.drill')) {
					$tableName = str_replace('view.drill', '', $table->TABLE_NAME);
				} else {
					$tableName = $table->TABLE_NAME;
				}
				$tableNames[] = $tableName;
			}
		}

		$this->logMessage(LogType::Query, 'Ending getTableNames');
		return $tableNames;
	}

	/**
	 * Retrieve the View Names
	 *
	 * @param string $plugin Plugin/Datasource to retrieve view names from
	 * @param ?string $schema Schema to retrieve view names from
	 *
	 * @return ?array List of names or null if error
	 * @throws Exception
	 */
	public function getViewNames(string $plugin, ?string $schema): ?array {
		if (!$this->isActive()) {
			return null;
		}

		$pluginSchema = $plugin;
		if(isset($schema)) {
			$pluginSchema .= '.'.$schema;
		}

		$viewNames = [];
		$sql = "SELECT `TABLE_NAME` FROM INFORMATION_SCHEMA.views WHERE `table_schema`='{$pluginSchema}'";
		$results = $this->query($sql)->getRows();

		foreach ($results as $result) {
			$viewNames[] = $result['TABLE_NAME'];
		}
		return $viewNames;
	}

	/**
	 * Retrieve List of files in path
	 *
	 * @param string $pluginName Plugin Name
	 * @param string $filePath File Path
	 * @return Result[]
	 * @throws Exception
	 */
	public function getFiles(string $pluginName, string $filePath): array {
		$this->logMessage(LogType::Request, 'Starting getFiles()');

		$results = $this->query("SHOW FILES IN `{$pluginName}`.{$filePath}")->getRows();

		$this->logMessage(LogType::Request, 'Ending getFiles()');

		return $results;
	}

	/**
	 * Retrieve list of Excel Sheets/Tabs
	 *
	 * @param string $pluginName Plugin Name
	 * @param string $filePath File Path
	 * @return Table[]
	 * @throws Exception
	 */
	public function getExcelSheets(string $pluginName, string $filePath): array {
		$this->logMessage(LogType::Request, 'Starting getExcelSheets()');

		$results = $this->query("SELECT _sheets AS sheets FROM `{$pluginName}`.{$filePath} LIMIT 1")->getRows();

		$tables = [];
		if(count($results) >= 1 && isset($results[0]->sheets)) {
			foreach($results[0]->sheets as $sheetName) {
				$tables[] = new Table(['schema'=> $filePath, 'name'=>$sheetName]);
			}
		}

		$this->logMessage(LogType::Request, 'Ending getExcelSheets()');

		return $tables;
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
	 * @param ?string $schema The schema name
	 * @param ?string $tableName The table or file name
	 * @param ?string $pluginType Plugin Type [default: null]
	 *
	 * @return Column[] List of columns present
	 * @throws Exception
	 */
	public function getColumns(string $plugin, ?string $schema, ?string $tableName, ?string $pluginType = null): array {
		$this->logMessage(LogType::Query, 'Starting getColumns');

		if(! isset($pluginType)) {
			$pluginType = $this->getPluginType($plugin);
		}

		$filePath = '';
		if(isset($schema)) {
			$filePath = $schema . '.';
		}

		$filePath .= $tableName ?? '';

		// Since MongoDB uses the ** notation, bypass that and query the data directly
		// TODO: Add API functionality here as well
		if ($pluginType === 'file' || $pluginType === 'mongo' || $pluginType === 'splunk') {

			$views = $this->getViewNames($plugin, $schema);

			$pluginSchema = $plugin;
			if(isset($schema)) {
				$pluginSchema .= '.'.$schema;
			}

			if (in_array($tableName, $views)) {
				$quotedFileName = "`{$pluginSchema}`.`{$tableName}`"; // NOTE: escape char ` may need to go around plugin and schema separately
			} else {
				$quotedFileName = $this->formatDrillTable($plugin, $filePath);
			}

			// TODO: process this to return Column[]
			return $this->getFileColumns($quotedFileName);

		} else if (str_contains($tableName, "SELECT")) { // replaced with regex, str_contains is >=PHP8.0
			$sql = "SELECT * FROM {$tableName} LIMIT 1";
		} else {
			/*
			 * Case for everything else.
			 */
			$quotedSchema = $this->formatDrillTable($plugin, $filePath);
			$sql = "DESCRIBE {$quotedSchema}";
		}

		$this->logMessage(LogType::Query, $sql);

		$rows = $this->query($sql)->getRows();

		$columns = [];
		foreach ($rows as $row) {
			$data = [
				'plugin' => $plugin,
				'schema' => $schema,
				'table' => $tableName,
				'name' => $row->COLUMN_NAME,
				'data_type' => $row->DATA_TYPE,
				'is_nullable' => $row->IS_NULLABLE
			];

			$columns[] = new Column($data);
		}

		$this->logMessage(LogType::Query, 'Ending getColumns');
		return $columns;
	}

	/**
	 * Get File Columns
	 *
	 * @param string $fullformattedPath Full Formatted FilePath
	 * @return array
	 */
	public function getFileColumns(string $fullformattedPath): array {

		$this->logMessage(LogType::Query, 'Starting getFileColumns');

		$columns = [];

		try {
			$sql = "SELECT * FROM {$fullformattedPath} LIMIT 1";

			$rows = $this->query($sql)->getSchema();

			foreach ($rows as $row) {
				$data = [
					'plugin' => '',
					'schema' => '',
					'table' => '',
					'name' => $row['column'],
					'data_type' => $row['data_type'],
					'is_nullable' => false
				];

				$columns[] = new Column($data);
			}



		} catch(Exception $e) {
			$this->logMessage(LogType::Error, $e->getMessage());
		}

		$this->logMessage(LogType::Query, 'Ending getFileColumns');
		return $columns;
	}

	/**
	 * Get Complex maps
	 *
	 * @param string $pluginName Plugin Name
	 * @param string $filePath File Path
	 * @param string $mapPath Map Path
	 * @return array
	 */
	public function getComplexMaps(string $pluginName, string $filePath, string $mapPath): array {

		$this->logMessage(LogType::Query, 'Starting getComplexMaps');

		$columns = [];

		try {
			$sql = "SELECT getMapSchema(`d`.{$mapPath}) AS `listing` FROM `{$pluginName}`.{$filePath} AS `d` LIMIT 1";

			$responseData = $this->query($sql, RequestFunction::MapQuery)->getRows();

			foreach($responseData[0]->listing as $key=>$value) {
				$columns[] = [
					'column' => $key,
					'data_type' => $value
				];
			}

		} catch(Exception $e) {
			$this->logMessage(LogType::Error, $e->getMessage());
		}

		$this->logMessage(LogType::Query, 'Ending getComplexMaps');
		return $columns;
	}

	/**
	 * Get Excel Columns
	 *
	 * @param string $pluginName Plugin Name
	 * @param string $filePath File Path
	 * @param string $sheetName Sheet Name
	 * @return array
	 * @throws Exception
	 */
	public function getExcelColumns(string $pluginName, string $filePath, string $sheetName): array {
		$this->logMessage(LogType::Request, 'Starting getExcelColumns()');

		$columns = [];
		$sheetName = $this->removeBackTicks($sheetName);

		try {
			$sql = "SELECT * FROM TABLE(`{$pluginName}`.{$filePath} (type => 'excel', sheetName => '{$sheetName}')) LIMIT 1";
			$responseData = $this->query($sql, RequestFunction::MapQuery)->getSchema();

			foreach($responseData as $column) {
				$columns[] = new Column([
					'plugin' => $pluginName,
					'schema' => $filePath,
					'table_name' => $sheetName,
					'name' => $column['column'],
					'data_type' => $column['data_type']
				]);
			}
		} catch(Exception $e) {
			$this->logMessage(LogType::Error, $e->getMessage());
		}

		$this->logMessage(LogType::Request, 'Ending getExcelColumns()');
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
	 * @param string $pluginName The plugin name
	 * @param string ...$pathItems The path to the required tree
	 * @throws Exception
	 */
	public function getNestedTree(string $pluginName, ...$pathItems): array {
		$this->logMessage(LogType::Info, 'Starting getNestedTree() request.');

		$plugin = $this->getStoragePlugin($pluginName);
		if(! isset($plugin) || ! isset($plugin->config)) {
			$this->logMessage(LogType::Error, 'Unable to access requested plugin: ' . $pluginName);
			return [];
		}
		$pluginType = $plugin->config->type; // jdbc, http, file, splunk, etc
		$specificType = $this->specificType($plugin);
        $this->logMessage(LogType::Info, "specific type: {$specificType}");

		$itemCount = count($pathItems);
		if($itemCount == 1 && $pathItems[0] == '') {
			$itemCount = 0;
		}

		$results = [];

		switch(PluginType::tryFrom($pluginType)) {
			case PluginType::File:

				$pathLimit = $itemCount;
				$prevResults = null;
				$prevItem = null;
				$excelFile = false;

				do {
					$nestedData = false;

					// build the full path
					[$filePath, $remaining, $lastItem] = $this->buildFilePath($pathItems, $pathLimit);

					$this->logMessage(LogType::Info, "Calling Get Files, pathLimit: {$pathLimit}, filePath: {$filePath}, remaining: {$remaining}");

					$results = $this->getFiles($pluginName, $filePath);

					$this->logMessage(LogType::Info, 'getFiles Results: ' . print_r($results, true));

					if($pathLimit >= self::DIRECTORY_DEPTH && count($results) == 1 && preg_match('/\.xlsx?`$/', $filePath)) {
						$excelFile = true;
						$this->logMessage(LogType::Debug, 'Excel File.');
					}


					// if no results, question...
					if (count($results) == 0) {

						$this->logMessage(LogType::Warning, 'No results, check one level up.');

						$prevItem = $lastItem;
						$prevResults = $results;
						// check if error is a result of attempting to grab a file that should have been nested data.
						$nestedData = true;
						$pathLimit--;

					} elseif (isset($prevResults) && (count($results) > 1 || (count($results) == 1 && $results[0]->name == $prevItem))) {
						// original values were valid.
						$this->logMessage(LogType::Info, 'Reverting back to previous value.');
						$lastItem = $prevItem;
						$results = $prevResults;

						// TODO: fix possible bug on items where nested folders of the same name may give false positive
					} elseif ($excelFile) {
						// Identify if file is an excel file
						if (isset($prevResults) && $results[0]->name == $lastItem) {
							// check if submitted path is actually a file plus a sheet name.  If so get columns
							$results = $this->getExcelColumns($pluginName, $filePath, $remaining);

						} elseif ($results[0]->name == $lastItem) {
							// Path is Excel file.  Return list of sheets.
							$results = $this->getExcelSheets($pluginName, $filePath);
						}
					}
					elseif (isset($prevResults) && count($results) == 1 && $results[0]->name == $lastItem) {
						// Found file... now checking for nested data
						$results = $this->getComplexMaps($pluginName, $filePath, $remaining);

					} elseif ($pathLimit >= self::DIRECTORY_DEPTH && count($results) == 1 && $results[0]->name == $lastItem) {
						// check if submitted path is actually a file.  If so get columns
						$results = $this->getFileColumns("`{$pluginName}`.{$filePath}");
					}


				} while ($nestedData && $pathLimit >= self::DIRECTORY_DEPTH);


				break;
			case PluginType::JDBC:
			case PluginType::Mongo:
				// NOTE: this may be a hack.  Need to know db plugin in order to decipher . level meanings
				$offset = $this->jdbcTableOffset($specificType);


				$this->logMessage(LogType::Info, "path item count: {$itemCount}");
				$this->logMessage(LogType::Info, "table offset: {$offset}");

				$finalCount = $itemCount - $offset;

				$dbName = $pathItems[0] ?? null;
				$tableName = $pathItems[1] ?? null;

				$this->logMessage(LogType::Info, 'Final Count: ' . $finalCount);

				// TODO: clean this up to work better with the offset value
				if($finalCount > 1) {
					$tableName = $pathItems[count($pathItems)-1];

					if($finalCount == 2 && $finalCount == count($pathItems) + 1) {
						// There is no db for this senario
						$dbName = null;
					}
					else {
						for ($i = 1; $i < count($pathItems) - 1; $i++) {
							$dbName .= '.' . $pathItems[$i];
						}
					}
				}
				elseif($finalCount == 1) {
					for($i = 1; $i < count($pathItems); $i++) {
						$dbName .= '.'.$pathItems[$i];
					}

					$tableName = null;
				}
				$this->logMessage(LogType::Info, 'DB Name: ' . $dbName);

				if($finalCount < 1) {
					$list = $this->getSchemaNames($pluginName, true);
					$this->logMessage(LogType::Info, 'Returned a result set of size: ' . count($list));

					$results = [];
					foreach($list as $name) {
						$results[] = new Schema(['plugin'=>$pluginName, 'name'=>$name]);
					}
				}
				elseif($finalCount == 1) {
					$results = $this->getTables($pluginName, $dbName, $pluginType);
				}
				elseif($finalCount == 2) {
					$results = $this->getColumns($pluginName, $dbName, $tableName, $pluginType);
				}
				break;
			case PluginType::Elastic:
			case PluginType::Splunk:
				if($itemCount < 1) {
					$results = $this->getTables($pluginName, $pathItems[0], $pluginType);
				}
				elseif($itemCount == 1) {
					$results = $this->getColumns($pluginName, null, $pathItems[0], $pluginType);
				}
				break;
			default:
				$this->logMessage(LogType::Warning, 'Unsupported Plugin Type');
		}

		$this->logMessage(LogType::Info, 'Ending getNestedTree() request.');
		return $results;

	}

	// endregion
	// endregion
	// region Management Methods

	/**
	 * Format Drill Table
	 *
	 * @param string $plugin Plugin name
	 * @param string $schema Schema path
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function formatDrillTable(string $plugin, string $schema): string {
		$formattedSchema = "`{$plugin}`";

		try {
			$pluginInfo = $this->getStoragePlugin($plugin);
		} catch (Exception $e) {
			throw new Exception('Error acquiring plugin info');
		}

		$schema = str_replace('`', '', $schema);

		// For files, the last section will be the file extension
		$schemaParts = explode('.', $schema);

		if (isset($pluginInfo->config->type) && $pluginInfo->config->type === 'file') {
			$workspaces = [];
			foreach($pluginInfo->config->workspaces as $name=>$data) {
				$workspaces[] = $name;
			}

			$dotCounter = 0;
			$remaining = '';
			foreach($schemaParts as $part) {

				// check if first $part is in workspace array
				if($dotCounter++ < 1 && in_array($part, $workspaces)) {
					$formattedSchema .= ".`{$part}`";
				}
				else{
					$remaining .= $remaining =='' ? $part : ".{$part}";
				}
			}

			if($remaining != '') {
				$formattedSchema .= ".`{$remaining}`";
			}

		} else {
			// Case for everything else
			foreach($schemaParts as $part) {
				$formattedSchema .= ".`{$part}`";
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
	 * Initiate Request to Drill Server
	 *
	 * @param RequestUrl $url Request Endpoint URL
	 * @param ?RequestData $postData Post Data [default: null]
	 * @return ?Response
	 *
	 * @throws Error
	 * @throws Exception
	 */
	private function drillRequest(RequestUrl $url, ?RequestData $postData = null): ?Response {
		$this->logMessage(LogType::Request, 'Prepping Request to drill: '. print_r($url, true));

		$curlOptions = [
			CURLOPT_USERPWD => $this->username.':'.$this->password,
			CURLOPT_CUSTOMREQUEST => $url->getRequestType()->value,
			CURLOPT_RETURNTRANSFER => true
		];

		$curlHeaders = [
			'Content-Type: application/json'
		];

		switch ($url->getRequestType()) {
			case RequestType::GET:
				$curlOptions[CURLOPT_HEADER] = 0;
				break;
			case RequestType::POST:
				$curlOptions[CURLOPT_POST] = true;
				$curlOptions[CURLOPT_POSTFIELDS] = json_encode($postData);
			case RequestType::DELETE:
				$curlOptions[CURLOPT_HTTPHEADER] = $curlHeaders;
				break;
			default:
				throw new Exception('Invalid Request Type');
		}

		$ch = curl_init($url->getUrl());
		curl_setopt_array($ch, $curlOptions);

		$response = curl_exec($ch);

		$this->logMessage(LogType::Info, 'cURL Options: ' . print_r($curlOptions, true));
		$this->logMessage(LogType::Info, 'cURL info: ' . print_r(curl_getinfo($ch), true));

		// check for errors. If any, close connection and throw Error
		if ($error = curl_error($ch)) {
			curl_close($ch);
			$this->logMessage(LogType::Error, 'Curl Error: ' . $error);
			throw new Error($error);
		}

		curl_close($ch);

		// TODO: create response object based on request type....

		$result = json_decode($response);
		if (! isset($result)) {
			$this->logMessage(LogType::Debug, 'Drill response is null');
			return null;
		}

		unset($response);

		switch ($url->getFunction()) {
			case RequestFunction::MapQuery:
				$response = new RawResponse($result);
				break;
			case RequestFunction::Query:
				$response = new QueryResponse($result);
				break;
			case RequestFunction::Profiles:
				$response = new ProfilesResponse($result);
				break;
			case RequestFunction::Profile:
				$response = new ProfileResponse($result);
				break;
			case RequestFunction::DeletePlugin:
			case RequestFunction::CancelProfile:
			case RequestFunction::EnablePlugin:
			case RequestFunction::DisablePlugin:
			case RequestFunction::UpdateAccessToken:
			case RequestFunction::UpdateRefreshToken:
			case RequestFunction::UpdateOAuthTokens:
				$response = new ConfirmationResponse($result);
				break;
			case RequestFunction::Storage:
				$response = new StoragePluginListResponse($result);
				break;
			case RequestFunction::CreatePlugin:
			case RequestFunction::PluginInfo:
				$response = new StoragePluginResponse($result);
				break;
			case RequestFunction::Drillbits:
				$response = new ClusterResponse($result);
				break;
			case RequestFunction::Status:
				$response = new StatusResponse($result);
				break;
			case RequestFunction::Metrics:
				$response = new StatusMetricsResponse($result);
				break;
			case RequestFunction::ThreadStatus:
				$response = new StatusThreadsResponse($result);
				break;
			case RequestFunction::Options:
				$response = new OptionsListResponse($result);
				break;
			default:
				throw new Exception('Unable to determine request/response type');
		}

		return $response;
	}


	/**
	 * Identify Specific Plugin Type
	 *
	 * @param StoragePluginResponse $plugin Plugin configuration object
	 * @return ?String Plugin type
	 */
	protected function specificType(StoragePluginResponse $plugin): ?string {
		switch(PluginType::tryFrom($plugin->config->type)) {
			case PluginType::JDBC:
				$matches = [];
				if(preg_match('/(?<=jdbc:)(\w+?)(?=:.*)/', $plugin->config->url, $matches)) {
					$type = $matches[1];
				}
				else {
					$type = null;
				}
				break;
			case PluginType::File:
			case PluginType::Mongo:
			case PluginType::Elastic:
				$type = $plugin->config->type;
				break;
			default:
				$type = null;
		}

		return $type;
	}


	/**
	 * Get JDBC table level
	 * @param string $specificType Specific JDBC Type
	 *
	 * @return int Level the table reference will be found at
	 */
	protected function jdbcTableOffset(string $specificType): int {
		$level = match ($specificType) {
			'bigquery' => 1,
            'postgresql' => -1,
			'snowflake' => 1,
			'sqlserver' => -1,
			default => 0,
		};
		return $level;
	}

	/**
	 * Build File path
	 *
	 * @param String[] $pathItems Path Items
	 * @param ?int $limit Path Item limit
	 * @return array File Path String [$filePath, $remaining]
	 */
	protected function buildFilePath(array $pathItems, ?int $limit = null): array {
		// Build initial path
		$count = 0;
		$itemCount = count($pathItems);
		$filePath = '';
		$dirPath = '';
		$remaining = '';
		$lastItem = null;

		foreach ($pathItems as $path) {
			// check if we have hit the limit
			if(isset($limit) && ++$count > $limit) {
				$remaining .= ($remaining == '' ? "`{$path}`" : ".`{$path}`");
				continue;
			}

			$lastItem = $path;
			if ($count == self::WORKSPACE_DEPTH) {
				$filePath .= "`{$path}`";
			} elseif ($count == self::DIRECTORY_DEPTH && $itemCount == self::DIRECTORY_DEPTH) {
				$filePath .= ".`{$path}`";
			} else {
				$dirPath .= $count == self::DIRECTORY_DEPTH ? $path : '/' . $path;
			}
		}

		// Build directory path
		if ($count > self::DIRECTORY_DEPTH) {
			$filePath .= ".`{$dirPath}`";
		}

		return [$filePath, $remaining, $lastItem];
	}

	/**
	 * Remove back-ticks
	 *
	 * @param string $path Path with backticks
	 * @return string $path with backticks removed
	 */
	private function removeBackTicks(string $path): string {
		return preg_replace('/`/', '', $path);
	}

	// endregion
}
