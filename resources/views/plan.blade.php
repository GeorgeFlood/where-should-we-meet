<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        !function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init capture register register_once register_for_session unregister opt_out_capturing has_opted_out_capturing opt_in_capturing reset isFeatureEnabled getFeatureFlag getFeatureFlagPayload reloadFeatureFlags group identify setPersonProperties setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags resetGroups onFeatureFlags addFeatureFlagsHandler onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey getNextSurveyStep".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
        posthog.init('phc_3pdoARvoLmreCrFicKx9rYWdEmchFvqc8fZiLMSnrtr', {
            api_host: 'https://us.i.posthog.com',
            defaults: '2026-01-30'
        })
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meet at {{ $plan['venue']['name'] }} — MeetHere</title>
    <meta property="og:title" content="Let's meet at {{ $plan['venue']['name'] }}!">
    <meta property="og:description" content="{{ $plan['venue']['subcategory'] ?? ucfirst($plan['venue']['type'] ?? 'spot') }} · {{ $plan['venue']['address'] ?? '' }}">

    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Instrument Sans', system-ui, sans-serif; background: #f1f5f9; min-height: 100vh; }

        .hero-map { width: 100%; height: 220px; position: relative; transition: height 0.4s ease; }
        .hero-map.tracking-active { height: 320px; }
        #planMap { width: 100%; height: 100%; }

        .plan-card {
            max-width: 480px;
            margin: -40px auto 0;
            position: relative;
            z-index: 500;
            background: white;
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.10);
            overflow: hidden;
        }

        .venue-header {
            padding: 24px 24px 16px;
            color: white;
        }
        .venue-header h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .venue-header .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: rgba(255,255,255,0.2); margin-right: 6px; }
        .venue-header .address { font-size: 13px; opacity: 0.85; margin-top: 6px; }

        .info-row { display: flex; align-items: center; gap: 10px; padding: 14px 24px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
        .info-row svg { flex-shrink: 0; color: #94a3b8; }
        .info-row a { color: #4f46e5; text-decoration: none; font-weight: 500; word-break: break-all; }

        .section-title { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 24px 8px; }

        .direction-card {
            margin: 0 16px 10px;
            padding: 14px 16px;
            background: #f8fafc;
            border-radius: 14px;
        }
        .direction-card .from { font-size: 14px; font-weight: 600; color: #0f172a; margin-bottom: 4px; }
        .direction-card .duration { font-size: 13px; color: #64748b; margin-bottom: 10px; }
        .leg-step { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 6px; font-size: 12px; color: #475569; }
        .leg-icon { width: 22px; height: 22px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; }
        .leg-icon.walking { background: #f0fdf4; }
        .leg-icon.tube { background: #fef2f2; }
        .leg-icon.bus { background: #eff6ff; }
        .leg-icon.rail { background: #fefce8; }
        .line-pill { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; background: #e2e8f0; color: #334155; margin-left: 4px; }

        .footer { padding: 16px 24px 24px; text-align: center; }
        .footer a.btn {
            display: inline-block; padding: 14px 28px; background: #4f46e5; color: white;
            font-weight: 600; border-radius: 14px; text-decoration: none; font-size: 14px;
            box-shadow: 0 2px 8px rgba(79,70,229,0.3);
        }
        .footer .small { font-size: 11px; color: #94a3b8; margin-top: 12px; }

        .departure-section { padding: 0 16px 16px; }
        .departure-input-row { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; padding: 0 8px; }
        .departure-input-row label { font-size: 13px; font-weight: 500; color: #475569; white-space: nowrap; }
        .departure-input-row input { flex: 1; padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-weight: 600; font-family: inherit; outline: none; }
        .departure-input-row input:focus { border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(165,180,252,0.3); }
        .departure-results { display: flex; flex-direction: column; gap: 8px; padding: 0 8px; }

        .tracker-section { margin: 8px 16px; border-radius: 14px; overflow: hidden; background: #f8fafc; }
        .tracker-header { display: flex; align-items: center; gap: 8px; padding: 14px 16px 10px; }
        .tracker-header h3 { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }

        .tracker-person {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 16px;
            transition: background 0.3s;
        }
        .tracker-person:last-child { border-bottom: none; }
        .tracker-person.is-me { background: #eef2ff; border-radius: 10px; margin: 0 4px; }

        .tracker-dot {
            width: 10px; height: 10px; border-radius: 50%;
            flex-shrink: 0; transition: all 0.3s;
        }
        .tracker-dot.pending { background: #d1d5db; }
        .tracker-dot.on_my_way { background: #f59e0b; animation: tracker-pulse 1.5s ease-in-out infinite; }
        .tracker-dot.arrived { background: #22c55e; }
        @keyframes tracker-pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.3); opacity: 0.7; } }

        .tracker-postcode { font-size: 13px; font-weight: 600; color: #0f172a; }
        .tracker-info { flex: 1; min-width: 0; }
        .tracker-distance { font-size: 11px; font-weight: 500; color: #6366f1; margin-top: 1px; }
        .tracker-distance.close { color: #16a34a; font-weight: 600; }
        .tracker-status-col { text-align: right; flex-shrink: 0; }

        .person-map-marker {
            width: 32px; height: 32px; border-radius: 50%;
            background: #4f46e5; color: white; font-weight: 700;
            font-size: 12px; display: flex; align-items: center;
            justify-content: center; border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            font-family: 'Instrument Sans', system-ui, sans-serif;
        }
        .person-map-marker.arrived-marker { background: #22c55e; }

        .tracker-identity { padding: 14px 16px; text-align: center; }
        .identity-btn {
            display: block; width: 100%; padding: 10px 14px;
            margin-bottom: 6px; border: 1.5px solid #e2e8f0;
            border-radius: 10px; background: white;
            font-size: 13px; font-weight: 600; font-family: inherit;
            color: #334155; cursor: pointer; transition: all 0.15s;
        }
        .identity-btn:hover { border-color: #6366f1; background: #eef2ff; color: #4338ca; }

        .tracker-action {
            display: none; padding: 10px 16px; text-align: center;
        }
        .action-btn {
            width: 100%; padding: 12px;
            border: none; border-radius: 10px;
            font-size: 13px; font-weight: 600; font-family: inherit;
            cursor: pointer; transition: all 0.2s;
        }
        .action-btn.on-my-way { background: #f59e0b; color: white; }
        .action-btn.arrived { background: #22c55e; color: white; }
        .action-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .action-btn.done { background: #f0fdf4; color: #16a34a; border: 1.5px solid #bbf7d0; cursor: default; }

        .celebration-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.5);
            align-items: center; justify-content: center;
        }
        .celebration-card {
            background: white; border-radius: 24px; padding: 40px 32px;
            text-align: center; max-width: 340px; width: 90%;
            animation: celebration-pop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes celebration-pop { 0% { transform: scale(0.5); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }

        .rating-prompt {
            margin: 12px 16px; padding: 20px; border-radius: 14px;
            background: linear-gradient(135deg, #fefce8, #fef9c3);
            border: 1.5px solid #fde68a; text-align: center;
        }
        .rating-btn {
            padding: 10px 20px; border: 1.5px solid #e2e8f0; border-radius: 10px;
            background: white; font-size: 14px; cursor: pointer; font-family: inherit;
            font-weight: 600; transition: all 0.15s; margin: 0 4px;
        }
        .rating-btn:hover { transform: scale(1.05); }
        .rating-btn.positive:hover { border-color: #22c55e; background: #f0fdf4; }
        .rating-btn.negative:hover { border-color: #ef4444; background: #fef2f2; }

        .review-badge-plan {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 8px;
            font-size: 12px; font-weight: 600; margin: 8px 16px;
        }
    </style>
</head>
<body>
    @php
        $venue = $plan['venue'];
        $occasion = $plan['occasion'] ?? 'casual';
        $themes = [
            'pub' => ['gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)', 'icon' => '🍺'],
            'cafe' => ['gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)', 'icon' => '☕'],
            'restaurant' => ['gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)', 'icon' => '🍽️'],
            'entertainment' => ['gradient' => 'linear-gradient(135deg, #ec4899, #db2777)', 'icon' => '🎭'],
            'station' => ['gradient' => 'linear-gradient(135deg, #3b82f6, #2563eb)', 'icon' => '🚉'],
        ];
        $theme = $themes[$venue['type'] ?? 'other'] ?? ['gradient' => 'linear-gradient(135deg, #6366f1, #4f46e5)', 'icon' => '📍'];
    @endphp

    <div class="hero-map">
        <div id="planMap"></div>
    </div>

    <div class="plan-card">
        <div class="venue-header" style="background: {{ $theme['gradient'] }};">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span style="font-size: 26px;">{{ $theme['icon'] }}</span>
                <h1>{{ $venue['name'] }}</h1>
            </div>
            <div>
                <span class="badge">{{ $venue['subcategory'] ?? ucfirst($venue['type'] ?? 'Spot') }}</span>
                @if(!empty($venue['cuisine']))
                    <span class="badge">{{ $venue['cuisine'] }}</span>
                @endif
            </div>
            @if(!empty($venue['address']))
                <p class="address">{{ $venue['address'] }}</p>
            @endif
        </div>

        @php
            $isCinema = strtolower($venue['subcategory'] ?? '') === 'cinema';
            $isTheatre = strtolower($venue['subcategory'] ?? '') === 'theatre';
            $isFood = in_array($venue['type'] ?? '', ['restaurant', 'cafe']);
        @endphp

        @if($isFood)
        <div class="info-row" style="background: #f0fdf4; border-radius: 10px; margin: 8px 16px; border: 1px solid #bbf7d0; padding: 14px 18px;">
            <span style="font-size: 16px;">📖</span>
            <a href="{{ $venue['website'] ?? 'https://www.google.com/search?q=' . urlencode(($venue['name'] ?? '') . ' menu London') }}" target="_blank" style="color: #166534; font-weight: 600;">View Menu</a>
        </div>
        @elseif($isCinema)
        <div class="info-row" style="background: #fdf4ff; border-radius: 10px; margin: 8px 16px; border: 1px solid #f0abfc; padding: 14px 18px;">
            <span style="font-size: 16px;">🎬</span>
            <a href="{{ $venue['website'] ?? 'https://www.google.com/search?q=' . urlencode(($venue['name'] ?? '') . ' showtimes today') }}" target="_blank" style="color: #86198f; font-weight: 600;">What's On — Showtimes</a>
        </div>
        @elseif($isTheatre)
        <div class="info-row" style="background: #fefce8; border-radius: 10px; margin: 8px 16px; border: 1px solid #fde68a; padding: 14px 18px;">
            <span style="font-size: 16px;">🎭</span>
            <a href="{{ $venue['website'] ?? 'https://www.google.com/search?q=' . urlencode(($venue['name'] ?? '') . ' whats on today') }}" target="_blank" style="color: #854d0e; font-weight: 600;">What's On — Shows & Tickets</a>
        </div>
        @elseif(!empty($venue['website']))
        <div class="info-row">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
            <a href="{{ $venue['website'] }}" target="_blank">{{ str_replace(['https://', 'http://', 'www.'], '', $venue['website']) }}</a>
        </div>
        @endif

        @if(!empty($venue['phone']))
        <div class="info-row">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            <a href="tel:{{ $venue['phone'] }}">{{ $venue['phone'] }}</a>
        </div>
        @endif

        <div class="info-row">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <a href="https://www.google.com/maps/search/?api=1&query={{ $venue['lat'] }},{{ $venue['lng'] }}" target="_blank">Open in Google Maps</a>
        </div>

        <!-- Review badge (loaded by JS) -->
        <div id="reviewBadgePlan" style="display: none;"></div>

        @if(!empty($venue['times']))
        <!-- Live Tracker -->
        <div class="tracker-section" id="trackerSection">
            <div class="tracker-header">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; animation: tracker-pulse 2s ease-in-out infinite;"></span>
                <h3>Who's where?</h3>
            </div>

            <!-- Identity picker -->
            <div class="tracker-identity" id="identityPicker">
                <p style="font-size: 12px; color: #94a3b8; margin-bottom: 10px;">Tap your postcode to start tracking</p>
                @foreach($venue['times'] as $i => $t)
                <button class="identity-btn" data-person="{{ $i }}" data-postcode="{{ $t['from'] }}">
                    {{ $t['from'] }}
                </button>
                @endforeach
            </div>

            <!-- Status list -->
            <div id="trackerList" style="display: none;">
                @foreach($venue['times'] as $i => $t)
                <div class="tracker-person" data-person="{{ $i }}">
                    <div class="tracker-dot pending" data-dot="{{ $i }}"></div>
                    <div class="tracker-info">
                        <div class="tracker-postcode">{{ $t['from'] }}</div>
                        <div class="tracker-distance" data-distance="{{ $i }}" style="display: none;"></div>
                    </div>
                    <div class="tracker-status-col">
                        <span style="font-size: 12px; font-weight: 500; color: #94a3b8;" data-dist-label="{{ $i }}">—</span>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Action button -->
            <div class="tracker-action" id="trackerAction">
                <button class="action-btn on-my-way" id="trackerActionBtn">
                    🚶 I'm on my way!
                </button>
            </div>
        </div>

        <!-- Rating prompt (shown 24h+ after plan creation) -->
        <div class="rating-prompt" id="ratingPrompt" style="display: none;">
            <p style="font-size: 16px; margin-bottom: 6px;">⭐</p>
            <p style="font-size: 14px; font-weight: 600; color: #92400e; margin-bottom: 4px;">How was {{ $venue['name'] }}?</p>
            <p style="font-size: 12px; color: #a16207; margin-bottom: 14px;">Your rating helps others on MeetHere</p>
            <div>
                <button class="rating-btn positive" id="ratePositive">👍 Good</button>
                <button class="rating-btn negative" id="rateNegative">👎 Not great</button>
            </div>
            <p id="ratingThanks" style="display: none; font-size: 13px; color: #16a34a; font-weight: 600; margin-top: 10px;">Thanks for your review!</p>
        </div>

        <!-- Celebration overlay -->
        <div class="celebration-overlay" id="celebrationOverlay">
            <div class="celebration-card">
                <p style="font-size: 48px; margin-bottom: 12px;">🎉</p>
                <h2 style="font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Everyone's here!</h2>
                <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">Time to have an amazing time at {{ $venue['name'] }}.</p>
                <button onclick="document.getElementById('celebrationOverlay').style.display='none'" style="padding: 12px 28px; background: #4f46e5; color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit;">Let's go!</button>
            </div>
        </div>

        <p class="section-title">Getting there</p>
        @foreach($venue['times'] as $t)
        <div class="direction-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p class="from">From {{ $t['from'] }}</p>
                    <p class="duration">{{ $t['duration'] }} min journey</p>
                </div>
                @if(!empty($t['fare']['total_pence']))
                <span style="font-size: 13px; font-weight: 700; color: #059669; background: #ecfdf5; padding: 4px 10px; border-radius: 10px;">£{{ number_format($t['fare']['total_pence'] / 100, 2) }}</span>
                @endif
            </div>
            @if(!empty($t['legs']))
                @foreach($t['legs'] as $leg)
                <div class="leg-step">
                    @php
                        $mode = $leg['mode'] ?? 'walking';
                        $modeClass = match(true) {
                            str_contains($mode, 'tube') => 'tube',
                            str_contains($mode, 'bus') => 'bus',
                            str_contains($mode, 'rail') || str_contains($mode, 'dlr') || str_contains($mode, 'elizabeth') || str_contains($mode, 'overground') => 'rail',
                            default => 'walking',
                        };
                        $emoji = match($modeClass) { 'tube' => '🔴', 'bus' => '🔵', 'rail' => '🟡', default => '🚶' };
                    @endphp
                    <div class="leg-icon {{ $modeClass }}">{{ $emoji }}</div>
                    <span>{{ $leg['summary'] }}@if(!empty($leg['line']))<span class="line-pill">{{ $leg['line'] }}</span>@endif <span style="color: #94a3b8;">· {{ $leg['duration'] }} min</span></span>
                </div>
                @endforeach
            @endif
        </div>
        @endforeach

        @php
            $fares = array_filter(array_map(fn($t) => $t['fare']['total_pence'] ?? null, $venue['times']));
            $allHaveFares = count($fares) === count($venue['times']);
        @endphp
        @if($allHaveFares && count($fares) >= 2)
        <div style="margin: 8px 16px; padding: 16px; background: linear-gradient(135deg, #ecfdf5, #f0fdf4); border: 1.5px solid #bbf7d0; border-radius: 14px;">
            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 10px;">
                <span style="font-size: 16px;">💳</span>
                <span style="font-size: 12px; font-weight: 600; color: #166534; text-transform: uppercase; letter-spacing: 0.05em;">Split the fare</span>
            </div>
            @php
                $totalPence = array_sum($fares);
                $fairShare = (int) round($totalPence / count($fares));
                $diffs = [];
                foreach ($venue['times'] as $t) {
                    $diffs[] = ['from' => $t['from'], 'diff' => ($t['fare']['total_pence'] ?? 0) - $fairShare];
                }
                $overpayers = array_filter($diffs, fn($d) => $d['diff'] > 0);
                $underpayers = array_filter($diffs, fn($d) => $d['diff'] < 0);
                usort($overpayers, fn($a, $b) => $b['diff'] <=> $a['diff']);
                usort($underpayers, fn($a, $b) => $a['diff'] <=> $b['diff']);

                $settlements = [];
                $oi = 0; $ui = 0;
                $overBal = array_map(fn($o) => array_merge($o, ['rem' => $o['diff']]), $overpayers);
                $underBal = array_map(fn($u) => array_merge($u, ['rem' => -$u['diff']]), $underpayers);
                while ($oi < count($overBal) && $ui < count($underBal)) {
                    $amount = min($overBal[$oi]['rem'], $underBal[$ui]['rem']);
                    if ($amount > 0) $settlements[] = ['payer' => $underBal[$ui]['from'], 'payee' => $overBal[$oi]['from'], 'pence' => $amount];
                    $overBal[$oi]['rem'] -= $amount;
                    $underBal[$ui]['rem'] -= $amount;
                    if ($overBal[$oi]['rem'] <= 0) $oi++;
                    if ($underBal[$ui]['rem'] <= 0) $ui++;
                }
            @endphp
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span style="font-size: 12px; color: #6b7280;">Total travel cost</span>
                <span style="font-size: 14px; font-weight: 700; color: #166534;">£{{ number_format($totalPence / 100, 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span style="font-size: 12px; color: #6b7280;">Fair share each</span>
                <span style="font-size: 14px; font-weight: 700; color: #166534;">£{{ number_format($fairShare / 100, 2) }}</span>
            </div>
            @if(empty($settlements))
                <p style="font-size: 13px; color: #059669; font-weight: 600;">All even — no one owes anything!</p>
            @else
                @foreach($settlements as $s)
                <div style="display: flex; align-items: center; gap: 6px; padding: 8px 10px; background: white; border-radius: 8px; border: 1px solid #d1fae5; margin-bottom: 4px;">
                    <span style="font-size: 13px; font-weight: 600; color: #1e293b;">{{ $s['payer'] }}</span>
                    <span style="font-size: 11px; color: #6b7280;">owes</span>
                    <span style="font-size: 13px; font-weight: 600; color: #1e293b;">{{ $s['payee'] }}</span>
                    <span style="margin-left: auto; font-size: 14px; font-weight: 700; color: #059669;">£{{ number_format($s['pence'] / 100, 2) }}</span>
                </div>
                @endforeach
            @endif
        </div>
        @endif

        <p class="section-title">When should I leave?</p>
        <div class="departure-section">
            <div class="departure-input-row">
                <label for="planArrival">Arrive by</label>
                <input type="time" id="planArrival">
            </div>
            <div class="departure-results" id="planDepartures"></div>
        </div>
        @endif

        <div class="footer">
            <a class="btn" href="/">Plan your own meetup</a>
            <p class="small">Made with MeetHere</p>
        </div>
    </div>

    <div style="height: 40px;"></div>

    <script>
        const lat = {{ $venue['lat'] }};
        const lng = {{ $venue['lng'] }};
        const planId = @json($planId);
        const venueName = @json($venue['name']);
        const venueLat = {{ $venue['lat'] }};
        const venueLng = {{ $venue['lng'] }};
        const peopleCount = {{ count($venue['times'] ?? []) }};
        const planCreated = @json($plan['created'] ?? null);

        const map = L.map('planMap', { zoomControl: false, attributionControl: false }).setView([lat, lng], 15);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);
        L.marker([lat, lng]).addTo(map);

        const times = @json($venue['times'] ?? []);

        // Departure planner
        const arrivalInput = document.getElementById('planArrival');
        const departuresEl = document.getElementById('planDepartures');

        if (arrivalInput) {
            arrivalInput.addEventListener('input', function () {
                const val = this.value;
                if (!val || !times.length) { departuresEl.innerHTML = ''; return; }
                departuresEl.innerHTML = '';
                const sorted = [...times].sort((a, b) => b.duration - a.duration);
                sorted.forEach((t, i) => {
                    const [h, m] = val.split(':').map(Number);
                    const total = ((h * 60 + m - t.duration) % 1440 + 1440) % 1440;
                    const lh = Math.floor(total / 60), lm = total % 60;
                    const suffix = lh >= 12 ? 'pm' : 'am';
                    const display = lh === 0 ? 12 : lh > 12 ? lh - 12 : lh;
                    const timeStr = display + ':' + String(lm).padStart(2, '0') + ' ' + suffix;
                    const isFirst = i === 0;
                    const card = document.createElement('div');
                    card.style.cssText = 'display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:12px;background:' + (isFirst ? '#eef2ff;border:1px solid #c7d2fe' : '#f8fafc');
                    card.innerHTML = '<div style="flex:1;min-width:0"><p style="font-size:13px;font-weight:500;color:#334155">' + t.from + '</p><p style="font-size:11px;color:#94a3b8">' + t.duration + ' min journey</p></div><div style="text-align:right;flex-shrink:0"><p style="font-size:17px;font-weight:700;color:' + (isFirst ? '#4f46e5' : '#0f172a') + '">' + timeStr + '</p><p style="font-size:11px;color:' + (isFirst ? '#6366f1' : '#94a3b8') + ';font-weight:' + (isFirst ? '600' : '400') + '">' + (isFirst ? 'leaves first' : 'leave by') + '</p></div>';
                    departuresEl.appendChild(card);
                });
            });
        }

        // ============================
        //  Review badge
        // ============================
        (async function loadReviewBadge() {
            try {
                const params = new URLSearchParams({ name: venueName, lat: venueLat, lng: venueLng });
                const resp = await fetch('/api/venue-reviews?' + params);
                const data = await resp.json();
                if (!data.has_reviews) return;
                const el = document.getElementById('reviewBadgePlan');
                el.innerHTML = '<div class="review-badge-plan" style="background:' + data.color + '18;color:' + data.color + ';">⭐ ' + data.label + ' <span style="opacity:0.7;font-weight:400;">(' + data.total + ' MeetHere ' + (data.total === 1 ? 'review' : 'reviews') + ')</span></div>';
                el.style.display = 'block';
            } catch (_) {}
        })();

        // ============================
        //  24h Rating prompt
        // ============================
        const ratingStorageKey = 'rated_' + planId;
        if (planCreated && !localStorage.getItem(ratingStorageKey)) {
            const ageMs = Date.now() - new Date(planCreated).getTime();
            const showDelay = Math.max(0, 24 * 60 * 60 * 1000 - ageMs);
            if (ageMs >= 24 * 60 * 60 * 1000) {
                document.getElementById('ratingPrompt').style.display = 'block';
            } else {
                setTimeout(() => {
                    document.getElementById('ratingPrompt').style.display = 'block';
                }, showDelay);
            }
        }

        document.getElementById('ratePositive')?.addEventListener('click', () => submitRating('positive'));
        document.getElementById('rateNegative')?.addEventListener('click', () => submitRating('negative'));

        async function submitRating(rating) {
            try {
                await fetch('/api/review', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ venue_name: venueName, venue_lat: venueLat, venue_lng: venueLng, rating, plan_id: planId }),
                });
                localStorage.setItem(ratingStorageKey, '1');
                document.getElementById('ratePositive').style.display = 'none';
                document.getElementById('rateNegative').style.display = 'none';
                document.getElementById('ratingThanks').style.display = 'block';
                setTimeout(() => { document.getElementById('ratingPrompt').style.display = 'none'; }, 2000);
            } catch (_) {}
        }

        // ============================
        //  Live Tracker
        // ============================
        const identityPicker = document.getElementById('identityPicker');
        const trackerList = document.getElementById('trackerList');
        const trackerAction = document.getElementById('trackerAction');
        const trackerActionBtn = document.getElementById('trackerActionBtn');
        const celebrationOverlay = document.getElementById('celebrationOverlay');
        const heroMap = document.querySelector('.hero-map');

        const storageKey = `tracker_identity_${planId}`;
        let myPersonIndex = null;
        let myCurrentStatus = 'pending';
        let pollInterval = null;
        let celebrationShown = false;
        let geoWatchId = null;
        let lastSentLocation = null;
        const personMarkers = {};
        const AUTO_ARRIVE_METRES = 50;

        const personColors = ['#4f46e5', '#e11d48', '#0891b2', '#7c3aed', '#c2410c'];

        function haversineDistance(lat1, lng1, lat2, lng2) {
            const R = 6371000;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function formatDistance(metres) {
            if (metres < 50) return 'Here!';
            if (metres < 200) return 'Nearly there';
            const miles = metres / 1609.34;
            if (miles < 0.3) return Math.round(metres) + 'm away';
            return miles.toFixed(1) + ' mi away';
        }

        function createPersonMapIcon(index) {
            const color = personColors[index % personColors.length];
            return L.divIcon({
                className: '',
                html: '<div class="person-map-marker" style="background:' + color + ';">' + (index + 1) + '</div>',
                iconSize: [32, 32],
                iconAnchor: [16, 16],
            });
        }

        function createArrivedMapIcon() {
            return L.divIcon({
                className: '',
                html: '<div class="person-map-marker arrived-marker">\u2713</div>',
                iconSize: [32, 32],
                iconAnchor: [16, 16],
            });
        }

        const savedIdentity = localStorage.getItem(storageKey);
        if (savedIdentity !== null) {
            myPersonIndex = parseInt(savedIdentity, 10);
            activateTracker();
        }

        if (identityPicker) {
            identityPicker.addEventListener('click', function (e) {
                const btn = e.target.closest('.identity-btn');
                if (!btn) return;
                myPersonIndex = parseInt(btn.dataset.person, 10);
                localStorage.setItem(storageKey, myPersonIndex);
                activateTracker();
            });
        }

        async function activateTracker() {
            if (identityPicker) identityPicker.style.display = 'none';
            if (trackerList) trackerList.style.display = 'block';
            if (trackerAction) trackerAction.style.display = 'block';

            const myRow = trackerList?.querySelector('.tracker-person[data-person="' + myPersonIndex + '"]');
            if (myRow) {
                myRow.classList.add('is-me');
                const postcode = myRow.querySelector('.tracker-postcode');
                if (postcode) postcode.innerHTML += ' <span style="font-size:10px;color:#6366f1;font-weight:600;">(you)</span>';
            }

            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }

            await pollStatuses();
            if (myCurrentStatus === 'on_my_way') startLocationTracking();
            pollInterval = setInterval(pollStatuses, 5000);
        }

        function startLocationTracking() {
            if (geoWatchId !== null) return;
            if (!navigator.geolocation) return;

            heroMap.classList.add('tracking-active');
            setTimeout(() => map.invalidateSize(), 500);

            geoWatchId = navigator.geolocation.watchPosition(
                function (pos) {
                    const myLat = pos.coords.latitude;
                    const myLng = pos.coords.longitude;
                    const dist = haversineDistance(myLat, myLng, lat, lng);

                    updateMyMarker(myLat, myLng);
                    updateDistLabel(myPersonIndex, dist);

                    if (dist <= AUTO_ARRIVE_METRES && myCurrentStatus === 'on_my_way') {
                        autoArrive(myLat, myLng);
                    }

                    const now = Date.now();
                    if (!lastSentLocation || now - lastSentLocation > 15000) {
                        lastSentLocation = now;
                        sendLocationUpdate(myLat, myLng, dist);
                    }
                },
                function () {},
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
            );
        }

        async function autoArrive(myLat, myLng) {
            myCurrentStatus = 'arrived';
            updateActionButton();
            stopLocationTracking();
            await sendLocationUpdate(lat, lng, 0);
            pollStatuses();
        }

        function stopLocationTracking() {
            if (geoWatchId !== null) {
                navigator.geolocation.clearWatch(geoWatchId);
                geoWatchId = null;
            }
        }

        function updateMyMarker(myLat, myLng) {
            if (!personMarkers[myPersonIndex]) {
                personMarkers[myPersonIndex] = L.marker([myLat, myLng], {
                    icon: createPersonMapIcon(myPersonIndex),
                    zIndexOffset: 1000,
                }).addTo(map);
                fitMapToTrackers();
            } else {
                personMarkers[myPersonIndex].setLatLng([myLat, myLng]);
            }
        }

        function updateDistLabel(personIndex, metres) {
            const distEl = document.querySelector('[data-distance="' + personIndex + '"]');
            const labelEl = document.querySelector('[data-dist-label="' + personIndex + '"]');
            if (distEl) {
                distEl.textContent = formatDistance(metres);
                distEl.style.display = 'block';
                distEl.className = 'tracker-distance' + (metres < 200 ? ' close' : '');
            }
            if (labelEl) {
                labelEl.textContent = formatDistance(metres);
                labelEl.style.color = metres < 200 ? '#16a34a' : '#6366f1';
                labelEl.style.fontWeight = metres < 200 ? '600' : '500';
            }
        }

        async function sendLocationUpdate(myLat, myLng, dist) {
            try {
                await fetch('/api/plan/' + planId + '/status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        person: myPersonIndex,
                        status: myCurrentStatus,
                        lat: myLat, lng: myLng,
                        distance_metres: Math.round(dist),
                    }),
                });
            } catch (_) {}
        }

        function fitMapToTrackers() {
            const points = [[lat, lng]];
            Object.values(personMarkers).forEach(m => {
                const ll = m.getLatLng();
                points.push([ll.lat, ll.lng]);
            });
            if (points.length > 1) {
                map.fitBounds(points, { padding: [30, 30], maxZoom: 15 });
            }
        }

        async function pollStatuses() {
            try {
                const resp = await fetch('/api/plan/' + planId + '/status');
                if (!resp.ok) return;
                const data = await resp.json();

                let allArrived = true;
                let hasAnyTracking = false;

                (data.statuses || []).forEach(s => {
                    const dot = document.querySelector('[data-dot="' + s.person + '"]');
                    const labelEl = document.querySelector('[data-dist-label="' + s.person + '"]');

                    if (dot) dot.className = 'tracker-dot ' + s.status;

                    if (s.person === myPersonIndex) {
                        myCurrentStatus = s.status;
                        updateActionButton();
                    }

                    if (s.person !== myPersonIndex) {
                        if (s.status === 'on_my_way' && s.distance_metres != null) {
                            updateDistLabel(s.person, s.distance_metres);
                            if (s.lat != null && s.lng != null) {
                                hasAnyTracking = true;
                                if (!personMarkers[s.person]) {
                                    personMarkers[s.person] = L.marker([s.lat, s.lng], { icon: createPersonMapIcon(s.person) }).addTo(map);
                                } else {
                                    personMarkers[s.person].setLatLng([s.lat, s.lng]);
                                }
                            }
                        } else if (s.status === 'arrived') {
                            if (labelEl) { labelEl.textContent = 'Arrived!'; labelEl.style.color = '#16a34a'; labelEl.style.fontWeight = '600'; }
                            const distEl = document.querySelector('[data-distance="' + s.person + '"]');
                            if (distEl) { distEl.textContent = 'Here!'; distEl.style.display = 'block'; distEl.className = 'tracker-distance close'; }
                            if (personMarkers[s.person]) {
                                personMarkers[s.person].setLatLng([lat, lng]);
                                personMarkers[s.person].setIcon(createArrivedMapIcon());
                            }
                        } else {
                            if (labelEl) { labelEl.textContent = '—'; labelEl.style.color = '#94a3b8'; }
                        }
                    }

                    if (s.status !== 'arrived') allArrived = false;
                });

                if (hasAnyTracking || geoWatchId !== null) {
                    heroMap.classList.add('tracking-active');
                    setTimeout(() => { map.invalidateSize(); fitMapToTrackers(); }, 100);
                }

                if (allArrived && peopleCount >= 2 && !celebrationShown) {
                    celebrationShown = true;
                    stopLocationTracking();
                    celebrationOverlay.style.display = 'flex';
                    if (pollInterval) clearInterval(pollInterval);

                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification("You've all arrived at " + venueName + " 🎉", {
                            body: 'Have fun!',
                        });
                    }
                }
            } catch (_) {}
        }

        function updateActionButton() {
            if (!trackerActionBtn) return;
            if (myCurrentStatus === 'pending') {
                trackerActionBtn.className = 'action-btn on-my-way';
                trackerActionBtn.innerHTML = '🚶 I\'m on my way!';
                trackerActionBtn.disabled = false;
            } else if (myCurrentStatus === 'on_my_way') {
                trackerActionBtn.className = 'action-btn arrived';
                trackerActionBtn.innerHTML = '📍 I\'ve arrived!';
                trackerActionBtn.disabled = false;
            } else {
                trackerActionBtn.className = 'action-btn done';
                trackerActionBtn.innerHTML = '✅ You\'re here!';
                trackerActionBtn.disabled = true;
                stopLocationTracking();
            }
        }

        if (trackerActionBtn) {
            trackerActionBtn.addEventListener('click', async function () {
                if (myPersonIndex === null) return;
                let newStatus;
                if (myCurrentStatus === 'pending') newStatus = 'on_my_way';
                else if (myCurrentStatus === 'on_my_way') newStatus = 'arrived';
                else return;

                trackerActionBtn.disabled = true;
                try {
                    const resp = await fetch('/api/plan/' + planId + '/status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ person: myPersonIndex, status: newStatus }),
                    });
                    if (resp.ok) {
                        myCurrentStatus = newStatus;
                        updateActionButton();
                        if (newStatus === 'on_my_way') startLocationTracking();
                        else if (newStatus === 'arrived') { stopLocationTracking(); sendLocationUpdate(lat, lng, 0); }
                        pollStatuses();
                    }
                } catch (_) {
                    trackerActionBtn.disabled = false;
                }
            });
        }
    </script>
</body>
</html>
