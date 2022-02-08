# gp-translation-helpers


## Code standards

### PHP

Before checking the PHP code standards, please, install the dependencies using:
```
composer install
```

To check the PHP code standards, use:
```
composer lint
```

To automatically try to resolve the PHP code standards errors, use:
```
composer format
```

If you want to see all the PHP errors and warnings, use:
```
php ./vendor/bin/phpcs
```

To see only the PHP errors and not the PHP warnings, use:
```
php -n ./vendor/bin/phpcs
```

### JavaScript

Before checking the JavaScript code standards, please, install the dependencies using:
```
npm install
```

To check the JavaScript code standards, use:
```
npm run lint:js
```

To automatically try to resolve the JavaScript code standards errors, use:
```
npm run lint:js-fix
```

## Changelog

### [0.0.2] Not released

### [0.0.1] 2017-03-29

Forked version from https://github.com/Automattic/gp-translation-helpers