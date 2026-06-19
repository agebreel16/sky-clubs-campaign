<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgentPortalController extends Controller
{
    public function enter(Request $request, string $uuid): RedirectResponse
    {
        $agent = Agent::where('agent_id', $uuid)->whereNotNull('portal_token')->first();

        if (!$agent) {
            abort(404, 'رابط غير صالح.');
        }

        if (!hash_equals($agent->portal_token, (string) $request->query('token', ''))) {
            abort(403, 'رمز الدخول غير صحيح.');
        }

        $request->session()->regenerate();
        $request->session()->put('agent_portal_id', $uuid);

        return redirect()->route('agent.portal.syncing', ['uuid' => $uuid]);
    }

    public function logout(Request $request, string $uuid): RedirectResponse
    {
        $request->session()->forget('agent_portal_id');

        return redirect()->route('agent.portal.enter', ['uuid' => $uuid])
            ->with('status', 'تم تسجيل الخروج بنجاح.');
    }

    public function subscribePush(Request $request, string $uuid): JsonResponse
    {
        $agent = $request->attributes->get('portal_agent');

        $data = $request->validate([
            'endpoint'    => ['required', 'url'],
            'keys.auth'   => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
        ]);

        \NotificationChannels\WebPush\PushSubscription::createOrUpdate($agent, $data);

        return response()->json(['status' => 'subscribed']);
    }
}
