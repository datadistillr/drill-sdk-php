[![Php_version](https://img.shields.io/packagist/php-v/datadistillr/drill-sdk-php.svg)](https://packagist.org/packages/datadistillr/drill-sdk-php)
[![Package version](https://img.shields.io/packagist/v/datadistillr/drill-sdk-php.svg?include_prereleases&style=flat-square)](https://packagist.org/packages/datadistillr/drill-sdk-php)
[![Slack](https://img.shields.io/badge/slack-%23datadistillrcommunity-blue.svg?style=flat-square)](https://symfony.com/slack-invite)
[![License](https://img.shields.io/badge/license-Apache_2.0-blue.svg?style=flat-square)](LICENSE)

# PHP SDK Library for Connecting to Apache Drill.

This library allows you to connect to and query Apache Drill programmatically.  It is loosely modeled after PHP's
mysql interface, so if you are familiar with that, you already pretty much know how to use the Drill connector.

## Installing the Library
The libarary can be installed using [Composer](https://getcomposer.org) by using the following command:
```
composer require "datadistillr/drill-sdk-php:^0.6.0"
```

The current pre-release version is: `0.6.1`

## Using the Connector
The first step is to make a Drill connection handle.  The module uses Drill's RESTful interface, so it will not
maintain an open connection like a typical JDBC/ODBC connection would.

```php
$drillHandle = new DrillConnection( 'localhost', 8047 );
```

You can use the `is_active()` menthod to verify that your connection is active.
```php
if( $drillHandle->is_active() ) {
    print( "Connection Active" );
} else {
    print( "Connection Inactive" );
}
```

## Querying Drill
Now that you've created your Drill connection handle, you can query Drill in a similar fashion as MySQL by calling
the `query()` method. Once you've called the `query()` method, you can use one of the `fetch()` methods to retrieve
the results, in a similar manner as MySQL.  Currently, the Drill connector currently has:
* **`fetchAll()`**:  Returns all query results in an associative array.
* **`fetchAssoc()`**:  Returns a single query row as an associative array.
* **`fetchObject()`**:  Returns a single row as a PHP Object.

You might also find these functions useful:
* **`dataSeek($n)`**: Returns the row at index `$n` and sets the current row to `$n`. 
* **`numEows()`**: Returns the number of rows returned by the query.
* **`fieldCount()`**:  Returns the number of columns returned by the query.

Thus, if you want to execute a query in Drill, you can do so as follows:
```PHP
$query_result = $drillHandle->query( "SELECT * FROM cp.`employee.json` LIMIT 20" );
while( $row = $query_result->fetch_assoc() ) {
    print( "Field 1: {$row['field1']}\n" );
    print( "Field 2: {$row['field2']}\n" );
}
```
## Interacting with Drill
You can also use the connector to activate/deactivate Drill's storage as well as get information about Drill's plugins.

* **`disablePlugin( $plugin )`**  Disables the given plugin.  Returns true if successful, false if not.
* **`enablePlugin( $plugin )`**   Enables the given plugin.  Returns true if successful, false if not.
* **`getAllStoragePlugins()`**  Returns an array of all storage plugins.
* **`getDisabledStoragePlugins()`**  Returns an array of all disabled plugins.
* **`getEnabledStoragePlugins()`**  Returns an array of all enabled plugins.
* **`getStoragePlugins()`**  Returns an associative array of plugins and associated configuration options for all plugins.
* **`getStoragePluginInfo( $plugin )`**  Returns an associative array of configuration options for a given plugin. 
* **`saveStoragePlugin( $plugin_name, $config )`**  Creates or edits a storage plugin. Returns true if successful,
throws exception if not.
* **`deleteStoragePlugin( $plugin_name )`**  Deletes a storage plugin. Returns true if successful, throws exception if not.
