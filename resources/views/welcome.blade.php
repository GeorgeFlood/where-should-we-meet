<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Where Should We Meet?</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; font-family: 'Instrument Sans', system-ui, sans-serif; }
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
            .panel {
                top: auto;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                max-height: 65vh;
            }
        }

        .panel-card {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        @media (max-width: 640px) {
            .panel-card { border-radius: 20px 20px 0 0; }
        }

        .panel-scroll {
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
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
            flex: 1;
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
        <div class="panel-card flex flex-col" style="max-height: 100%;">

            <!-- Panel header -->
            <div style="padding: 20px 20px 0;">
                <h1 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 2px;">Where Should We Meet?</h1>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">Drop your postcodes, pick the vibe, and we'll find the best spot.</p>
            </div>

            <!-- Scrollable content -->
            <div class="panel-scroll" style="flex: 1; min-height: 0;">
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
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">What's the vibe?</label>
                        <div id="occasionSelector" style="display: flex; flex-wrap: wrap; gap: 6px;"></div>
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

                    <div style="height: 1px; background: #e2e8f0; margin: 0 20px 16px;"></div>

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

                    <!-- Vote buttons -->
                    <div style="padding: 10px 20px; display: flex; gap: 8px;">
                        <button type="button" id="voteYesBtn" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 12px; background: #ecfdf5; border: 1.5px solid #a7f3d0; border-radius: 12px; color: #059669; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.15s;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Let's go!
                        </button>
                        <button type="button" id="voteNoBtn" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 12px; background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 12px; color: #dc2626; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.15s;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Next option
                        </button>
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
                        <h3 style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px;">Plan your arrival</h3>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <label for="arrivalTime" style="font-size: 13px; font-weight: 500; color: #475569; white-space: nowrap;">Arrive by</label>
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

                    <!-- Fare split -->
                    <div id="fareSplitSection" style="display: none; margin: 10px 20px 0; padding: 16px; background: linear-gradient(135deg, #ecfdf5, #f0fdf4); border: 1.5px solid #bbf7d0; border-radius: 14px;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 10px;">
                            <span style="font-size: 16px;">💳</span>
                            <h3 style="font-size: 12px; font-weight: 600; color: #166534; text-transform: uppercase; letter-spacing: 0.05em;">Travel fares</h3>
                        </div>
                        <div id="fareBreakdown" style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px;"></div>
                        <div style="border-top: 1px solid #bbf7d0; padding-top: 10px;">
                            <div id="fareSplitResult" style="font-size: 13px; color: #15803d; line-height: 1.5;"></div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <div style="padding: 10px 20px; font-size: 10px; color: #94a3b8; text-align: center; border-top: 1px solid #f1f5f9;">
                Powered by TfL &middot; OpenStreetMap
            </div>
        </div>
    </div>

    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
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
        });

        L.control.zoom({ position: 'topright' }).addTo(map);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
            maxZoom: 19,
        }).addTo(map);

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
            map.fitBounds(group.getBounds().pad(0.15), { maxZoom: 14, animate: true, duration: 0.8 });
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
            casual:      { label: 'Casual',      icon: '🍻', desc: 'Pubs, bars & casual spots',            subtitle: 'Your casual hangout' },
            date:        { label: 'Date night',   icon: '🌹', desc: 'Top restaurants & cocktail bars',     subtitle: 'Perfect for date night' },
            coffee:      { label: 'Coffee',       icon: '☕', desc: 'Cafes & coffee shops',                 subtitle: 'Coffee & chat at' },
            work:        { label: 'Work',         icon: '💼', desc: 'Quiet cafes & meeting spots',          subtitle: 'Work meeting at' },
            celebration: { label: 'Entertainment', icon: '🎉', desc: 'Bowling, cinema, karaoke & arcades',    subtitle: 'Entertainment pick' },
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
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.occasion = id;
            btn.className = 'pill-btn' + (id === 'casual' ? ' active' : '');
            btn.innerHTML = `${occ.icon}&nbsp; ${occ.label}`;
            btn.title = occ.desc;
            occasionSelector.appendChild(btn);
        });

        occasionSelector.addEventListener('click', function (e) {
            const btn = e.target.closest('.pill-btn');
            if (!btn) return;
            selectedOccasion = btn.dataset.occasion;
            occasionInput.value = selectedOccasion;
            occasionSelector.querySelectorAll('.pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
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
            const section = document.getElementById('fareSplitSection');
            const breakdown = document.getElementById('fareBreakdown');
            const result = document.getElementById('fareSplitResult');

            const fares = venue.times.map(t => ({
                from: t.from,
                pence: t.fare?.total_pence ?? null,
                chargeLevel: t.fare?.charge_level ?? null,
                zones: (t.fare?.zone_low && t.fare?.zone_high)
                    ? (t.fare.zone_low === t.fare.zone_high ? `Zone ${t.fare.zone_low}` : `Zones ${t.fare.zone_low}-${t.fare.zone_high}`)
                    : null,
            }));

            const allHaveFares = fares.every(f => f.pence !== null);
            if (!allHaveFares) {
                section.style.display = 'none';
                return;
            }

            breakdown.innerHTML = fares.map(f => `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px; color: #374151; font-weight: 500;">${f.from}</span>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        ${f.zones ? `<span style="font-size: 10px; color: #6b7280; background: #f3f4f6; padding: 1px 6px; border-radius: 6px;">${f.zones}</span>` : ''}
                        ${f.chargeLevel ? `<span style="font-size: 10px; color: #6b7280; background: #f3f4f6; padding: 1px 6px; border-radius: 6px;">${f.chargeLevel}</span>` : ''}
                        <span style="font-size: 14px; font-weight: 700; color: #166534;">£${(f.pence / 100).toFixed(2)}</span>
                    </div>
                </div>
            `).join('');

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
                    settlements.push({
                        payer: underBalances[ui].from,
                        payee: overBalances[oi].from,
                        pence: amount,
                    });
                }
                overBalances[oi].remaining -= amount;
                underBalances[ui].remaining -= amount;
                if (overBalances[oi].remaining <= 0) oi++;
                if (underBalances[ui].remaining <= 0) ui++;
            }

            let html = `<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <span style="font-size: 12px; color: #6b7280;">Total travel cost</span>
                <span style="font-size: 14px; font-weight: 700; color: #166534;">£${(totalPence / 100).toFixed(2)}</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 12px; color: #6b7280;">Fair share each</span>
                <span style="font-size: 14px; font-weight: 700; color: #166534;">£${(fairSharePence / 100).toFixed(2)}</span>
            </div>`;

            if (settlements.length === 0) {
                html += `<p style="font-size: 13px; color: #059669; font-weight: 600;">All even — no one owes anything!</p>`;
            } else {
                html += settlements.map(s =>
                    `<div style="display: flex; align-items: center; gap: 6px; padding: 6px 10px; background: white; border-radius: 8px; border: 1px solid #d1fae5;">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">${s.payer}</span>
                        <span style="font-size: 11px; color: #6b7280;">owes</span>
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">${s.payee}</span>
                        <span style="margin-left: auto; font-size: 14px; font-weight: 700; color: #059669;">£${(s.pence / 100).toFixed(2)}</span>
                    </div>`
                ).join('');
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

                const fareHtml = t.fare ? `<span style="font-size: 11px; font-weight: 600; color: #059669; background: #ecfdf5; padding: 2px 7px; border-radius: 8px; flex-shrink: 0;">£${(t.fare.total_pence / 100).toFixed(2)}</span>` : '';

                const rowHtml = `
                    <div class="journey-row" style="padding: 6px 0;">
                        <span style="font-size: 13px; font-weight: 500; color: #475569; width: 80px; flex-shrink: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${t.from}</span>
                        <div class="journey-bar-bg">
                            <div class="journey-bar-fill" style="width: ${pct}%; background: ${theme.barColor};"></div>
                        </div>
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b; width: 55px; text-align: right; flex-shrink: 0;">${formatDuration(t.duration)}</span>
                        ${fareHtml}
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
        const shareOverlay = document.getElementById('shareOverlay');
        const shareWhatsApp = document.getElementById('shareWhatsApp');
        const shareCopyBtn = document.getElementById('shareCopyBtn');
        const shareCopyFeedback = document.getElementById('shareCopyFeedback');

        function updateVoteCounter() {
            const counter = allResults.length > 1 ? ` (${currentResultIndex + 1}/${allResults.length})` : '';
            voteNoBtn.innerHTML = `<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Next option${counter}`;
        }

        voteNoBtn.addEventListener('click', function () {
            if (allResults.length <= 1) return;
            currentResultIndex = (currentResultIndex + 1) % allResults.length;
            const nextVenue = allResults[currentResultIndex];
            shareOverlay.style.display = 'none';
            renderVenueResult(nextVenue, null);
            updateVoteCounter();
            fitAllMarkers();
        });

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

                shareOverlay.style.display = 'block';
                shareOverlay.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } catch (err) {
                showError('Could not create share link. Please try again.');
            } finally {
                voteYesBtn.disabled = false;
                voteYesBtn.innerHTML = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Let\'s go!';
            }
        });

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
