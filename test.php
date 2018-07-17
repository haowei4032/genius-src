<?php

require __DIR__ . '/vendor/autoload.php';

//Genius\Application::runArguments(null);

$request = new Genius\Adapter\Request();
$property = $request->getProperty();
var_dump( $property->getString('SERVER_ADDR') );
var_dump( $property->getInt('SERVER_PORT') );

$query = $request->getQuery();
$query->getString();

/**
 *
 * Genius::getContext()->request->query->getString('xxxxx')
 *
 *
 *
 */

