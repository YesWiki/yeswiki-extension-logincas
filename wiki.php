<?php

if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

$wikiClasses [] = 'casServer';
$wikiClassesContent [] = '';

///////////////////////////////////////
// Basic Config of the phpCAS client //
///////////////////////////////////////

// Full Hostname of your CAS Server
$wakkaConfig['cas_host'] = isset($wakkaConfig['cas_host']) ? $wakkaConfig['cas_host'] : '';

// Context of the CAS Server
$wakkaConfig['cas_context'] = isset($wakkaConfig['cas_context']) ? $wakkaConfig['cas_context'] : '/cas';

// Port of your CAS server. Normally for a https server it's 443
$wakkaConfig['cas_port'] = isset($wakkaConfig['cas_port']) ? $wakkaConfig['cas_port'] : 443;

// Path to the ca chain that issued the cas server certificate
$wakkaConfig['cas_server_ca_cert_path'] = isset($wakkaConfig['cas_server_ca_cert_path']) ? $wakkaConfig['cas_server_ca_cert_path'] : ''; // '/path/to/cachain.pem'

// Port of your CAS server. Normally for a https server it's 443
$wakkaConfig['cas_bazar_mapping'] = isset($wakkaConfig['cas_bazar_mapping']) ? $wakkaConfig['cas_bazar_mapping'] : array();
