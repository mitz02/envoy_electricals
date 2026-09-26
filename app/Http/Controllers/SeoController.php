<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SolarPackage;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(Request $request): Response
    {
        $host = rtrim((string) (env('SITE_URL') ?: config('app.url')), '/');

        $content = "User-agent: *\n"
            ."Allow: /\n"
            ."Disallow: /cart\n"
            ."Disallow: /checkout\n"
            ."Disallow: /portal\n"
            ."Disallow: /orders\n"
            ."Disallow: /pay/bank-transfer\n"
            ."Disallow: /packages/*/pay/bank-transfer\n"
            ."Disallow: /training/*/pay/bank-transfer\n"
            ."Disallow: /newsletter/unsubscribe\n"
            ."Disallow: /login\n"
            ."Disallow: /register\n"
            ."Disallow: /dashboard\n"
            ."Disallow: /admin\n"
            ."Disallow: /profile\n"
            ."Disallow: /up\n"
            ."Disallow: /api\n"
            ."\n"
            ."Sitemap: {$host}/sitemap.xml\n";

        return response($content)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(Request $request): Response
    {
        $host = rtrim((string) (env('SITE_URL') ?: config('app.url')), '/');

        $urls = [
            [$host.'/', 'daily', '1.0', null],
            [$host.'/shop', 'daily', '0.9', null],
            [$host.'/packages', 'weekly', '0.9', null],
            [$host.'/training', 'weekly', '0.9', null],
            [$host.'/calculator', 'monthly', '0.7', null],
            [$host.'/projects', 'weekly', '0.6', null],
            [$host.'/about', 'monthly', '0.5', null],
            [$host.'/contact', 'monthly', '0.5', null],
        ];

        $lastmod = static fn ($model): ?string => $model?->updated_at?->toAtomString();

        foreach (Product::where('is_visible_online', true)->orderBy('id')->get() as $product) {
            $urls[] = [route('products.show', $product), 'weekly', '0.8', $lastmod($product)];
        }

        foreach (SolarPackage::where('is_visible_online', true)->orderBy('id')->get() as $package) {
            $urls[] = [route('packages.show', $package), 'weekly', '0.8', $lastmod($package)];
        }

        foreach (Training::active()->orderBy('id')->get() as $training) {
            $urls[] = [route('training.show', $training), 'weekly', '0.8', $lastmod($training)];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            [$loc, $freq, $priority, $lastmodAt] = $url;
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8')."</loc>\n";
            if ($lastmodAt) {
                $xml .= '    <lastmod>'.htmlspecialchars($lastmodAt, ENT_XML1 | ENT_QUOTES, 'UTF-8')."</lastmod>\n";
            }
            $xml .= "    <changefreq>{$freq}</changefreq>\n";
            $xml .= "    <priority>{$priority}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>\n";

        return response($xml)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
