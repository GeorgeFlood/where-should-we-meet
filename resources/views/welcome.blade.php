<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Where Should We Meet?</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen font-sans">

    <div class="max-w-2xl mx-auto px-4 py-12 sm:py-20">

        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-3">
                Where Should We Meet?
            </h1>
            <p class="text-lg text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                Enter everyone's postcodes, pick a venue type, and we'll find the fairest place to meet.
            </p>
        </div>

        <!-- Form Card -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 p-6 sm:p-8">

            <form id="meetingForm">
                <!-- Postcode Inputs Container -->
                <div id="postcodeInputs" class="space-y-3 mb-6">
                    <div class="flex items-center gap-3 postcode-row">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 text-sm font-semibold shrink-0">1</div>
                        <input
                            type="text"
                            placeholder="e.g. SW1A 1AA"
                            class="flex-1 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow text-base"
                            name="postcode"
                            required
                        >
                        <button type="button" class="remove-btn hidden text-slate-400 hover:text-red-500 transition-colors p-1" title="Remove">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-3 postcode-row">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 text-sm font-semibold shrink-0">2</div>
                        <input
                            type="text"
                            placeholder="e.g. E1 6AN"
                            class="flex-1 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow text-base"
                            name="postcode"
                            required
                        >
                        <button type="button" class="remove-btn hidden text-slate-400 hover:text-red-500 transition-colors p-1" title="Remove">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Add Person Button -->
                <button
                    type="button"
                    id="addPersonBtn"
                    class="w-full py-3 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xl text-slate-500 dark:text-slate-400 hover:border-indigo-400 hover:text-indigo-500 dark:hover:border-indigo-500 dark:hover:text-indigo-400 transition-colors text-sm font-medium mb-6"
                >
                    + Add another person
                </button>

                <!-- Venue Type Selector -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300 mb-3">Meet at a...</label>
                    <div id="venueTypeSelector" class="flex flex-wrap gap-2"></div>
                    <input type="hidden" name="type" id="venueType" value="any">
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-md hover:shadow-lg transition-all text-base disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span id="submitText">Find the fairest meeting point</span>
                    <span id="submitLoading" class="hidden items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Searching for the fairest spot...
                    </span>
                </button>
            </form>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="hidden mt-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 text-red-700 dark:text-red-400 text-sm">
            <p id="errorText"></p>
        </div>

        <!-- Results -->
        <div id="resultsSection" class="hidden mt-8 space-y-6">

            <!-- Best Result Card -->
            <div id="resultCard" class="rounded-2xl shadow-lg overflow-hidden transition-colors duration-300">

                <!-- Result Header (themed dynamically) -->
                <div id="resultHeader" class="px-6 sm:px-8 py-6 text-white">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <p id="resultSubtitle" class="text-sm font-medium mb-1 opacity-75">Best meeting point</p>
                            <div class="flex items-center gap-3 mb-2">
                                <span id="resultIcon" class="text-3xl"></span>
                                <h2 id="resultName" class="text-2xl sm:text-3xl font-bold truncate"></h2>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span id="resultTypeBadge" class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white"></span>
                                <span id="resultAddress" class="text-sm opacity-75"></span>
                            </div>
                        </div>
                        <a id="resultMapLink" href="#" target="_blank" class="shrink-0 mt-1 ml-4 px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5" title="Open in Google Maps">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Map
                        </a>
                    </div>
                </div>

                <!-- Reviews Snapshot -->
                <div id="reviewsSection" class="hidden px-6 sm:px-8 py-5 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3 mb-3">
                        <div id="ratingStars" class="flex items-center gap-1"></div>
                        <span id="ratingValue" class="text-sm font-bold text-slate-900 dark:text-slate-100"></span>
                        <span id="reviewCount" class="text-sm text-slate-500 dark:text-slate-400"></span>
                    </div>
                    <div id="reviewSnippets" class="space-y-3"></div>
                </div>

                <!-- Menu / Website Links -->
                <div id="venueLinksSection" class="hidden px-6 sm:px-8 py-4 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                    <div id="venueLinks" class="flex items-center gap-3 flex-wrap"></div>
                </div>

                <!-- Journey Times -->
                <div id="resultBody" class="px-6 sm:px-8 py-6 bg-white dark:bg-slate-900">
                    <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-4">Journey times</h3>
                    <div id="journeyTimes" class="space-y-3"></div>
                </div>

                <!-- Stats -->
                <div id="resultStats" class="border-t border-slate-200 dark:border-slate-800 px-6 sm:px-8 py-5 bg-slate-50 dark:bg-slate-800/50">
                    <div class="flex items-center justify-between text-sm">
                        <div class="text-center flex-1">
                            <p class="text-slate-500 dark:text-slate-400">Longest</p>
                            <p id="statMax" class="text-lg font-bold text-slate-900 dark:text-slate-100"></p>
                        </div>
                        <div class="w-px h-10 bg-slate-200 dark:bg-slate-700"></div>
                        <div class="text-center flex-1">
                            <p class="text-slate-500 dark:text-slate-400">Shortest</p>
                            <p id="statMin" class="text-lg font-bold text-slate-900 dark:text-slate-100"></p>
                        </div>
                        <div class="w-px h-10 bg-slate-200 dark:bg-slate-700"></div>
                        <div class="text-center flex-1">
                            <p class="text-slate-500 dark:text-slate-400">Spread</p>
                            <p id="statSpread" class="text-lg font-bold text-slate-900 dark:text-slate-100"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alternatives -->
            <div id="alternativesSection" class="hidden">
                <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Also consider</h3>
                <div id="alternativesList" class="space-y-3"></div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-slate-400 dark:text-slate-600 mt-10">
            Powered by TfL Journey Planner, OpenStreetMap &amp; Foursquare &middot; London, UK
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ============================
            //  Theme config per venue type
            // ============================
            const venueThemes = {
                pub: {
                    label: 'Pub',
                    icon: '🍺',
                    emoji: '🍻',
                    subtitle: 'Grab a pint at',
                    headerGradient: 'linear-gradient(135deg, #92400e, #78350f)',
                    barColor: 'bg-amber-500',
                    pillBg: 'bg-amber-50 dark:bg-amber-950/40',
                    pillBorder: 'border-amber-300 dark:border-amber-700',
                    pillText: 'text-amber-700 dark:text-amber-400',
                    pillActiveBg: 'bg-amber-100 dark:bg-amber-900/50',
                    pillActiveBorder: 'border-amber-500 dark:border-amber-500',
                    pillActiveText: 'text-amber-800 dark:text-amber-300',
                    altBadgeBg: 'bg-amber-100 dark:bg-amber-900/40',
                    altBadgeText: 'text-amber-700 dark:text-amber-400',
                },
                cafe: {
                    label: 'Cafe',
                    icon: '☕',
                    emoji: '🧁',
                    subtitle: 'Meet for a coffee at',
                    headerGradient: 'linear-gradient(135deg, #9a3412, #7c2d12)',
                    barColor: 'bg-orange-500',
                    pillBg: 'bg-orange-50 dark:bg-orange-950/40',
                    pillBorder: 'border-orange-300 dark:border-orange-700',
                    pillText: 'text-orange-700 dark:text-orange-400',
                    pillActiveBg: 'bg-orange-100 dark:bg-orange-900/50',
                    pillActiveBorder: 'border-orange-500 dark:border-orange-500',
                    pillActiveText: 'text-orange-800 dark:text-orange-300',
                    altBadgeBg: 'bg-orange-100 dark:bg-orange-900/40',
                    altBadgeText: 'text-orange-700 dark:text-orange-400',
                },
                restaurant: {
                    label: 'Restaurant',
                    icon: '🍽️',
                    emoji: '🥂',
                    subtitle: 'Dine together at',
                    headerGradient: 'linear-gradient(135deg, #9f1239, #881337)',
                    barColor: 'bg-rose-500',
                    pillBg: 'bg-rose-50 dark:bg-rose-950/40',
                    pillBorder: 'border-rose-300 dark:border-rose-700',
                    pillText: 'text-rose-700 dark:text-rose-400',
                    pillActiveBg: 'bg-rose-100 dark:bg-rose-900/50',
                    pillActiveBorder: 'border-rose-500 dark:border-rose-500',
                    pillActiveText: 'text-rose-800 dark:text-rose-300',
                    altBadgeBg: 'bg-rose-100 dark:bg-rose-900/40',
                    altBadgeText: 'text-rose-700 dark:text-rose-400',
                },
                station: {
                    label: 'Station',
                    icon: '🚂',
                    emoji: '🗺️',
                    subtitle: 'Rendezvous at',
                    headerGradient: 'linear-gradient(135deg, #334155, #1e293b)',
                    barColor: 'bg-sky-500',
                    pillBg: 'bg-sky-50 dark:bg-sky-950/40',
                    pillBorder: 'border-sky-300 dark:border-sky-700',
                    pillText: 'text-sky-700 dark:text-sky-400',
                    pillActiveBg: 'bg-sky-100 dark:bg-sky-900/50',
                    pillActiveBorder: 'border-sky-500 dark:border-sky-500',
                    pillActiveText: 'text-sky-800 dark:text-sky-300',
                    altBadgeBg: 'bg-sky-100 dark:bg-sky-900/40',
                    altBadgeText: 'text-sky-700 dark:text-sky-400',
                },
                any: {
                    label: 'Anywhere',
                    icon: '📍',
                    emoji: '✨',
                    subtitle: 'Best meeting point',
                    headerGradient: 'linear-gradient(135deg, #4f46e5, #7c3aed)',
                    barColor: 'bg-indigo-500',
                    pillBg: 'bg-slate-50 dark:bg-slate-800',
                    pillBorder: 'border-slate-300 dark:border-slate-600',
                    pillText: 'text-slate-600 dark:text-slate-400',
                    pillActiveBg: 'bg-indigo-50 dark:bg-indigo-900/50',
                    pillActiveBorder: 'border-indigo-500 dark:border-indigo-500',
                    pillActiveText: 'text-indigo-700 dark:text-indigo-300',
                    altBadgeBg: 'bg-slate-100 dark:bg-slate-800',
                    altBadgeText: 'text-slate-600 dark:text-slate-300',
                },
                other: {
                    label: 'Venue',
                    icon: '📍',
                    emoji: '✨',
                    subtitle: 'Best meeting point',
                    headerGradient: 'linear-gradient(135deg, #4f46e5, #7c3aed)',
                    barColor: 'bg-indigo-500',
                    altBadgeBg: 'bg-slate-100 dark:bg-slate-800',
                    altBadgeText: 'text-slate-600 dark:text-slate-300',
                },
            };

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
            const venueTypeInput = document.getElementById('venueType');

            // ============================
            //  Build venue type pill buttons
            // ============================
            const venueTypeSelector = document.getElementById('venueTypeSelector');
            const pillTypes = ['any', 'pub', 'cafe', 'restaurant', 'station'];

            function buildPills() {
                venueTypeSelector.innerHTML = '';
                pillTypes.forEach(type => {
                    const theme = venueThemes[type];
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.dataset.type = type;
                    btn.className = `venue-type-btn px-4 py-2 rounded-full text-sm font-medium border transition-all ${theme.pillBg} ${theme.pillBorder} ${theme.pillText}`;
                    btn.textContent = `${theme.icon}  ${theme.label}`;
                    if (type === 'any') {
                        btn.classList.add('active');
                        btn.className = `venue-type-btn active px-4 py-2 rounded-full text-sm font-semibold border-2 transition-all ${theme.pillActiveBg} ${theme.pillActiveBorder} ${theme.pillActiveText}`;
                    }
                    venueTypeSelector.appendChild(btn);
                });
            }
            buildPills();

            venueTypeSelector.addEventListener('click', function (e) {
                const btn = e.target.closest('.venue-type-btn');
                if (!btn) return;

                venueTypeInput.value = btn.dataset.type;

                // Re-style all buttons
                venueTypeSelector.querySelectorAll('.venue-type-btn').forEach(b => {
                    const t = venueThemes[b.dataset.type];
                    b.classList.remove('active');
                    b.className = `venue-type-btn px-4 py-2 rounded-full text-sm font-medium border transition-all ${t.pillBg} ${t.pillBorder} ${t.pillText}`;
                });

                // Active state
                const theme = venueThemes[btn.dataset.type];
                btn.classList.add('active');
                btn.className = `venue-type-btn active px-4 py-2 rounded-full text-sm font-semibold border-2 transition-all ${theme.pillActiveBg} ${theme.pillActiveBorder} ${theme.pillActiveText}`;
            });

            // ============================
            //  Dynamic postcode rows
            // ============================
            function updateRowNumbers() {
                const rows = postcodeInputs.querySelectorAll('.postcode-row');
                rows.forEach((row, i) => {
                    row.querySelector('div').textContent = i + 1;
                    const removeBtn = row.querySelector('.remove-btn');
                    removeBtn.classList.toggle('hidden', rows.length <= 2);
                });
            }

            addPersonBtn.addEventListener('click', function () {
                const count = postcodeInputs.querySelectorAll('.postcode-row').length;
                const row = document.createElement('div');
                row.className = 'flex items-center gap-3 postcode-row';
                row.innerHTML = `
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 text-sm font-semibold shrink-0">${count + 1}</div>
                    <input type="text" placeholder="e.g. N1 9GU"
                        class="flex-1 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow text-base"
                        name="postcode" required>
                    <button type="button" class="remove-btn text-slate-400 hover:text-red-500 transition-colors p-1" title="Remove">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>`;
                postcodeInputs.appendChild(row);
                row.querySelector('input').focus();
                updateRowNumbers();
            });

            postcodeInputs.addEventListener('click', function (e) {
                const removeBtn = e.target.closest('.remove-btn');
                if (removeBtn) {
                    removeBtn.closest('.postcode-row').remove();
                    updateRowNumbers();
                }
            });

            // ============================
            //  Helpers
            // ============================
            function setLoading(loading) {
                submitBtn.disabled = loading;
                submitText.classList.toggle('hidden', loading);
                submitLoading.classList.toggle('hidden', !loading);
                submitLoading.classList.toggle('flex', loading);
            }

            function showError(message) {
                errorText.textContent = message;
                errorMessage.classList.remove('hidden');
                resultsSection.classList.add('hidden');
            }

            function hideError() {
                errorMessage.classList.add('hidden');
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
            //  Render results with theming
            // ============================
            function getTheme(venueType) {
                return venueThemes[venueType] || venueThemes['other'];
            }

            function renderStars(rating) {
                let html = '';
                const full = Math.floor(rating);
                const half = rating - full >= 0.3;
                const empty = 5 - full - (half ? 1 : 0);

                for (let i = 0; i < full; i++) {
                    html += '<svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
                }
                if (half) {
                    html += '<svg class="w-4 h-4 text-amber-400" viewBox="0 0 20 20"><defs><linearGradient id="halfStar"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="#d1d5db"/></linearGradient></defs><path fill="url(#halfStar)" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
                }
                for (let i = 0; i < empty; i++) {
                    html += '<svg class="w-4 h-4 text-slate-300 dark:text-slate-600" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
                }
                return html;
            }

            function renderReviewSnippet(tip) {
                return `
                    <div class="bg-slate-50 dark:bg-slate-800/60 rounded-lg p-3">
                        ${tip.created_at ? `<span class="text-xs text-slate-400 dark:text-slate-500 mb-1 block">${tip.created_at}</span>` : ''}
                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">"${tip.text}"</p>
                    </div>`;
            }

            function renderReviews(venue) {
                const section = document.getElementById('reviewsSection');
                const starsContainer = document.getElementById('ratingStars');
                const ratingValue = document.getElementById('ratingValue');
                const reviewCount = document.getElementById('reviewCount');
                const snippetsContainer = document.getElementById('reviewSnippets');

                // Hide if no rating data
                if (!venue.rating) {
                    section.classList.add('hidden');
                    return;
                }

                section.classList.remove('hidden');

                // Rating stars + number
                starsContainer.innerHTML = renderStars(venue.rating);
                ratingValue.textContent = venue.rating.toFixed(1);
                reviewCount.textContent = venue.review_count
                    ? `(${venue.review_count.toLocaleString()} ratings)`
                    : '';

                // Review snippets
                snippetsContainer.innerHTML = '';
                if (venue.reviews && venue.reviews.length > 0) {
                    venue.reviews.forEach(review => {
                        if (review.text) {
                            snippetsContainer.insertAdjacentHTML('beforeend', renderReviewSnippet(review));
                        }
                    });
                }
            }

            function renderVenueLinks(venue) {
                const section = document.getElementById('venueLinksSection');
                const container = document.getElementById('venueLinks');
                container.innerHTML = '';

                const hasMenu = venue.menu_url;
                const hasWebsite = venue.website;

                if (!hasMenu && !hasWebsite) {
                    section.classList.add('hidden');
                    return;
                }

                section.classList.remove('hidden');

                if (hasMenu) {
                    container.insertAdjacentHTML('beforeend', `
                        <a href="${venue.menu_url}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            View menu
                        </a>`);
                }

                if (hasWebsite) {
                    container.insertAdjacentHTML('beforeend', `
                        <a href="${venue.website}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Website
                        </a>`);
                }
            }

            function renderInlineRating(rating, reviewCount) {
                if (!rating) return '';
                const stars = renderStars(rating);
                const count = reviewCount ? `<span class="text-xs text-slate-400 dark:text-slate-500">(${reviewCount.toLocaleString()})</span>` : '';
                return `<span class="inline-flex items-center gap-1">${stars} <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">${rating.toFixed(1)}</span> ${count}</span>`;
            }

            function renderVenueResult(venue) {
                const theme = getTheme(venue.type);
                const header = document.getElementById('resultHeader');

                // Apply themed gradient to header
                header.style.background = theme.headerGradient;

                // Header content
                document.getElementById('resultSubtitle').textContent = theme.subtitle;
                document.getElementById('resultIcon').textContent = theme.icon;
                document.getElementById('resultName').textContent = venue.name;
                document.getElementById('resultTypeBadge').textContent = `${theme.emoji}  ${theme.label}`;
                document.getElementById('resultAddress').textContent = venue.address || '';
                document.getElementById('resultMapLink').href = `https://www.google.com/maps/search/?api=1&query=${venue.lat},${venue.lng}`;

                // Journey bars — themed bar color
                const container = document.getElementById('journeyTimes');
                container.innerHTML = '';
                const maxTime = venue.max;

                venue.times.forEach((t, i) => {
                    const pct = maxTime > 0 ? (t.duration / maxTime) * 100 : 0;
                    const row = document.createElement('div');
                    row.className = 'flex items-center gap-4';
                    row.innerHTML = `
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-300 w-24 shrink-0 truncate">${t.from}</span>
                        <div class="flex-1 bg-slate-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden">
                            <div class="${theme.barColor} h-full rounded-full transition-all duration-700" style="width: ${pct}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 w-16 text-right">${formatDuration(t.duration)}</span>`;
                    container.appendChild(row);
                });

                // Stats
                document.getElementById('statMax').textContent = formatDuration(venue.max);
                document.getElementById('statMin').textContent = formatDuration(venue.min);
                document.getElementById('statSpread').textContent = formatDuration(venue.spread);

                // Reviews & links
                renderReviews(venue);
                renderVenueLinks(venue);
            }

            function renderAlternatives(alternatives) {
                const section = document.getElementById('alternativesSection');
                const list = document.getElementById('alternativesList');
                list.innerHTML = '';

                if (!alternatives || alternatives.length === 0) {
                    section.classList.add('hidden');
                    return;
                }

                section.classList.remove('hidden');

                alternatives.forEach(venue => {
                    const theme = getTheme(venue.type);
                    const ratingHtml = venue.rating ? renderInlineRating(venue.rating, venue.review_count) : '';

                    // Build inline link pills for menu/website
                    let altLinks = '';
                    if (venue.menu_url) {
                        altLinks += `<a href="${venue.menu_url}" target="_blank" rel="noopener noreferrer" class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors">Menu</a>`;
                    }
                    if (venue.website) {
                        altLinks += `<a href="${venue.website}" target="_blank" rel="noopener noreferrer" class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 transition-colors">Website</a>`;
                    }

                    const card = document.createElement('div');
                    card.className = 'bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 flex items-center justify-between';
                    card.innerHTML = `
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">${theme.icon}</span>
                                <p class="font-semibold text-slate-900 dark:text-slate-100 truncate">${venue.name}</p>
                            </div>
                            ${ratingHtml ? `<div class="mt-1.5 ml-7">${ratingHtml}</div>` : ''}
                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full ${theme.altBadgeBg} ${theme.altBadgeText}">${theme.label}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">Longest: ${formatDuration(venue.max)} &middot; Spread: ${formatDuration(venue.spread)}</span>
                                ${altLinks}
                            </div>
                        </div>
                        <a href="https://www.google.com/maps/search/?api=1&query=${venue.lat},${venue.lng}" target="_blank"
                           class="shrink-0 ml-4 px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Map
                        </a>`;
                    list.appendChild(card);
                });
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
                            type: venueTypeInput.value,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || data.message || 'Something went wrong.');
                    }

                    renderVenueResult(data.best);
                    renderAlternatives(data.alternatives);
                    resultsSection.classList.remove('hidden');
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
