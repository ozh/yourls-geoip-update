# GeoIP Update

This plugin updates the GeoIP database.

Requires [YOURLS](https://yourls.org) `1.8.2` and above,
and a [Maxmind account](https://www.maxmind.com/en/account/login) and a free license key. 

## Installation

1. In `/user/plugins`, create a new folder named `geoip-update`.
2. Drop these files in that directory.
3. Go to the Plugins administration page (eg. `http://sho.rt/admin/plugins.php`) and activate the plugin.
4. Have fun!

## Advanced usage

You can use this plugin to automatically update the GeoIP database using `cron` if available on your server.

See file `run.php` in the plugin directory for more information. 

## License

Do whatever the hell you want with it.
