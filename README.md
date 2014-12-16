#HOW TO INSTALL

* Make sure you have the PHP5 CLI installed (on Ubuntu install the "php5-cli"-package).
* Now run the install-file "sudo php5 install_knjphpframework.php". This will create symlinks in "/usr/share/php5", "usr/share/php4" and "/usr/share/php"..


#HOW TO USE

* After running the setup-script, you can include a part of the framework like this:
```php
<?php
	require_once("knjphpframwork/functions_knj_extensions.php");
?>
```

You can then use the functions of the part like this:

```php
<?php
   if (!knj_dl("gtk2")){
   	die("Could not load the PHP5-GTK2-module.\n");
   }
?>
```
License
-------

	Copyright 2014

	Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at

	   http://www.apache.org/licenses/LICENSE-2.0

	Unless required by applicable law or agreed to in writing, software
	distributed under the License is distributed on an "AS IS" BASIS,
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	See the License for the specific language governing permissions and
	limitations under the License.
