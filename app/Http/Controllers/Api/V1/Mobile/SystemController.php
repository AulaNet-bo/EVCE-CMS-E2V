<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\Promotion;
use App\Models\PromotionView;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function config(Request $request)
    {
        $settings = SystemSetting::get();
        $user = auth('sanctum')->user();

        // Get active promotions
        $promotions = Promotion::active()->get();

        // Filter promotions based on frequency if user is logged in
        if ($user) {
            $promotions = $promotions->filter(function ($promo) use ($user) {
                if ($promo->frequency === 'once_total') {
                    return !PromotionView::where('user_id', $user->id)
                        ->where('promotion_id', $promo->id)
                        ->exists();
                }

                if ($promo->frequency === 'daily') {
                    return !PromotionView::where('user_id', $user->id)
                        ->where('promotion_id', $promo->id)
                        ->whereDate('seen_at', now()->toDateString())
                        ->exists();
                }

                return true; // every_open always shows
            })->values();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'platform_name' => $settings->platform_name,
                'logo_url' => $settings->logo_path ? asset('storage/' . $settings->logo_path) : null,
                'branding' => [
                    'primary_color' => $settings->primary_color,
                    'secondary_color' => $settings->secondary_color,
                    'button_color' => $settings->button_color,
                    'text_color' => $settings->text_color,
                    'font_family' => $settings->font_family,
                ],
                'legal' => [
                    'disclaimer_url' => url('/disclaimer'),
                    'is_disclaimer_visible' => (bool) $settings->is_disclaimer_visible,
                ],
                'promotions' => $promotions->map(fn($p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'body' => $p->body,
                    'image_url' => $p->image_path ? asset('storage/' . $p->image_path) : null,
                    'type' => $p->type,
                    'frequency' => $p->frequency,
                ]),
                'policies' => [
                    'invoicing_policy' => $settings->invoicing_policy ?? 'recharge',
                    'nit_requirement_policy' => $settings->nit_requirement_policy ?? 'optional',
                ],
            ]
        ]);
    }

    public function trackSeen(Request $request)
    {
        $request->validate([
            'promotion_id' => 'required|exists:promotions,id'
        ]);

        $user = auth('sanctum')->user();
        if (!$user)
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        PromotionView::updateOrCreate(
            ['user_id' => $user->id, 'promotion_id' => $request->promotion_id],
            ['seen_at' => now()]
        );

        return response()->json(['status' => 'success']);
    }
}
