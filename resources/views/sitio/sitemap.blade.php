<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($paginas as $p)
    <url>
        <loc>{{ $p->url() }}</loc>
        <lastmod>{{ $p->updated_at?->toAtomString() ?? now()->toAtomString() }}</lastmod>
        <changefreq>{{ $p->frecuenciaSitemap() }}</changefreq>
        <priority>{{ $p->prioridadSitemap() }}</priority>
    </url>
@endforeach
</urlset>
