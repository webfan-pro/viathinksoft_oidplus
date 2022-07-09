#!/bin/bash

# OIDplus 2.0
# Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

DIR=$( dirname "$0" )

cd "$DIR/.."

# We temporarily move the .svn directory, otherwise
# we cannot checkout fileformats and vnag in the vendor
# directory
if [ -d ".svn" ]; then
	mv .svn _svn
fi

# Remove vendor and composer.lock, so we can download everything again
# (We need to receive everything again, because we had to delete the .git
# .svn files and therefore we cannot do a simple "svn update" delta update anymore)
rm -rf vendor
rm composer.lock

# Download everything again
composer update

# Remove stuff we don't want to publish or PHP files which could be
# executed (which would be a security risk, because the vendor/ directory
# can be accessed via the web-browser)
remove_vendor_rubbish() {
	shopt -s globstar
	rm -rf $1vendor/**/.svn
	rm -rf $1vendor/**/.git
	rm -rf $1vendor/**/.gitignore
	rm -rf $1vendor/**/.gitattributes
	rm -rf $1vendor/**/.github
	rm -rf $1vendor/**/demo
	rm -rf $1vendor/**/demos
	rm -rf $1vendor/twbs/bootstrap/package*
	rm -rf $1vendor/twbs/bootstrap/*.js
	rm -rf $1vendor/twbs/bootstrap/*.yml
	rm -rf $1vendor/twbs/bootstrap/.* 2>/dev/null
	rm -rf $1vendor/twbs/bootstrap/nuget/
	rm -rf $1vendor/twbs/bootstrap/scss/
	rm -rf $1vendor/twbs/bootstrap/js/
	rm -rf $1vendor/twbs/bootstrap/build/
	rm -rf $1vendor/twbs/bootstrap/site/
	rm -rf $1vendor/google/recaptcha/examples/
	rm -rf $1vendor/**/tests
	rm -rf $1vendor/**/test
	rm $1vendor/**/*.phpt
	rm $1vendor/**/example.php
	rm -rf $1vendor/danielmarschall/vnag/logos
	rm -rf $1vendor/danielmarschall/vnag/doc
	rm -rf $1vendor/danielmarschall/vnag/plugins
	rm -rf $1vendor/danielmarschall/uuid_mac_utils/*.php
	rm -rf $1vendor/danielmarschall/uuid_mac_utils/*.sh
	rm -rf $1vendor/danielmarschall/uuid_mac_utils/*.css
	rm -rf $1vendor/paragonie/random_compat/other
}
remove_vendor_rubbish ./

# It is important that symlinks are not existing, otherwise the .tar.gz dir
# cannot be correctly extracted in Windows
rm -rf vendor/bin
rm -rf vendor/matthiasmullie/minify/bin

# Remove docker stuff since it might confuse services like synk
rm vendor/matthiasmullie/minify/Dockerfile
rm vendor/matthiasmullie/minify/docker-compose.yml

# Enable SVN again
if [ -d "_svn" ]; then
	mv _svn .svn
fi

composer license > vendor/licenses

# -------
# Update composer dependencies of plugins
# -------

composer update -d plugins/viathinksoft/publicPages/100_whois/whois/xml/
composer license -d plugins/viathinksoft/publicPages/100_whois/whois/xml/ > plugins/viathinksoft/publicPages/100_whois/whois/xml/vendor/licenses
remove_vendor_rubbish plugins/viathinksoft/publicPages/100_whois/whois/xml/

composer update -d plugins/viathinksoft/publicPages/100_whois/whois/json/
composer license -d plugins/viathinksoft/publicPages/100_whois/whois/json/ > plugins/viathinksoft/publicPages/100_whois/whois/json/vendor/licenses
remove_vendor_rubbish plugins/viathinksoft/publicPages/100_whois/whois/json/
