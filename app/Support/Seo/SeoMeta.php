<?php

namespace App\Support\Seo;

final class SeoMeta
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $robots,
        public readonly ?string $canonical,
        public readonly string $siteName,
        public readonly string $locale,
        public readonly string $type,
        public readonly ?string $image,
        public readonly ?int $imageWidth,
        public readonly ?int $imageHeight,
        public readonly ?string $imageAlt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'robots' => $this->robots,
            'canonical' => $this->canonical,
            'site_name' => $this->siteName,
            'locale' => $this->locale,
            'type' => $this->type,
            'image' => $this->image,
            'image_width' => $this->imageWidth,
            'image_height' => $this->imageHeight,
            'image_alt' => $this->imageAlt,
        ];
    }
}
