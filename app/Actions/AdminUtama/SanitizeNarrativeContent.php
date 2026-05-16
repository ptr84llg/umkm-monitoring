<?php
namespace App\Actions\AdminUtama;
class SanitizeNarrativeContent { public function execute(string $html): string { return strip_tags($html, '<p><br><strong><em><ul><ol><li><a><h1><h2><h3><blockquote>'); }}
