<?php

namespace App\Support\Seo;

use Illuminate\Support\Str;

final class SeoManager
{
    public static function make(array $options = []): SeoMeta
    {
        $config = config('umkm-seo', []);

        $siteName = self::cleanText((string) ($config['site_name'] ?? config('app.name', 'UMKM Monitoring')));
        $titleSuffix = self::cleanText((string) ($config['title_suffix'] ?? $siteName));
        $rawTitle = self::cleanText((string) ($options['title'] ?? $config['default_title'] ?? $siteName));

        $title = self::buildTitle($rawTitle, $titleSuffix);

        $description = self::limitCleanText(
            (string) ($options['description'] ?? $config['default_description'] ?? ''),
            165
        );

        $area = self::cleanArea((string) ($options['area'] ?? 'public'));

        $robots = self::resolveRobots(
            $options['robots'] ?? null,
            $area,
            $config
        );

        $canonical = self::resolveCanonical(
            $options['canonical'] ?? null,
            $area
        );

        $image = self::resolveImage(
            $options['image'] ?? $config['default_image'] ?? null
        );

        $locale = self::cleanText((string) ($options['locale'] ?? $config['default_locale'] ?? 'id_ID'));
        $type = self::cleanText((string) ($options['type'] ?? $config['default_type'] ?? 'website'));

        return new SeoMeta(
            title: $title,
            description: $description,
            robots: $robots,
            canonical: $canonical,
            siteName: $siteName,
            locale: $locale,
            type: $type,
            image: $image,
        );
    }

    private static function buildTitle(string $title, string $suffix): string
    {
        if ($title === '') {
            return $suffix !== '' ? $suffix : 'UMKM Monitoring';
        }

        if ($suffix === '' || Str::contains($title, $suffix)) {
            return $title;
        }

        return $title . ' | ' . $suffix;
    }

    private static function resolveRobots(mixed $robots, string $area, array $config): string
    {
        if (is_string($robots) && trim($robots) !== '' && $robots !== 'public') {
            return self::cleanRobots($robots);
        }

        if ($area !== 'public') {
            return self::cleanRobots((string) ($config['private_robots'] ?? 'noindex,nofollow,noarchive,nosnippet'));
        }

        $indexingEnabled = (bool) ($config['indexing_enabled'] ?? false);

        if ($indexingEnabled) {
            return self::cleanRobots((string) ($config['public_robots_when_indexing_enabled'] ?? 'index,follow'));
        }

        return self::cleanRobots((string) ($config['public_robots_when_indexing_disabled'] ?? 'noindex,nofollow,noarchive'));
    }

    private static function resolveCanonical(mixed $canonical, string $area): ?string
    {
        if ($area !== 'public') {
            return null;
        }

        if (is_string($canonical) && trim($canonical) !== '') {
            return url(trim($canonical));
        }

        if (app()->runningInConsole()) {
            return null;
        }

        return request()->url();
    }

    private static function resolveImage(mixed $image): ?string
    {
        if (! is_string($image) || trim($image) === '') {
            return null;
        }

        return url(trim($image));
    }

    private static function cleanArea(string $area): string
    {
        $area = strtolower(trim($area));

        return in_array($area, ['public', 'private', 'auth', 'dashboard', 'internal'], true)
            ? $area
            : 'private';
    }

    private static function cleanText(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';

        return trim($value);
    }

    private static function limitCleanText(string $value, int $limit): string
    {
        $value = self::cleanText($value);

        if ($value === '') {
            return '';
        }

        return Str::limit($value, $limit, '');
    }

    private static function cleanRobots(string $robots): string
    {
        $robots = strtolower(strip_tags($robots));
        $robots = preg_replace('/[^a-z0-9,\-_: ]/i', '', $robots) ?: '';
        $robots = preg_replace('/\s+/u', '', $robots) ?: '';

        return trim($robots) !== '' ? trim($robots) : 'noindex,nofollow,noarchive';
    }
}
