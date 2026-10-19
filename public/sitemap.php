<?php
require_once dirname(__DIR__) . '/bootstrap/app.php';

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600, s-maxage=86400');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach (mds_seo_public_routes() as $page) { foreach (mds_supported_languages() as $language) { ?>
  <url>
    <loc><?php echo htmlspecialchars(mds_absolute_url(mds_seo_route($page, $language)), ENT_XML1, 'UTF-8'); ?></loc>
    <?php foreach (mds_supported_languages() as $alternateLanguage) { ?>
    <xhtml:link rel="alternate" hreflang="<?php echo $alternateLanguage; ?>" href="<?php echo htmlspecialchars(mds_absolute_url(mds_seo_route($page, $alternateLanguage)), ENT_XML1, 'UTF-8'); ?>" />
    <?php } ?>
    <xhtml:link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars(mds_absolute_url(mds_seo_route($page, 'en')), ENT_XML1, 'UTF-8'); ?>" />
  </url>
<?php } } ?>
</urlset>
