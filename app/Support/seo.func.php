<?php

function mds_seo_metadata($scriptName = null, $language = null)
{
    $scriptName = basename((string)($scriptName ?: ($_SERVER['SCRIPT_NAME'] ?? 'index.php')));
    $language = mds_normalize_language($language ?: mds_current_language());
    $isGerman = $language === 'de';
    $siteName = trim((string)(configuration::$applicationName ?: 'Maritime Data Server'));

    $publicPages = array(
        'index.php' => array(
            'path' => '',
            'title' => $isGerman
                ? 'Maritime Data Server | IoT- und LoRaWAN-Telemetrie'
                : 'Maritime Data Server | IoT and LoRaWAN Telemetry',
            'description' => $isGerman
                ? 'Maritime Data Server erfasst, speichert und visualisiert Telemetriedaten von ESP32-, IoT- und LoRaWAN-Geräten in Dashboards, Charts und Karten.'
                : 'Maritime Data Server collects, stores and visualizes telemetry from ESP32, IoT and LoRaWAN devices in dashboards, charts and maps.',
        ),
        'privacy.php' => array(
            'path' => 'privacy.php',
            'title' => ($isGerman ? 'Datenschutz' : 'Privacy') . ' | ' . $siteName,
            'description' => $isGerman
                ? 'Informationen zur Verarbeitung personenbezogener Daten im Maritime Data Server.'
                : 'Information about the processing of personal data in Maritime Data Server.',
        ),
        'imprint.php' => array(
            'path' => 'imprint.php',
            'title' => ($isGerman ? 'Impressum' : 'Imprint') . ' | ' . $siteName,
            'description' => $isGerman
                ? 'Impressum und Kontaktangaben des Betreibers des Maritime Data Servers.'
                : 'Legal notice and contact details for the operator of Maritime Data Server.',
        ),
    );

    if (isset($publicPages[$scriptName])) {
        $page = $publicPages[$scriptName];
        return array(
            'title' => $page['title'],
            'description' => $page['description'],
            'canonical' => mds_absolute_url($page['path']),
            'robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
            'indexable' => true,
            'websiteSchema' => $scriptName === 'index.php',
            'siteName' => $siteName,
        );
    }

    return array(
        'title' => $siteName,
        'description' => $isGerman
            ? 'Geschützter Bereich des Maritime Data Servers.'
            : 'Protected Maritime Data Server area.',
        'canonical' => null,
        'robots' => 'noindex,nofollow,noarchive',
        'indexable' => false,
        'websiteSchema' => false,
        'siteName' => $siteName,
    );
}

function mds_seo_website_structured_data(array $metadata)
{
    if (empty($metadata['websiteSchema']) || empty($metadata['canonical'])) {
        return null;
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $metadata['siteName'],
        'url' => mds_absolute_url(''),
        'description' => $metadata['description'],
        'inLanguage' => mds_current_language(),
    );
}
