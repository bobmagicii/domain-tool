# Domain Registration and SSL Certification Lookup Tool

* use RDAP to check Domain registrations.
* use cURL or OpenSSL to check SSL Certs.

## Requirements

* PHP 8.3 (with CLI, cURL, OpenSSL)
* OpenRDAP v0.9.1 (CLI App)

## Basic Usage

Print a report for the list of domains.

* `php domain.phar report <domain> ...`

Print a report for all the domains listed in all the files. One domain per line.

* `php domain.phar report --files <filename> ...`

## More Options

* `report --sort` - Sort the report by domain name.
* `report --short` - Only show the report table.



## Dev Notes

### Build PHAR

* You Give: `php bin/domain.php phar`
* You Receive: `build/domain-ver.phar`
