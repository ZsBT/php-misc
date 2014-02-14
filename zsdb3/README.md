#[Z]sombor's [S]imple [D]ata[B]ase        v3.0
        
##Usage

```php
include "zsdb3.php";
$db = new zsdb3($connspec);

###Connection specification syntax

####psql, mysql, mysqli, mssql, oracle

	type::dbname@host[:port][/user:password]
	eg: mysqli::mydb@localhost/username:s3cr3tp@ss

####sqlite3

	sqlite::/path/to/sqlite3.db

---
2012-2013
