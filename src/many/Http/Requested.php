<?php 
namespace Many\Http;
/**
 * This file is part of  Many
 * 
 * $ php -S localhost:8000
 * http://localhost:8000/de/test?f=b&v=y
 */

class Requested
{ 

    /**
     * @var String "$localeRemove" array key to remove from path
     * @var Array "$set" settings array
     */
    public $localeRemove, $set = [];

    /**
     * Get full request array
     *
     * @return Array request path
     */
    public function get($set=[]): Array
    {
        $this->set = $set;
        $fAll = $this->fetchAll();
        $r = $fAll;
        $r['locale'] = $this->localize();
        /** fix path first **/
        if ($set['fix_path'] ?? false) {
            $this->fixPath();
        }
        /** dynamic **/
        $r['request_line'] = "{$fAll['method']} {$fAll['url']} {$fAll['server_protocol']}";
        if ($this->localeRemove ?? false) {
            $newPath = \str_replace('/'.$this->localeRemove.'/', '/', $fAll['real_path']);
            $r['path'] = $newPath;
            $r['path_parts'] = \explode('/', \ltrim($newPath, '/'));
            $r['first_path'] = $r['path_parts'][0] ?? false ? '/' . $r['path_parts'][0] : false;
            $r['reverse_path'] = \array_reverse($r['path_parts']);
            $r['reverse_link'] = \implode('/', $r['reverse_path']);
        }
        /** keep parameter **/
        if ($_GET AND ($set['keep_parameter'] ?? false)) {
            $r['keep_parameter'] = $this->keepParameter($set['keep_parameter']);
        }
        /** extended locales functionality **/
        if ($set['accept_locales'] ?? false) {
            if ($r['header']['Accept-Language'] ?? false) {
                $acceptLang = \explode(',', $r['header']['Accept-Language']);
                $acceptLanguagesKeys = ['iso'=>[], 'index'=>[], 'iso_to_index'=>[]];
                foreach($acceptLang as $i => $v) {
                    $expl = \explode(';', $v);
                    $q = $expl[1] ?? false ? $expl[1] : 1 ;
                    $acceptLanguagesKeys['iso'][$expl[0]] = \str_replace('q=', false, $q);
                    $acceptLanguagesKeys['index'][$i] = $expl[0];
                    $acceptLanguagesKeys['iso_to_index'][$expl[0]] = $i;
                }
                $keys = \array_keys($acceptLanguagesKeys['iso']);
                $r['preferred_locale'] = true;
                if ($keys[0] ?? false AND $r['locale']['iso'] != $keys[0])
                    $r['preferred_locale'] = false;
                $r['accept_locales'] = [
                    'iso' => $acceptLanguagesKeys['iso'],
                    'index' => $acceptLanguagesKeys['index'],
                    'iso_to_index' => $acceptLanguagesKeys['iso_to_index'],
                ];
            }
        }
        return $r;
    }

    /**
     * Fix requesting path, if host is www. or trailing / repeating slashes are in path
     *
     * @return Mixed 
     */
    public function fixPath() 
    {
        $conf = $this->set['fetched'];
        $useHost = $conf['host'];
        $isWww = \strpos($useHost, 'www.') !== false;
        $useHost = $conf['protocol'] . '://' . $useHost;
        if ($isWww) 
            $useHost = $conf['protocol'] . '://' . \str_replace('www.', false, $conf['host']);
        $real_request_uri = $_SERVER['REQUEST_URI'] ?? false ;
        $real_query_string = ($_SERVER['QUERY_STRING'] ?? false) ? '?' . $_SERVER['QUERY_STRING'] : false ;
        $real_request_path = $real_request_uri;
        $buildPath = \implode('/', $conf['path_parts']);
        $idealPath = '/' . \ltrim($buildPath, '/');
        $idealPath = $idealPath ? $idealPath : '/' ;
        if ($real_query_string) 
            $real_request_path = \str_replace($real_query_string, false, $real_request_uri);
        $redirectTo = $useHost . $idealPath . $real_query_string;
        if ($isWww OR \urldecode($real_request_path) != $idealPath) {
            @\header("Location: " . \urldecode($redirectTo), true, 301);
            exit(\sprintf('<h1>Redirection failed</h1><p><a href="%1$s">%1$s</a></p>', $redirectTo));
        }
    }

