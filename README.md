[![Latest Stable Version](https://poser.okvpn.org/julio101290/boilerplatequotes/v/stable)](https://packagist.org/packages/julio101290/boilerplatequotes) [![Total Downloads](https://poser.okvpn.org/julio101290/boilerplatequotes/downloads)](https://packagist.org/packages/julio101290/boilerplatequotes) [![Latest Unstable Version](https://poser.okvpn.org/julio101290/boilerplatequotes/v/unstable)](https://packagist.org/packages/julio101290/boilerplatequotes) [![License](https://poser.okvpn.org/julio101290/boilerplatequotes/license)](https://packagist.org/packages/julio101290/boilerplatequotes)

## CodeIgniter 4 Boilerplate CFDI
library for the administration of CFDI electronic invoices, printing, uploading, downloading, etc.

## Requirements
* PhpCfdi\SatCatalogos
* julio101290/boilerplatelog
* hermawan/codeigniter4-datatables
* phpcfdi/cfditopdf
* phpcfdi/cfdi-to-json"
* phpcfdi/xml-cancelacion

## Installation

### Run commands
	
 	composer require phpcfdi/sat-catalogos

   	composer require hermawan/codeigniter4-datatables

    composer require julio101290/boilerplatelog

	composer require julio101290/boilerplatecompanies

  	composer require julio101290/boilerplatestorages

	composer require julio101290/boilerplatetypesmovement

	composer require julio101290/boilerplatequotes


### Run command for migration and seeder

	php spark boilerplatecompanies:installcompaniescrud

 	php spark boilerplatelog:installlog

  	php spark boilerplatestorages:installstorages

	php spark boilerplatetypesmovement:installtypesmovement

	php spark boilerplatequotes:installquotes
	

# Make the Menu, Example

![image](https://github.com/user-attachments/assets/e4a13d86-c714-4237-b29e-adb5616b7283)



# Ready

![image](https://github.com/user-attachments/assets/34038a0e-533b-466e-bd4d-21ee439dad05)



Usage
-----
You can find how it works with the read code routes, controller and views etc. Finnally... Happy Coding!

Changelog
--------
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

Contributing
------------
Contributions are very welcome.

License
-------

This package is free software distributed under the terms of the [MIT license](LICENSE.md).
