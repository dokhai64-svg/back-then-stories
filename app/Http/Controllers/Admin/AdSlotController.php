<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{
    AdSlot,
    AdSlotAudit,
    Site
};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdSlotController extends Controller
{
    public function index(Request $request)
    {
        $siteId =
            $request->integer('site_id')
            ?: Site::value('id');

        $slots = AdSlot::with('site')
            ->when(
                $siteId,
                fn ($q) =>
                    $q->where(
                        'site_id',
                        $siteId
                    )
            )
            ->orderBy('key')
            ->get();

        return view(
            'admin.ads.index',
            [
                'slots' => $slots,
                'sites' =>
                    Site::orderBy('name')
                        ->get(),
                'siteId' => $siteId,
            ]
        );
    }

    public function edit(AdSlot $adSlot)
    {
        return view(
            'admin.ads.form',
            [
                'slot' => $adSlot,
                'sites' =>
                    Site::orderBy('name')
                        ->get(),
            ]
        );
    }

    public function update(
        Request $request,
        AdSlot $adSlot
    ) {
        $data = $request->validate([
            'label' => [
                'required',
                'string',
                'max:255',
            ],
            'ad_unit_path' => [
                'nullable',
                'string',
                'max:500',
            ],
            'sizes_text' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'type' => [
                'required',
                Rule::in([
                    'display',
                    'anchor',
                    'interstitial',
                    'rewarded',
                ]),
            ],
            'enabled' => [
                'nullable',
                'boolean',
            ],
        ]);

        $before = [
            'label' =>
                $adSlot->label,
            'ad_unit_path' =>
                $adSlot->ad_unit_path,
            'sizes' =>
                $adSlot->sizes,
            'type' =>
                $adSlot->type,
            'enabled' =>
                (bool) $adSlot->enabled,
        ];

        $data['enabled'] =
            $request->boolean('enabled');

        $data['sizes'] =
            collect(
                preg_split(
                    '/[\s,]+/',
                    $data['sizes_text']
                        ?? '',
                    -1,
                    PREG_SPLIT_NO_EMPTY
                )
            )
                ->map(
                    fn ($value) =>
                        trim($value)
                )
                ->values()
                ->all();

        unset($data['sizes_text']);

        $adSlot->update($data);

        $after = [
            'label' =>
                $adSlot->label,
            'ad_unit_path' =>
                $adSlot->ad_unit_path,
            'sizes' =>
                $adSlot->sizes,
            'type' =>
                $adSlot->type,
            'enabled' =>
                (bool) $adSlot->enabled,
        ];

        $changed = [];

        foreach ($after as $field => $value) {
            if (
                json_encode($before[$field])
                !== json_encode($value)
            ) {
                $changed[$field] = [
                    'from' => $before[$field],
                    'to' => $value,
                ];
            }
        }

        if ($changed) {
            AdSlotAudit::create([
                'ad_slot_id' =>
                    $adSlot->id,
                'site_id' =>
                    $adSlot->site_id,
                'user_id' =>
                    $request->user()?->id,
                'action' =>
                    'updated',
                'changed_fields' =>
                    $changed,
            ]);
        }

        return back()->with(
            'ok',
            'Ad slot saved.'
        );
    }
}
