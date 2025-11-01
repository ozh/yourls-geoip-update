# GeoIP Update [![Listed in Awesome YOURLS!](https://img.shields.io/static/v1?label=Awesome&message=YOURLS&color=C5A3BE&style=flat-square)](https://github.com/YOURLS/awesome-yourls/)

This plugin updates the GeoIP database.

Requires [YOURLS](https://yourls.org) `1.8.2` and above, a [Maxmind account](https://www.maxmind.com/en/account/login) and a free license key. 

## Installation

1. In `/user/plugins`, create a new folder named `geoip-update`.
2. Drop these files in that directory.
3. Go to the Plugins administration page (eg. `http://sho.rt/admin/plugins.php`) and activate the plugin.
4. Have fun!

## Usage

Input your account ID, license key and click "Update DB". That's all.

<img width="972" height="286" alt="image" src="https://github.com/user-attachments/assets/42abd562-1886-4355-b2db-bc9b31124233" />

## Advanced usage

You can use this plugin to automatically update the GeoIP database using `cron` if available on your server.

See file `run.php` in the plugin directory for more information and sample code.

## License

Do whatever the hell you want with it.
