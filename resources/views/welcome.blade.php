<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <script>
        !function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init capture register register_once register_for_session unregister opt_out_capturing has_opted_out_capturing opt_in_capturing reset isFeatureEnabled getFeatureFlag getFeatureFlagPayload reloadFeatureFlags group identify setPersonProperties setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags resetGroups onFeatureFlags addFeatureFlagsHandler onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey getNextSurveyStep".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
        posthog.init('phc_3pdoARvoLmreCrFicKx9rYWdEmchFvqc8fZiLMSnrtr', {
            api_host: 'https://us.i.posthog.com',
            defaults: '2026-01-30'
        })
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#4f46e5">
    <meta name="color-scheme" content="light">

    <title>Midway — Find the fairest meeting spot in London</title>
    <link rel="icon" type="image/png" sizes="16x16" href="/midwayFavi16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/midwayFavi32.png">
    <link rel="icon" type="image/png" sizes="48x48" href="/midwayFavi48.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/midwayFavi180.png">
    <meta name="description" content="Enter your postcodes, pick the vibe, and we'll find the fairest meeting spot in London with TfL directions, fare splitting, and live arrival tracking.">
    <link rel="canonical" href="{{ config('app.url') }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:title" content="Midway — Find the fairest meeting spot in London">
    <meta property="og:description" content="Drop your postcodes, pick the vibe, and we'll find the perfect spot in the middle. With TfL directions, fare splitting, and live tracking.">
    <meta property="og:image" content="{{ config('app.url') }}/og-image.png">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Midway — Find the fairest meeting spot in London">
    <meta name="twitter:description" content="Drop your postcodes, pick the vibe, and we'll find the perfect spot in the middle.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; font-family: 'Instrument Sans', system-ui, sans-serif; }
        html { height: -webkit-fill-available; }
        body { min-height: 100vh; min-height: -webkit-fill-available; }
        #map { position: absolute; inset: 0; z-index: 0; }

        .panel {
            position: absolute; top: 16px; left: 16px; bottom: 16px;
            width: 400px; z-index: 1000;
            display: flex; flex-direction: column; pointer-events: none;
        }
        .panel > * { pointer-events: auto; }
        @media (max-width: 640px) {
            #map { bottom: 0; }
            .panel {
                top: auto; left: 0; right: 0; bottom: 0;
                width: 100%; max-height: 60vh; height: auto;
                padding-bottom: env(safe-area-inset-bottom, 0);
            }
        }

        .panel-card {
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08), 0 1px 4px rgba(0,0,0,0.04);
            overflow: hidden; display: flex; flex-direction: column;
        }
        @media (max-width: 640px) {
            .panel-card { border-radius: 20px 20px 0 0; max-height: 100%; height: auto; }
        }

        .panel-handle { display: none; }
        @media (max-width: 640px) {
            .panel-handle { display: block; width: 36px; height: 4px; background: #cbd5e1; border-radius: 2px; margin: 8px auto 0; flex-shrink: 0; }
        }

        .panel-scroll {
            overflow-y: auto; -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain; scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent; flex: 1; min-height: 0;
        }
        .panel-scroll::-webkit-scrollbar { width: 4px; }
        .panel-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        /* Views transition */
        .view { display: none; animation: viewFadeIn 0.25s ease; }
        .view.active { display: block; }
        @keyframes viewFadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

        /* Map markers */
        .person-marker {
            display: flex; align-items: center; justify-content: center;
            width: 36px; height: 36px; border-radius: 50%;
            background: #4f46e5; color: white; font-weight: 700; font-size: 14px;
            font-family: 'Instrument Sans', system-ui, sans-serif;
            box-shadow: 0 3px 12px rgba(79,70,229,0.4); border: 3px solid white;
        }
        .person-marker.animate-drop { animation: markerDrop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .venue-marker {
            display: flex; align-items: center; justify-content: center;
            width: 44px; height: 44px; border-radius: 50%; color: white; font-size: 22px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25); border: 3px solid white;
        }
        .venue-marker.animate-drop { animation: markerDrop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
        @keyframes markerDrop {
            0% { transform: translateY(-40px) scale(0.5); opacity: 0; }
            60% { transform: translateY(4px) scale(1.1); opacity: 1; }
            100% { transform: translateY(0) scale(1); }
        }

        /* Form elements */
        .input-field {
            width: 100%; padding: 10px 14px; border-radius: 12px;
            border: 1.5px solid #e2e8f0; background: #f8fafc;
            font-size: 15px; font-family: inherit; color: #0f172a;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-field:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
        .input-field::placeholder { color: #94a3b8; }

        .gps-btn {
            display: flex; align-items: center; justify-content: center;
            width: 34px; height: 34px; flex-shrink: 0; border-radius: 10px;
            border: 1.5px solid #e2e8f0; background: #f8fafc; color: #94a3b8;
            cursor: pointer; transition: all 0.15s;
        }
        .gps-btn:hover { border-color: #6366f1; color: #6366f1; background: #eef2ff; }
        .gps-btn.locating { color: #6366f1; animation: gps-pulse 1s ease-in-out infinite; }
        .gps-btn.located { color: #22c55e; border-color: #22c55e; background: #f0fdf4; }
        @keyframes gps-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

        /* Occasion cards */
        .occasion-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .occasion-card {
            display: flex; align-items: center; gap: 10px; padding: 12px;
            border: 1.5px solid #e2e8f0; border-radius: 14px; background: white;
            cursor: pointer; transition: all 0.15s; font-family: inherit; text-align: left;
        }
        .occasion-card:hover { border-color: #a5b4fc; background: #fafafe; }
        .occasion-card.active { border-color: #6366f1; background: #eef2ff; box-shadow: 0 0 0 3px rgba(99,102,241,0.08); }
        .occasion-card .oc-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .occasion-card .oc-label { font-size: 13px; font-weight: 600; color: #0f172a; }
        .occasion-card .oc-desc { font-size: 11px; color: #94a3b8; line-height: 1.3; }
        .occasion-card.active .oc-label { color: #4338ca; }

        /* Buttons */
        .btn-primary {
            width: 100%; padding: 14px; background: #4f46e5; color: white;
            font-size: 15px; font-weight: 600; border: none; border-radius: 14px;
            cursor: pointer; font-family: inherit; transition: background 0.15s;
            box-shadow: 0 2px 8px rgba(79,70,229,0.25);
        }
        .btn-primary:hover { background: #4338ca; }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

        .btn-secondary {
            width: 100%; padding: 12px; background: white; color: #475569;
            font-size: 14px; font-weight: 500; border: 1.5px solid #e2e8f0;
            border-radius: 14px; cursor: pointer; font-family: inherit; transition: all 0.15s;
        }
        .btn-secondary:hover { border-color: #a5b4fc; color: #4f46e5; }

        .btn-ghost {
            background: none; border: none; color: #6366f1; font-size: 13px;
            font-weight: 500; cursor: pointer; font-family: inherit; padding: 8px 0;
            display: flex; align-items: center; gap: 4px;
        }

        /* Results */
        .result-header { padding: 20px; color: white; border-radius: 16px; margin: 0 16px 12px; }
        .journey-row { display: flex; align-items: center; gap: 12px; padding: 0 16px; }
        .journey-bar-bg { width: 100%; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
        .journey-bar-fill { height: 100%; border-radius: 4px; transition: width 0.7s ease; }
        .journey-item { cursor: pointer; }
        .journey-item:hover .journey-row { background: #f8fafc; border-radius: 8px; }
        .journey-legs { max-height: 0; overflow: hidden; transition: max-height 0.35s ease, opacity 0.25s ease; opacity: 0; }
        .journey-legs.open { max-height: 600px; opacity: 1; }
        .leg-step { display: flex; align-items: flex-start; gap: 10px; padding: 6px 0; position: relative; }
        .leg-step:not(:last-child)::before { content: ''; position: absolute; left: 13px; top: 30px; bottom: -6px; width: 2px; background: #e2e8f0; }
        .leg-mode-icon { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 13px; }
        .leg-mode-icon.walking { background: #f1f5f9; color: #64748b; }
        .leg-mode-icon.tube { background: #dbeafe; color: #2563eb; }
        .leg-mode-icon.bus { background: #fef3c7; color: #d97706; }
        .leg-mode-icon.rail { background: #ede9fe; color: #7c3aed; }
        .leg-mode-icon.other { background: #f1f5f9; color: #475569; }
        .disruption-pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 8px; font-size: 11px; font-weight: 600; background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .alert-banner { display: flex; align-items: flex-start; gap: 8px; padding: 10px 14px; margin: 0 20px 12px; background: #fefce8; border: 1px solid #fde68a; border-radius: 12px; font-size: 12px; color: #854d0e; line-height: 1.4; }
        .chevron-toggle { transition: transform 0.25s ease; flex-shrink: 0; }
        .chevron-toggle.open { transform: rotate(180deg); }

        /* Vote button */
        .vote-btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 20px; border-radius: 12px; font-size: 14px; font-weight: 600;
            cursor: pointer; font-family: inherit; transition: all 0.15s; border: none; width: 100%;
        }
        .vote-btn.voteable { background: #4f46e5; color: white; box-shadow: 0 2px 8px rgba(79,70,229,0.25); }
        .vote-btn.voteable:hover { background: #4338ca; }
        .vote-btn.voted { background: #ecfdf5; color: #059669; border: 2px solid #86efac; }
        .vote-count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; border-radius: 11px; font-size: 12px; font-weight: 700; padding: 0 6px; }

        /* Participant pills */
        .participant-pill {
            display: flex; align-items: center; gap: 8px; padding: 10px 14px;
            background: #f8fafc; border-radius: 12px; font-size: 13px; color: #334155;
        }
        .participant-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

        /* Confetti */
        @keyframes confettiDrop { 0% { transform: translateY(-20px) rotate(0deg); opacity: 1; } 100% { transform: translateY(40px) rotate(360deg); opacity: 0; } }
        .confetti-piece { position: absolute; width: 6px; height: 6px; border-radius: 2px; animation: confettiDrop 1.2s ease forwards; pointer-events: none; }

        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes tracker-pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.3); opacity: 0.7; } }
        @keyframes pulseRing { 0% { transform: scale(1); opacity: 0.6; } 100% { transform: scale(1.8); opacity: 0; } }
    </style>
</head>
<body>

    <div id="map"></div>

    <div class="panel">
        <div class="panel-card" style="max-height: 100%;">
            <div class="panel-handle"></div>
            <div class="panel-scroll">

                <!-- ═══════════════ LANDING ═══════════════ -->
                <div id="viewLanding" class="view active">
                    <div style="padding: 20px 20px 0; text-align: center;">
                        <div style="height: 56px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                            <img src="{{ asset('midwayLogo.png') }}" alt="Midway Logo" width="200">
                        </div>
                        <p style="font-size: 14px; color: #475569; line-height: 1.5; margin-bottom: 20px;">
                            Find the <strong style="color: #4f46e5;">fairest spot</strong> to meet your friends in London.
                        </p>
                    </div>

                    <div style="padding: 0 20px;">
                        <label style="display: block; font-size: 11px; font-weight: 600; color: #94a3b8; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">What's the vibe?</label>
                        <div id="occasionSelector" class="occasion-grid"></div>
                    </div>

                    <div style="padding: 20px;">
                        <button type="button" id="startSessionBtn" class="btn-primary" style="margin-bottom: 10px;">
                            Start a Session
                        </button>
                        <button type="button" id="goManualBtn" class="btn-ghost" style="width: 100%; justify-content: center; color: #94a3b8;">
                            Or enter postcodes manually
                        </button>
                    </div>
                </div>

                <!-- ═══════════════ LOCATION PROMPT ═══════════════ -->
                <div id="viewLocationPrompt" class="view">
                    <div style="padding: 24px 20px; text-align: center;">
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: #eef2ff; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 24px;">
                            <svg width="28" height="28" fill="none" stroke="#4f46e5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h2 id="locationPromptTitle" style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Share your location?</h2>
                        <p id="locationPromptDesc" style="font-size: 14px; color: #64748b; line-height: 1.5; margin-bottom: 24px;">
                            We'll use your location to find spots that are fair for everyone. It's never stored or shared.
                        </p>

                        <button type="button" id="allowLocationBtn" class="btn-primary" style="margin-bottom: 10px;">
                            <span id="allowLocationText">Share my location</span>
                            <span id="allowLocationLoading" style="display: none;">
                                <svg style="animation: spin 1s linear infinite; width: 18px; height: 18px;" viewBox="0 0 24 24"><circle style="opacity: 0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path style="opacity: 0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </span>
                        </button>
                        <button type="button" id="declineLocationBtn" class="btn-ghost" style="width: 100%; justify-content: center; margin-bottom: 16px;">
                            I'll type my postcode
                        </button>

                        <div id="manualPostcodeEntry" style="display: none;">
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="selfPostcodeInput" class="input-field" placeholder="e.g. SW1A 1AA" style="flex: 1;">
                                <button type="button" id="submitSelfPostcodeBtn" class="btn-primary" style="width: auto; padding: 10px 20px;">Go</button>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 0 20px 20px;">
                        <button type="button" id="backFromLocationBtn" class="btn-ghost">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Back
                        </button>
                    </div>
                </div>

                <!-- ═══════════════ WAITING ROOM ═══════════════ -->
                <div id="viewWaiting" class="view">
                    <div style="padding: 24px 20px 0; text-align: center;">
                        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: #ecfdf5; border-radius: 20px; margin-bottom: 12px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; position: relative;">
                                <span style="position: absolute; inset: -3px; border-radius: 50%; border: 2px solid #22c55e; animation: pulseRing 1.5s ease-out infinite;"></span>
                            </span>
                            <span style="font-size: 12px; font-weight: 600; color: #059669;">Session live</span>
                        </div>
                        <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Waiting for friends</h2>
                        <p id="waitingSubtext" style="font-size: 13px; color: #94a3b8; margin-bottom: 16px;">Share the link so they can join</p>
                    </div>

                    <div style="padding: 0 20px 16px;">
                        <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                            <button type="button" id="inviteWhatsAppBtn" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 11px; background: #25D366; color: white; border-radius: 10px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; font-family: inherit;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WhatsApp
                            </button>
                            <button type="button" id="inviteCopyBtn" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 11px; background: #4f46e5; color: white; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                Copy link
                            </button>
                        </div>
                        <p id="inviteCopyFeedback" style="display: none; font-size: 12px; color: #059669; text-align: center; margin-bottom: 8px; font-weight: 500;"></p>
                    </div>

                    <div style="padding: 0 20px;">
                        <label style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; display: block;">Who's in</label>
                        <div id="participantList" style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px;"></div>
                    </div>

                    <div style="padding: 0 20px 20px;">
                        <button type="button" id="toggleAddPostcodeBtn" style="width: 100%; padding: 10px; border: 2px dashed #e2e8f0; border-radius: 12px; background: transparent; color: #94a3b8; font-size: 13px; font-weight: 500; cursor: pointer; font-family: inherit; transition: all 0.15s; margin-bottom: 12px;">
                            + Add someone's postcode
                        </button>
                        <div id="addPostcodeForm" style="display: none; margin-bottom: 12px;">
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="addPostcodeInput" class="input-field" placeholder="Their postcode" style="flex: 1;">
                                <button type="button" id="addPostcodeBtn" class="btn-primary" style="width: auto; padding: 10px 20px;">Add</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════ SEARCHING ═══════════════ -->
                <div id="viewSearching" class="view">
                    <div style="padding: 48px 20px; text-align: center;">
                        <svg style="animation: spin 1.2s linear infinite; width: 32px; height: 32px; color: #4f46e5; margin-bottom: 16px;" viewBox="0 0 24 24"><circle style="opacity: 0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path style="opacity: 0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <h2 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 4px;">Finding the perfect spot</h2>
                        <p id="searchingStepText" style="font-size: 13px; color: #94a3b8;">Calculating routes for everyone...</p>
                    </div>
                </div>

                <!-- ═══════════════ RESULTS / VOTING ═══════════════ -->
                <div id="viewResults" class="view">
                    <div id="alertsBanner" style="display: none;"></div>

                    <div id="resultHeader" class="result-header">
                        <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                            <div style="flex: 1; min-width: 0;">
                                <p id="resultSubtitle" style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Best meeting point</p>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <span id="resultIcon" style="font-size: 24px;"></span>
                                    <h2 id="resultName" style="font-size: 20px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></h2>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <span id="resultTypeBadge" style="display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: rgba(255,255,255,0.2);"></span>
                                    <span id="resultCuisineBadge" style="display: none; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: rgba(255,255,255,0.25);"></span>
                                    <span id="resultAddress" style="font-size: 12px; opacity: 0.8;"></span>
                                </div>
                            </div>
                            <a id="resultMapLink" href="#" target="_blank" style="flex-shrink: 0; margin-left: 12px; padding: 6px 12px; background: rgba(255,255,255,0.2); border-radius: 10px; font-size: 12px; font-weight: 500; color: white; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Map
                            </a>
                        </div>
                    </div>

                    <!-- Navigation + Vote -->
                    <div style="padding: 10px 20px;">
                        <div id="voteDots" style="display: flex; justify-content: center; gap: 6px; margin-bottom: 10px;"></div>
                        <div id="voteArea" style="display: flex; gap: 8px;">
                            <button type="button" id="voteNoBtn" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; color: #64748b; cursor: pointer; font-family: inherit; transition: all 0.15s; flex-shrink: 0;">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button type="button" id="voteYesBtn" class="vote-btn voteable">
                                <span id="voteYesBtnText">Let's go!</span>
                                <span id="voteYesBtnCount" class="vote-count" style="display: none; background: rgba(255,255,255,0.25); color: white;"></span>
                            </button>
                            <button type="button" id="voteNextBtn" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; color: #64748b; cursor: pointer; font-family: inherit; transition: all 0.15s; flex-shrink: 0;">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>

                    <div id="reviewBadge" style="display: none; padding: 0 20px 6px;">
                        <div id="reviewBadgeInner" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;"></div>
                    </div>

                    <div id="venueInfoSection" style="display: none; padding: 4px 20px 8px;">
                        <div id="venueInfoLinks" style="display: flex; flex-direction: column; gap: 6px;"></div>
                    </div>

                    <div style="padding: 12px 20px;">
                        <h3 style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Journey times</h3>
                        <div id="journeyTimes" style="display: flex; flex-direction: column; gap: 10px;"></div>
                    </div>

                    <div style="padding: 12px 20px; border-top: 1px solid #f1f5f9;">
                        <h3 style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Departure times</h3>
                        <p style="font-size: 12px; color: #94a3b8; margin-bottom: 10px;">When should everyone leave?</p>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <label for="arrivalTime" style="font-size: 13px; font-weight: 500; color: #475569; white-space: nowrap;">Meet at</label>
                            <input type="time" id="arrivalTime" class="input-field" style="flex: 1; padding: 8px 12px; font-weight: 600;">
                            <button type="button" id="arrivalNowBtn" style="padding: 8px 12px; border-radius: 10px; font-size: 12px; font-weight: 500; border: 1.5px solid #e2e8f0; background: white; color: #475569; cursor: pointer; font-family: inherit; white-space: nowrap;">Now + 1h</button>
                        </div>
                        <div id="departureTimes" style="display: none; flex-direction: column; gap: 8px;"></div>
                    </div>

                    <div style="margin: 0 20px; padding: 14px; background: #f8fafc; border-radius: 14px; display: flex; text-align: center;">
                        <div style="flex: 1;">
                            <p style="font-size: 11px; color: #94a3b8;">Longest</p>
                            <p id="statMax" style="font-size: 16px; font-weight: 700; color: #0f172a;"></p>
                        </div>
                        <div style="width: 1px; background: #e2e8f0;"></div>
                        <div style="flex: 1;">
                            <p style="font-size: 11px; color: #94a3b8;">Shortest</p>
                            <p id="statMin" style="font-size: 16px; font-weight: 700; color: #0f172a;"></p>
                        </div>
                        <div style="width: 1px; background: #e2e8f0;"></div>
                        <div style="flex: 1;">
                            <p style="font-size: 11px; color: #94a3b8;">Spread</p>
                            <p id="statSpread" style="font-size: 16px; font-weight: 700; color: #0f172a;"></p>
                        </div>
                    </div>

                    <div id="fareSplitCompact" style="display: none; margin: 8px 20px 0; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;">
                        <div id="fareSplitResult" style="font-size: 12px; color: #15803d; line-height: 1.6;"></div>
                    </div>

                    <div id="liveTrackerSection" style="display: none; margin: 10px 20px 0;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; animation: tracker-pulse 2s ease-in-out infinite;"></span>
                            <h3 style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Live — Who's where?</h3>
                        </div>
                        <div id="liveTrackerList" style="display: flex; flex-direction: column; gap: 4px;"></div>
                    </div>

                    <div style="height: 20px;"></div>
                </div>

                <!-- ═══════════════ CONFIRMED ═══════════════ -->
                <div id="viewConfirmed" class="view">
                    <div id="confirmedConfetti" style="position: relative; overflow: hidden; height: 0;"></div>
                    <div style="padding: 24px 20px 0; text-align: center;">
                        <div style="font-size: 28px; margin-bottom: 8px;">🎉</div>
                        <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">It's decided!</h2>
                        <p id="confirmedVenueName" style="font-size: 15px; color: #4f46e5; font-weight: 600;"></p>
                    </div>

                    <div id="confirmedHeader" class="result-header" style="margin-top: 16px;"></div>

                    <div id="confirmedActions" style="padding: 0 20px 16px; display: flex; flex-direction: column; gap: 8px;"></div>

                    <div id="confirmedShareOverlay" style="padding: 12px 20px 16px; background: #ecfdf5; margin: 0 12px; border-radius: 14px;">
                        <p style="font-size: 13px; font-weight: 600; color: #059669; margin-bottom: 10px; text-align: center;">Share this plan</p>
                        <div style="display: flex; gap: 8px;">
                            <a id="confirmedWhatsAppBtn" href="#" target="_blank" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 11px; background: #25D366; color: white; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; font-family: inherit;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WhatsApp
                            </a>
                            <button type="button" id="confirmedCopyBtn" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 11px; background: #4f46e5; color: white; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                Copy link
                            </button>
                        </div>
                        <p id="confirmedCopyFeedback" style="display: none; font-size: 12px; color: #059669; text-align: center; margin-top: 8px; font-weight: 500;"></p>
                    </div>

                    <div id="confirmedJourneySection" style="padding: 12px 20px;">
                        <h3 style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Journey times</h3>
                        <div id="confirmedJourneyTimes" style="display: flex; flex-direction: column; gap: 10px;"></div>
                    </div>

                    <div id="confirmedTrackerSection" style="display: none; margin: 10px 20px;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; animation: tracker-pulse 2s ease-in-out infinite;"></span>
                            <h3 style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Live — Who's where?</h3>
                        </div>
                        <div id="confirmedTrackerList" style="display: flex; flex-direction: column; gap: 4px;"></div>
                    </div>

                    <div style="height: 20px;"></div>
                </div>

                <!-- ═══════════════ MANUAL POSTCODE MODE ═══════════════ -->
                <div id="viewManual" class="view">
                    <div style="padding: 20px 20px 0; text-align: center;">
                        <div style="height: 56px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                            <img src="{{ asset('midwayLogo.png') }}" alt="Midway Logo" width="200">
                        </div>
                        <p style="font-size: 14px; color: #475569; line-height: 1.5; margin-bottom: 14px;">
                            Find the <strong style="color: #4f46e5;">fairest spot</strong> to meet in London.
                        </p>
                    </div>

                    <form id="manualForm" style="padding: 0 20px 20px;">
                        <div id="postcodeInputs" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;">
                            <div class="postcode-row" style="display: flex; align-items: center; gap: 10px;">
                                <div class="person-marker" style="width: 28px; height: 28px; font-size: 12px; flex-shrink: 0; box-shadow: none; border: 2px solid #e0e7ff;">1</div>
                                <input type="text" placeholder="e.g. SW1A 1AA" class="input-field" name="postcode" required>
                                <button type="button" class="gps-btn" title="Use my location">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v2m0 16v2M2 12h2m16 0h2m-5 0a5 5 0 11-10 0 5 5 0 0110 0zm-3 0a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </button>
                                <button type="button" class="remove-btn" style="display: none; background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;" title="Remove">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="postcode-row" style="display: flex; align-items: center; gap: 10px;">
                                <div class="person-marker" style="width: 28px; height: 28px; font-size: 12px; flex-shrink: 0; box-shadow: none; border: 2px solid #e0e7ff;">2</div>
                                <input type="text" placeholder="e.g. E1 6AN" class="input-field" name="postcode" required>
                                <button type="button" class="gps-btn" title="Use my location">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v2m0 16v2M2 12h2m16 0h2m-5 0a5 5 0 11-10 0 5 5 0 0110 0zm-3 0a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </button>
                                <button type="button" class="remove-btn" style="display: none; background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;" title="Remove">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <button type="button" id="addPersonBtn" style="width: 100%; padding: 10px; border: 2px dashed #e2e8f0; border-radius: 12px; background: transparent; color: #94a3b8; font-size: 13px; font-weight: 500; cursor: pointer; margin-bottom: 16px; font-family: inherit; transition: all 0.15s;">
                            + Add another person
                        </button>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 11px; font-weight: 600; color: #94a3b8; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">What's the vibe?</label>
                            <div id="manualOccasionSelector" class="occasion-grid"></div>
                            <input type="hidden" name="occasion" id="manualOccasionInput" value="casual">
                        </div>

                        <button type="submit" id="manualSubmitBtn" class="btn-primary">
                            <span id="manualSubmitText">Find the perfect spot</span>
                            <span id="manualSubmitLoading" style="display: none; align-items: center; justify-content: center; gap: 8px;">
                                <svg style="animation: spin 1s linear infinite; width: 18px; height: 18px;" viewBox="0 0 24 24"><circle style="opacity: 0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path style="opacity: 0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <span id="manualLoadingStepText">Locating postcodes...</span>
                            </span>
                        </button>
                    </form>

                    <div id="manualError" style="display: none; margin: 0 20px 16px; padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; color: #dc2626; font-size: 13px;">
                        <p id="manualErrorText"></p>
                    </div>

                    <div style="padding: 0 20px 20px;">
                        <button type="button" id="backToLandingBtn" class="btn-ghost">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Back
                        </button>
                    </div>
                </div>

            </div>

            <div style="padding: 10px 20px; font-size: 10px; color: #94a3b8; text-align: center; border-top: 1px solid #f1f5f9;">
                Midway &middot; Powered by TfL &amp; OpenStreetMap
            </div>
        </div>
    </div>

    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebApplication",
        "name": "Midway",
        "url": "{{ config('app.url') }}",
        "description": "Find the fairest meeting spot in London. Enter postcodes, pick the vibe, get TfL directions, fare splitting, and live arrival tracking.",
        "applicationCategory": "TravelApplication",
        "operatingSystem": "Any",
        "offers": { "@@type": "Offer", "price": "0", "priceCurrency": "GBP" }
    }
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

    // ═══════════════════════════════════════
    //  CONSTANTS
    // ═══════════════════════════════════════
    const venueThemes = {
        pub:           { label: 'Pub',           icon: '🍺', emoji: '🍻', headerGradient: 'linear-gradient(135deg, #92400e, #78350f)', barColor: '#f59e0b' },
        cafe:          { label: 'Cafe',          icon: '☕', emoji: '🧁', headerGradient: 'linear-gradient(135deg, #9a3412, #7c2d12)', barColor: '#f97316' },
        restaurant:    { label: 'Restaurant',    icon: '🍽️', emoji: '🥂', headerGradient: 'linear-gradient(135deg, #9f1239, #881337)', barColor: '#f43f5e' },
        station:       { label: 'Station',       icon: '🚂', emoji: '🗺️', headerGradient: 'linear-gradient(135deg, #334155, #1e293b)', barColor: '#0ea5e9' },
        entertainment: { label: 'Entertainment', icon: '🎳', emoji: '🎉', headerGradient: 'linear-gradient(135deg, #7e22ce, #a21caf)', barColor: '#a855f7' },
        any:           { label: 'Anywhere',      icon: '📍', emoji: '✨', headerGradient: 'linear-gradient(135deg, #4f46e5, #7c3aed)', barColor: '#6366f1' },
        other:         { label: 'Venue',         icon: '📍', emoji: '✨', headerGradient: 'linear-gradient(135deg, #4f46e5, #7c3aed)', barColor: '#6366f1' },
    };

    const occasions = {
        casual:      { label: 'Drinks',      icon: '🍻', bg: '#fef3c7', desc: 'Pubs & bars',           subtitle: 'Your casual hangout' },
        date:        { label: 'Date night',  icon: '🌹', bg: '#ffe4e6', desc: 'Restaurants & cafes',    subtitle: 'Perfect for date night' },
        coffee:      { label: 'Coffee',      icon: '☕', bg: '#fef9c3', desc: 'Cafes & chill spots',    subtitle: 'Coffee & chat at' },
        celebration: { label: 'Fun & games', icon: '🎳', bg: '#f3e8ff', desc: 'Bowling, cinema & more', subtitle: 'Entertainment pick' },
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const loadingSteps = [
        { text: 'Locating postcodes...', delay: 0 },
        { text: 'Finding your area...', delay: 2000 },
        { text: 'Searching venues...', delay: 5000 },
        { text: 'Calculating routes...', delay: 10000 },
        { text: 'Picking the fairest spot...', delay: 18000 },
        { text: 'Almost there...', delay: 30000 },
    ];

    // ═══════════════════════════════════════
    //  STATE
    // ═══════════════════════════════════════
    let selectedOccasion = 'casual';
    let sessionId = null;
    let sessionToken = null;
    let sessionData = null;
    let isJoining = false;
    let pollInterval = null;
    let allVenues = [];
    let currentVenueIndex = 0;
    let confirmedPlanId = null;
    let trackerPollInterval = null;
    let allArrivedNotified = false;
    let isSessionMode = false;

    // ═══════════════════════════════════════
    //  MAP
    // ═══════════════════════════════════════
    const map = L.map('map', {
        center: [51.505, -0.09], zoom: 12, zoomControl: false,
        tap: true, dragging: true, touchZoom: true,
    });
    L.control.zoom({ position: 'topright' }).addTo(map);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
        maxZoom: 19,
    }).addTo(map);
    setTimeout(() => map.invalidateSize(), 300);

    const personMarkers = {};
    const venueMarkers = [];

    function createPersonIcon(number) {
        return L.divIcon({ className: '', html: `<div class="person-marker animate-drop">${number}</div>`, iconSize: [36, 36], iconAnchor: [18, 18] });
    }
    function createVenueIcon(emoji, gradient) {
        return L.divIcon({ className: '', html: `<div class="venue-marker animate-drop" style="background: ${gradient};">${emoji}</div>`, iconSize: [44, 44], iconAnchor: [22, 22] });
    }
    function clearVenueMarkers() { venueMarkers.forEach(m => map.removeLayer(m)); venueMarkers.length = 0; }
    function clearAllMarkers() { clearVenueMarkers(); Object.values(personMarkers).forEach(m => map.removeLayer(m)); Object.keys(personMarkers).forEach(k => delete personMarkers[k]); }
    function fitAllMarkers() {
        const all = [...Object.values(personMarkers), ...venueMarkers];
        if (!all.length) return;
        const group = L.featureGroup(all);
        const isMobile = window.innerWidth <= 640;
        map.invalidateSize();
        map.fitBounds(group.getBounds().pad(0.15), {
            maxZoom: 14, animate: true, duration: 0.8,
            paddingTopLeft: isMobile ? [20, 20] : [420, 20],
            paddingBottomRight: isMobile ? [20, Math.round(window.innerHeight * 0.55)] : [20, 20],
        });
    }

    // ═══════════════════════════════════════
    //  VIEW MANAGEMENT
    // ═══════════════════════════════════════
    const allViews = ['viewLanding', 'viewLocationPrompt', 'viewWaiting', 'viewSearching', 'viewResults', 'viewConfirmed', 'viewManual'];

    function showView(viewId) {
        allViews.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('active', id === viewId);
        });
        const scroll = document.querySelector('.panel-scroll');
        if (scroll) scroll.scrollTop = 0;
    }

    // ═══════════════════════════════════════
    //  OCCASION SELECTORS (landing + manual)
    // ═══════════════════════════════════════
    function buildOccasionSelector(container, inputEl) {
        Object.entries(occasions).forEach(([id, occ]) => {
            const card = document.createElement('button');
            card.type = 'button';
            card.dataset.occasion = id;
            card.className = 'occasion-card' + (id === 'casual' ? ' active' : '');
            card.innerHTML = `<div class="oc-icon" style="background: ${occ.bg};">${occ.icon}</div><div><div class="oc-label">${occ.label}</div><div class="oc-desc">${occ.desc}</div></div>`;
            container.appendChild(card);
        });
        container.addEventListener('click', function (e) {
            const card = e.target.closest('.occasion-card');
            if (!card) return;
            selectedOccasion = card.dataset.occasion;
            if (inputEl) inputEl.value = selectedOccasion;
            container.querySelectorAll('.occasion-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
        });
    }
    buildOccasionSelector(document.getElementById('occasionSelector'), null);
    buildOccasionSelector(document.getElementById('manualOccasionSelector'), document.getElementById('manualOccasionInput'));

    // ═══════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════
    function formatDuration(mins) {
        if (mins >= 60) { const h = Math.floor(mins / 60), m = mins % 60; return m > 0 ? `${h}h ${m}m` : `${h}h`; }
        return `${mins} min`;
    }
    function getTheme(type) { return venueThemes[type] || venueThemes['other']; }
    function modeIconClass(mode) {
        const m = (mode || '').toLowerCase();
        if (m === 'walking' || m === 'walk') return 'walking';
        if (m.includes('tube') || m.includes('underground')) return 'tube';
        if (m.includes('bus')) return 'bus';
        if (m.includes('rail') || m.includes('overground') || m.includes('dlr') || m.includes('elizabeth')) return 'rail';
        return 'other';
    }
    function modeEmoji(mode) {
        const m = (mode || '').toLowerCase();
        if (m === 'walking' || m === 'walk') return '🚶';
        if (m.includes('tube') || m.includes('underground')) return '🚇';
        if (m.includes('bus')) return '🚌';
        if (m.includes('rail') || m.includes('overground') || m.includes('dlr') || m.includes('elizabeth')) return '🚂';
        return '🔄';
    }
    function padTime(n) { return String(n).padStart(2, '0'); }
    function subtractMinutes(timeStr, mins) {
        const [h, m] = timeStr.split(':').map(Number);
        const total = ((h * 60 + m - mins) % 1440 + 1440) % 1440;
        return `${padTime(Math.floor(total / 60))}:${padTime(total % 60)}`;
    }
    function formatTime12h(timeStr) {
        const [h, m] = timeStr.split(':').map(Number);
        return `${h === 0 ? 12 : h > 12 ? h - 12 : h}:${padTime(m)} ${h >= 12 ? 'pm' : 'am'}`;
    }
    function haversineMetres(lat1, lng1, lat2, lng2) {
        const R = 6371000, dLat = (lat2 - lat1) * Math.PI / 180, dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }
    function fmtDist(m) { if (m < 50) return 'Here!'; if (m < 200) return 'Nearly there'; const mi = m / 1609.34; return mi < 0.3 ? Math.round(m) + 'm away' : mi.toFixed(1) + ' mi away'; }

    async function reverseGeocode(lat, lng) {
        const resp = await fetch(`https://api.postcodes.io/postcodes?lon=${lng}&lat=${lat}&limit=1`);
        const data = await resp.json();
        return data?.result?.[0]?.postcode || null;
    }

    async function geocodePostcode(postcode) {
        const cleaned = postcode.trim().replace(/\s+/g, '');
        if (cleaned.length < 3) return null;
        const res = await fetch(`https://api.postcodes.io/postcodes/${encodeURIComponent(cleaned)}`);
        const data = await res.json();
        if (data.status === 200 && data.result) return { lat: data.result.latitude, lng: data.result.longitude, postcode: data.result.postcode };
        return null;
    }

    // ═══════════════════════════════════════
    //  RENDERING: Venue details
    // ═══════════════════════════════════════
    function renderLegs(legs, disruptions) {
        let html = '';
        (legs || []).forEach(leg => {
            const iconCls = modeIconClass(leg.mode);
            const emoji = modeEmoji(leg.mode);
            const linePill = leg.line ? `<span style="display:inline-block;padding:1px 7px;border-radius:6px;font-size:10px;font-weight:600;background:#e0e7ff;color:#3730a3;margin-left:6px;">${leg.line}</span>` : '';
            html += `<div class="leg-step"><div class="leg-mode-icon ${iconCls}">${emoji}</div><div style="flex:1;min-width:0;"><p style="font-size:12px;color:#334155;line-height:1.4;">${leg.summary}${linePill}</p><p style="font-size:11px;color:#94a3b8;">${formatDuration(leg.duration)}</p></div></div>`;
        });
        (disruptions || []).forEach(d => { html += `<div class="disruption-pill" style="margin-top:6px;">⚠️ ${d.description}</div>`; });
        return html;
    }

    function renderVenueResult(venue) {
        const theme = getTheme(venue.type);
        clearVenueMarkers();

        const venueIcon = createVenueIcon(theme.icon, theme.headerGradient);
        const marker = L.marker([venue.lat, venue.lng], { icon: venueIcon, zIndexOffset: 1000 }).addTo(map);
        marker.bindTooltip(venue.name, { direction: 'top', offset: [0, -24] });
        venueMarkers.push(marker);

        const occasionData = occasions[selectedOccasion];
        document.getElementById('resultHeader').style.background = theme.headerGradient;
        document.getElementById('resultSubtitle').textContent = occasionData ? occasionData.subtitle : 'Best meeting point';
        document.getElementById('resultIcon').textContent = theme.icon;
        document.getElementById('resultName').textContent = venue.name;
        document.getElementById('resultTypeBadge').textContent = `${theme.emoji}  ${venue.subcategory || theme.label}`;
        const cuisineBadge = document.getElementById('resultCuisineBadge');
        if (venue.cuisine) { cuisineBadge.textContent = venue.cuisine; cuisineBadge.style.display = 'inline-block'; } else { cuisineBadge.style.display = 'none'; }
        document.getElementById('resultAddress').textContent = venue.address || '';
        document.getElementById('resultMapLink').href = `https://www.google.com/maps/search/?api=1&query=${venue.lat},${venue.lng}`;

        // Journey times
        const container = document.getElementById('journeyTimes');
        container.innerHTML = '';
        const maxTime = venue.max;
        (venue.times || []).forEach(t => {
            const pct = maxTime > 0 ? (t.duration / maxTime) * 100 : 0;
            const hasLegs = t.legs && t.legs.length > 0;
            const item = document.createElement('div');
            item.className = 'journey-item';
            const fareTag = t.fare ? `£${(t.fare.total_pence / 100).toFixed(2)}` : '';
            item.innerHTML = `
                <div class="journey-row" style="padding: 6px 0;"><span style="font-size:13px;font-weight:500;color:#475569;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:72px;">${t.from}</span><div style="flex:1;min-width:20px;max-width:120px;"><div class="journey-bar-bg"><div class="journey-bar-fill" style="width:${pct}%;background:${theme.barColor};"></div></div></div><span style="font-size:13px;font-weight:600;color:#1e293b;flex-shrink:0;white-space:nowrap;">${formatDuration(t.duration)}${fareTag ? ` <span style="font-size:11px;font-weight:600;color:#059669;">· ${fareTag}</span>` : ''}</span>${hasLegs ? '<svg class="chevron-toggle" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M6 9l6 6 6-6"/></svg>' : ''}</div>
                ${hasLegs ? `<div class="journey-legs" style="padding:4px 0 8px 0;margin-left:4px;">${renderLegs(t.legs, t.disruptions)}</div>` : ''}`;
            if (hasLegs) item.addEventListener('click', () => { const legs = item.querySelector('.journey-legs'); const chev = item.querySelector('.chevron-toggle'); if (legs) { legs.classList.toggle('open'); if (chev) chev.classList.toggle('open'); } });
            container.appendChild(item);
        });

        // Stats
        document.getElementById('statMax').textContent = formatDuration(venue.max);
        document.getElementById('statMin').textContent = formatDuration(venue.min);
        document.getElementById('statSpread').textContent = formatDuration(venue.spread);

        renderFareSplit(venue);
        renderVenueInfo(venue);
        fetchReviewBadge(venue);
        currentVenueTimes = venue.times || [];
        currentVenueTheme = theme;
    }

    let currentVenueTimes = [];
    let currentVenueTheme = null;

    function renderFareSplit(venue) {
        const section = document.getElementById('fareSplitCompact');
        const result = document.getElementById('fareSplitResult');
        const fares = venue.times.map(t => ({ from: t.from, pence: t.fare?.total_pence ?? null }));
        if (!fares.every(f => f.pence !== null)) { section.style.display = 'none'; return; }
        const totalPence = fares.reduce((sum, f) => sum + f.pence, 0);
        const fairSharePence = Math.round(totalPence / fares.length);
        const diffs = fares.map(f => ({ from: f.from, diff: f.pence - fairSharePence }));
        const overpayers = diffs.filter(d => d.diff > 0).sort((a, b) => b.diff - a.diff);
        const underpayers = diffs.filter(d => d.diff < 0).sort((a, b) => a.diff - b.diff);
        let oi = 0, ui = 0;
        const overB = overpayers.map(o => ({ ...o, remaining: o.diff }));
        const underB = underpayers.map(u => ({ ...u, remaining: -u.diff }));
        const settlements = [];
        while (oi < overB.length && ui < underB.length) {
            const amount = Math.min(overB[oi].remaining, underB[ui].remaining);
            if (amount > 0) settlements.push({ payer: underB[ui].from, payee: overB[oi].from, pence: amount });
            overB[oi].remaining -= amount; underB[ui].remaining -= amount;
            if (overB[oi].remaining <= 0) oi++; if (underB[ui].remaining <= 0) ui++;
        }
        let html = `<span style="font-weight:600;">💳 Total: £${(totalPence / 100).toFixed(2)}</span> · <span>Fair share: £${(fairSharePence / 100).toFixed(2)} each</span>`;
        if (settlements.length > 0) html += '<br>' + settlements.map(s => `<span style="font-weight:500;">${s.payer}</span> → ${s.payee} <strong>£${(s.pence / 100).toFixed(2)}</strong>`).join(' · ');
        result.innerHTML = html; section.style.display = 'block';
    }

    function buildMenuUrl(venue) {
        if (venue.website) {
            const w = venue.website.replace(/\/$/, ''), host = w.toLowerCase();
            if (host.includes('wagamama')) return w + '/our-menu'; if (host.includes('francomanca')) return w + '/menu';
            if (host.includes('pizzaexpress')) return w + '/menu'; if (host.includes('nandos')) return w + '/food/menu';
            if (host.includes('prezzo')) return w + '/menu'; if (host.includes('zizzi')) return w + '/menu';
            if (host.includes('dishoom')) return w + '/menus'; return w;
        }
        return `https://www.google.com/search?q=${encodeURIComponent(venue.name + ' menu ' + (venue.address || 'London'))}`;
    }
    function buildShowtimesUrl(venue) { return venue.website || `https://www.google.com/search?q=${encodeURIComponent(venue.name + ' showtimes today')}`; }
    function buildBookingUrl(venue) { return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(venue.name + ' ' + (venue.address || 'London'))}`; }

    function renderVenueInfo(venue) {
        const section = document.getElementById('venueInfoSection');
        const container = document.getElementById('venueInfoLinks');
        container.innerHTML = '';
        const items = [];
        const isCinema = (venue.subcategory || '').toLowerCase() === 'cinema';
        const isTheatre = (venue.subcategory || '').toLowerCase() === 'theatre';
        const isFood = venue.type === 'restaurant' || venue.type === 'cafe';

        if (venue.cuisine) items.push(`<div style="display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;background:#fef3c7;border:1px solid #fde68a;"><span style="font-size:16px;">🍽️</span><span style="font-size:14px;font-weight:600;color:#92400e;">${venue.cuisine}</span></div>`);
        if (isFood) {
            const menuUrl = buildMenuUrl(venue);
            items.push(`<a href="${menuUrl}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;font-size:13px;font-weight:600;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;text-decoration:none;">📖 <span style="flex:1">${venue.website ? 'View Menu' : 'Menu & Website'}</span><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>`);
        }
        if (isCinema) items.push(`<a href="${buildShowtimesUrl(venue)}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;font-size:13px;font-weight:600;background:#fdf4ff;color:#86198f;border:1px solid #f0abfc;text-decoration:none;">🎬 <span style="flex:1">What's On — Showtimes</span><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>`);
        if (isTheatre) items.push(`<a href="${venue.website || `https://www.google.com/search?q=${encodeURIComponent(venue.name + ' whats on today')}`}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;font-size:13px;font-weight:600;background:#fefce8;color:#854d0e;border:1px solid #fde68a;text-decoration:none;">🎭 <span style="flex:1">What's On — Shows & Tickets</span><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>`);
        if (venue.website && !isFood && !isCinema && !isTheatre) items.push(`<a href="${venue.website}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:500;background:#f8fafc;color:#334155;border:1px solid #e2e8f0;text-decoration:none;">🔗 <span style="flex:1">Website</span></a>`);
        if (venue.phone) items.push(`<a href="tel:${venue.phone}" style="display:flex;align-items:center;gap:10px;padding:8px 14px;font-size:13px;color:#334155;text-decoration:none;">📞 <span>${venue.phone}</span></a>`);

        if (!items.length) { section.style.display = 'none'; return; }
        section.style.display = 'block';
        container.innerHTML = items.join('');
    }

    async function fetchReviewBadge(venue) {
        const badge = document.getElementById('reviewBadge');
        const inner = document.getElementById('reviewBadgeInner');
        badge.style.display = 'none';
        try {
            const params = new URLSearchParams({ name: venue.name, lat: venue.lat, lng: venue.lng });
            const resp = await fetch(`/api/venue-reviews?${params}`);
            const data = await resp.json();
            if (!data.has_reviews) return;
            inner.style.background = data.color + '18'; inner.style.color = data.color;
            inner.innerHTML = `<span>⭐</span> <span>${data.label}</span> <span style="opacity:0.7;font-weight:400;">(${data.total} ${data.total === 1 ? 'review' : 'reviews'})</span>`;
            badge.style.display = 'block';
        } catch (_) {}
    }

    function renderAlerts(alerts) {
        const banner = document.getElementById('alertsBanner');
        banner.innerHTML = '';
        if (!alerts || !alerts.length) { banner.style.display = 'none'; return; }
        banner.style.display = 'block';
        alerts.forEach(a => {
            const reason = a.reason ? `<br><span style="font-weight:400;opacity:0.85;">${a.reason}</span>` : '';
            banner.insertAdjacentHTML('beforeend', `<div class="alert-banner"><span style="font-size:16px;flex-shrink:0;">⚠️</span><div><strong>${a.line}: ${a.status}</strong>${reason}</div></div>`);
        });
    }

    // ═══════════════════════════════════════
    //  RENDERING: Confirmed venue
    // ═══════════════════════════════════════
    function renderConfirmedVenue(venue) {
        const theme = getTheme(venue.type);
        document.getElementById('confirmedVenueName').textContent = venue.name;

        // Header
        const hdr = document.getElementById('confirmedHeader');
        hdr.style.background = theme.headerGradient;
        const isCinema = (venue.subcategory || '').toLowerCase() === 'cinema';
        const isTheatre = (venue.subcategory || '').toLowerCase() === 'theatre';
        const isFood = venue.type === 'restaurant' || venue.type === 'cafe';
        hdr.innerHTML = `<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;"><span style="font-size:24px;">${theme.icon}</span><h2 style="font-size:20px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${venue.name}</h2></div><div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;"><span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(255,255,255,0.2);">${theme.emoji}  ${venue.subcategory || theme.label}</span>${venue.address ? `<span style="font-size:12px;opacity:0.8;">${venue.address}</span>` : ''}</div>`;

        // Action buttons
        const actions = document.getElementById('confirmedActions');
        actions.innerHTML = '';
        if (isFood) actions.innerHTML += `<a href="${buildBookingUrl(venue)}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;border-radius:12px;font-size:14px;font-weight:600;background:#059669;color:white;text-decoration:none;box-shadow:0 2px 8px rgba(5,150,105,0.3);">📖 Book a table</a>`;
        if (isCinema) actions.innerHTML += `<a href="${buildShowtimesUrl(venue)}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;border-radius:12px;font-size:14px;font-weight:600;background:#7e22ce;color:white;text-decoration:none;box-shadow:0 2px 8px rgba(126,34,206,0.3);">🎬 View Showtimes</a>`;
        if (isTheatre) actions.innerHTML += `<a href="${venue.website || `https://www.google.com/search?q=${encodeURIComponent(venue.name + ' whats on today')}`}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;border-radius:12px;font-size:14px;font-weight:600;background:#854d0e;color:white;text-decoration:none;">🎭 Shows & Tickets</a>`;
        actions.innerHTML += `<a href="https://www.google.com/maps/dir/?api=1&destination=${venue.lat},${venue.lng}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:12px;border-radius:12px;font-size:13px;font-weight:600;background:#f8fafc;color:#334155;border:1.5px solid #e2e8f0;text-decoration:none;">🗺️ Get directions</a>`;

        // Journey times
        const jContainer = document.getElementById('confirmedJourneyTimes');
        jContainer.innerHTML = '';
        (venue.times || []).forEach(t => {
            const pct = venue.max > 0 ? (t.duration / venue.max) * 100 : 0;
            jContainer.innerHTML += `<div style="display:flex;align-items:center;gap:12px;padding:6px 0;"><span style="font-size:13px;font-weight:500;color:#475569;flex-shrink:0;max-width:72px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${t.from}</span><div style="flex:1;min-width:20px;max-width:120px;"><div class="journey-bar-bg"><div class="journey-bar-fill" style="width:${pct}%;background:${theme.barColor};"></div></div></div><span style="font-size:13px;font-weight:600;color:#1e293b;">${formatDuration(t.duration)}</span></div>`;
        });

        // Map
        clearVenueMarkers();
        const mk = L.marker([venue.lat, venue.lng], { icon: createVenueIcon(theme.icon, theme.headerGradient), zIndexOffset: 1000 }).addTo(map);
        mk.bindTooltip(venue.name, { direction: 'top', offset: [0, -24] });
        venueMarkers.push(mk);
        fitAllMarkers();
    }

    function showConfetti() {
        const container = document.getElementById('confirmedConfetti');
        container.style.height = '60px';
        const colors = ['#4f46e5', '#22c55e', '#f59e0b', '#f43f5e', '#a855f7'];
        for (let i = 0; i < 30; i++) {
            const piece = document.createElement('div');
            piece.className = 'confetti-piece';
            piece.style.left = Math.random() * 100 + '%';
            piece.style.top = Math.random() * 20 + 'px';
            piece.style.background = colors[Math.floor(Math.random() * colors.length)];
            piece.style.animationDelay = Math.random() * 0.5 + 's';
            container.appendChild(piece);
        }
        setTimeout(() => { container.innerHTML = ''; container.style.height = '0'; }, 2000);
    }

    // ═══════════════════════════════════════
    //  SESSION FLOW
    // ═══════════════════════════════════════
    async function createSession(location) {
        const body = { occasion: selectedOccasion };
        if (location) body.location = location;
        const resp = await fetch('/api/session', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(body),
        });
        const data = await resp.json();
        sessionId = data.id;
        sessionToken = data.token;
        sessionData = data.session;
        isSessionMode = true;
        localStorage.setItem('midway_session', JSON.stringify({ id: sessionId, token: sessionToken }));
        updateInviteLinks();
        renderParticipants();
        showView('viewWaiting');
        startSessionPoll();

        if (location && location.lat && location.lng) {
            addPersonMarker(0, location.lat, location.lng, location.postcode || 'You');
        }
    }

    async function joinSession(id, location) {
        const body = {};
        if (location.lat && location.lng) { body.lat = location.lat; body.lng = location.lng; }
        if (location.postcode) body.postcode = location.postcode;
        const resp = await fetch(`/api/session/${id}/join`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(body),
        });
        if (!resp.ok) { const err = await resp.json(); alert(err.error || 'Could not join session.'); return; }
        const data = await resp.json();
        sessionId = id;
        sessionToken = data.token;
        sessionData = data.session;
        isSessionMode = true;
        localStorage.setItem('midway_session', JSON.stringify({ id: sessionId, token: sessionToken }));
        handleSessionState(data.session);
        startSessionPoll();
    }

    function startSessionPoll() {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(async () => {
            if (!sessionId) return;
            try {
                const resp = await fetch(`/api/session/${sessionId}`, { headers: { 'X-Session-Token': sessionToken || '' } });
                if (!resp.ok) return;
                const data = await resp.json();
                sessionData = data.session;
                handleSessionState(data.session);
            } catch (_) {}
        }, 3000);
    }

    function handleSessionState(session) {
        renderParticipants();
        updateMarkers(session);

        const currentView = document.querySelector('.view.active')?.id;

        if (session.status === 'waiting' && currentView !== 'viewLocationPrompt') {
            if (currentView !== 'viewWaiting') showView('viewWaiting');
            updateInviteLinks();
        } else if (session.status === 'searching') {
            if (currentView !== 'viewSearching') showView('viewSearching');
        } else if (session.status === 'results') {
            allVenues = session.venues || [];
            selectedOccasion = session.occasion || 'casual';
            if (currentView !== 'viewResults') {
                currentVenueIndex = 0;
                renderAlerts(session.alerts || []);
                if (allVenues.length > 0) renderVenueResult(allVenues[0]);
                showView('viewResults');
            }
            updateVoteUI(session);
        } else if (session.status === 'confirmed' && session.confirmed_venue) {
            if (currentView !== 'viewConfirmed') {
                confirmedPlanId = session.plan_id;
                renderConfirmedVenue(session.confirmed_venue);
                showView('viewConfirmed');
                showConfetti();
                setupConfirmedShare(session);
                if (confirmedPlanId) startTrackerPoll();
            }
        }
    }

    function updateMarkers(session) {
        (session.participants || []).forEach((p, i) => {
            if (p.lat && p.lng) addPersonMarker(i, p.lat, p.lng, p.postcode);
        });
    }

    function addPersonMarker(index, lat, lng, label) {
        if (personMarkers[index]) {
            personMarkers[index].setLatLng([lat, lng]);
        } else {
            personMarkers[index] = L.marker([lat, lng], { icon: createPersonIcon(index + 1) }).addTo(map);
        }
        personMarkers[index].bindTooltip(label || `Person ${index + 1}`, { direction: 'top', offset: [0, -20] });
        fitAllMarkers();
    }

    function renderParticipants() {
        if (!sessionData) return;
        const list = document.getElementById('participantList');
        list.innerHTML = '';
        (sessionData.participants || []).forEach((p, i) => {
            list.innerHTML += `<div class="participant-pill"><div class="participant-dot" style="background: #4f46e5;"></div><span style="font-weight: 600;">${p.postcode}</span><span style="color: #94a3b8; font-size: 12px;">Person ${i + 1}</span></div>`;
        });
        (sessionData.manual_postcodes || []).forEach(pc => {
            list.innerHTML += `<div class="participant-pill"><div class="participant-dot" style="background: #94a3b8;"></div><span style="font-weight: 600;">${pc}</span><span style="color: #94a3b8; font-size: 12px;">Added manually</span></div>`;
        });
        const total = (sessionData.participants?.length || 0) + (sessionData.manual_postcodes?.length || 0);
        document.getElementById('waitingSubtext').textContent = total < 2
            ? `${total} person so far — need at least 2 to start`
            : `${total} people — searching will start automatically`;
    }

    function updateInviteLinks() {
        if (!sessionId) return;
        const url = `${window.location.origin}/s/${sessionId}`;
        const text = `Let's find a spot to meet! Join my Midway session: ${url}`;
        document.getElementById('inviteWhatsAppBtn').onclick = () => window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
        document.getElementById('inviteCopyBtn').onclick = () => {
            navigator.clipboard.writeText(url).then(() => {
                const fb = document.getElementById('inviteCopyFeedback');
                fb.textContent = 'Link copied!'; fb.style.display = 'block';
                setTimeout(() => fb.style.display = 'none', 2500);
            });
        };
    }

    function updateVoteUI(session) {
        const voteCounts = session.vote_counts || {};
        const myVote = session.my_vote;
        const total = session.participant_count || 0;

        // Navigation dots
        const dots = document.getElementById('voteDots');
        dots.innerHTML = '';
        if (allVenues.length > 1) {
            allVenues.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                const count = voteCounts[i] || 0;
                const isActive = i === currentVenueIndex;
                dot.style.cssText = `width:${isActive ? '20px' : (count > 0 ? '12px' : '8px')};height:8px;border-radius:4px;border:none;cursor:pointer;transition:all 0.2s;background:${isActive ? '#4f46e5' : (count > 0 ? '#a5b4fc' : '#cbd5e1')};`;
                dot.addEventListener('click', () => navigateToVenue(i));
                dots.appendChild(dot);
            });
        }

        // Vote button
        const btn = document.getElementById('voteYesBtn');
        const btnText = document.getElementById('voteYesBtnText');
        const btnCount = document.getElementById('voteYesBtnCount');
        const count = voteCounts[currentVenueIndex] || 0;

        if (isSessionMode && total > 1) {
            if (myVote === currentVenueIndex) {
                btn.className = 'vote-btn voted';
                btnText.textContent = 'Your pick ✓';
            } else {
                btn.className = 'vote-btn voteable';
                btnText.textContent = 'Vote for this spot';
            }
            if (count > 0) { btnCount.textContent = count; btnCount.style.display = 'inline-flex'; }
            else { btnCount.style.display = 'none'; }
        } else {
            btn.className = 'vote-btn voteable';
            btnText.innerHTML = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Let\'s go!';
            btnCount.style.display = 'none';
        }

        // Nav button states
        document.getElementById('voteNoBtn').style.opacity = allVenues.length > 1 ? '1' : '0.3';
        document.getElementById('voteNextBtn').style.opacity = allVenues.length > 1 ? '1' : '0.3';
    }

    function navigateToVenue(index) {
        if (allVenues.length <= 1) return;
        currentVenueIndex = index;
        renderVenueResult(allVenues[currentVenueIndex]);
        if (sessionData) updateVoteUI(sessionData);
        else updateVoteUI({ vote_counts: {}, my_vote: null, participant_count: 0 });
        fitAllMarkers();
    }

    async function castVote(venueIndex) {
        if (!sessionId || !sessionToken) return;
        try {
            const resp = await fetch(`/api/session/${sessionId}/vote`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ token: sessionToken, venue_index: venueIndex }),
            });
            const data = await resp.json();
            if (data.session) { sessionData = data.session; handleSessionState(data.session); }
        } catch (_) {}
    }

    // ═══════════════════════════════════════
    //  CONFIRMED: Share + Tracker
    // ═══════════════════════════════════════
    function setupConfirmedShare(session) {
        const venue = session.confirmed_venue;
        const planUrl = session.plan_url || '';
        const text = `Let's meet at ${venue.name}! ${planUrl}`;

        document.getElementById('confirmedWhatsAppBtn').href = `https://wa.me/?text=${encodeURIComponent(text)}`;
        document.getElementById('confirmedCopyBtn').onclick = () => {
            navigator.clipboard.writeText(planUrl).then(() => {
                const fb = document.getElementById('confirmedCopyFeedback');
                fb.textContent = 'Link copied!'; fb.style.display = 'block';
                setTimeout(() => fb.style.display = 'none', 2500);
            });
        };
    }

    function startTrackerPoll() {
        if (!confirmedPlanId) return;
        const section = document.getElementById('confirmedTrackerSection');
        section.style.display = 'block';
        pollTrackerStatuses();
        trackerPollInterval = setInterval(pollTrackerStatuses, 5000);
    }

    async function pollTrackerStatuses() {
        const planId = confirmedPlanId;
        if (!planId) return;
        try {
            const resp = await fetch(`/api/plan/${planId}/status`);
            if (!resp.ok) return;
            const data = await resp.json();
            const list = document.getElementById('confirmedTrackerList');
            list.innerHTML = '';
            let allArrived = true;
            (data.statuses || []).forEach(s => {
                const dotColor = { pending: '#d1d5db', on_my_way: '#f59e0b', arrived: '#22c55e' }[s.status] || '#d1d5db';
                let statusText = 'Waiting';
                if (s.status === 'arrived') { statusText = 'Arrived!'; }
                else if (s.status === 'on_my_way' && s.distance_metres != null) { statusText = fmtDist(s.distance_metres); }
                if (s.status !== 'arrived') allArrived = false;
                list.innerHTML += `<div class="participant-pill"><div class="participant-dot" style="background:${dotColor};"></div><span style="font-weight:600;flex:1;">${s.postcode}</span><span style="font-size:12px;color:${s.status === 'arrived' ? '#16a34a' : '#94a3b8'};font-weight:${s.status === 'arrived' ? '600' : '400'};">${statusText}</span></div>`;
            });
            if (allArrived && (data.statuses || []).length >= 2 && !allArrivedNotified) {
                allArrivedNotified = true;
                if (trackerPollInterval) { clearInterval(trackerPollInterval); trackerPollInterval = null; }
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification("Everyone's arrived! 🎉", { body: 'Have fun!' });
                }
            }
        } catch (_) {}
    }

    // ═══════════════════════════════════════
    //  MANUAL POSTCODE FLOW
    // ═══════════════════════════════════════
    let geocodeTimers = {};
    function handlePostcodeInput(input, index) {
        clearTimeout(geocodeTimers[index]);
        geocodeTimers[index] = setTimeout(async () => {
            const result = await geocodePostcode(input.value);
            if (result) addPersonMarker(index, result.lat, result.lng, result.postcode);
            else if (personMarkers[index]) { map.removeLayer(personMarkers[index]); delete personMarkers[index]; }
        }, 600);
    }

    function attachPostcodeListeners() {
        const inputs = document.getElementById('postcodeInputs').querySelectorAll('input[name="postcode"]');
        inputs.forEach((input, i) => {
            input.removeEventListener('input', input._handler);
            input._handler = () => handlePostcodeInput(input, i);
            input.addEventListener('input', input._handler);
        });
    }

    function updateRowNumbers() {
        const rows = document.getElementById('postcodeInputs').querySelectorAll('.postcode-row');
        rows.forEach((row, i) => {
            row.querySelector('.person-marker').textContent = i + 1;
            const removeBtn = row.querySelector('.remove-btn');
            removeBtn.style.display = rows.length <= 2 ? 'none' : 'block';
        });
        attachPostcodeListeners();
    }

    let manualLoadingTimer = null;
    function setManualLoading(loading) {
        const btn = document.getElementById('manualSubmitBtn');
        btn.disabled = loading;
        document.getElementById('manualSubmitText').style.display = loading ? 'none' : 'inline';
        document.getElementById('manualSubmitLoading').style.display = loading ? 'flex' : 'none';
        if (manualLoadingTimer) { clearTimeout(manualLoadingTimer); manualLoadingTimer = null; }
        if (loading) {
            const stepText = document.getElementById('manualLoadingStepText');
            stepText.textContent = loadingSteps[0].text;
            let si = 1;
            function next() {
                if (si < loadingSteps.length) {
                    manualLoadingTimer = setTimeout(() => {
                        stepText.style.transition = 'opacity 0.2s'; stepText.style.opacity = '0';
                        setTimeout(() => { stepText.textContent = loadingSteps[si].text; stepText.style.opacity = '1'; }, 200);
                        si++; next();
                    }, loadingSteps[si].delay - loadingSteps[si - 1].delay);
                }
            }
            next();
        }
    }

    // ═══════════════════════════════════════
    //  ARRIVAL PLANNER
    // ═══════════════════════════════════════
    const arrivalTimeInput = document.getElementById('arrivalTime');
    const departureTimesContainer = document.getElementById('departureTimes');

    function updateDepartureTimes() {
        const arrivalTime = arrivalTimeInput.value;
        if (!arrivalTime || !currentVenueTimes.length) { departureTimesContainer.style.display = 'none'; return; }
        departureTimesContainer.style.display = 'flex';
        departureTimesContainer.innerHTML = '';
        const sorted = [...currentVenueTimes].sort((a, b) => b.duration - a.duration);
        sorted.forEach((t, i) => {
            const leaveAt = subtractMinutes(arrivalTime, t.duration);
            const isEarliest = i === 0;
            departureTimesContainer.innerHTML += `<div style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:12px;background:${isEarliest ? '#eef2ff' : '#f8fafc'};${isEarliest ? 'border:1px solid #c7d2fe;' : ''}"><div style="flex:1;min-width:0;"><p style="font-size:13px;font-weight:500;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${t.from}</p><p style="font-size:11px;color:#94a3b8;">${formatDuration(t.duration)} journey</p></div><div style="text-align:right;flex-shrink:0;"><p style="font-size:17px;font-weight:700;color:${isEarliest ? '#4f46e5' : '#0f172a'};">${formatTime12h(leaveAt)}</p><p style="font-size:11px;color:${isEarliest ? '#6366f1' : '#94a3b8'};font-weight:${isEarliest ? '600' : '400'};">${isEarliest ? 'leaves first' : 'leave by'}</p></div></div>`;
        });
    }
    arrivalTimeInput.addEventListener('input', updateDepartureTimes);
    document.getElementById('arrivalNowBtn').addEventListener('click', function () {
        const now = new Date(); now.setHours(now.getHours() + 1);
        arrivalTimeInput.value = `${padTime(now.getHours())}:${padTime(now.getMinutes())}`;
        updateDepartureTimes();
    });

    // ═══════════════════════════════════════
    //  LIVE TRACKER (results view, manual confirmed)
    // ═══════════════════════════════════════
    let manualPlanId = null;
    let manualTrackerInterval = null;

    function startLiveTracker(planId, venue) {
        manualPlanId = planId;
        const section = document.getElementById('liveTrackerSection');
        const list = document.getElementById('liveTrackerList');
        section.style.display = 'block';
        list.innerHTML = (venue.times || []).map((t, i) => `<div class="participant-pill" data-tracker-person="${i}"><div class="participant-dot" style="background:#d1d5db;" data-tracker-dot="${i}"></div><span style="font-weight:600;flex:1;">${t.from}</span><span style="font-size:12px;color:#94a3b8;" data-tracker-dist="${i}">—</span></div>`).join('');
        pollManualTracker();
        manualTrackerInterval = setInterval(pollManualTracker, 5000);
    }

    async function pollManualTracker() {
        if (!manualPlanId) return;
        try {
            const resp = await fetch(`/api/plan/${manualPlanId}/status`);
            if (!resp.ok) return;
            const data = await resp.json();
            (data.statuses || []).forEach(s => {
                const dot = document.querySelector(`[data-tracker-dot="${s.person}"]`);
                const dist = document.querySelector(`[data-tracker-dist="${s.person}"]`);
                const dotColors = { pending: '#d1d5db', on_my_way: '#f59e0b', arrived: '#22c55e' };
                if (dot) dot.style.background = dotColors[s.status] || '#d1d5db';
                if (s.status === 'arrived') { if (dist) { dist.textContent = 'Arrived!'; dist.style.color = '#16a34a'; dist.style.fontWeight = '600'; } }
                else if (s.status === 'on_my_way' && s.distance_metres != null) { if (dist) { dist.textContent = fmtDist(s.distance_metres); dist.style.color = '#6366f1'; } }
                else { if (dist) { dist.textContent = 'Waiting'; dist.style.color = '#94a3b8'; } }
            });
        } catch (_) {}
    }

    // ═══════════════════════════════════════
    //  EVENT HANDLERS
    // ═══════════════════════════════════════

    // Landing: Start Session
    document.getElementById('startSessionBtn').addEventListener('click', () => {
        isJoining = false;
        document.getElementById('locationPromptTitle').textContent = 'Share your location?';
        document.getElementById('locationPromptDesc').textContent = "We'll use your location to find spots that are fair for everyone. It's never stored or shared.";
        showView('viewLocationPrompt');
    });

    // Landing: Go Manual
    document.getElementById('goManualBtn').addEventListener('click', () => {
        isSessionMode = false;
        showView('viewManual');
        attachPostcodeListeners();
    });

    // Location Prompt: Allow
    document.getElementById('allowLocationBtn').addEventListener('click', async () => {
        if (!navigator.geolocation) { alert('Geolocation is not supported by your browser.'); return; }
        const textEl = document.getElementById('allowLocationText');
        const loadEl = document.getElementById('allowLocationLoading');
        textEl.style.display = 'none'; loadEl.style.display = 'inline';

        navigator.geolocation.getCurrentPosition(
            async (pos) => {
                const postcode = await reverseGeocode(pos.coords.latitude, pos.coords.longitude);
                textEl.style.display = 'inline'; loadEl.style.display = 'none';
                if (!postcode) { alert('Could not find a postcode for your location. Are you in the UK?'); return; }
                const location = { lat: pos.coords.latitude, lng: pos.coords.longitude, postcode };
                if (isJoining && sessionId) { await joinSession(sessionId, location); }
                else { await createSession(location); }
            },
            (err) => {
                textEl.style.display = 'inline'; loadEl.style.display = 'none';
                if (err.code === err.PERMISSION_DENIED) alert('Location access was denied. Please allow location or type your postcode.');
                else alert('Could not determine your location. Please try again.');
            },
            { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
        );
    });

    // Location Prompt: Decline
    document.getElementById('declineLocationBtn').addEventListener('click', () => {
        document.getElementById('manualPostcodeEntry').style.display = 'block';
        document.getElementById('selfPostcodeInput').focus();
    });

    // Location Prompt: Submit postcode
    document.getElementById('submitSelfPostcodeBtn').addEventListener('click', async () => {
        const input = document.getElementById('selfPostcodeInput');
        const pc = input.value.trim();
        if (!pc) { input.focus(); return; }
        if (isJoining && sessionId) { await joinSession(sessionId, { postcode: pc }); }
        else { await createSession({ postcode: pc }); }
    });
    document.getElementById('selfPostcodeInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') document.getElementById('submitSelfPostcodeBtn').click();
    });

    // Location Prompt: Back
    document.getElementById('backFromLocationBtn').addEventListener('click', () => {
        document.getElementById('manualPostcodeEntry').style.display = 'none';
        showView('viewLanding');
    });

    // Waiting: Add postcode toggle
    document.getElementById('toggleAddPostcodeBtn').addEventListener('click', () => {
        const form = document.getElementById('addPostcodeForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        if (form.style.display === 'block') document.getElementById('addPostcodeInput').focus();
    });

    // Waiting: Add postcode submit
    document.getElementById('addPostcodeBtn').addEventListener('click', async () => {
        const input = document.getElementById('addPostcodeInput');
        const pc = input.value.trim();
        if (!pc || !sessionId) return;
        await fetch(`/api/session/${sessionId}/postcode`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ postcode: pc }),
        });
        input.value = '';
        document.getElementById('addPostcodeForm').style.display = 'none';
    });
    document.getElementById('addPostcodeInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') document.getElementById('addPostcodeBtn').click();
    });

    // Results: Navigation
    document.getElementById('voteNoBtn').addEventListener('click', () => {
        if (allVenues.length <= 1) return;
        navigateToVenue((currentVenueIndex - 1 + allVenues.length) % allVenues.length);
    });
    document.getElementById('voteNextBtn').addEventListener('click', () => {
        if (allVenues.length <= 1) return;
        navigateToVenue((currentVenueIndex + 1) % allVenues.length);
    });

    // Results: Vote / Let's Go
    document.getElementById('voteYesBtn').addEventListener('click', async () => {
        if (isSessionMode && sessionData && sessionData.participant_count > 1) {
            await castVote(currentVenueIndex);
        } else {
            // Manual mode: confirm immediately via share
            const venue = allVenues[currentVenueIndex];
            if (!venue) return;
            const btn = document.getElementById('voteYesBtn');
            btn.disabled = true;
            try {
                const resp = await fetch('/api/share', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ venue, occasion: selectedOccasion }),
                });
                const result = await resp.json();
                confirmedPlanId = result.id;
                const planUrl = result.url;
                sessionData = {
                    confirmed_venue: venue,
                    plan_id: result.id,
                    plan_url: planUrl,
                    occasion: selectedOccasion,
                };
                renderConfirmedVenue(venue);
                setupConfirmedShare(sessionData);
                showView('viewConfirmed');
                showConfetti();
                startTrackerPoll();
                startLiveTracker(result.id, venue);
            } catch (err) {
                alert('Could not create share link. Please try again.');
            } finally {
                btn.disabled = false;
            }
        }
    });

    // Manual form: postcode rows
    attachPostcodeListeners();

    document.getElementById('addPersonBtn').addEventListener('click', function () {
        const container = document.getElementById('postcodeInputs');
        const count = container.querySelectorAll('.postcode-row').length;
        const row = document.createElement('div');
        row.className = 'postcode-row';
        row.style.cssText = 'display: flex; align-items: center; gap: 10px;';
        row.innerHTML = `<div class="person-marker" style="width:28px;height:28px;font-size:12px;flex-shrink:0;box-shadow:none;border:2px solid #e0e7ff;">${count + 1}</div><input type="text" placeholder="e.g. N1 9GU" class="input-field" name="postcode" required><button type="button" class="gps-btn" title="Use my location"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v2m0 16v2M2 12h2m16 0h2m-5 0a5 5 0 11-10 0 5 5 0 0110 0zm-3 0a2 2 0 11-4 0 2 2 0 014 0z"/></svg></button><button type="button" class="remove-btn" style="background:none;border:none;color:#94a3b8;cursor:pointer;padding:4px;" title="Remove"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>`;
        container.appendChild(row);
        row.querySelector('input').focus();
        updateRowNumbers();
    });

    document.getElementById('postcodeInputs').addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.remove-btn');
        if (!removeBtn) return;
        const row = removeBtn.closest('.postcode-row');
        const rows = document.getElementById('postcodeInputs').querySelectorAll('.postcode-row');
        if (rows.length <= 2) return;
        const index = Array.from(rows).indexOf(row);
        row.remove();
        if (personMarkers[index]) { map.removeLayer(personMarkers[index]); delete personMarkers[index]; }
        const remaining = {};
        document.getElementById('postcodeInputs').querySelectorAll('.postcode-row').forEach((r, i) => {
            const oldIdx = Object.keys(personMarkers).find(k => personMarkers[k] && r.querySelector('input').value && personMarkers[k]._tooltip?._content?.includes(r.querySelector('input').value.trim().toUpperCase()));
            if (oldIdx !== undefined && personMarkers[oldIdx]) { remaining[i] = personMarkers[oldIdx]; remaining[i].setIcon(createPersonIcon(i + 1)); }
        });
        Object.keys(personMarkers).forEach(k => { if (!remaining[k] && personMarkers[k]) map.removeLayer(personMarkers[k]); });
        Object.keys(personMarkers).forEach(k => delete personMarkers[k]);
        Object.assign(personMarkers, remaining);
        updateRowNumbers(); fitAllMarkers();
    });

    // GPS in manual mode
    document.getElementById('postcodeInputs').addEventListener('click', function (e) {
        const gpsBtn = e.target.closest('.gps-btn');
        if (!gpsBtn || gpsBtn.classList.contains('locating') || !navigator.geolocation) return;
        const row = gpsBtn.closest('.postcode-row');
        const input = row.querySelector('input');
        gpsBtn.classList.add('locating');
        navigator.geolocation.getCurrentPosition(
            async (pos) => {
                try {
                    const postcode = await reverseGeocode(pos.coords.latitude, pos.coords.longitude);
                    if (postcode) { input.value = postcode; input.dispatchEvent(new Event('input', { bubbles: true })); gpsBtn.classList.remove('locating'); gpsBtn.classList.add('located'); setTimeout(() => gpsBtn.classList.remove('located'), 3000); }
                    else { gpsBtn.classList.remove('locating'); alert('Could not find a postcode for your location.'); }
                } catch { gpsBtn.classList.remove('locating'); alert('Failed to look up your postcode.'); }
            },
            (err) => { gpsBtn.classList.remove('locating'); alert(err.code === err.PERMISSION_DENIED ? 'Location access was denied.' : 'Could not determine your location.'); },
            { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
        );
    });

    // Manual form submit
    document.getElementById('manualForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const errEl = document.getElementById('manualError');
        const errText = document.getElementById('manualErrorText');
        errEl.style.display = 'none';

        const inputs = document.getElementById('postcodeInputs').querySelectorAll('input[name="postcode"]');
        const locations = Array.from(inputs).map(i => i.value.trim()).filter(v => v);
        if (locations.length < 2) { errText.textContent = 'Please enter at least 2 postcodes.'; errEl.style.display = 'block'; return; }

        setManualLoading(true);
        try {
            const resp = await fetch('/api/find', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ locations, occasion: selectedOccasion }),
            });
            const data = await resp.json();
            if (!resp.ok) throw new Error(data.error || data.message || 'Something went wrong.');

            allVenues = [data.best, ...(data.alternatives || [])];
            currentVenueIndex = 0;
            confirmedPlanId = null;
            allArrivedNotified = false;
            isSessionMode = false;
            sessionData = null;

            renderAlerts(data.alerts || []);
            renderVenueResult(allVenues[0]);
            updateVoteUI({ vote_counts: {}, my_vote: null, participant_count: 1 });
            showView('viewResults');
            fitAllMarkers();
        } catch (err) {
            errText.textContent = err.message; errEl.style.display = 'block';
        } finally {
            setManualLoading(false);
        }
    });

    // Back to landing
    document.getElementById('backToLandingBtn').addEventListener('click', () => {
        clearAllMarkers();
        showView('viewLanding');
    });

    // ═══════════════════════════════════════
    //  INIT: Detect session from URL
    // ═══════════════════════════════════════
    const pathMatch = window.location.pathname.match(/^\/s\/([A-Za-z0-9]+)$/);
    if (pathMatch) {
        const joinId = pathMatch[1];
        sessionId = joinId;
        isJoining = true;
        isSessionMode = true;
        document.getElementById('locationPromptTitle').textContent = "You've been invited!";
        document.getElementById('locationPromptDesc').textContent = 'Share your location to join the session and help find the perfect meeting spot.';
        document.getElementById('backFromLocationBtn').style.display = 'none';
        showView('viewLocationPrompt');
    }

    });
    </script>
</body>
</html>
