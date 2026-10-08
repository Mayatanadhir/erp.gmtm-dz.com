@props([
    'class' => 'w-14 h-14 sm:w-16 sm:h-16 shrink-0',
])

{{-- Precision Volumetric Standard Prover (Mastering Gas MG-1000): Brushed Stainless Steel Vessel, Graduated Sight Glass Column, Dual Calibration Scale Plates, Brass Valve & Spec Nameplate --}}
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        {{-- Vessel Base Shadow --}}
        <radialGradient id="prv-shadow" cx="32" cy="61.5" r="16" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#000000" stop-opacity="0.35" />
            <stop offset="70%" stop-color="#000000" stop-opacity="0.12" />
            <stop offset="100%" stop-color="#000000" stop-opacity="0" />
        </radialGradient>

        {{-- Main Cylindrical Stainless Steel Body Gradient (Horizontal Lighting Stripe) --}}
        <linearGradient id="prv-steel-cyl" x1="16" y1="0" x2="48" y2="0" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#475569" />
            <stop offset="8%" stop-color="#94A3B8" />
            <stop offset="22%" stop-color="#E2E8F0" />
            <stop offset="34%" stop-color="#FFFFFF" />
            <stop offset="52%" stop-color="#CBD5E1" />
            <stop offset="68%" stop-color="#F8FAFC" />
            <stop offset="86%" stop-color="#94A3B8" />
            <stop offset="100%" stop-color="#334155" />
        </linearGradient>

        {{-- Conical Reducer Shoulder Metallic Gradient --}}
        <linearGradient id="prv-steel-cone" x1="16" y1="28" x2="48" y2="37" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#334155" />
            <stop offset="14%" stop-color="#64748B" />
            <stop offset="30%" stop-color="#FFFFFF" />
            <stop offset="52%" stop-color="#CBD5E1" />
            <stop offset="72%" stop-color="#F1F5F9" />
            <stop offset="88%" stop-color="#64748B" />
            <stop offset="100%" stop-color="#1E293B" />
        </linearGradient>

        {{-- Vertical Calibration Scale Plates Gradient --}}
        <linearGradient id="prv-steel-plate" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#FFFFFF" />
            <stop offset="25%" stop-color="#E2E8F0" />
            <stop offset="70%" stop-color="#CBD5E1" />
            <stop offset="100%" stop-color="#94A3B8" />
        </linearGradient>

        {{-- Tubular Handle Gradient (Vertical Cylinder) --}}
        <linearGradient id="prv-steel-handle" x1="0" y1="52" x2="0" y2="54.2" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#94A3B8" />
            <stop offset="25%" stop-color="#FFFFFF" />
            <stop offset="65%" stop-color="#CBD5E1" />
            <stop offset="100%" stop-color="#475569" />
        </linearGradient>

        {{-- Flange Rings & Mushroom Knob Gradient --}}
        <linearGradient id="prv-steel-cap" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stop-color="#475569" />
            <stop offset="25%" stop-color="#E2E8F0" />
            <stop offset="50%" stop-color="#FFFFFF" />
            <stop offset="75%" stop-color="#CBD5E1" />
            <stop offset="100%" stop-color="#334155" />
        </linearGradient>

        {{-- Guard Rail Pipes Gradient --}}
        <linearGradient id="prv-steel-pipe" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stop-color="#64748B" />
            <stop offset="35%" stop-color="#FFFFFF" />
            <stop offset="70%" stop-color="#CBD5E1" />
            <stop offset="100%" stop-color="#475569" />
        </linearGradient>

        {{-- Polished Brass / Gold Valve Collar --}}
        <linearGradient id="prv-gold-valve" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stop-color="#92400E" />
            <stop offset="25%" stop-color="#F59E0B" />
            <stop offset="50%" stop-color="#FEF08A" />
            <stop offset="75%" stop-color="#D97706" />
            <stop offset="100%" stop-color="#78350F" />
        </linearGradient>

        {{-- Central Sight Glass Tube Gradient --}}
        <linearGradient id="prv-glass-tube" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stop-color="#64748B" />
            <stop offset="20%" stop-color="#E0F2FE" />
            <stop offset="55%" stop-color="#BAE6FD" />
            <stop offset="80%" stop-color="#7DD3FC" />
            <stop offset="100%" stop-color="#475569" />
        </linearGradient>

        {{-- Handle Gusset Bracket Gradient --}}
        <linearGradient id="prv-bracket" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#F1F5F9" />
            <stop offset="45%" stop-color="#CBD5E1" />
            <stop offset="100%" stop-color="#64748B" />
        </linearGradient>

        {{-- Stepped Bottom Base Rim Gradient --}}
        <linearGradient id="prv-steel-skirt" x1="16" y1="0" x2="48" y2="0" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#334155" />
            <stop offset="25%" stop-color="#94A3B8" />
            <stop offset="50%" stop-color="#F1F5F9" />
            <stop offset="75%" stop-color="#CBD5E1" />
            <stop offset="100%" stop-color="#1E293B" />
        </linearGradient>
    </defs>

    {{-- 0. Ambient Ground Contact Shadow --}}
    <ellipse cx="32" cy="61.5" rx="16.5" ry="1.5" fill="url(#prv-shadow)" />

    {{-- 1. Outer Guard Rail Loops (Left & Right Side Handles) --}}
    <path d="M 23.6 28 V 14.5 C 23.6 13.5 24.5 13.2 25.5 13.5" stroke="url(#prv-steel-pipe)" stroke-width="1.2" stroke-linecap="round" />
    <line x1="24.8" y1="13.7" x2="26.4" y2="13.7" stroke="#94A3B8" stroke-width="0.8" />
    <line x1="23.3" y1="15" x2="23.3" y2="27" stroke="#FFFFFF" stroke-width="0.35" stroke-linecap="round" opacity="0.8" />

    <path d="M 40.4 28 V 14.5 C 40.4 13.5 39.5 13.2 38.5 13.5" stroke="url(#prv-steel-pipe)" stroke-width="1.2" stroke-linecap="round" />
    <line x1="39.2" y1="13.7" x2="37.6" y2="13.7" stroke="#94A3B8" stroke-width="0.8" />
    <line x1="40.1" y1="15" x2="40.1" y2="27" stroke="#FFFFFF" stroke-width="0.35" stroke-linecap="round" opacity="0.8" />

    {{-- Structural Guide Rods (Behind Glass & Plates) --}}
    <line x1="29.2" y1="8" x2="29.2" y2="26.5" stroke="#94A3B8" stroke-width="0.7" />
    <line x1="34.8" y1="8" x2="34.8" y2="26.5" stroke="#94A3B8" stroke-width="0.7" />

    {{-- 2. Main Vessel Cylindrical Body & Bottom Skirt --}}
    <path d="M 16 37 L 16 57.5 C 16 60.5 48 60.5 48 57.5 L 48 37 C 40 35.8 24 35.8 16 37 Z" fill="url(#prv-steel-cyl)" stroke="#64748B" stroke-width="0.35" />

    {{-- Cylindrical Specular Highlights (Left and Center) --}}
    <rect x="23" y="37" width="5.5" height="21" fill="#FFFFFF" opacity="0.22" />
    <rect x="24.8" y="37" width="1.6" height="21" fill="#FFFFFF" opacity="0.45" />
    <rect x="38.5" y="37" width="2.8" height="21" fill="#FFFFFF" opacity="0.18" />

    {{-- Circumferential Weld Bead Line --}}
    <path d="M 16 50.2 C 24 51.5 40 51.5 48 50.2" stroke="#CBD5E1" stroke-width="0.45" stroke-dasharray="0.8,0.4" opacity="0.9" />

    {{-- Stepped Bottom Base Rim --}}
    <path d="M 16.4 57.6 C 16.4 60.6 47.6 60.6 47.6 57.6 L 47.8 59.8 C 47.8 62.4 16.2 62.4 16.2 59.8 Z" fill="url(#prv-steel-skirt)" stroke="#475569" stroke-width="0.35" />
    <path d="M 17 60.2 C 24 61.8 40 61.8 47 60.2" stroke="#FFFFFF" stroke-width="0.3" opacity="0.5" />

    {{-- 3. Conical Reducer Shoulder (Shoulder Slope) --}}
    <path d="M 16 37 C 24 35.8 40 35.8 48 37 L 36.6 28.5 C 33.6 28 30.4 28 27.4 28.5 L 16 37 Z" fill="url(#prv-steel-cone)" stroke="#64748B" stroke-width="0.35" />

    {{-- Conical Specular Ray Reflections --}}
    <polygon points="23,36.5 27.2,28.5 29.2,28.5 26.5,36.5" fill="#FFFFFF" opacity="0.32" />
    <polygon points="34.5,36.5 33,28.5 34.6,28.5 37.5,36.5" fill="#FFFFFF" opacity="0.18" />

    {{-- 4. Neck Flange Collar & Sampling Drain Petcock --}}
    <path d="M 27.2 27 C 27.2 26.4 36.8 26.4 36.8 27 L 37.2 28.6 C 37.2 29.2 26.8 29.2 26.8 28.6 Z" fill="url(#prv-steel-cap)" stroke="#64748B" stroke-width="0.3" />

    {{-- Collar Hex Cap Screws --}}
    <circle cx="28.2" cy="27.8" r="0.4" fill="#E2E8F0" stroke="#475569" stroke-width="0.15" />
    <circle cx="30.7" cy="28.1" r="0.4" fill="#FFFFFF" stroke="#475569" stroke-width="0.15" />
    <circle cx="33.3" cy="28.1" r="0.4" fill="#FFFFFF" stroke="#475569" stroke-width="0.15" />
    <circle cx="35.8" cy="27.8" r="0.4" fill="#CBD5E1" stroke="#475569" stroke-width="0.15" />

    {{-- Angled Forward Sampling Drain Port Fitting --}}
    <path d="M 33.4 28.4 L 35.2 30.2 L 35.9 29.5 L 34.1 27.7 Z" fill="url(#prv-steel-cap)" stroke="#475569" stroke-width="0.2" />
    <circle cx="35.6" cy="30" r="0.55" fill="#F59E0B" stroke="#B45309" stroke-width="0.2" />

    {{-- 5. Brass / Golden Hex Valve Nut (Base of Sight Tube) --}}
    <rect x="29.6" y="24.8" width="4.8" height="2.2" rx="0.4" fill="url(#prv-gold-valve)" stroke="#B45309" stroke-width="0.25" />
    <line x1="31.2" y1="24.9" x2="31.2" y2="26.9" stroke="#FEF08A" stroke-width="0.35" />
    <line x1="32.8" y1="24.9" x2="32.8" y2="26.9" stroke="#92400E" stroke-width="0.35" />

    {{-- 6. Central Measurement Column (Sight Glass Tube) --}}
    <rect x="30.5" y="8" width="3" height="17" rx="1.5" fill="url(#prv-glass-tube)" stroke="#64748B" stroke-width="0.25" />

    {{-- Inner Calibration Liquid Column --}}
    <rect x="30.8" y="15.5" width="2.4" height="9.2" fill="#0284C7" opacity="0.32" />
    <line x1="30.8" y1="15.5" x2="33.2" y2="15.5" stroke="#38BDF8" stroke-width="0.5" stroke-linecap="round" />

    {{-- Sight Glass Longitudinal Reflection Line --}}
    <line x1="31.1" y1="8.5" x2="31.1" y2="24.5" stroke="#FFFFFF" stroke-width="0.45" stroke-linecap="round" opacity="0.9" />

    {{-- Sight Glass Upper & Lower Steel Bushings --}}
    <rect x="29.8" y="7.6" width="4.4" height="1" rx="0.3" fill="url(#prv-steel-cap)" stroke="#64748B" stroke-width="0.2" />
    <rect x="29.8" y="24" width="4.4" height="0.9" rx="0.3" fill="url(#prv-steel-cap)" stroke="#64748B" stroke-width="0.2" />

    {{-- 7. Left Scale Plate (Specification & Cautionary Markings) --}}
    <rect x="24.4" y="9.5" width="4.8" height="14.2" rx="0.6" fill="url(#prv-steel-plate)" stroke="#94A3B8" stroke-width="0.35" />
    {{-- Left Plate Fasteners --}}
    <circle cx="26.8" cy="10.3" r="0.35" fill="#475569" />
    <circle cx="26.8" cy="22.9" r="0.35" fill="#475569" />
    {{-- Swirl Gas Graphic --}}
    <path d="M 26.2 11.6 C 26.6 11.2 27.4 11.2 27.5 11.7 C 27.6 12.2 26.5 12.3 26.9 12.8" stroke="#334155" stroke-width="0.35" fill="none" />
    {{-- Micro Engraved Lines --}}
    <rect x="25.2" y="13.4" width="3.2" height="0.35" rx="0.15" fill="#334155" />
    <rect x="25.4" y="14.4" width="2.8" height="0.3" rx="0.15" fill="#475569" />
    <rect x="25.2" y="15.5" width="3.2" height="0.3" rx="0.15" fill="#475569" />
    <rect x="25.4" y="16.6" width="2.8" height="0.3" rx="0.15" fill="#475569" />
    <rect x="25.2" y="17.7" width="3.2" height="0.3" rx="0.15" fill="#475569" />
    <rect x="25.4" y="18.8" width="2.8" height="0.3" rx="0.15" fill="#475569" />
    {{-- Caution Triangle --}}
    <polygon points="26.8,20.3 25.8,21.8 27.8,21.8" stroke="#334155" stroke-width="0.35" fill="none" />
    <circle cx="26.8" cy="21.3" r="0.2" fill="#334155" />

    {{-- 8. Right Scale Plate (Precision Graduation Rules & Manual Icon) --}}
    <rect x="34.8" y="9.5" width="4.8" height="14.2" rx="0.6" fill="url(#prv-steel-plate)" stroke="#94A3B8" stroke-width="0.35" />
    {{-- Right Plate Fasteners --}}
    <circle cx="37.2" cy="10.3" r="0.35" fill="#475569" />
    <circle cx="37.2" cy="22.9" r="0.35" fill="#475569" />
    {{-- Manual Instruction Book Icon --}}
    <path d="M 36.4 11.5 C 36.8 11.2 37.2 11.3 37.2 12.2 M 38 11.5 C 37.6 11.2 37.2 11.3 37.2 12.2 M 36.4 12.6 C 36.8 12.3 37.2 12.4 37.2 13.2 M 38 12.6 C 37.6 12.3 37.2 12.4 37.2 13.2" stroke="#334155" stroke-width="0.3" fill="none" />
    {{-- Graduated Measurement Scale --}}
    <line x1="36.5" y1="14" x2="36.5" y2="20.2" stroke="#64748B" stroke-width="0.25" />
    <line x1="36.5" y1="14.2" x2="38.2" y2="14.2" stroke="#334155" stroke-width="0.35" />
    <line x1="36.5" y1="15.2" x2="37.5" y2="15.2" stroke="#475569" stroke-width="0.25" />
    <line x1="36.5" y1="16.2" x2="37.5" y2="16.2" stroke="#475569" stroke-width="0.25" />
    <line x1="36.5" y1="17.2" x2="38.5" y2="17.2" stroke="#0F172A" stroke-width="0.45" /> {{-- Zero Reference Mark --}}
    <line x1="36.5" y1="18.2" x2="37.5" y2="18.2" stroke="#475569" stroke-width="0.25" />
    <line x1="36.5" y1="19.2" x2="37.5" y2="19.2" stroke="#475569" stroke-width="0.25" />
    <line x1="36.5" y1="20.2" x2="38.2" y2="20.2" stroke="#334155" stroke-width="0.35" />
    {{-- Caution Triangle --}}
    <polygon points="37.2,20.8 36.2,22.2 38.2,22.2" stroke="#334155" stroke-width="0.35" fill="none" />
    <circle cx="37.2" cy="21.7" r="0.2" fill="#334155" />

    {{-- 9. Upper Tower Flange Assembly & Clamping Bolts --}}
    <rect x="25.5" y="4.6" width="13" height="2.2" rx="0.5" fill="url(#prv-steel-cap)" stroke="#94A3B8" stroke-width="0.35" />
    <path d="M 26 6.8 H 38 L 37.2 7.7 H 26.8 Z" fill="#64748B" />

    {{-- Vertical Upper Tie Bolts --}}
    <rect x="26.7" y="7.6" width="1" height="2" rx="0.2" fill="#CBD5E1" stroke="#475569" stroke-width="0.15" />
    <rect x="29.9" y="7.6" width="1" height="2" rx="0.2" fill="#FFFFFF" stroke="#475569" stroke-width="0.15" />
    <rect x="33.1" y="7.6" width="1" height="2" rx="0.2" fill="#FFFFFF" stroke="#475569" stroke-width="0.15" />
    <rect x="36.3" y="7.6" width="1" height="2" rx="0.2" fill="#94A3B8" stroke="#475569" stroke-width="0.15" />

    {{-- Top Mushroom Adjustment Knob / Lifting Eye --}}
    <rect x="31" y="3" width="2" height="1.8" rx="0.3" fill="url(#prv-steel-cap)" stroke="#64748B" stroke-width="0.2" />
    <path d="M 29.4 3 C 29.4 1.8 30.6 1.2 32 1.2 C 33.4 1.2 34.6 1.8 34.6 3 C 34.4 3.4 29.6 3.4 29.4 3 Z" fill="url(#prv-steel-cap)" stroke="#475569" stroke-width="0.35" />
    <ellipse cx="32" cy="2" rx="1.6" ry="0.5" fill="#FFFFFF" opacity="0.65" />
    <circle cx="32" cy="1.2" r="0.45" fill="#E2E8F0" />

    {{-- 10. Front Anodized Black Specification Plate ("MASTERING GAS - MG-1000") --}}
    <rect x="22.5" y="39.6" width="19" height="7.8" rx="0.8" fill="#0B1120" stroke="#94A3B8" stroke-width="0.4" />

    {{-- Spec Plate Corner Fastener Screws --}}
    <circle cx="23.3" cy="40.4" r="0.28" fill="#F8FAFC" stroke="#475569" stroke-width="0.1" />
    <circle cx="40.7" cy="40.4" r="0.28" fill="#F8FAFC" stroke="#475569" stroke-width="0.1" />
    <circle cx="23.3" cy="46.6" r="0.28" fill="#F8FAFC" stroke="#475569" stroke-width="0.1" />
    <circle cx="40.7" cy="46.6" r="0.28" fill="#F8FAFC" stroke="#475569" stroke-width="0.1" />

    {{-- Header: "MASTERING GAS" --}}
    <text x="32" y="41.3" text-anchor="middle" font-size="1.1" font-weight="bold" fill="#FFFFFF" letter-spacing="0.15" font-family="system-ui, -apple-system, sans-serif">MASTERING GAS</text>
    <line x1="22.8" y1="41.9" x2="41.2" y2="41.9" stroke="#334155" stroke-width="0.25" />

    {{-- Specification Table Rows --}}
    <line x1="22.8" y1="42.9" x2="41.2" y2="42.9" stroke="#1E293B" stroke-width="0.15" />
    <line x1="22.8" y1="43.9" x2="41.2" y2="43.9" stroke="#1E293B" stroke-width="0.15" />
    <line x1="22.8" y1="44.9" x2="41.2" y2="44.9" stroke="#1E293B" stroke-width="0.15" />
    <line x1="22.8" y1="45.9" x2="41.2" y2="45.9" stroke="#1E293B" stroke-width="0.15" />
    <line x1="31.2" y1="41.9" x2="31.2" y2="46.9" stroke="#334155" stroke-width="0.2" />

    {{-- Labels (Left Column) --}}
    <text x="23.3" y="42.7" font-size="0.68" font-weight="600" fill="#94A3B8" font-family="system-ui, -apple-system, sans-serif">TYPE</text>
    <text x="23.3" y="43.7" font-size="0.68" font-weight="600" fill="#94A3B8" font-family="system-ui, -apple-system, sans-serif">MAX PRESSURE</text>
    <text x="23.3" y="44.7" font-size="0.68" font-weight="600" fill="#94A3B8" font-family="system-ui, -apple-system, sans-serif">MAX TEMP.</text>
    <text x="23.3" y="45.7" font-size="0.68" font-weight="600" fill="#94A3B8" font-family="system-ui, -apple-system, sans-serif">SERIAL N°</text>
    <text x="23.3" y="46.7" font-size="0.68" font-weight="600" fill="#94A3B8" font-family="system-ui, -apple-system, sans-serif">DATE</text>

    {{-- Values (Right Column) --}}
    <text x="31.8" y="42.7" font-size="0.68" font-weight="bold" fill="#F1F5F9" font-family="system-ui, -apple-system, sans-serif">MG-1000</text>
    <text x="31.8" y="43.7" font-size="0.68" font-weight="bold" fill="#F1F5F9" font-family="system-ui, -apple-system, sans-serif">350 bar</text>
    <text x="31.8" y="44.7" font-size="0.68" font-weight="bold" fill="#F1F5F9" font-family="system-ui, -apple-system, sans-serif">+50 °C</text>
    <text x="31.8" y="45.7" font-size="0.68" font-weight="bold" fill="#F1F5F9" font-family="system-ui, -apple-system, sans-serif">MG-1000-0425</text>
    <text x="31.8" y="46.7" font-size="0.68" font-weight="bold" fill="#F1F5F9" font-family="system-ui, -apple-system, sans-serif">04/2025</text>

    {{-- 11. Lower Heavy Tubular Carrying Handle & Triangular Gusset Brackets --}}
    {{-- Left Triangular Gusset Ear --}}
    <path d="M 19.8 48.6 L 23.4 48.6 L 23.4 54.2 L 20.2 54.8 L 19.8 48.6 Z" fill="url(#prv-bracket)" stroke="#475569" stroke-width="0.3" />
    <path d="M 23.4 48.6 L 23.4 54.2 L 22.2 54.2 Z" fill="#94A3B8" opacity="0.7" />
    <circle cx="21" cy="50" r="0.4" fill="#FFFFFF" stroke="#475569" stroke-width="0.15" />
    <circle cx="21" cy="53" r="0.4" fill="#CBD5E1" stroke="#475569" stroke-width="0.15" />

    {{-- Right Triangular Gusset Ear --}}
    <path d="M 44.2 48.6 L 40.6 48.6 L 40.6 54.2 L 43.8 54.8 L 44.2 48.6 Z" fill="url(#prv-bracket)" stroke="#475569" stroke-width="0.3" />
    <path d="M 40.6 48.6 L 40.6 54.2 L 41.8 54.2 Z" fill="#94A3B8" opacity="0.7" />
    <circle cx="43" cy="50" r="0.4" fill="#FFFFFF" stroke="#475569" stroke-width="0.15" />
    <circle cx="43" cy="53" r="0.4" fill="#CBD5E1" stroke="#475569" stroke-width="0.15" />

    {{-- Heavy Horizontal Stainless Steel Pipe / Bar --}}
    <rect x="20.4" y="52" width="23.2" height="2.2" rx="1.1" fill="url(#prv-steel-handle)" stroke="#64748B" stroke-width="0.3" />
    {{-- Longitudinal Metallic Highlight --}}
    <line x1="21.4" y1="52.6" x2="42.6" y2="52.6" stroke="#FFFFFF" stroke-width="0.45" stroke-linecap="round" opacity="0.95" />
    {{-- Lower Shadow Edge --}}
    <line x1="21.4" y1="53.8" x2="42.6" y2="53.8" stroke="#334155" stroke-width="0.35" opacity="0.55" />
</svg>
