<?php

function mds_seo_route($page, $language = 'en')
{
    $language = mds_normalize_language($language);
    $routes = array(
        'home' => array('en' => 'en/', 'de' => 'de/'),
        'privacy' => array('en' => 'en/privacy', 'de' => 'de/datenschutz'),
        'imprint' => array('en' => 'en/imprint', 'de' => 'de/impressum'),
        'features' => array('en' => 'en/features', 'de' => 'de/funktionen'),
        'ttn' => array('en' => 'en/ttn-integration', 'de' => 'de/ttn-integration'),
        'api' => array('en' => 'en/api', 'de' => 'de/api'),
        'ota' => array('en' => 'en/ota-updates', 'de' => 'de/ota-updates'),
    );

    return $routes[$page][$language] ?? $routes['home'][$language];
}

function mds_seo_public_routes()
{
    return array('home', 'features', 'ttn', 'api', 'ota', 'privacy', 'imprint');
}

function mds_seo_page_key($scriptName)
{
    $scriptName = basename((string)$scriptName);
    if ($scriptName === 'index.php') {
        return 'home';
    }
    if ($scriptName === 'privacy.php') {
        return 'privacy';
    }
    if ($scriptName === 'imprint.php') {
        return 'imprint';
    }
    if ($scriptName === 'docs.php') {
        $topic = (string)($_GET['topic'] ?? 'features');
        return in_array($topic, array('features', 'ttn', 'api', 'ota'), true) ? $topic : 'features';
    }

    return null;
}

function mds_seo_metadata($scriptName = null, $language = null)
{
    $scriptName = basename((string)($scriptName ?: ($_SERVER['SCRIPT_NAME'] ?? 'index.php')));
    $language = mds_normalize_language($language ?: mds_current_language());
    $isGerman = $language === 'de';
    $siteName = trim((string)(configuration::$applicationName ?: 'Maritime Data Server'));
    $pageKey = mds_seo_page_key($scriptName);
    $copy = array(
        'home' => array(
            'title' => $isGerman ? 'Maritime Data Server | ESP32- und LoRaWAN-Telemetrie' : 'Maritime Data Server | ESP32 and LoRaWAN Telemetry',
            'description' => $isGerman ? 'Telemetriedaten von ESP32-, IoT- und LoRaWAN-Geräten sicher erfassen, visualisieren und überwachen: Dashboards, Charts, Karten und Alarme.' : 'Collect, visualize and monitor telemetry from ESP32, IoT and LoRaWAN devices with dashboards, charts, maps and alerts.',
        ),
        'features' => array(
            'title' => ($isGerman ? 'Funktionen für maritime IoT-Telemetrie' : 'Maritime IoT telemetry features') . ' | ' . $siteName,
            'description' => $isGerman ? 'Dashboards, Sensor-Charts, GPS-Karten, Ereignisverläufe und E-Mail-Alarme für vernetzte maritime Geräte.' : 'Dashboards, sensor charts, GPS maps, event timelines and e-mail alerts for connected maritime devices.',
        ),
        'ttn' => array(
            'title' => 'The Things Network Integration | ' . $siteName,
            'description' => $isGerman ? 'ESP32- und LoRaWAN-Telemetrie über einen abgesicherten TTN-Webhook anhand der MAC-Adresse einem Board zuordnen.' : 'Connect ESP32 and LoRaWAN telemetry through a secured TTN webhook and identify boards primarily by MAC address.',
        ),
        'api' => array(
            'title' => 'Telemetry Ingest API | ' . $siteName,
            'description' => $isGerman ? 'JSON-Schnittstellen für Boards, Sensordaten, Firmware-Versionen und Gerätezustände im Maritime Data Server.' : 'JSON interfaces for boards, sensor readings, firmware versions and device states in Maritime Data Server.',
        ),
        'ota' => array(
            'title' => 'ESP32 OTA Updates | ' . $siteName,
            'description' => $isGerman ? 'Signierte und protokollierte Firmware-Verteilung für ESP32-Geräte über den geschützten MDS-OTA-Endpunkt.' : 'Authenticated and logged firmware delivery for ESP32 devices through the protected MDS OTA endpoint.',
        ),
        'privacy' => array(
            'title' => ($isGerman ? 'Datenschutz' : 'Privacy') . ' | ' . $siteName,
            'description' => $isGerman ? 'Informationen zur Verarbeitung personenbezogener Daten im Maritime Data Server.' : 'Information about the processing of personal data in Maritime Data Server.',
        ),
        'imprint' => array(
            'title' => ($isGerman ? 'Impressum' : 'Imprint') . ' | ' . $siteName,
            'description' => $isGerman ? 'Impressum und Kontaktangaben des Betreibers des Maritime Data Servers.' : 'Legal notice and contact details for the operator of Maritime Data Server.',
        ),
    );

    if ($pageKey !== null && isset($copy[$pageKey])) {
        $canonical = mds_absolute_url(mds_seo_route($pageKey, $language));
        return array(
            'title' => $copy[$pageKey]['title'],
            'description' => $copy[$pageKey]['description'],
            'canonical' => $canonical,
            'alternates' => array(
                'en' => mds_absolute_url(mds_seo_route($pageKey, 'en')),
                'de' => mds_absolute_url(mds_seo_route($pageKey, 'de')),
                'x-default' => mds_absolute_url(mds_seo_route($pageKey, 'en')),
            ),
            'image' => mds_absolute_url('assets/img/mds-social-preview.png'),
            'robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
            'indexable' => true,
            'websiteSchema' => $pageKey === 'home',
            'siteName' => $siteName,
            'pageKey' => $pageKey,
        );
    }

    return array(
        'title' => $siteName,
        'description' => $isGerman ? 'Geschützter Bereich des Maritime Data Servers.' : 'Protected Maritime Data Server area.',
        'canonical' => null,
        'alternates' => array(),
        'image' => null,
        'robots' => 'noindex,nofollow,noarchive',
        'indexable' => false,
        'websiteSchema' => false,
        'siteName' => $siteName,
        'pageKey' => null,
    );
}

function mds_apply_public_cache_headers()
{
    if (headers_sent() || session_status() === PHP_SESSION_ACTIVE || mds_start_session_if_present()) {
        return;
    }

    header('Cache-Control: public, max-age=300, s-maxage=900, stale-while-revalidate=86400');
    header('Content-Language: ' . mds_current_language());
    header('Vary: Accept-Encoding');
}

function mds_seo_website_structured_data(array $metadata)
{
    if (empty($metadata['websiteSchema']) || empty($metadata['canonical'])) {
        return null;
    }

    return array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'WebSite',
                'name' => $metadata['siteName'],
                'url' => $metadata['canonical'],
                'description' => $metadata['description'],
                'inLanguage' => mds_current_language(),
            ),
            array(
                '@type' => 'SoftwareApplication',
                'name' => $metadata['siteName'],
                'applicationCategory' => 'UtilitiesApplication',
                'operatingSystem' => 'Web',
                'description' => $metadata['description'],
                'image' => $metadata['image'],
                'url' => $metadata['canonical'],
                'isAccessibleForFree' => true,
                'codeRepository' => 'https://github.com/bytecrusher/MaritimeDataServer',
            ),
        ),
    );
}
