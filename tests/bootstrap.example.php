<?php
/**
 * FluxBB
 *
 * LICENSE
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @category	FluxBB
 * @package		Flux_Database
 * @subpackage	Tests
 * @copyright	Copyright (c) 2011 FluxBB (http://fluxbb.org)
 * @license		http://www.gnu.org/licenses/lgpl.html	GNU Lesser General Public License
 */

$_ENV['DB_MYSQL_DBNAME'] = 'fluxbb__test';
$_ENV['DB_MYSQL_HOST'] = '0.0.0.0';
$_ENV['DB_MYSQL_USER'] = '';
$_ENV['DB_MYSQL_PASSWD'] = '';

$_ENV['DB_SQLITE_DBNAME'] = ':memory:';

$_ENV['DB_PGSQL_DBNAME'] = 'fluxbb__test';
$_ENV['DB_PGSQL_HOST'] = '127.0.0.1';
$_ENV['DB_PGSQL_USER'] = 'postgres';
$_ENV['DB_PGSQL_PASSWD'] = '';
