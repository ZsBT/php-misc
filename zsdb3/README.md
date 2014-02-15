# [Z]sombor's [S]imple [D]ata[B]ase        v3.0
        
## Usage

```php
include "zsdb3.php";
$db = new zsdb3($connspec);
```

### Connection specification syntax

#### psql, mysql, mysqli, mssql, oracle

	Connspec syntax is:
	dbtype::dbname@host[:port][/user:password]
	
	Mysql example:
	mysqli::mydb@localhost/username:s3cr3tp@ss

#### sqlite3

	sqlite3::/path/to/sqlite3.db

## Class synopsis

### Store methods
```php
  public function insert($table, $datarr) {	/* inserts an array-specified row to a table */
  public function i($table,$datarr)	/* alias for insert() */
  public function update($table, $datarr, $cond="" ) { /* updates a row */
  public function u($table,$datarr,$cond=0) /* alias for update() */
  public function iou($t, $datarr, $cond=0) {	/* insert-or-update: deletes then inserts a row */
```
### Query methods
```php
  public function Q($query) { /* Query one field of one row. returns a simple value */
  public function QFO($query) /* queries the first row then returns as an object */
  public function QFA($query) /* queries the first row then returns as an array */
  public function QA($query) /* queries one field of several rows */
  public function QAA($query) /* queries array of rows, elements are associated array of fields */
  public function QOA($query) /* queries array of rows, elements are object of fields */
```
### Transaction handling
```php
  public function btrans() /* begin transaction */
  public function commit() /* commit transaction */
  public function rollback() /* rollback transaction */
```
---
2012-2013
