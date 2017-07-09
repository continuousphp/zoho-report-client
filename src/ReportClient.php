<?php

namespace Zoho\Report;

/**
 * ReportClient provides the php based language binding to the https based api of ZohoReports.
 */
class ReportClient
{
    /**
     * @var string $zoho_url The base request api URL.
     */
    public $zoho_url = "https://reportsapi.zoho.com/api/";
    /**
     * @var const ZOHO_API_VERSION It contain the api version.It is a constant one.
     */
    const ZOHO_API_VERSION = '1.0';
    /**
     * @var string $zoho_action It is action name, that is performed by the URL.
     */
    public $zoho_action;
    /**
     * @var string $zoho_authtoken It is a unique token that authenticates the user to access the Zoho Account. This is a user-specific and permanent token, that need to be passed along with every Zoho Reports API request.
     */
    public $zoho_authtoken;
    /**
     * @var boolean $proxy It will indicate wheather the proxy is set or not.
     */
    public $proxy = FALSE;
    /**
     * @var string $proxy_host The hostname/ip address of the proxy-server.
     */
    public $proxy_host;
    /**
     * @var int $proxy_port The proxy server port.
     */
    public $proxy_port;
    /**
     * @var string $proxy_user_name The user name for proxy-server authentication.
     */
    public $proxy_user_name;
    /**
     * @var string $proxy_password The password for proxy-server authentication.
     */
    public $proxy_password;
    /**
     * @var string $proxy_type Can be any one ( HTTP , HTTPS , BOTH ).Specify "BOTH" if same configuration can be used for both http and https.
     */
    public $proxy_type;
    /**
     * @var int $connection_timeout It is a time value until a connection is etablished.
     */
    public $connection_timeout;
    /**
     * @var int $read_timeout It is a time value until waiting to read data.
     */
    public $read_timeout;

    /**
     * @internal Creates a new ZohoReportClient instance.
     */
    function __construct($authtoken)
    {
        $this->zoho_authtoken = $authtoken;
    }

