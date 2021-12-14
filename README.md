# PHP SDK Library for Connecting to Apache Drill.

This library allows you to connect to and query Apache Drill programmatically.  It is loosely modeled after PHP's
mysql interface, so if you are familiar with that, you already pretty much know how to use the Drill connector.

## Installing the Library
The libarary can be installed using [Composer](https://getcomposer.org) by using the following command:
```
composer require "datadistillr/drill-sdk-php:^0.6.0"
```

The current pre-release version is: `0.6.0`

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
* **`fetch_all()`**:  Returns all query results in an associative array.
* **`fetch_assoc()`**:  Returns a single query row as an associative array.
* **`fetch_object()`**:  Returns a single row as a PHP Object.

You might also find these functions useful:
* **`data_seek($n)`**: Returns the row at index `$n` and sets the current row to `$n`. 
* **`num_rows()`**: Returns the number of rows returned by the query.
* **`field_count()`**:  Returns the number of columns returned by the query.

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

* **`disable_plugin( $plugin )`**  Disables the given plugin.  Returns true if successful, false if not.
* **`enable_plugin( $plugin )`**   Enables the given plugin.  Returns true if successful, false if not.
* **`get_all_storage_plugins()`**  Returns an array of all storage plugins.
* **`get_disabled_storage_plugins()`**  Returns an array of all disabled plugins.
* **`get_enabled_storage_plugins()`**  Returns an array of all enabled plugins.
* **`get_storage_plugins()`**  Returns an associative array of plugins and associated configuration options for all plugins.
* **`get_storage_plugin_info( $plugin )`**  Returns an associative array of configuration options for a given plugin. 
* **`save_storage_plugin( $plugin_name, $config )`**  Creates or edits a storage plugin. Returns true if successful,
throws exception if not.
* **`delete_storage_plugin( $plugin_name )`**  Deletes a storage plugin. Returns true if successful, throws exception if not.
