<?php

namespace FragTale\Constant\Setup\Database;

use FragTale\Constant;

/**
 *
 * Supported database drivers.
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
abstract class Driver extends Constant {

	/**
	 * MySQL & MariaDB
	 *
	 * mysql:host=localhost;port=3306;dbname=testdb
	 *
	 * @var string
	 */
	const MYSQL = 'mysql';

	/**
	 * MicroSoft SQL Server
	 *
	 * sqlsrv:Server=localhost,1521;Database=testdb
	 *
	 * Note: Install sqlsrv driver via pecl
	 * pecl install sqlsrv
	 * pecl install pdo_sqlsrv
	 *
	 * @var string
	 */
	const MSSQL = 'sqlsrv';

	/**
	 * PostgreSQL
	 *
	 * pgsql:host=localhost;port=5432;dbname=testdb;
	 *
	 * @var string
	 */
	const POSTGRESQL = 'pgsql';

	/**
	 * odbc:Driver={Microsoft Access Driver (*.mdb)};Dbq=C:\\db.mdb;Uid=Admin
	 *
	 * @var string
	 */
	// const ODBC = 'odbc';

	/**
	 * oci:dbname=DBNAME;host=NN.NN.NN.NNN
	 *
	 * @var string
	 */
	const ORACLE = 'oci';

	/**
	 * MongoDB, NoSQL
	 *
	 * @var string
	 */
	const MONGO = 'mongodb';
}