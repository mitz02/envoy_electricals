<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $seo = \App\Models\Setting::pluck('value', 'key');
            $businessName = $seo['business.name'] ?? 'Envoy Electricals';
            $businessEmail = $seo['business.email'] ?? '';
            $businessPhone = $seo['business.phone'] ?? '';
            $businessAddress = $seo['business.address'] ?? '';
            $siteTitle = $businessName.' — Premium Solar Panels, Inverters & Battery Storage in Nigeria';
            $siteDescription = $businessName.' supplies premium solar panels, inverters, lithium batteries and electrical equipment across Nigeria, with professional installation, energy audits and solar training. Call '.$businessPhone.' for a free solar assessment.';
            $siteUrl = rtrim(config('app.url'), '/');
            $currentUrl = url()->current();
            $ogImage = $siteUrl.'/images/landing/hero_solar_panels.jpg';
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Search engine -->
        <meta name="description" content="{{ $siteDescription }}">
        <meta name="author" content="{{ $businessName }}">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="theme-color" content="#0D1527">
        <link rel="canonical" href="{{ $currentUrl }}">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $businessName }}">
        <meta property="og:title" content="{{ $siteTitle }}">
        <meta property="og:description" content="{{ $siteDescription }}">
        <meta property="og:url" content="{{ $currentUrl }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:locale" content="en_NG">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $siteTitle }}">
        <meta name="twitter:description" content="{{ $siteDescription }}">
        <meta name="twitter:image" content="{{ $ogImage }}">

        <!-- Structured data (generative-engine optimization) -->
        @php
            $structuredData = [
                "@context" => "https://schema.org",
                "@graph" => [
                    [
                        "@type" => "WebSite",
                        "@id" => $siteUrl . "/#website",
                        "url" => $siteUrl . "/",
                        "name" => $businessName,
                        "inLanguage" => "en-NG"
                    ],
                    [
                        "@type" => "LocalBusiness",
                        "@id" => $siteUrl . "/#business",
                        "name" => $businessName,
                        "image" => $ogImage,
                        "url" => $siteUrl . "/",
                        "telephone" => $businessPhone,
                        "email" => $businessEmail,
                        "priceRange" => "₦₦",
                        "geo" => [ "@type" => "GeoCoordinates", "latitude" => 7.2571, "longitude" => 5.2058 ],
                        "openingHoursSpecification" => [
                            [ "@type" => "OpeningHoursSpecification", "dayOfWeek" => ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"], "opens" => "08:00", "closes" => "18:00" ]
                        ],
                        "areaServed" => "Nigeria",
                        "department" => [
                            [ "@type" => "Store", "name" => $businessName . " Solar & Electrical Store" ],
                            [ "@type" => "Service", "name" => "Solar Installation & Energy Audit" ],
                            [ "@type" => "EducationalOrganization", "name" => "Envoy Solar Academy" ]
                        ]
                    ]
                ]
            ];

            if ($businessAddress) {
                $structuredData["@graph"][1]["address"] = [ "@type" => "PostalAddress", "streetAddress" => $businessAddress ];
            }
        @endphp
        <script type="application/ld+json">
            {{{ json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) }}}
        </script>

        <!-- Favicon -->
        <link rel="icon" href="/images/favicon_io/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon_io/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon_io/favicon-16x16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon_io/apple-touch-icon.png">
        <link rel="manifest" href="/images/favicon_io/site.webmanifest">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
