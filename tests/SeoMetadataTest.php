<?php

class configuration
{
    public static $applicationName = 'Maritime Data Server';
}

function mds_normalize_language($language)
{
    return strtolower(substr((string)$language, 0, 2)) === 'de' ? 'de' : 'en';
}

function mds_current_language()
{
    return 'en';
}

function mds_absolute_url($path = '')
{
    $path = trim((string)$path, '/');
    return 'https://mds-git.derguntmar.de/' . $path;
}

function assertSeoValue($expected, $actual, $message)
{
    if ($expected === $actual) {
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
    fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
    exit(1);
}

require_once dirname(__DIR__) . '/app/Support/seo.func.php';

$home = mds_seo_metadata('index.php', 'en');
assertSeoValue(true, $home['indexable'], 'The public homepage must be indexable.');
assertSeoValue('https://mds-git.derguntmar.de/en', $home['canonical'], 'The homepage canonical URL must point to the stable English URL.');
assertSeoValue(true, $home['websiteSchema'], 'WebSite structured data must be enabled on the homepage.');
assertSeoValue('https://mds-git.derguntmar.de/de', $home['alternates']['de'], 'The homepage must advertise its German alternate.');

$privacy = mds_seo_metadata('privacy.php', 'de');
assertSeoValue('Datenschutz | Maritime Data Server', $privacy['title'], 'The German privacy page needs an individual title.');
assertSeoValue(false, $privacy['websiteSchema'], 'WebSite structured data must not be duplicated on legal pages.');
assertSeoValue('https://mds-git.derguntmar.de/de/datenschutz', $privacy['canonical'], 'The German privacy canonical must use its localized route.');

$_GET['topic'] = 'ttn';
$ttn = mds_seo_metadata('docs.php', 'en');
assertSeoValue('ttn', $ttn['pageKey'], 'The TTN topic must be recognized as a public SEO page.');
assertSeoValue('https://mds-git.derguntmar.de/en/ttn-integration', $ttn['canonical'], 'The TTN topic needs its stable canonical URL.');

$settings = mds_seo_metadata('settings.php', 'en');
assertSeoValue(false, $settings['indexable'], 'Protected settings must not be indexable.');
assertSeoValue('noindex,nofollow,noarchive', $settings['robots'], 'Protected pages need a noindex directive.');
assertSeoValue(null, $settings['canonical'], 'Protected pages must not advertise a canonical public URL.');

assertSeoValue(array('home', 'features', 'ttn', 'api', 'ota', 'privacy', 'imprint'), mds_seo_public_routes(), 'All public routes must be included in the sitemap source.');

fwrite(STDOUT, "SEO metadata tests passed.\n");
