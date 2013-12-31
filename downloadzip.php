<?php

function get($url, $methods = array('fopen', 'curl', 'socket')) {
        if(gettype($methods) == 'string')
            $methods = array($methods);
        elseif(!is_array($methods))
            return false;
        foreach($methods as $method)
        {
            switch($method)
            {
            case 'fopen':
                //uses file_get_contents in place of fopen
                //allow_url_fopen must still be enabled
                if(ini_get('allow_url_fopen'))
                {
                    $contents = file_get_contents($url);
                    if($contents !== false)
                        return $contents;
                }
            break;
            case 'curl':
                if(function_exists('curl_init'))
                {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    return curl_exec($ch);
                }
            break;
            case 'socket':
                //make sure the url contains a protocol, otherwise $parts['host'] won't be set
                if(strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0)
                    $url = 'http://' . $url;
                $parts = parse_url($url);
                if($parts['scheme'] == 'https')
                {
                    $target = 'ssl://' . $parts['host'];
                    $port = isset($parts['port']) ? $parts['port'] : 443;
                }
                else
                {
                    $target = $parts['host'];
                    $port = isset($parts['port']) ? $parts['port'] : 80;
                }
                $page    = isset($parts['path'])        ? $parts['path']            : '';
                $page   .= isset($parts['query'])       ? '?' . $parts['query']     : '';
                $page   .= isset($parts['fragment'])    ? '#' . $parts['fragment']  : '';
                $page    = ($page == '')                ? '/'                       : $page;
                if($fp = fsockopen($target, $port, $errno, $errstr, 15))
                {
                    $headers  = "GET $page HTTP/1.1\r\n";
                    $headers .= "Host: {$parts['host']}\r\n";
                    $headers .= "Connection: Close\r\n\r\n";
                    if(fwrite($fp, $headers))
                    {
                        $resp = '';
                        //while not eof and an error does not occur when calling fgets
                        while(!feof($fp) && ($curr = fgets($fp, 128)) !== false)
                            $resp .= $curr;
                        if(isset($curr) && $curr !== false)
                            return substr(strstr($resp, "\r\n\r\n"), 3);
                    }
                    fclose($fp);
                }
            break;
            }
        }
        return false;
    }

// You would replace this with your own url
$download = get('http://remote.dev/0.4.zip');

mkdir('upload');

// The url that your going to store the .zip in
$destinationPath    = 'upload/azip.zip';

// Your going to use the $download variable up there that contains the .zip in it
file_put_contents($destinationPath, $download);

echo 'Successfully downloaded zip <a href="' . $destinationPath . '">Download Zip</a>';