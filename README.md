# Many Requested

`composer require eypsilon/many-requested`

```php
<?php
/**
 * $ php -S localhost:8000
 * http://localhost:8000/?a&b
 */
use Many\Http\Requested;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var Mixed Requested **/
$print_pre('Requested',
    /** 
     * @var Bool "$accept_locales" if true, Method returns an extended Locales array based on header("Accept-Language")
     * @var Bool "$fix_path" if true, clear the requesting path, repeatedly "/" and rediret from "www." to none "www."
     * @var Array "$locales" Optional locales settings, expected array
     * @var Array "$keep_parameter" Optional $_GET helper, expects already validated $_GET Key and value 
     */
    (new Requested)->get([
        'accept_locales' => true,
        'fix_path' => true,
        'locales'=> [
            'de' => [
                'id' => 1,
                'title' => 'Deutsch',
                'iso' => 'de',
                'is_default' => true,
            ],
        ],
        'keep_parameter' => [
            'get' => [
                'a' => 'test', 
                'b' => 'parameter'
            ],
            'separator' => '&',
        ]
    ])
);
```
