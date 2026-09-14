@php($slot=$activeAdSlots->get($key))
@if($slot && $currentSite?->gam_enabled && $slot->ad_unit_path && $slot->type==='display')
@php($id='gam-'.$slot->key.'-'.$slot->id)
<div class="ad" id="{{ $id }}" data-gam-slot data-ad-unit="{{ $slot->ad_unit_path }}" data-sizes='@json($slot->sizes ?? [])'>ADVERTISEMENT</div>
@else<div class="ad">ADVERTISEMENT · {{ $key }} (off)</div>@endif
