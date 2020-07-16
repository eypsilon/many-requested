<?php 

/**
 * $ php -S localhost:8000
 * http://localhost:8000
 * http://localhost:8000/?a&b
 */
use Many\Http\Requested;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @return String output helper **/
$print_pre = function($name, $var) {
    return printf('<h2 style="font-size:1.2rem;">%s</h2><pre>%s</pre><hr>', $name, print_r($var, true));
};


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
