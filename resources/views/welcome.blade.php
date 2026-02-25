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
    <link rel="apple-touch-icon" sizes="180x180" href="/midwayFavi.png">
    <meta name="description" content="Enter your postcodes, pick the vibe, and we'll find the fairest meeting spot in London with TfL directions, fare splitting, and live arrival tracking.">
    <meta name="keywords" content="meeting point, London, TfL, fair meeting, postcode, where to meet, travel planner">
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
            position: absolute;
            top: 16px;
            left: 16px;
            bottom: 16px;
            width: 400px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            pointer-events: none;
        }
        .panel > * { pointer-events: auto; }

        @media (max-width: 640px) {
            #map { bottom: 0; }
            .panel {
                top: auto;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                max-height: 60vh;
                height: auto;
                padding-bottom: env(safe-area-inset-bottom, 0);
            }
        }

        .panel-card {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        @media (max-width: 640px) {
            .panel-card {
                border-radius: 20px 20px 0 0;
                max-height: 100%;
                height: auto;
            }
        }

        .panel-handle {
            display: none;
        }
        @media (max-width: 640px) {
            .panel-handle {
                display: block;
                width: 36px;
                height: 4px;
                background: #cbd5e1;
                border-radius: 2px;
                margin: 8px auto 0;
                flex-shrink: 0;
            }
        }

        .panel-scroll {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
            flex: 1;
            min-height: 0;
        }
        .panel-scroll::-webkit-scrollbar { width: 4px; }
        .panel-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        .person-marker {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #4f46e5;
            color: white;
            font-weight: 700;
            font-size: 14px;
            font-family: 'Instrument Sans', system-ui, sans-serif;
            box-shadow: 0 3px 12px rgba(79,70,229,0.4);
            border: 3px solid white;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .person-marker.animate-drop {
            animation: markerDrop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .venue-marker {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            color: white;
            font-size: 22px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
            border: 3px solid white;
        }
        .venue-marker.animate-drop {
            animation: markerDrop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .alt-marker {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: white;
            color: #64748b;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            border: 2px solid #e2e8f0;
        }

        @keyframes markerDrop {
            0% { transform: translateY(-40px) scale(0.5); opacity: 0; }
            60% { transform: translateY(4px) scale(1.1); opacity: 1; }
            100% { transform: translateY(0) scale(1); opacity: 1; }
        }

        .gps-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.15s;
        }
        .gps-btn:hover { border-color: #6366f1; color: #6366f1; background: #eef2ff; }
        .gps-btn.locating { color: #6366f1; animation: gps-pulse 1s ease-in-out infinite; }
        .gps-btn.located { color: #22c55e; border-color: #22c55e; background: #f0fdf4; }
        @keyframes gps-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

        .input-field {
            width: 100%;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            font-size: 15px;
            font-family: inherit;
            color: #0f172a;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-field:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }
        .input-field::placeholder { color: #94a3b8; }

        .pill-btn {
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            border: 1.5px solid #e2e8f0;
            background: white;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s;
            font-family: inherit;
            white-space: nowrap;
        }
        .pill-btn:hover { border-color: #a5b4fc; }
        .pill-btn.active {
            background: #eef2ff;
            border-color: #6366f1;
            color: #4338ca;
            font-weight: 600;
        }

        .occasion-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .occasion-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.15s;
            font-family: inherit;
            text-align: left;
        }
        .occasion-card:hover { border-color: #a5b4fc; background: #fafafe; }
        .occasion-card.active { border-color: #6366f1; background: #eef2ff; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .occasion-card .oc-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .occasion-card .oc-label { font-size: 13px; font-weight: 600; color: #0f172a; }
        .occasion-card .oc-desc { font-size: 11px; color: #94a3b8; line-height: 1.3; }
        .occasion-card.active .oc-label { color: #4338ca; }

        .result-header {
            padding: 20px;
            color: white;
            border-radius: 16px;
            margin: 0 16px 12px;
        }

        .journey-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 16px;
        }
        .journey-bar-bg {
            width: 100%;
            height: 8px;
            background: #f1f5f9;
            border-radius: 4px;
            overflow: hidden;
        }
        .journey-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.7s ease;
        }

        .journey-item { cursor: pointer; }
        .journey-item:hover .journey-row { background: #f8fafc; border-radius: 8px; }

        .journey-legs {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease, opacity 0.25s ease;
            opacity: 0;
        }
        .journey-legs.open {
            max-height: 600px;
            opacity: 1;
        }

        .leg-step {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 6px 0;
            position: relative;
        }
        .leg-step:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 13px;
            top: 30px;
            bottom: -6px;
            width: 2px;
            background: #e2e8f0;
        }

        .leg-mode-icon {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 13px;
        }
        .leg-mode-icon.walking { background: #f1f5f9; color: #64748b; }
        .leg-mode-icon.tube { background: #dbeafe; color: #2563eb; }
        .leg-mode-icon.bus { background: #fef3c7; color: #d97706; }
        .leg-mode-icon.rail { background: #ede9fe; color: #7c3aed; }
        .leg-mode-icon.other { background: #f1f5f9; color: #475569; }

        .disruption-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .alert-banner {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 10px 14px;
            margin: 0 20px 12px;
            background: #fefce8;
            border: 1px solid #fde68a;
            border-radius: 12px;
            font-size: 12px;
            color: #854d0e;
            line-height: 1.4;
        }

        .chevron-toggle {
            transition: transform 0.25s ease;
            flex-shrink: 0;
        }
        .chevron-toggle.open {
            transform: rotate(180deg);
        }
    </style>
</head>
<body>

    <!-- Full-screen map -->
    <div id="map"></div>

    <!-- Floating panel -->
    <div class="panel">
        <div class="panel-card" style="max-height: 100%;">
            <div class="panel-handle"></div>

            <!-- Scrollable content -->
            <div class="panel-scroll">
                <!-- Panel header (scrolls with content) -->
                <div id="panelIntro" style="padding: 20px 20px 0;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; text-align: center;">
                        <div style="width: 100%; height: 62px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <img src="{{ asset('midwayLogo.png') }}" alt="Midway Logo" width='220px'>
                        </div>
                    </div>
                    <p style="font-size: 14px; color: #334155; line-height: 1.5; margin-bottom: 14px;">Find the <strong style="color: #4f46e5;">fairest meeting spot</strong> between you and your friends, anywhere in <strong>London</strong>. We'll sort the place, TfL directions, costs, and when to leave.</p>
                </div>

                <form id="meetingForm" style="padding: 0 20px 20px;">

                    <!-- Postcode inputs -->
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

                    <!-- Add person -->
                    <button type="button" id="addPersonBtn" style="width: 100%; padding: 10px; border: 2px dashed #e2e8f0; border-radius: 12px; background: transparent; color: #94a3b8; font-size: 13px; font-weight: 500; cursor: pointer; margin-bottom: 16px; font-family: inherit; transition: all 0.15s;" onmouseover="this.style.borderColor='#a5b4fc';this.style.color='#6366f1'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#94a3b8'">
                        + Add another person
                    </button>

                    <!-- Occasion selector -->
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">What are you doing?</label>
                        <div id="occasionSelector" class="occasion-grid"></div>
                        <input type="hidden" name="occasion" id="occasionInput" value="casual">
                    </div>

                    <!-- Submit -->
                    <button type="submit" id="submitBtn" style="width: 100%; padding: 14px; background: #4f46e5; color: white; font-size: 15px; font-weight: 600; border: none; border-radius: 14px; cursor: pointer; font-family: inherit; transition: background 0.15s; box-shadow: 0 2px 8px rgba(79,70,229,0.3);" onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                        <span id="submitText">Find the perfect spot</span>
                        <span id="submitLoading" style="display: none; align-items: center; justify-content: center; gap: 8px;">
                            <svg style="animation: spin 1s linear infinite; width: 18px; height: 18px;" viewBox="0 0 24 24"><circle style="opacity: 0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path style="opacity: 0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span id="loadingStepText">Locating postcodes...</span>
                        </span>
                    </button>
                </form>

                <!-- Error -->
                <div id="errorMessage" style="display: none; margin: 0 20px 16px; padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; color: #dc2626; font-size: 13px;">
                    <p id="errorText"></p>
                </div>

                <!-- Results -->
                <div id="resultsSection" style="display: none; padding-bottom: 20px;">

                    <!-- Back to browsing (hidden until confirmed) -->
                    <div id="backToBrowsing" style="display: none; padding: 10px 20px 0;">
                        <button type="button" id="backToBrowsingBtn" style="background: none; border: none; color: #6366f1; font-size: 13px; font-weight: 500; cursor: pointer; font-family: inherit; padding: 0; display: flex; align-items: center; gap: 4px;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Pick a different spot
                        </button>
                    </div>

                    <div id="browsingDivider" style="height: 1px; background: #e2e8f0; margin: 0 20px 16px;"></div>

                    <!-- TfL Alerts -->
                    <div id="alertsBanner" style="display: none;"></div>

                    <!-- Best result header -->
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
                            <a id="resultMapLink" href="#" target="_blank" style="flex-shrink: 0; margin-left: 12px; padding: 6px 12px; background: rgba(255,255,255,0.2); border-radius: 10px; font-size: 12px; font-weight: 500; color: white; text-decoration: none; display: flex; align-items: center; gap: 4px; transition: background 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Map
                            </a>
                        </div>
                    </div>

                    <!-- Vote buttons + dots -->
                    <div id="voteArea" style="padding: 10px 20px;">
                        <div id="voteDots" style="display: flex; justify-content: center; gap: 6px; margin-bottom: 10px;"></div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" id="voteNoBtn" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; color: #64748b; cursor: pointer; font-family: inherit; transition: all 0.15s; flex-shrink: 0;" title="Previous">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button type="button" id="voteYesBtn" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 12px; background: #4f46e5; border: none; border-radius: 12px; color: white; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.15s; box-shadow: 0 2px 8px rgba(79,70,229,0.3);">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Let's go!
                            </button>
                            <button type="button" id="voteNextBtn" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; color: #64748b; cursor: pointer; font-family: inherit; transition: all 0.15s; flex-shrink: 0;" title="Next">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Review badge -->
                    <div id="reviewBadge" style="display: none; padding: 0 20px 6px;">
                        <div id="reviewBadgeInner" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;"></div>
                    </div>

                    <!-- Venue info (website, cuisine, phone) -->
                    <div id="venueInfoSection" style="display: none; padding: 4px 20px 8px;">
                        <div id="venueInfoLinks" style="display: flex; flex-direction: column; gap: 6px;"></div>
                    </div>

                    <!-- Share overlay (hidden by default) -->
                    <div id="shareOverlay" style="display: none; padding: 12px 20px 16px; background: #ecfdf5; margin: 0 12px; border-radius: 14px;">
                        <p style="font-size: 13px; font-weight: 600; color: #059669; margin-bottom: 10px; text-align: center;">Share this plan with everyone</p>
                        <div style="display: flex; gap: 8px;">
                            <a id="shareWhatsApp" href="#" target="_blank" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 11px; background: #25D366; color: white; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; font-family: inherit;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WhatsApp
                            </a>
                            <button type="button" id="shareCopyBtn" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 11px; background: #4f46e5; color: white; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                Copy link
                            </button>
                            <button type="button" id="shareEmailBtn" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 11px; background:rgb(243, 42, 48); color: white; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit;".\>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Email
                            </button>   
                        </div>
                        <p id="shareCopyFeedback" style="display: none; font-size: 12px; color: #059669; text-align: center; margin-top: 8px; font-weight: 500;"></p>
                    </div>

                    <!-- Journey times -->
                    <div style="padding: 12px 20px;">
                        <h3 style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Journey times</h3>
                        <div id="journeyTimes" style="display: flex; flex-direction: column; gap: 10px;"></div>
                    </div>

                    <!-- Arrival planner -->
                    <div style="padding: 12px 20px; border-top: 1px solid #f1f5f9;">
                        <h3 style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Departure times</h3>
                        <p style="font-size: 12px; color: #94a3b8; margin-bottom: 10px;">When do you all need to be there? We'll tell each person when to leave.</p>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <label for="arrivalTime" style="font-size: 13px; font-weight: 500; color: #475569; white-space: nowrap;">Meet at</label>
                            <input type="time" id="arrivalTime" class="input-field" style="flex: 1; padding: 8px 12px; font-weight: 600;">
                            <button type="button" id="arrivalNowBtn" class="pill-btn" style="font-size: 12px; padding: 8px 12px;">Now + 1h</button>
                        </div>
                        <div id="departureTimes" style="display: none; flex-direction: column; gap: 8px;"></div>
                    </div>

                    <!-- Stats -->
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

                    <!-- Compact fare split (rendered inside journey section by JS) -->
                    <div id="fareSplitCompact" style="display: none; margin: 4px 20px 0; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;">
                        <div id="fareSplitResult" style="font-size: 12px; color: #15803d; line-height: 1.6;"></div>
                    </div>

                    <!-- Live tracker (shown after confirmation) -->
                    <div id="liveTrackerSection" style="display: none; margin: 10px 20px 0;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; animation: tracker-pulse 2s ease-in-out infinite;"></span>
                            <h3 style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Live — Who's where?</h3>
                        </div>
                        <div id="liveTrackerList" style="display: flex; flex-direction: column; gap: 4px;"></div>
                    </div>

                </div>
            </div>

            <!-- Footer -->
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
        "offers": {
            "@@type": "Offer",
            "price": "0",
            "priceCurrency": "GBP"
        }
    }
    </script>

    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes tracker-pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.3); opacity: 0.7; } }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ============================
        //  Leaflet map
        // ============================
        const map = L.map('map', {
            center: [51.505, -0.09],
            zoom: 12,
            zoomControl: false,
            tap: true,
            dragging: true,
            touchZoom: true,
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
            return L.divIcon({
                className: '',
                html: `<div class="person-marker animate-drop">${number}</div>`,
                iconSize: [36, 36],
                iconAnchor: [18, 18],
            });
        }

        function createVenueIcon(emoji, gradient) {
            return L.divIcon({
                className: '',
                html: `<div class="venue-marker animate-drop" style="background: ${gradient};">${emoji}</div>`,
                iconSize: [44, 44],
                iconAnchor: [22, 22],
            });
        }

        function clearVenueMarkers() {
            venueMarkers.forEach(m => map.removeLayer(m));
            venueMarkers.length = 0;
        }

        function fitAllMarkers() {
            const allMarkers = [...Object.values(personMarkers), ...venueMarkers];
            if (allMarkers.length === 0) return;
            const group = L.featureGroup(allMarkers);
            const isMobile = window.innerWidth <= 640;
            map.invalidateSize();
            const panelHeight = isMobile ? Math.round(window.innerHeight * 0.55) : 20;
            map.fitBounds(group.getBounds().pad(0.15), {
                maxZoom: 14,
                animate: true,
                duration: 0.8,
                paddingTopLeft: isMobile ? [20, 20] : [420, 20],
                paddingBottomRight: isMobile ? [20, panelHeight] : [20, 20],
            });
        }

        // ============================
        //  Geocode a postcode and place/move marker
        // ============================
        let geocodeTimers = {};

        async function geocodeAndMark(postcode, index) {
            const cleaned = postcode.trim().replace(/\s+/g, '');
            if (cleaned.length < 3) {
                if (personMarkers[index]) {
                    map.removeLayer(personMarkers[index]);
                    delete personMarkers[index];
                }
                return;
            }

            try {
                const res = await fetch(`https://api.postcodes.io/postcodes/${encodeURIComponent(cleaned)}`);
                const data = await res.json();
                if (data.status === 200 && data.result) {
                    const { latitude, longitude } = data.result;
                    if (personMarkers[index]) {
                        personMarkers[index].setLatLng([latitude, longitude]);
                    } else {
                        personMarkers[index] = L.marker([latitude, longitude], { icon: createPersonIcon(index + 1) }).addTo(map);
                    }
                    personMarkers[index].bindTooltip(`Person ${index + 1}: ${postcode.trim().toUpperCase()}`, {
                        direction: 'top', offset: [0, -20], className: 'leaflet-tooltip',
                    });
                    fitAllMarkers();
                }
            } catch (_) {}
        }

        function handlePostcodeInput(input, index) {
            clearTimeout(geocodeTimers[index]);
            geocodeTimers[index] = setTimeout(() => geocodeAndMark(input.value, index), 600);
        }

        // ============================
        //  Theme config per venue type (used for result card styling)
        // ============================
        const venueThemes = {
            pub:        { label: 'Pub',        icon: '🍺', emoji: '🍻', headerGradient: 'linear-gradient(135deg, #92400e, #78350f)', barColor: '#f59e0b' },
            cafe:       { label: 'Cafe',       icon: '☕', emoji: '🧁', headerGradient: 'linear-gradient(135deg, #9a3412, #7c2d12)', barColor: '#f97316' },
            restaurant: { label: 'Restaurant', icon: '🍽️', emoji: '🥂', headerGradient: 'linear-gradient(135deg, #9f1239, #881337)', barColor: '#f43f5e' },
            station:       { label: 'Station',       icon: '🚂', emoji: '🗺️', headerGradient: 'linear-gradient(135deg, #334155, #1e293b)', barColor: '#0ea5e9' },
            entertainment: { label: 'Entertainment', icon: '🎳', emoji: '🎉', headerGradient: 'linear-gradient(135deg, #7e22ce, #a21caf)', barColor: '#a855f7' },
            any:           { label: 'Anywhere',      icon: '📍', emoji: '✨', headerGradient: 'linear-gradient(135deg, #4f46e5, #7c3aed)', barColor: '#6366f1' },
            other:         { label: 'Venue',         icon: '📍', emoji: '✨', headerGradient: 'linear-gradient(135deg, #4f46e5, #7c3aed)', barColor: '#6366f1' },
        };

        // ============================
        //  Occasion config
        // ============================
        const occasions = {
            casual:      { label: 'Drinks',        icon: '🍻', bg: '#fef3c7', desc: 'Pubs & bars',            subtitle: 'Your casual hangout' },
            date:        { label: 'Date night',    icon: '🌹', bg: '#ffe4e6', desc: 'Restaurants & cafes',     subtitle: 'Perfect for date night' },
            coffee:      { label: 'Coffee',        icon: '☕', bg: '#fef9c3', desc: 'Cafes & chill spots',     subtitle: 'Coffee & chat at' },
            celebration: { label: 'Fun & games',   icon: '🎳', bg: '#f3e8ff', desc: 'Bowling, cinema & more',  subtitle: 'Entertainment pick' },
        };
        let selectedOccasion = 'casual';

        // ============================
        //  DOM refs
        // ============================
        const postcodeInputs = document.getElementById('postcodeInputs');
        const addPersonBtn = document.getElementById('addPersonBtn');
        const meetingForm = document.getElementById('meetingForm');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitLoading = document.getElementById('submitLoading');
        const resultsSection = document.getElementById('resultsSection');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        const occasionInput = document.getElementById('occasionInput');
        const arrivalTimeInput = document.getElementById('arrivalTime');
        const arrivalNowBtn = document.getElementById('arrivalNowBtn');
        const departureTimesContainer = document.getElementById('departureTimes');
        let currentVenueTimes = [];
        let currentVenueTheme = null;
        let currentVenue = null;
        let allResults = [];
        let currentResultIndex = 0;

        // ============================
        //  Attach postcode listeners
        // ============================
        function attachPostcodeListeners() {
            const inputs = postcodeInputs.querySelectorAll('input[name="postcode"]');
            inputs.forEach((input, i) => {
                input.removeEventListener('input', input._handler);
                input._handler = () => handlePostcodeInput(input, i);
                input.addEventListener('input', input._handler);
            });
        }
        attachPostcodeListeners();

        // ============================
        //  Build occasion pill buttons
        // ============================
        const occasionSelector = document.getElementById('occasionSelector');

        Object.entries(occasions).forEach(([id, occ]) => {
            const card = document.createElement('button');
            card.type = 'button';
            card.dataset.occasion = id;
            card.className = 'occasion-card' + (id === 'casual' ? ' active' : '');
            card.innerHTML = `
                <div class="oc-icon" style="background: ${occ.bg};">${occ.icon}</div>
                <div>
                    <div class="oc-label">${occ.label}</div>
                    <div class="oc-desc">${occ.desc}</div>
                </div>`;
            occasionSelector.appendChild(card);
        });

        occasionSelector.addEventListener('click', function (e) {
            const card = e.target.closest('.occasion-card');
            if (!card) return;
            selectedOccasion = card.dataset.occasion;
            occasionInput.value = selectedOccasion;
            occasionSelector.querySelectorAll('.occasion-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
        });

        // ============================
        //  Dynamic postcode rows
        // ============================
        function updateRowNumbers() {
            const rows = postcodeInputs.querySelectorAll('.postcode-row');
            rows.forEach((row, i) => {
                row.querySelector('.person-marker').textContent = i + 1;
                const removeBtn = row.querySelector('.remove-btn');
                removeBtn.style.display = rows.length <= 2 ? 'none' : 'block';
            });
            attachPostcodeListeners();
        }

        addPersonBtn.addEventListener('click', function () {
            const count = postcodeInputs.querySelectorAll('.postcode-row').length;
            const row = document.createElement('div');
            row.className = 'postcode-row';
            row.style.cssText = 'display: flex; align-items: center; gap: 10px;';
            row.innerHTML = `
                <div class="person-marker" style="width: 28px; height: 28px; font-size: 12px; flex-shrink: 0; box-shadow: none; border: 2px solid #e0e7ff;">${count + 1}</div>
                <input type="text" placeholder="e.g. N1 9GU" class="input-field" name="postcode" required>
                <button type="button" class="gps-btn" title="Use my location">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v2m0 16v2M2 12h2m16 0h2m-5 0a5 5 0 11-10 0 5 5 0 0110 0zm-3 0a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </button>
                <button type="button" class="remove-btn" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;" title="Remove">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>`;
            postcodeInputs.appendChild(row);
            row.querySelector('input').focus();
            updateRowNumbers();
        });

        postcodeInputs.addEventListener('click', function (e) {
            const removeBtn = e.target.closest('.remove-btn');
            if (!removeBtn) return;
            const row = removeBtn.closest('.postcode-row');
            const rows = postcodeInputs.querySelectorAll('.postcode-row');
            const index = Array.from(rows).indexOf(row);
            row.remove();
            if (personMarkers[index]) {
                map.removeLayer(personMarkers[index]);
                delete personMarkers[index];
            }
            // Reindex markers
            const remaining = {};
            const allRows = postcodeInputs.querySelectorAll('.postcode-row');
            allRows.forEach((r, i) => {
                const oldIndex = Object.keys(personMarkers).find(k => {
                    const marker = personMarkers[k];
                    return marker && r.querySelector('input').value && marker._tooltip && marker._tooltip._content.includes(r.querySelector('input').value.trim().toUpperCase());
                });
                if (oldIndex !== undefined && personMarkers[oldIndex]) {
                    remaining[i] = personMarkers[oldIndex];
                    remaining[i].setIcon(createPersonIcon(i + 1));
                }
            });
            Object.keys(personMarkers).forEach(k => {
                if (!remaining[k] && personMarkers[k]) map.removeLayer(personMarkers[k]);
            });
            Object.keys(personMarkers).forEach(k => delete personMarkers[k]);
            Object.assign(personMarkers, remaining);
            updateRowNumbers();
            fitAllMarkers();
        });

        // ============================
        //  GPS "Use my location"
        // ============================
        postcodeInputs.addEventListener('click', function (e) {
            const gpsBtn = e.target.closest('.gps-btn');
            if (!gpsBtn) return;
            if (gpsBtn.classList.contains('locating')) return;
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }
            const row = gpsBtn.closest('.postcode-row');
            const input = row.querySelector('input');
            gpsBtn.classList.add('locating');
            gpsBtn.classList.remove('located');

            navigator.geolocation.getCurrentPosition(
                async function (pos) {
                    try {
                        const resp = await fetch(`https://api.postcodes.io/postcodes?lon=${pos.coords.longitude}&lat=${pos.coords.latitude}&limit=1`);
                        const data = await resp.json();
                        const nearest = data?.result?.[0];
                        if (nearest?.postcode) {
                            input.value = nearest.postcode;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            gpsBtn.classList.remove('locating');
                            gpsBtn.classList.add('located');
                            setTimeout(() => gpsBtn.classList.remove('located'), 3000);
                        } else {
                            gpsBtn.classList.remove('locating');
                            alert('Could not find a postcode for your location. Are you in the UK?');
                        }
                    } catch {
                        gpsBtn.classList.remove('locating');
                        alert('Failed to look up your postcode. Please try again.');
                    }
                },
                function (err) {
                    gpsBtn.classList.remove('locating');
                    if (err.code === err.PERMISSION_DENIED) {
                        alert('Location access was denied. Please allow location access and try again.');
                    } else {
                        alert('Could not determine your location. Please try again.');
                    }
                },
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
            );
        });

        // ============================
        //  Helpers
        // ============================
        const loadingSteps = [
            { text: 'Locating postcodes...', delay: 0 },
            { text: 'Finding your area...', delay: 2000 },
            { text: 'Searching venues...', delay: 5000 },
            { text: 'Calculating routes...', delay: 10000 },
            { text: 'Picking the fairest spot...', delay: 18000 },
            { text: 'Almost there...', delay: 30000 },
        ];
        let loadingTimer = null;

        function setLoading(loading) {
            submitBtn.disabled = loading;
            submitBtn.style.opacity = loading ? '0.6' : '1';
            submitBtn.style.cursor = loading ? 'not-allowed' : 'pointer';
            submitText.style.display = loading ? 'none' : 'inline';
            submitLoading.style.display = loading ? 'flex' : 'none';

            if (loadingTimer) { clearTimeout(loadingTimer); loadingTimer = null; }

            if (loading) {
                const stepText = document.getElementById('loadingStepText');
                stepText.textContent = loadingSteps[0].text;
                let stepIndex = 1;
                function nextStep() {
                    if (stepIndex < loadingSteps.length) {
                        const step = loadingSteps[stepIndex];
                        const prevDelay = loadingSteps[stepIndex - 1].delay;
                        loadingTimer = setTimeout(() => {
                            stepText.style.transition = 'opacity 0.2s';
                            stepText.style.opacity = '0';
                            setTimeout(() => {
                                stepText.textContent = step.text;
                                stepText.style.opacity = '1';
                            }, 200);
                            stepIndex++;
                            nextStep();
                        }, step.delay - prevDelay);
                    }
                }
                nextStep();
            }
        }

        function showError(message) {
            errorText.textContent = message;
            errorMessage.style.display = 'block';
            resultsSection.style.display = 'none';
        }

        function hideError() {
            errorMessage.style.display = 'none';
        }

        function formatDuration(mins) {
            if (mins >= 60) {
                const h = Math.floor(mins / 60);
                const m = mins % 60;
                return m > 0 ? `${h}h ${m}m` : `${h}h`;
            }
            return `${mins} min`;
        }

        // ============================
        //  Render helpers
        // ============================
        function getTheme(venueType) {
            return venueThemes[venueType] || venueThemes['other'];
        }

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

        function renderLegs(legs, disruptions) {
            let html = '';
            (legs || []).forEach(leg => {
                const iconCls = modeIconClass(leg.mode);
                const emoji = modeEmoji(leg.mode);
                const linePill = leg.line ? `<span style="display:inline-block;padding:1px 7px;border-radius:6px;font-size:10px;font-weight:600;background:#e0e7ff;color:#3730a3;margin-left:6px;">${leg.line}</span>` : '';
                html += `
                    <div class="leg-step">
                        <div class="leg-mode-icon ${iconCls}">${emoji}</div>
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:12px;color:#334155;line-height:1.4;">${leg.summary}${linePill}</p>
                            <p style="font-size:11px;color:#94a3b8;">${formatDuration(leg.duration)}</p>
                        </div>
                    </div>`;
            });

            (disruptions || []).forEach(d => {
                html += `<div class="disruption-pill" style="margin-top:6px;">⚠️ ${d.description}</div>`;
            });

            return html;
        }

        function renderStars(rating) {
            let html = '';
            const full = Math.floor(rating);
            const half = rating - full >= 0.3;
            const empty = 5 - full - (half ? 1 : 0);
            const starFull = '<svg style="width:14px;height:14px;color:#f59e0b;" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
            const starEmpty = '<svg style="width:14px;height:14px;color:#cbd5e1;" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
            for (let i = 0; i < full; i++) html += starFull;
            if (half) html += starFull;
            for (let i = 0; i < empty; i++) html += starEmpty;
            return html;
        }

        function buildMenuUrl(venue) {
            if (venue.website) {
                const w = venue.website.replace(/\/$/, '');
                const host = w.toLowerCase();
                if (host.includes('wagamama')) return w + '/our-menu';
                if (host.includes('francomanca')) return w + '/menu';
                if (host.includes('pizzaexpress')) return w + '/menu';
                if (host.includes('nandos')) return w + '/food/menu';
                if (host.includes('prezzo')) return w + '/menu';
                if (host.includes('zizzi')) return w + '/menu';
                if (host.includes('dishoom')) return w + '/menus';
                return w;
            }
            return `https://www.google.com/search?q=${encodeURIComponent(venue.name + ' menu ' + (venue.address || 'London'))}`;
        }

        function buildShowtimesUrl(venue) {
            if (venue.website) {
                return venue.website;
            }
            return `https://www.google.com/search?q=${encodeURIComponent(venue.name + ' showtimes today')}`;
        }

        function renderFareSplit(venue) {
            const section = document.getElementById('fareSplitCompact');
            const result = document.getElementById('fareSplitResult');

            const fares = venue.times.map(t => ({
                from: t.from,
                pence: t.fare?.total_pence ?? null,
            }));

            const allHaveFares = fares.every(f => f.pence !== null);
            if (!allHaveFares) {
                section.style.display = 'none';
                return;
            }

            const totalPence = fares.reduce((sum, f) => sum + f.pence, 0);
            const fairSharePence = Math.round(totalPence / fares.length);

            const settlements = [];
            const diffs = fares.map(f => ({ from: f.from, diff: f.pence - fairSharePence }));
            const overpayers = diffs.filter(d => d.diff > 0).sort((a, b) => b.diff - a.diff);
            const underpayers = diffs.filter(d => d.diff < 0).sort((a, b) => a.diff - b.diff);

            let oi = 0, ui = 0;
            const overBalances = overpayers.map(o => ({ ...o, remaining: o.diff }));
            const underBalances = underpayers.map(u => ({ ...u, remaining: -u.diff }));

            while (oi < overBalances.length && ui < underBalances.length) {
                const amount = Math.min(overBalances[oi].remaining, underBalances[ui].remaining);
                if (amount > 0) {
                    settlements.push({ payer: underBalances[ui].from, payee: overBalances[oi].from, pence: amount });
                }
                overBalances[oi].remaining -= amount;
                underBalances[ui].remaining -= amount;
                if (overBalances[oi].remaining <= 0) oi++;
                if (underBalances[ui].remaining <= 0) ui++;
            }

            let html = `<span style="font-weight: 600;">💳 Total: £${(totalPence / 100).toFixed(2)}</span> · <span>Fair share: £${(fairSharePence / 100).toFixed(2)} each</span>`;

            if (settlements.length > 0) {
                html += '<br>' + settlements.map(s =>
                    `<span style="font-weight: 500;">${s.payer}</span> → ${s.payee} <strong>£${(s.pence / 100).toFixed(2)}</strong>`
                ).join(' · ');
            }

            result.innerHTML = html;
            section.style.display = 'block';
        }

        function renderVenueInfo(venue) {
            const section = document.getElementById('venueInfoSection');
            const container = document.getElementById('venueInfoLinks');
            container.innerHTML = '';

            const items = [];
            const isCinema = (venue.subcategory || '').toLowerCase() === 'cinema';
            const isTheatre = (venue.subcategory || '').toLowerCase() === 'theatre';
            const isFood = venue.type === 'restaurant' || venue.type === 'cafe';

            // Cuisine badge — prominent for restaurants
            if (venue.cuisine) {
                items.push(`<div style="display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;background:#fef3c7;border:1px solid #fde68a;">
                    <span style="font-size:16px;">🍽️</span>
                    <span style="font-size:14px;font-weight:600;color:#92400e;">${venue.cuisine}</span>
                </div>`);
            }

            // Menu link for restaurants/cafes
            if (isFood) {
                const menuUrl = buildMenuUrl(venue);
                const hasDirectMenu = venue.website && menuUrl !== venue.website;
                items.push(`<a href="${menuUrl}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;font-size:13px;font-weight:600;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;text-decoration:none;transition:all 0.15s;" onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
                    <span style="font-size:16px;">📖</span>
                    <span style="flex:1">${hasDirectMenu ? 'View Menu' : 'Menu & Website'}</span>
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>`);
                if (!venue.website) {
                    items.push(`<a href="https://www.google.com/search?q=${encodeURIComponent(venue.name + ' menu ' + (venue.address || 'London'))}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:500;background:#f8fafc;color:#475569;border:1px solid #e2e8f0;text-decoration:none;">
                        <span>🔍</span><span style="flex:1">Search for menu</span>
                    </a>`);
                }
            }

            // Showtimes for cinemas
            if (isCinema) {
                const showtimesUrl = buildShowtimesUrl(venue);
                items.push(`<a href="${showtimesUrl}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;font-size:13px;font-weight:600;background:#fdf4ff;color:#86198f;border:1px solid #f0abfc;text-decoration:none;transition:all 0.15s;" onmouseover="this.style.background='#fae8ff'" onmouseout="this.style.background='#fdf4ff'">
                    <span style="font-size:16px;">🎬</span>
                    <span style="flex:1">What's On — Showtimes</span>
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>`);
                if (!venue.website) {
                    items.push(`<a href="https://www.google.com/search?q=${encodeURIComponent(venue.name + ' showtimes today')}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:500;background:#f8fafc;color:#475569;border:1px solid #e2e8f0;text-decoration:none;">
                        <span>🔍</span><span style="flex:1">Search showtimes on Google</span>
                    </a>`);
                }
            }

            // What's on for theatres
            if (isTheatre) {
                const theatreUrl = venue.website || `https://www.google.com/search?q=${encodeURIComponent(venue.name + ' whats on today')}`;
                items.push(`<a href="${theatreUrl}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;font-size:13px;font-weight:600;background:#fefce8;color:#854d0e;border:1px solid #fde68a;text-decoration:none;transition:all 0.15s;" onmouseover="this.style.background='#fef9c3'" onmouseout="this.style.background='#fefce8'">
                    <span style="font-size:16px;">🎭</span>
                    <span style="flex:1">What's On — Shows & Tickets</span>
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>`);
            }

            // Generic website link for other types (entertainment, etc.)
            if (venue.website && !isFood && !isCinema && !isTheatre) {
                items.push(`<a href="${venue.website}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:500;background:#f8fafc;color:#334155;border:1px solid #e2e8f0;text-decoration:none;transition:background 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                    <span>🔗</span><span style="flex:1">Website</span>
                    <svg width="14" height="14" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>`);
            }

            // Phone
            if (venue.phone) {
                items.push(`<a href="tel:${venue.phone}" style="display:flex;align-items:center;gap:10px;padding:8px 14px;font-size:13px;color:#334155;text-decoration:none;"><span>📞</span><span>${venue.phone}</span></a>`);
            }

            if (items.length === 0) { section.style.display = 'none'; return; }
            section.style.display = 'block';
            container.innerHTML = items.join('');
        }

        // ============================
        //  Render main result
        // ============================
        function renderAlerts(alerts) {
            const banner = document.getElementById('alertsBanner');
            banner.innerHTML = '';
            if (!alerts || alerts.length === 0) {
                banner.style.display = 'none';
                return;
            }
            banner.style.display = 'block';
            alerts.forEach(a => {
                const reason = a.reason ? `<br><span style="font-weight:400;opacity:0.85;">${a.reason}</span>` : '';
                banner.insertAdjacentHTML('beforeend', `
                    <div class="alert-banner">
                        <span style="font-size:16px;flex-shrink:0;">⚠️</span>
                        <div><strong>${a.line}: ${a.status}</strong>${reason}</div>
                    </div>`);
            });
        }

        function renderVenueResult(venue, centroid) {
            const theme = getTheme(venue.type);
            clearVenueMarkers();

            // Place venue marker on map
            const venueIcon = createVenueIcon(theme.icon, theme.headerGradient);
            const marker = L.marker([venue.lat, venue.lng], { icon: venueIcon, zIndexOffset: 1000 }).addTo(map);
            marker.bindTooltip(venue.name, { direction: 'top', offset: [0, -24], className: 'leaflet-tooltip' });
            venueMarkers.push(marker);

            // Header — use occasion-specific subtitle if available
            document.getElementById('resultHeader').style.background = theme.headerGradient;
            const occasionData = occasions[selectedOccasion];
            document.getElementById('resultSubtitle').textContent = occasionData ? occasionData.subtitle : 'Best meeting point';
            document.getElementById('resultIcon').textContent = theme.icon;
            document.getElementById('resultName').textContent = venue.name;
            const badgeLabel = venue.subcategory || theme.label;
            document.getElementById('resultTypeBadge').textContent = `${theme.emoji}  ${badgeLabel}`;
            const cuisineBadge = document.getElementById('resultCuisineBadge');
            if (venue.cuisine) {
                cuisineBadge.textContent = venue.cuisine;
                cuisineBadge.style.display = 'inline-block';
            } else {
                cuisineBadge.style.display = 'none';
            }
            document.getElementById('resultAddress').textContent = venue.address || '';
            document.getElementById('resultMapLink').href = `https://www.google.com/maps/search/?api=1&query=${venue.lat},${venue.lng}`;

            // Journey times (expandable with directions)
            const container = document.getElementById('journeyTimes');
            container.innerHTML = '';
            const maxTime = venue.max;

            venue.times.forEach(t => {
                const pct = maxTime > 0 ? (t.duration / maxTime) * 100 : 0;
                const hasLegs = t.legs && t.legs.length > 0;
                const hasDisruptions = t.disruptions && t.disruptions.length > 0;

                const item = document.createElement('div');
                item.className = 'journey-item';

                const fareTag = t.fare ? `£${(t.fare.total_pence / 100).toFixed(2)}` : '';

                const rowHtml = `
                    <div class="journey-row" style="padding: 6px 0;">
                        <span style="font-size: 13px; font-weight: 500; color: #475569; flex-shrink: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 72px;">${t.from}</span>
                        <div style="flex: 1; min-width: 20px; max-width: 120px;">
                            <div class="journey-bar-bg">
                                <div class="journey-bar-fill" style="width: ${pct}%; background: ${theme.barColor};"></div>
                            </div>
                        </div>
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b; flex-shrink: 0; white-space: nowrap;">${formatDuration(t.duration)}${fareTag ? ` <span style="font-size: 11px; font-weight: 600; color: #059669;">· ${fareTag}</span>` : ''}</span>
                        ${hasDisruptions ? '<span style="font-size: 14px; flex-shrink: 0;" title="Disruptions on route">⚠️</span>' : ''}
                        ${hasLegs ? '<svg class="chevron-toggle" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M6 9l6 6 6-6"/></svg>' : ''}
                    </div>`;

                const legsHtml = hasLegs ? `
                    <div class="journey-legs" style="padding: 4px 0 8px 0; margin-left: 4px;">
                        ${renderLegs(t.legs, t.disruptions)}
                    </div>` : '';

                item.innerHTML = rowHtml + legsHtml;

                if (hasLegs) {
                    item.addEventListener('click', () => {
                        const legsEl = item.querySelector('.journey-legs');
                        const chevron = item.querySelector('.chevron-toggle');
                        if (legsEl) {
                            legsEl.classList.toggle('open');
                            if (chevron) chevron.classList.toggle('open');
                        }
                    });
                }

                container.appendChild(item);
            });

            // Stats
            document.getElementById('statMax').textContent = formatDuration(venue.max);
            document.getElementById('statMin').textContent = formatDuration(venue.min);
            document.getElementById('statSpread').textContent = formatDuration(venue.spread);

            renderFareSplit(venue);
            renderVenueInfo(venue);
            fetchReviewBadge(venue);

            // Arrival planner reset
            currentVenueTimes = venue.times;
            currentVenueTheme = theme;
            currentVenue = venue;
            arrivalTimeInput.value = '';
            departureTimesContainer.style.display = 'none';
            departureTimesContainer.innerHTML = '';
        }

        // renderAlternatives removed — vote system handles cycling

        // ============================
        //  Arrival planner
        // ============================
        function padTime(n) { return String(n).padStart(2, '0'); }

        function subtractMinutes(timeStr, mins) {
            const [h, m] = timeStr.split(':').map(Number);
            const totalMins = h * 60 + m - mins;
            const wrapped = ((totalMins % 1440) + 1440) % 1440;
            return `${padTime(Math.floor(wrapped / 60))}:${padTime(wrapped % 60)}`;
        }

        function formatTime12h(timeStr) {
            const [h, m] = timeStr.split(':').map(Number);
            const suffix = h >= 12 ? 'pm' : 'am';
            const display = h === 0 ? 12 : h > 12 ? h - 12 : h;
            return `${display}:${padTime(m)} ${suffix}`;
        }

        function updateDepartureTimes() {
            const arrivalTime = arrivalTimeInput.value;
            if (!arrivalTime || currentVenueTimes.length === 0) {
                departureTimesContainer.style.display = 'none';
                return;
            }

            departureTimesContainer.style.display = 'flex';
            departureTimesContainer.innerHTML = '';

            const sortedTimes = [...currentVenueTimes].sort((a, b) => b.duration - a.duration);

            sortedTimes.forEach((t, i) => {
                const leaveAt = subtractMinutes(arrivalTime, t.duration);
                const isEarliest = i === 0;
                const card = document.createElement('div');
                card.style.cssText = `display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 12px; background: ${isEarliest ? '#eef2ff' : '#f8fafc'}; ${isEarliest ? 'border: 1px solid #c7d2fe;' : ''}`;
                card.innerHTML = `
                    <div style="flex: 1; min-width: 0;">
                        <p style="font-size: 13px; font-weight: 500; color: #334155; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${t.from}</p>
                        <p style="font-size: 11px; color: #94a3b8;">${formatDuration(t.duration)} journey</p>
                    </div>
                    <div style="text-align: right; flex-shrink: 0;">
                        <p style="font-size: 17px; font-weight: 700; color: ${isEarliest ? '#4f46e5' : '#0f172a'};">${formatTime12h(leaveAt)}</p>
                        <p style="font-size: 11px; color: ${isEarliest ? '#6366f1' : '#94a3b8'}; font-weight: ${isEarliest ? '600' : '400'};">${isEarliest ? 'leaves first' : 'leave by'}</p>
                    </div>`;
                departureTimesContainer.appendChild(card);
            });
        }

        arrivalTimeInput.addEventListener('input', updateDepartureTimes);

        arrivalNowBtn.addEventListener('click', function () {
            const now = new Date();
            now.setHours(now.getHours() + 1);
            arrivalTimeInput.value = `${padTime(now.getHours())}:${padTime(now.getMinutes())}`;
            updateDepartureTimes();
        });

        // ============================
        //  Vote Yes / No + Share
        // ============================
        const voteYesBtn = document.getElementById('voteYesBtn');
        const voteNoBtn = document.getElementById('voteNoBtn');
        const voteNextBtn = document.getElementById('voteNextBtn');
        const voteDots = document.getElementById('voteDots');
        const shareOverlay = document.getElementById('shareOverlay');
        const shareWhatsApp = document.getElementById('shareWhatsApp');
        const shareCopyBtn = document.getElementById('shareCopyBtn');
        const shareEmailBtn = document.getElementById('shareEmailBtn');
        const shareCopyFeedback = document.getElementById('shareCopyFeedback');

        function updateVoteCounter() {
            voteDots.innerHTML = '';
            if (allResults.length <= 1) return;
            allResults.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.style.cssText = `width: ${i === currentResultIndex ? '20px' : '8px'}; height: 8px; border-radius: 4px; border: none; cursor: pointer; transition: all 0.2s; background: ${i === currentResultIndex ? '#4f46e5' : '#cbd5e1'};`;
                dot.addEventListener('click', () => navigateToResult(i));
                voteDots.appendChild(dot);
            });
            voteNoBtn.style.opacity = allResults.length > 1 ? '1' : '0.3';
            voteNextBtn.style.opacity = allResults.length > 1 ? '1' : '0.3';
        }

        function navigateToResult(index) {
            if (allResults.length <= 1) return;
            currentResultIndex = index;
            shareOverlay.style.display = 'none';
            renderVenueResult(allResults[currentResultIndex], null);
            updateVoteCounter();
            fitAllMarkers();
        }

        voteNoBtn.addEventListener('click', function () {
            if (allResults.length <= 1) return;
            navigateToResult((currentResultIndex - 1 + allResults.length) % allResults.length);
        });

        voteNextBtn.addEventListener('click', function () {
            if (allResults.length <= 1) return;
            navigateToResult((currentResultIndex + 1) % allResults.length);
        });

        let confirmedPlanId = null;
        let trackerPollInterval = null;

        function enterConfirmedMode() {
            document.getElementById('panelIntro').style.display = 'none';
            document.getElementById('meetingForm').style.display = 'none';
            document.getElementById('backToBrowsing').style.display = 'block';
            document.getElementById('browsingDivider').style.display = 'none';
            document.getElementById('voteArea').style.display = 'none';
            shareOverlay.style.display = 'block';
            if (confirmedPlanId) startLiveTracker();
            document.querySelector('.panel-scroll').scrollTop = 0;
        }

        function exitConfirmedMode() {
            document.getElementById('panelIntro').style.display = '';
            document.getElementById('meetingForm').style.display = '';
            document.getElementById('backToBrowsing').style.display = 'none';
            document.getElementById('browsingDivider').style.display = '';
            document.getElementById('voteArea').style.display = '';
            shareOverlay.style.display = 'none';
            document.getElementById('liveTrackerSection').style.display = 'none';
            if (trackerPollInterval) { clearInterval(trackerPollInterval); trackerPollInterval = null; }
        }

        document.getElementById('backToBrowsingBtn').addEventListener('click', exitConfirmedMode);

        voteYesBtn.addEventListener('click', async function () {
            if (!currentVenue) return;
            voteYesBtn.disabled = true;
            voteYesBtn.innerHTML = '<svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24"><circle style="opacity:0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path style="opacity:0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>';

            try {
                const resp = await fetch('/api/share', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ venue: currentVenue, occasion: selectedOccasion }),
                });
                const result = await resp.json();
                confirmedPlanId = result.id;
                const shareUrl = result.url;
                const shareText = `Let's meet at ${currentVenue.name}! ${shareUrl}`;

                shareWhatsApp.href = `https://wa.me/?text=${encodeURIComponent(shareText)}`;
                shareCopyBtn.onclick = () => {
                    navigator.clipboard.writeText(shareUrl).then(() => {
                        shareCopyFeedback.textContent = 'Link copied!';
                        shareCopyFeedback.style.display = 'block';
                        setTimeout(() => shareCopyFeedback.style.display = 'none', 2500);
                    });
                };

                shareEmailBtn.onclick = () => {
                        const email = '';
                        const emailSubject = `Let's meet at ${currentVenue.name}!`;
                        const subjectBody = `Let's start with this link:\n\n${shareUrl}`;
                        const mailToLink = `mailto:${email}?subject=${encodeURIComponent(emailSubject)}&body=${encodeURIComponent(subjectBody)}`;
                        window.location.href = mailToLink;
                }

                
                enterConfirmedMode();
            } catch (err) {
                showError('Could not create share link. Please try again.');
            } finally {
                voteYesBtn.disabled = false;
                voteYesBtn.style.opacity = '1';
                voteYesBtn.innerHTML = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Let\'s go!';
            }
        });

        // ============================
        //  Live Tracker (in confirmed mode)
        // ============================
        function haversineMetres(lat1, lng1, lat2, lng2) {
            const R = 6371000;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function fmtDist(metres) {
            if (metres < 50) return 'Here!';
            if (metres < 200) return 'Nearly there';
            const miles = metres / 1609.34;
            if (miles < 0.3) return Math.round(metres) + 'm away';
            return miles.toFixed(1) + ' mi away';
        }

        function startLiveTracker() {
            if (!confirmedPlanId || !currentVenue) return;
            const section = document.getElementById('liveTrackerSection');
            const list = document.getElementById('liveTrackerList');
            section.style.display = 'block';

            list.innerHTML = currentVenue.times.map((t, i) => `
                <div style="display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 10px; background: #f8fafc;" data-tracker-person="${i}">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #d1d5db; flex-shrink: 0;" data-tracker-dot="${i}"></span>
                    <span style="font-size: 13px; font-weight: 600; color: #334155; flex: 1;">${t.from}</span>
                    <span style="font-size: 12px; color: #94a3b8; font-weight: 500;" data-tracker-dist="${i}">—</span>
                </div>
            `).join('');

            pollTrackerStatuses();
            trackerPollInterval = setInterval(pollTrackerStatuses, 5000);

            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }

        let allArrivedNotified = false;

        async function pollTrackerStatuses() {
            if (!confirmedPlanId) return;
            try {
                const resp = await fetch(`/api/plan/${confirmedPlanId}/status`);
                if (!resp.ok) return;
                const data = await resp.json();
                let allArrived = true;

                (data.statuses || []).forEach(s => {
                    const dot = document.querySelector(`[data-tracker-dot="${s.person}"]`);
                    const dist = document.querySelector(`[data-tracker-dist="${s.person}"]`);
                    const row = document.querySelector(`[data-tracker-person="${s.person}"]`);

                    const dotColors = { pending: '#d1d5db', on_my_way: '#f59e0b', arrived: '#22c55e' };
                    if (dot) dot.style.background = dotColors[s.status] || '#d1d5db';

                    if (s.status === 'arrived') {
                        if (dist) { dist.textContent = 'Arrived!'; dist.style.color = '#16a34a'; dist.style.fontWeight = '600'; }
                        if (row) row.style.background = '#f0fdf4';
                    } else if (s.status === 'on_my_way' && s.distance_metres != null) {
                        if (dist) { dist.textContent = fmtDist(s.distance_metres); dist.style.color = '#6366f1'; }
                    } else {
                        if (dist) { dist.textContent = 'Waiting'; dist.style.color = '#94a3b8'; }
                    }

                    if (s.status !== 'arrived') allArrived = false;
                });

                if (allArrived && (data.statuses || []).length >= 2 && !allArrivedNotified) {
                    allArrivedNotified = true;
                    if (trackerPollInterval) { clearInterval(trackerPollInterval); trackerPollInterval = null; }
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification("Everyone's arrived at " + currentVenue.name + "! 🎉", { body: 'Have fun!' });
                    }
                }
            } catch (_) {}
        }

        // ============================
        //  Review badge
        // ============================
        async function fetchReviewBadge(venue) {
            const badge = document.getElementById('reviewBadge');
            const inner = document.getElementById('reviewBadgeInner');
            badge.style.display = 'none';
            try {
                const params = new URLSearchParams({ name: venue.name, lat: venue.lat, lng: venue.lng });
                const resp = await fetch(`/api/venue-reviews?${params}`);
                const data = await resp.json();
                if (!data.has_reviews) return;
                inner.style.background = data.color + '18';
                inner.style.color = data.color;
                inner.innerHTML = `<span>⭐</span> <span>${data.label}</span> <span style="opacity: 0.7; font-weight: 400;">(${data.total} Midway ${data.total === 1 ? 'review' : 'reviews'})</span>`;
                badge.style.display = 'block';
            } catch (_) {}
        }

        // ============================
        //  Form submit
        // ============================
        meetingForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            hideError();

            const inputs = postcodeInputs.querySelectorAll('input[name="postcode"]');
            const locations = Array.from(inputs).map(input => input.value.trim()).filter(v => v);

            if (locations.length < 2) {
                showError('Please enter at least 2 postcodes.');
                return;
            }

            setLoading(true);

            try {
                const response = await fetch('/api/find', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        locations,
                        occasion: selectedOccasion,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || data.message || 'Something went wrong.');
                }

                allResults = [data.best, ...(data.alternatives || [])];
                currentResultIndex = 0;
                confirmedPlanId = null;
                allArrivedNotified = false;
                exitConfirmedMode();
                shareOverlay.style.display = 'none';

                renderAlerts(data.alerts || []);
                renderVenueResult(allResults[0], data.centroid);
                updateVoteCounter();
                resultsSection.style.display = 'block';
                fitAllMarkers();

                resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

            } catch (err) {
                showError(err.message);
            } finally {
                setLoading(false);
            }
        });
    });
    </script>
</body>
</html>
