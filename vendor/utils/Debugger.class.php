<?php

/**
 * @link http://genius.haowei.me
 * @copyright Copyright (c) 2015 Genius Software LLC
 * @license http://genius.haowei.me/license
 */

namespace Genius\Utils;

use ErrorException;
use Genius;
use Genius\Application;

final class Debugger
{
    private function __construct()
    {
    }

    public static function Exception($e)
    {
        if (ob_get_length()) ob_clean();
        $severity = !method_exists($e, 'getSeverity') ? E_ERROR : $e->getSeverity();
        $datetime = date('Y-m-d H:i:s');

        switch (error_reporting() & $severity) {
            case E_NOTICE:
            case E_USER_NOTICE:
                $level = 'notice';
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $level = 'warning';
                break;
            case E_ERROR:
            case E_USER_ERROR:
                $level = 'error';
                break;
            case E_DEPRECATED;
            case E_USER_DEPRECATED:
                $level = 'deprecated';
                break;
            default:
                $level = 'info';
        }

        $list = [];

        foreach ($e->getTrace() as $value) {
            if (isset($value['file'])) {
                array_push($list, '<li><em>' . $value['file'] . '</em> line ' . $value['line'] . '</li>');
            }
        }

        echo sprintf('<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>500 Internet Server Error</title>
<style type="text/css">
body{margin:0;padding:10px;font-family:arial,Helvetica,sans-serif;font-size:13px}
.hide{display:none;}
.exception{min-width:700px;max-width:750px;box-sizing:border-box;background:#eee;display:inline-block;}
.inside{padding:0 20px 10px 20px;}
.exception h1{margin:0;padding:35px 20px 20px 20px;line-height:20px;position:relative;display:block;font-weight:normal;color:#f00;font-size:20px;border-bottom:solid 1px #ccc;}
.exception h1 span{position:absolute;right:20px;background:#ccc;padding:4px 10px;color:#666;font-size:.5rem;line-height:1;}
.fixed {font-size:12px;display:block;background:#e0e0e0;margin:0;padding:0 20px;list-style:none;border-top:solid 1px #ddd;}
.fixed li{padding:10px 0;line-height:20px;height:20px;color:#444;display:inline-block;margin-right:10px;}
.fixed li span{display:inline-block;font-size:12px;line-height:16px;height:16px;margin:2px 0 2px 10px;border-radius:2px;padding:0 10px;background:#999;color:#fff;}
.fixed li:last-child{margin:0;}
.fixed li span.passed{background:#090;}
.fixed li span.wraning{background:#e90;}
.fixed li span.highlight{background:#e00;}
.exception .message{line-height:1.8;color:#333;padding:30px 0;}
.exception .message em{color:#f00;text-decoration:underline;}
.exception .message span.level{text-transform: capitalize;border-radius:2px;color:#fff;line-height:18px;padding:0 6px;display:inline-block;height:18px;font-size:12px;margin-right:10px;}
.exception span.info{background:#999;}
.exception span.notice{background:#090;}
.exception span.warning{background:#e90;}
.exception span.error{background:#e00;}
.exception span.deprecated{background:#c60;text-decoration:line-through;}
.exception .footer{color:#444;font-style:italic;}
.exception .footer .breakpoint{}
.exception .footer ul.list{padding-left:30px;}
.exception .footer ul.list li{line-height:1.65;}
.exception .footer .list li em{text-decoration:underline;}
</style>
</head>

<body>
<div class="exception">
<h1><strong>Genius Exception</strong><span class="datatime">%s</span></h1>
<div class="inside">
<div class="message"><span class="level %s">%s</span>%s</div>
<div class="footer">
<div class="breakpoint">
<span>Breakpoint list</span>
<ul class="list">
%s
</ul>
</div>
</div>
</div>
<ul class="fixed">
<li><label>Genius</label><span>%s</span></li>
<li><label>PHP</label><span>%s</span></li>
<li><label>Status</label><span class="highlight">500</span></li>
<li><label>Route</label><span>%s</span></li>
<li><label>Memory</label><span>%s</span></li>
<li><label>Time</label><span>%s</span></li>
</ul>
</div>
</body>
</html>', $datetime,
            $level,
            $level,
            $e->getMessage(),
            implode($list),
            GENIUS_VERSION,
            PHP_VERSION,
            'Index/Index',
            Application::elapsed('memory'),
            Application::elapsed('time'));

        ob_end_flush();
        error_log($e->getMessage(), 3, Genius::getAlias('~/runtime/log/error.log'));
        exit;
    }

    public static function Error($errno, $errstr, $errfile, $errline)
    {
        return self::exception(new ErrorException($errstr, 0, $errno, $errfile, $errline));
    }

    public static function Shutdown()
    {
        $error = error_get_last();
        if (in_array($error['type'], [E_USER_NOTICE, E_USER_WARNING, E_USER_ERROR, E_NOTICE, E_WARNING, E_ERROR])) {
            error_log($error['message'], 3, Genius::getAlias('~/runtime/log/error.log'));
            return self::error($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}