<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9">
  <xsl:output method="html" encoding="UTF-8"/>
  <xsl:template match="/">
    <html lang="fr"><head><title>Sitemap XML</title><meta name="robots" content="noindex,follow"/>
      <style>body{font:16px system-ui,sans-serif;max-width:1100px;margin:40px auto;padding:0 20px;color:#172033}table{width:100%;border-collapse:collapse}th,td{padding:12px;border-bottom:1px solid #ddd;text-align:left}a{color:#1769aa}</style>
    </head><body><h1>Sitemap XML</h1><p><xsl:value-of select="count(s:urlset/s:url)"/> URL(s)</p><table><thead><tr><th>Adresse</th><th>Dernière modification</th><th>Priorité</th></tr></thead><tbody>
      <xsl:for-each select="s:urlset/s:url"><tr><td><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td><td><xsl:value-of select="s:lastmod"/></td><td><xsl:value-of select="s:priority"/></td></tr></xsl:for-each>
    </tbody></table></body></html>
  </xsl:template>
</xsl:stylesheet>