    /**
     * Fetch requesting URL
     *
     * @return Array
     */
    public function fetchAll(): Array
    {
        $g = [
            'protocol' => $_SERVER['REQUEST_SCHEME'] ?? ('on' == ($_SERVER['HTTPS'] ?? false) ? 'https' : 'http'),
            'host' => \rtrim(($_SERVER['HTTP_HOST'] ?? false), '/'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? false,
        ];
        $shemeDivider = '://';
        $url = $g['protocol'] . $shemeDivider . $g['host'] . $g['request_uri'];
        $p = \parse_url($url);
        $path = \ltrim($p['path'], '/');
        $path_parts = \explode('/', $path);
        $path_parts = \array_filter($path_parts);
        $transform = $path_parts;
        $reverse_path = \array_reverse($transform);
        $last_path = \array_pop($transform);
        $query = $p['query'] ?? false;
        $query_parts = \explode('&', $query);
        $protocol = $p['scheme'] ?? $g['protocol'];
        $host = $g['host'] ?? $p['host'];
        $http_host = $protocol . $shemeDivider . $host;
        $this->set['first_path'] = $path_parts[0] ?? false;
        $this->set['fetched'] = [
            'host' => $host,
            'http_host' => $http_host,
            'protocol' => $protocol,
            'real_path' => $p['path'],
            'path_parts' => $path_parts,
        ];
        return [
            'locale' => false,
            'keep_parameter' => [],
            'user_ip' => $this->userIp(),
            'https' => $_SERVER['HTTPS'] ?? 'off',
            'is_https' => $protocol == 'https',
            'server_path' => $p['path'] ?? $g['request_uri'],
            'protocol' => $protocol,
            'host' => $host,
            'http_host' => $http_host,
            'url' => $g['request_uri'],
            'http_url' => $http_host . $g['request_uri'],
            'real_path' => $p['path'],
            'real_path_parts' => $path_parts,
            'path' => $p['path'],
            'first_path' => $path_parts[0] ?? false ? '/' . $path_parts[0] : false,
            'last_path' => $last_path,
            'path_parts' => $path_parts,
            'reverse_link' => \implode('/', $reverse_path),
            'reverse_path' => $reverse_path,
            'request_query' => $query,
            'query_parts' => $_GET,
            'hostname' => $p['host'] ?? false,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'server_protocol'  => $_SERVER['SERVER_PROTOCOL'] ?? false, // HTTP/2.0
            'http_path' => $http_host . $p['path'],
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? false,
            'server_addr' => $_SERVER['SERVER_ADDR'] ?? false,
            'server_port' => $g['port'] ?? $_SERVER['SERVER_PORT'] ?? false,
            'script_name' => $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? false,
            'request_time' => $_SERVER['REQUEST_TIME'] ?? \time(),
            'request_time_float' => $_SERVER['REQUEST_TIME_FLOAT'] ?? \microtime(true),
            'request_line' => false,
            'header' => $this->getAllHeaders(),
        ];
    }

    /**
     * Builds locale section
     *
     * @return Array all relevant data for locale
     */
    public function localize(): Array
    {
        $locales = $default_locale = $id_to_iso = [];
        $first_path = $is_default = $default_iso = 
        $default_id = $locale_id = $iso = $prefix = $title = false;
        if ($locales = ($this->set['locales'] ?? false)) {
            $indexed = \array_values($locales);
            $default_locale = $indexed[0] ?? false;
            $is_default = $default_locale['is_default'] ?? true;
            $default_iso = $default_locale['iso'] ?? false;
            $default_id = $default_locale['locale_id'] ?? $default_locale['id'];
            $locale_id = $default_id;
            $iso = $default_locale['iso'];
            $title = $default_locale['title'] ?? false;
            if ($first_path = ($this->set['first_path'] ?? false))
                if ($first_path != $default_locale['iso'] AND isset($locales[$first_path])) {
                    $is_default = false;
                    $use_locale_id = $locales[$first_path]['locale_id'] ?? $locales[$first_path]['id'] ?? false;
                    $locale_id = $use_locale_id;
                    $iso = $locales[$first_path]['iso'] ?? $iso;
                    $prefix = "/{$iso}";
                    $title = $locales[$first_path]['title'] ?? false;
                    $this->localeRemove = $iso;
                }
            foreach($locales as $iso => $data) {
                $useId = $data['locale_id'] ?? $data['id'] ?? false;
                if ($useId) 
                    $id_to_iso[$useId] = $data['iso'];
            }
        }
        $finalIso = $id_to_iso[$locale_id] ?? $iso ?? false;
        return [
            'is_default' => $is_default,
            'locale_id' => $locale_id,
            'iso' => $finalIso,
            'title' => $title,
            'prefix' => $prefix,
            'parameter' => "locale={$finalIso}",
            'default' => $default_iso,
            'default_id' => $default_id,
            'id_to_iso' => $id_to_iso,
        ];
    }

    /**
     * Keep requested GET Parameter specified in "$set['query']['keep_parameter']"
     * The data is expected as already validated, this function just keeps them
     * here for quick usage
     * 
     * @param Array $params
     * @return Array with parameters to keep
     */
    public function keepParameter($params, $r=[]): Array
    {
        $use_keys = $params;
        if ($km = ($params['get'] ?? false))
            if ($_GET)
                foreach($_GET as $key => $val)
                    if (array_key_exists($key, $km))
                        $r['get'][$key] = empty($km[$key]) ? 0 : $km[$key] ;
        if ($r) {
            $sep = $params['separator'] ?? '&';
            $hbq = http_build_query($r['get'], true, $sep);
            // $r['query'] = $hbq;
            $r['use'] = [
                'query'  => $hbq,
                'append' => "{$sep}{$hbq}",
                'finale' => "?{$hbq}",
            ];
            $r['arg_separator'] = [
                'setted'         => $sep,
                'ini_get.input'  => ini_get('arg_separator.input'),
                'ini_get.output' => ini_get('arg_separator.output'),
            ];
        }
        return $r;
    }

    /**
     * User IP
     *
     * @return String IP or Error
     */
    private function userIp(): String
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
        return 'error';  
    }

    /**
     * Get header
     *
     * @return Array
     */
    public function getAllHeaders(): Array 
    {
        return \array_merge(\getallheaders(),(\apache_request_headers() !== false)?\apache_request_headers():[]);
    }

}
