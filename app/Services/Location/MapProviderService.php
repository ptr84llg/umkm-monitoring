<?php
namespace App\Services\Location;
class MapProviderService { public function config(): array { $provider=config('umkm.map.provider','leaflet'); $googleEnabled=(bool)config('umkm.map.google_maps.enabled',false); $final=($provider==='google' && $googleEnabled && !empty(config('umkm.map.google_maps.api_key'))) ? 'google' : 'leaflet'; return ['provider'=>$final,'google_enabled'=>$googleEnabled,'google_api_key'=>config('umkm.map.google_maps.api_key'),'leaflet_tile_url'=>config('umkm.map.leaflet.tile_url'),'leaflet_attribution'=>config('umkm.map.leaflet.attribution')]; }}
