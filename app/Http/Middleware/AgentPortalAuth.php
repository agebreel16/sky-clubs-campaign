<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentPortalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $uuid            = $request->route('uuid');
        $authenticatedId = $request->session()->get('agent_portal_id');

        if ($authenticatedId !== $uuid) {
            return redirect()->route('agent.portal.enter', ['uuid' => $uuid])
                ->withErrors(['session' => 'انتهت جلستك، يرجى استخدام رابط الدخول مجدداً.']);
        }

        $agent = Agent::where('agent_id', $uuid)->first();
        if (!$agent) {
            $request->session()->forget('agent_portal_id');
            abort(404);
        }

        $request->attributes->set('portal_agent', $agent);
        return $next($request);
    }
}