    /**
     * Adds a row to the specified table identified by the URI.
     * @param string $table_uri The URI of the table.
     * @param array() $columnvalues Contains the values for the row. The column name(s) are the key.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return array Successfully added rows with value.
     */
    function addRow($table_uri, $columnvalues, $config = array())
    {
        foreach ($columnvalues as $key => $value) {
            $config[$key] = $value;
        }
        $this->zoho_action = 'ADDROW';
        $request_url = $this->getUrl($table_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        $response = $response['response']['result'];
        $count = count($response['column_order']);
        $result_array = array();
        for ($i = 0; $i < $count; $i++) {
            $result_array[$response['column_order'][$i]] = $response['rows'][0][$i];
        }
        return $result_array;
    }

    /**
     * Delete the data in the specified table identified by the URI.
     * @param string $table_uri The URI of the table.
     * @param string $criteria The criteria to be applied for deleting. Only rows matching the criteria will be deleted. Can be null. Incase it is null, then all rows will be deleted.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function deleteData($table_uri, $criteria = NULL, $config = array())
    {
        $this->zoho_action = 'DELETE';
        $config['ZOHO_CRITERIA'] = $criteria;
        $request_url = $this->getUrl($table_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * Update the data in the specified table identified by the URI.
     * @param string $table_uri The URI of the table.
     * @param array() $columnvalues Contains the values to be updated. The column name(s) are the key.
     * @param string $criteria The criteria to be applied for updating. Only rows matching the criteria will be updated. Can be null. Incase it is null, then all rows will be updated.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function updateData($table_uri, $columnvalues, $criteria = NULL, $config = array())
    {
        foreach ($columnvalues as $key => $value) {
            $config[$key] = $value;
        }
        $this->zoho_action = 'UPDATE';
        $config['ZOHO_CRITERIA'] = $criteria;
        $request_url = $this->getUrl($table_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * Import the data contained in a given file into the table identified by the URI.
     * @param string $table_uri The URI of the table.
     * @param string $import_type The type of import
     * @param file $file The file containing the data to be imported into the table.
     * @param string $auto_identify Used to specify whether to auto identify the CSV format.
     * @param string $on_error This parameter controls the action to be taken incase there is an error during import.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return object Import result class object.
     */
    function importData($table_uri, $import_type, $file, $auto_identify, $on_error, $config = array())
    {
        $this->zoho_action = 'IMPORT';
        $config['ZOHO_IMPORT_TYPE'] = $import_type;
        $config['ZOHO_AUTO_IDENTIFY'] = $auto_identify;
        $config['ZOHO_ON_IMPORT_ERROR'] = $on_error;
        if (!array_key_exists("ZOHO_CREATE_TABLE", $config)) {
            $config['ZOHO_CREATE_TABLE'] = 'FALSE';
        }
        $config = array_diff($config, array(''));
        $filename = end(explode('/', $file));
        $config['ZOHO_FILE'] = new CURLFile($file, 'json/csv', $filename);
        //$config['ZOHO_FILE'] = "@$file";
        $request_url = $this->getUrl($table_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        $import_obj = new ImportResult($response);
        return $import_obj;
    }

    /**
     * Import the data contained in a given string into the table identified by the URI.
     * @param string $table_uri The URI of the table.
     * @param string $import_type The type of import
     * @param string $import_data The string containing the data to be imported into the table.
     * @param string $auto_identify Used to specify whether to auto identify the CSV format.
     * @param string $on_error This parameter controls the action to be taken incase there is an error during import.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return object Import result class object.
     */
    function importDataAsString($table_uri, $import_type, $import_data, $auto_identify, $on_error, $config = array())
    {
        $this->zoho_action = 'IMPORT';
        $config['ZOHO_IMPORT_TYPE'] = $import_type;
        $config['ZOHO_AUTO_IDENTIFY'] = $auto_identify;
        $config['ZOHO_ON_IMPORT_ERROR'] = $on_error;
        if (!array_key_exists("ZOHO_CREATE_TABLE", $config)) {
            $config['ZOHO_CREATE_TABLE'] = 'FALSE';
        }
        $config = array_diff($config, array(''));
        $config['ZOHO_IMPORT_DATA'] = $import_data;
        $request_url = $this->getUrl($table_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        $import_obj = new ImportResult($response);
        return $import_obj;
    }

    /**
     * Exports the data/report of table (or report) identified by the URI.
     * @param string $table_uri The URI of the table.
     * @param string $file_format The format in which the data is to be exported.
     * @param string $criteria The criteria to be applied for exporting. Only rows matching the criteria will be exported. Can be null. Incase it is null, then all rows will be updated.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return string Table data.
     */
    function exportData($table_uri, $file_format, $criteria = NULL, $config = array())
    {
        $this->zoho_action = 'EXPORT';
        $config['ZOHO_CRITERIA'] = $criteria;
        $request_url = $this->getUrl($table_uri, $file_format);
        $response = $this->sendRequest($request_url, $config, true);
        return $response;
    }

    /**
     * Exports the data with the given SQL Query.
     * @param string $table_uri The URI of the table.
     * @param string $file_format The format in which the data is to be exported.
     * @param string $sql_query The SQL Query whose output is exported.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return string Table data.
     */
    function exportDataUsingSQL($table_uri, $file_format, $sql_query, $config = array())
    {
        $this->zoho_action = 'EXPORT';
        $config['ZOHO_SQLQUERY'] = $sql_query;
        $request_url = $this->getUrl($table_uri, $file_format);
        $response = $this->sendRequest($request_url, $config, true);
        return $response;
    }

    /**
     * Copy a specified database identified by the URI.
     * @param string $db_uri The URI of the database.
     * @param string $db_key Contains database key that user wants to copy.
     * @param string $new_db_name Contains new database name.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return string The new database id.
     */
    function copyDatabase($db_uri, $db_key, $new_db_name, $config = array())
    {
        $this->zoho_action = 'COPYDATABASE';
        $config['ZOHO_DATABASE_NAME'] = $new_db_name;
        $config['ZOHO_COPY_DB_KEY'] = $db_key;
        $request_url = $this->getUrl($db_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result']['dbid'];
    }

    /**
     * Delete a specified database from the Zoho Reports Account.
     * @param string $user_uri The URI of the user.
     * @param string $db_name The name of the database to be deleted from the Zoho Reports Account.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function deleteDatabase($user_uri, $db_name, $config = array())
    {
        $this->zoho_action = 'DELETEDATABASE';
        $config['ZOHO_DATABASE_NAME'] = $db_name;
        $request_url = $this->getUrl($user_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * Enable database for custom domain.
     * @param string $user_uri The URI of the user.
     * @param string $db_name The database names which you want to show in your custom domain.
     * @param string $domain_name Custom domain name.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return array() Response result of domain database status.
     */
    function enableDomainDB($user_uri, $db_name, $domain_name, $config = array())
    {
        $this->zoho_action = 'ENABLEDOMAINDB';
        $request_url = $this->getUrl($user_uri, 'JSON');
        $config['DBNAME'] = $db_name;
        $config['DOMAINNAME'] = $domain_name;
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result'];
    }

    /**
     * Disable database for custom domain.
     * @param string $user_uri The URI of the user.
     * @param string $db_name The database names which you want to disable from your custom domain.
     * @param string $domain_name Custom domain name.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return array() Response result of domain database status.
     */
    function disableDomainDB($user_uri, $db_name, $domain_name, $config = array())
    {
        $this->zoho_action = 'DISABLEDOMAINDB';
        $request_url = $this->getUrl($user_uri, 'JSON');
        $config['DBNAME'] = $db_name;
        $config['DOMAINNAME'] = $domain_name;
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result'];
    }

    /**
     * Create a table in the specified database.
     * @param string $db_uri The URI of the database.
     * @param JSON $table_design_JSON Table structure in JSON format (includes table name, description, folder name, column and lookup details).
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function createTable($db_uri, $table_design_JSON, $config = array())
    {
        $this->zoho_action = 'CREATETABLE';
        $config['ZOHO_TABLE_DESIGN'] = $table_design_JSON;
        $request_url = $this->getUrl($db_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * To generate reports.
     * @param string $table_uri The URI of the table.
     * @param string $source To set column or table.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return string Autogenerate result.
     */
    function autoGenReports($table_uri, $source, $config = array())
    {
        $this->zoho_action = "AUTOGENREPORTS";
        $config['ZOHO_SOURCE'] = $source;
        $request_url = $this->getUrl($table_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result'];
    }

    /**
     * Create reports similar as another table reports.
     * @param string $table_uri The URI of the table.
     * @param string $ref_view The reference table name.
     * @param string $folder_name Folder name where the reports to be saved.
     * @param boolean $copy_customformula If true, it will create reports with custom formula else it will ignore that formula.
     * @param boolean $copy_aggformula If true, it will create reports with aggregate formula else it will ignore that formula.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return array() Response result of similar views status.
     */
    function createSimilarViews($table_uri, $ref_view, $folder_name, $copy_customformula, $copy_aggformula, $config = array())
    {
        $this->zoho_action = 'CREATESIMILARVIEWS';
        $request_url = $this->getUrl($table_uri, 'JSON');
        $config['ZOHO_REFVIEW'] = $ref_view;
        $config['ZOHO_FOLDERNAME'] = $folder_name;
        $config['ISCOPYCUSTOMFORMULA'] = ($copy_customformula == TRUE) ? "true" : "false";
        $config['ISCOPYAGGFORMULA'] = ($copy_aggformula == TRUE) ? "true" : "false";
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result'];
    }

    /**
     * Rename the specified view with the new name and description.
     * @param string $db_uri The URI of the database.
     * @param string $viewname Current name of the view.
     * @param string $new_viewname New name for the view.
     * @param string $new_viewdesc New description for the view.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function renameView($db_uri, $viewname, $new_viewname, $new_viewdesc = NULL, $config = array())
    {
        $this->zoho_action = 'RENAMEVIEW';
        $config['ZOHO_VIEWNAME'] = $viewname;
        $config['ZOHO_NEW_VIEWNAME'] = $new_viewname;
        $config['ZOHO_NEW_VIEWDESC'] = $new_viewdesc;
        $request_url = $this->getUrl($db_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * The Copy Reports API is used to copy one or more reports from one database to another within the same account or even across user accounts.
     * @param string $db_uri The URI of the Database.
     * @param string $views This parameter holds the list of view names.
     * @param string $db_name The database name where the reports had to be copied.
     * @param string $db_key The secret key used for allowing the user to copy the database / reports.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function copyReports($db_uri, $views, $db_name, $db_key, $config = array())
    {
        $this->zoho_action = 'COPYREPORTS';
        $config['ZOHO_VIEWTOCOPY'] = $views;
        $config['ZOHO_DATABASE_NAME'] = $db_name;
        $config['ZOHO_COPY_DB_KEY'] = $db_key;
        $request_url = $this->getUrl($db_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * The Copy Formula API is used to copy one or more formula columns from one table to another within the same database or across databases and even across one user account to another.
     * @param string $table_uri The URI of the table.
     * @param string $formula This parameter holds the list of formula names.
     * @param string $db_name The database name where the formula's had to be copied.
     * @param string $db_key The secret key used for allowing the user to copy the formula.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function copyFormula($table_uri, $formula, $db_name, $db_key, $config = array())
    {
        $this->zoho_action = 'COPYFORMULA';
        $config['ZOHO_FORMULATOCOPY'] = $formula;
        $config['ZOHO_DATABASE_NAME'] = $db_name;
        $config['ZOHO_COPY_DB_KEY'] = $db_key;
        $request_url = $this->getUrl($table_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * Adds a column to the specified table identified by the URI.
     * @param string $table_uri The URI of the table.
     * @param string $column_name Contains the name of the column to be added.
     * @param string $data_type Contains the datatype of the column to be added.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function addColumn($table_uri, $column_name, $data_type, $config = array())
    {
        $this->zoho_action = 'ADDCOLUMN';
        $config['ZOHO_COLUMNNAME'] = $column_name;
        $config['ZOHO_DATATYPE'] = $data_type;
        $request_url = $this->getUrl($table_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * Delete the column in the specified table identified by the URI.
     * @param string $table_uri The URI of the table.
     * @param string $column_name Contains the name of the column to be deleted.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function deleteColumn($table_uri, $column_name, $config = array())
    {
        $this->zoho_action = 'DELETECOLUMN';
        $config['ZOHO_COLUMNNAME'] = $column_name;
        $request_url = $this->getUrl($table_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * Rename the column in the specified table identified by the URI.
     * @param string $table_uri The URI of the table.
     * @param string $old_column_name Contains the name of the column to be modified.
     * @param string $new_column_name Contains the new column name.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function renameColumn($table_uri, $old_column_name, $new_column_name, $config = array())
    {
        $this->zoho_action = 'RENAMECOLUMN';
        $config['OLDCOLUMNNAME'] = $old_column_name;
        $config['NEWCOLUMNNAME'] = $new_column_name;
        $request_url = $this->getUrl($table_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * To hide columns in the table.
     * @param string $table_uri The URI of the table.
     * @param array() $columnNames The column names of the table.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return array() Response result of hidecolumn.
     */
    function hideColumn($table_uri, $columnNames, $config = array())
    {
        $this->zoho_action = "HIDECOLUMN";
        $request_url = $this->getUrl($table_uri, 'JSON');
        for ($i = 0; $i < sizeof($columnNames); $i++) {
            $request_url = $request_url . "&ZOHO_COLUMNNAME=" . $columnNames[$i];
        }
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result'];
    }

    /**
     * Get the plan informations.
     * @param string $table_uri The URI of the table.
     * @param array() $columnNames The column names of the table.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return array() Response result of showcolumn.
     */
    function showColumn($table_uri, $columnNames, $config = array())
    {
        $this->zoho_action = "SHOWCOLUMN";
        $request_url = $this->getUrl($table_uri, 'JSON');
        for ($i = 0; $i < sizeof($columnNames); $i++) {
            $request_url = $request_url . "&ZOHO_COLUMNNAME=" . $columnNames[$i];
        }
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result'];
    }

    /**
     * Add the lookup for the given column.
     * @param string $table_uri The URI of the table.
     * @param string $column_name Name of the column (Child column).
     * @param string $referred_table Name of the referred table (parent table).
     * @param string $referred_column Name of the referred column (parent column).
     * @param string $on_error This parameter controls the action to be taken incase there is an error during lookup.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function addLookup($table_uri, $column_name, $referred_table, $referred_column, $on_error, $config = array())
    {
        $this->zoho_action = 'ADDLOOKUP';
        $config['ZOHO_COLUMNNAME'] = $column_name;
        $config['ZOHO_REFERREDTABLE'] = $referred_table;
        $config['ZOHO_REFERREDCOLUMN'] = $referred_column;
        $config['ZOHO_IFERRORONCONVERSION'] = $on_error;
        $request_url = $this->getUrl($table_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * Remove the lookup for the given column.
     * @param string $table_uri The URI of the table.
     * @param string $column_name Name of the column.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function removeLookup($table_uri, $column_name, $config = array())
    {
        $this->zoho_action = 'REMOVELOOKUP';
        $config['ZOHO_COLUMNNAME'] = $column_name;
        $request_url = $this->getUrl($table_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * This method is used to get the meta information about the reports.
     * @param string $user_uri The URI of the user.
     * @param string $metadata It specifies the information to be fetched.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return array() The metadata.
     */
    function getDatabaseMetadata($user_uri, $metadata, $config = array())
    {
        $this->zoho_action = 'DATABASEMETADATA';
        $config['ZOHO_METADATA'] = $metadata;
        $request_url = $this->getUrl($user_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result'];
    }

    /**
     * Get database name for a specified database identified by the URI.
     * @param string $user_uri The URI of the user.
     * @param string $db_id The ID of the database.
     * @param array() $config $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return string Database name for a specified database.
     */
    function getDatabaseName($user_uri, $db_id, $config = array())
    {
        $this->zoho_action = 'GETDATABASENAME';
        $config['DBID'] = $db_id;
        $request_url = $this->getUrl($user_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result']['dbname'];
    }

    /**
     * Check wheather the database is exist or not.
     * @param string $user_uri The URI of the user.
     * @param string $dbname The database name.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return boolean Wheather the database is exist or not.
     */
    function isDbExist($user_uri, $dbname, $config = array())
    {
        $this->zoho_action = "ISDBEXIST";
        $config['ZOHO_DB_NAME'] = $dbname;
        $request_url = $this->getUrl($user_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result']['isdbexist'];
    }

    /**
     * Get copy database key for a specified database identified by the URI.
     * @param string $db_uri The URI of the database.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return string Copy database key for a specified database.
     */
    function getCopyDbKey($db_uri, $config = array())
    {
        $this->zoho_action = 'GETCOPYDBKEY';
        $request_url = $this->getUrl($db_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result']['copydbkey'];
    }

    /**
     * This function returns the name of a view in Zoho Reports.
     * @param string $user_uri The URI of the User.
     * @param string $obj_id The view id (object id).
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return string The View name.
     */
    function getViewName($user_uri, $obj_id, $config = array())
    {
        $this->zoho_action = 'GETVIEWNAME';
        $config['OBJID'] = $obj_id;
        $request_url = $this->getUrl($user_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result']['viewname'];
    }

    /**
     * This method returns the Database ID (DBID) and View ID (OBJID) of the corresponding Database.
     * @param string $table_uri The URI of the table.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return array() The View-Id (object id) and Database-Id.
     */
    function getInfo($table_uri, $config = array())
    {
        $this->zoho_action = 'GETINFO';
        $request_url = $this->getUrl($table_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result'];
    }

    /**
     * This method is used to share the views (tables/reports/dashboards) created in Zoho Reports with users.
     * @param string $db_uri The URI of the database.
     * @param string $email_ids It contains the users email-id (comma seperated).
     * @param string $views It contains the view names.
     * @param string $criteria It can be null.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function shareView($db_uri, $email_ids, $views, $criteria = NULL, $config = array())
    {
        $this->zoho_action = 'SHARE';
        $config['ZOHO_EMAILS'] = $email_ids;
        $config['ZOHO_VIEWS'] = $views;
        $config['ZOHO_CRITERIA'] = $criteria;
        $request_url = $this->getUrl($db_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * This method is used to remove the shared views (tables/reports/dashboards) in Zoho Reports from the users.
     * @param string $db_uri The URI of the database.
     * @param string $email_ids It contains the users email-id (comma seperated).
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function removeShare($db_uri, $email_ids, $config = array())
    {
        $this->zoho_action = 'REMOVESHARE';
        $config['ZOHO_EMAILS'] = $email_ids;
        $request_url = $this->getUrl($db_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * This method is used to add new owners to the reports database.
     * @param string $db_uri The URI of the database.
     * @param string $email_ids It contains the users email-id (comma seperated).
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function addDbOwner($db_uri, $email_ids, $config = array())
    {
        $this->zoho_action = 'ADDDBOWNER';
        $config['ZOHO_EMAILS'] = $email_ids;
        $request_url = $this->getUrl($db_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * This method is used to remove the existing owners from the reports database.
     * @param string $db_uri The URI of the database.
     * @param string $email_ids It contains the owners email-id (comma seperated).
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function removeDbOwner($db_uri, $email_ids, $config = array())
    {
        $this->zoho_action = 'REMOVEDBOWNER';
        $config['ZOHO_EMAILS'] = $email_ids;
        $request_url = $this->getUrl($db_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * Get the shared informations.
     * @param string $db_uri The URI of the database.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return object ShareInfo class object.
     */
    function getShareInfo($db_uri, $config = array())
    {
        $this->zoho_action = "GETSHAREINFO";
        $request_url = $this->getUrl($db_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        $shareinfo_obj = new ShareInfo($response);
        return $shareinfo_obj;
    }

    /**
     * This method returns the URL to access the mentioned view.
     * @param string $table_uri The URI of the table.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return string The View URI.
     */
    function getViewUrl($table_uri, $config = array())
    {
        $this->zoho_action = 'GETVIEWURL';
        $request_url = $this->getUrl($table_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result']['viewurl'];
    }

    /**
     * The Get Embed URL API is used to get the embed URL of the particular table / view. This API is available only for the White Label Administrator.
     * @param string $table_uri The URI of the table.
     * @param string $criteria It can be null.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return string The embed URI.
     */
    function getEmbedURL($table_uri, $criteria = NULL, $config = array())
    {
        $this->zoho_action = 'GETEMBEDURL';
        $config['ZOHO_CRITERIA'] = $criteria;
        $request_url = $this->getUrl($table_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result']['embedUrl'];
    }

    /**
     * To get the users list.
     * @param string $user_uri The URI of the user.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return array() Users list.
     */
    function getUsers($user_uri, $config = array())
    {
        $this->zoho_action = "GETUSERS";
        $request_url = $this->getUrl($user_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        return $response['response']['result'];
    }

    /**
     * Adds the specified user(s) into your Zoho Reports Account.
     * @param string $user_uri The URI of the user.
     * @param string $emails The email addresses of the users to be added to your Zoho Reports Account separated by comma.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function addUser($user_uri, $emails, $config = array())
    {
        $this->zoho_action = 'ADDUSER';
        $config['ZOHO_EMAILS'] = $emails;
        $request_url = $this->getUrl($user_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * Removes the specified user(s) from your Zoho Reports Account.
     * @param string $user_uri The URI of the user.
     * @param string $emails The email addresses of the users to be removed from your Zoho Reports Account separated by comma.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function removeUser($user_uri, $emails, $config = array())
    {
        $this->zoho_action = 'REMOVEUSER';
        $config['ZOHO_EMAILS'] = $emails;
        $request_url = $this->getUrl($user_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * Activates the specified user(s) in your Zoho Reports Account.
     * @param string $user_uri The URI of the user.
     * @param string $emails The email addresses of the users to be activated in your Zoho Reports Account separated by comma.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function activateUser($user_uri, $emails, $config = array())
    {
        $this->zoho_action = 'ACTIVATEUSER';
        $config['ZOHO_EMAILS'] = $emails;
        $request_url = $this->getUrl($user_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * Deactivates the specified user(s) from your Zoho Reports Account.
     * @param string $user_uri The URI of the user.
     * @param string $emails The email addresses of the users to be deactivated from your Zoho Reports Account separated by comma.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     */
    function deActivateUser($user_uri, $emails, $config = array())
    {
        $this->zoho_action = 'DEACTIVATEUSER';
        $config['ZOHO_EMAILS'] = $emails;
        $request_url = $this->getUrl($user_uri, 'JSON');
        $this->sendRequest($request_url, $config, false);
    }

    /**
     * Get the plan informations.
     * @param string $user_uri The URI of the user.
     * @param array() $config Contains any additional control parameters. Can be null.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @throws ServerException If the server has recieved the request but did not process the request due to some error.
     * @throws ParseException If the server has responded but client was not able to parse the response.
     * @return object PlanInfo class object.
     */
    function getPlanInfo($user_uri, $config = array())
    {
        $this->zoho_action = "GETUSERPLANDETAILS";
        $request_url = $this->getUrl($user_uri, 'JSON');
        $response = $this->sendRequest($request_url, $config, true);
        $planinfo_obj = new PlanInfo($response);
        return $planinfo_obj;
    }

    /**
     * Returns the authtoken of the user.
     * @return string AuthToken.
     */
    function getAuthToken()
    {
        return $this->zoho_authtoken;
    }

    /**
     * Returns the URI for the specified user login email id. This URI should be used only in case of METADATA Action.
     * @param string $email User email id to get the database metadata.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @return string URI for the user.
     */
    function getUserURI($email)
    {
        return $this->zoho_url . urlencode($email);
    }

    /**
     * Returns the URI for the specified database. This URI should be used only in case of COPYDATABASE,GETCOPYDBKEY Action.
     * @param string $email User email id.
     * @param string $db_name The name of the database.
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @return string URI for the database.
     */
    function getDbURI($email, $db_name)
    {
        return $this->splCharReplace($this->zoho_url . urlencode($email) . "/" . urlencode($db_name));
    }

    /**
     * Returns the URI for the specified database table (or report).
     * @param string $email The owner of the database containing the table (or report).
     * @param string $db_name The name of the database containing the table (or report).
     * @param string $table_name The name of the table (or report).
     * @throws IOException If any communication related error(s) like request time out occurs when trying to contact the service.
     * @return string URI for the table.
     */
    function getURI($email, $db_name, $table_name)
    {
        return $this->splCharReplace($this->zoho_url . urlencode($email) . "/" . urlencode($db_name) . "/" . urlencode($table_name));
    }

    /**
     *Internal method for handling special charecters in the table or database name.
     * @param string $string The database or table name containing the special charecters.
     */
    function splCharReplace($string)
    {
        $string = str_replace("%2F", "(/)", $string);
        $string = str_replace("%5C", "(//)", $string);
        return $string;
    }

    /**
     * Used to specify the proxy server details.
     * @param string $proxy_host The hostname/ip address of the proxy-server.
     * @param int $proxy_port The proxy server port.
     * @param string $proxy_type Can be any one ( HTTP , HTTPS , BOTH ).Specify "BOTH" if same configuration can be used for both http and https.
     * @param string $proxy_user_name The user name for proxy-server authentication.
     * @param string $proxy_password The password for proxy-server authentication.
     */
    function setProxy($proxy_host, $proxy_port, $proxy_type, $proxy_user_name, $proxy_password)
    {
        $this->proxy = TRUE;
        $this->proxy_host = $proxy_host;
        $this->proxy_port = $proxy_port;
        $this->proxy_user_name = $proxy_user_name;
        $this->proxy_password = $proxy_password;
        $this->proxy_type = $proxy_type;
    }

    /**
     * Sets the timeout until a connection is etablished. A value of zero means the timeout is not used. The default value is 15000.
     * @param int $time_limit An integer value.
     */
    function setConnectionTimeout($time_limit)
    {
        $this->connection_timeout = $time_limit;
    }

    /**
     * Sets the timeout until waiting to read data. A value of zero means the timeout is not used. The default value is 15000.
     * @param int $time_limit An integer value.
     */
    function setReadTimeout($time_limit)
    {
        $this->read_timeout = $time_limit;
    }

    /**
     * Returns the timeout until a connection is etablished.A value of zero means the timeout is not used.
     * @return int Connection timeout limit.
     */
    function getConnectionTimeout()
    {
        return $this->connection_timeout;
    }

    /**
     * Returns the timeout until waiting to read data. A value of zero means the timeout is not used. The default value is 15000.
     * @return int Read timeout limit.
     */
    function getReadTimeout()
    {
        return $this->read_timeout;
    }

    /**
     * @internal To build request url.
     */
    function getUrl($table_uri, $zoho_output_format)
    {
        $request_url = $table_uri . '?ZOHO_ACTION=' . $this->zoho_action . '&ZOHO_OUTPUT_FORMAT=' . $zoho_output_format . '&ZOHO_ERROR_FORMAT=JSON&authtoken='
            . $this->zoho_authtoken . '&ZOHO_API_VERSION=' . self::ZOHO_API_VERSION;
        return $request_url;
    }

    /**
     * @internal Send request and get response from the server.
     */
    function sendRequest($request_url, $config, $return_response)
    {
        if ($this->zoho_action != "IMPORT") {
            $config = array_diff($config, array(''));
        }
        $HTTP_request = curl_init();
        curl_setopt($HTTP_request, CURLOPT_URL, $request_url);
        curl_setopt($HTTP_request, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($HTTP_request, CURLOPT_FOLLOWLOCATION, TRUE);
        if (is_array($config)) {
            curl_setopt($HTTP_request, CURLOPT_POST, 1);
            curl_setopt($HTTP_request, CURLOPT_POSTFIELDS, $config);
        }
        curl_setopt($HTTP_request, CURLOPT_CONNECTTIMEOUT, $this->connection_timeout);
        curl_setopt($HTTP_request, CURLOPT_TIMEOUT, $this->read_timeout);
        if ($this->proxy == TRUE) {
            curl_setopt($HTTP_request, CURLOPT_PROXY, $this->proxy_host);
            curl_setopt($HTTP_request, CURLOPT_PROXYTYPE, $this->proxy_type);
            curl_setopt($HTTP_request, CURLOPT_PROXYPORT, $this->proxy_port);
            curl_setopt($HTTP_request, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($HTTP_request, CURLOPT_PROXYUSERPWD, "$this->proxy_user_name:$this->proxy_password");
        }
        $HTTP_response = curl_exec($HTTP_request);
        $HTTP_status_code = curl_getinfo($HTTP_request, CURLINFO_HTTP_CODE);
        if ($HTTP_response != FALSE) {
            if ($HTTP_status_code != 200) {
                $JSON_response = json_decode($HTTP_response, TRUE);
                if (json_last_error() != JSON_ERROR_NONE) {
                    $HTTP_response = stripslashes($HTTP_response);
                    $JSON_response = json_decode($HTTP_response, TRUE);
                }
                if (json_last_error()) {
                    throw new ParseException("Returned JSON format for " . $this->zoho_action . " is not proper. Could possibly be version mismatch");
                }
                $error_message = $JSON_response['response']['error']['message'];
                $error_code = $JSON_response['response']['error']['code'];
                throw new ServerException($error_code, $error_message, $this->zoho_action, $HTTP_status_code);
            } else {
                $action = $this->zoho_action;
                if ($action == "EXPORT") {
                    return $HTTP_response;
                } else if ($return_response == true) {
                    $JSON_response = json_decode($HTTP_response, TRUE);
                    if (json_last_error() != JSON_ERROR_NONE) {
                        $HTTP_response = stripslashes($HTTP_response);
                        $JSON_response = json_decode($HTTP_response, TRUE);
                    }
                    if (json_last_error()) {
                        throw new ParseException("Returned JSON format for " . $this->zoho_action . " is not proper. Could possibly be version mismatch");
                    } else {
                        return $JSON_response;
                    }
                }
            }
        } else {
            throw new IOException(curl_error($HTTP_request), $this->zoho_action, $HTTP_status_code);
        }
        curl_close($HTTP_request);
    }
}
