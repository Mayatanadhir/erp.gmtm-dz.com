@props([
    'class' => 'w-14 h-14 sm:w-16 sm:h-16 shrink-0',
])

{{-- Industrial Process Gas Chromatograph (GC): Analytical Transmitter Enclosure with Fluted Oven Dome, Sampling Manifold & Chromatogram Display --}}
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        {{-- Cylindrical Top Oven / Column Dome Gradient --}}
        <linearGradient id="gc-dome" x1="19" y1="2" x2="43" y2="2" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#1E65A4" />
            <stop offset="25%" stop-color="#3B93DF" />
            <stop offset="55%" stop-color="#64B0F3" />
            <stop offset="75%" stop-color="#2D83D0" />
            <stop offset="100%" stop-color="#144C7E" />
        </linearGradient>

        {{-- Main Blue Cast Enclosure Body Gradient --}}
        <linearGradient id="gc-body" x1="12" y1="24" x2="58" y2="58" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#3D95E3" />
            <stop offset="30%" stop-color="#267DC9" />
            <stop offset="75%" stop-color="#195D99" />
            <stop offset="100%" stop-color="#11426E" />
        </linearGradient>

        {{-- Left Mounting Bracket Flange Gradient --}}
        <linearGradient id="gc-bracket" x1="4" y1="24" x2="12" y2="40" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#2D83D0" />
            <stop offset="100%" stop-color="#144C7E" />
        </linearGradient>

        {{-- Front Circular Bezel Ring Gradient --}}
        <linearGradient id="gc-bezel" x1="31" y1="27" x2="59" y2="55" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#6DB7F7" />
            <stop offset="35%" stop-color="#2E88D6" />
            <stop offset="70%" stop-color="#1A619F" />
            <stop offset="100%" stop-color="#0F3D66" />
        </linearGradient>

        {{-- Inner Dark Bezel Recess Plate Gradient --}}
        <radialGradient id="gc-dial-recess" cx="45" cy="41" r="12" gradientUnits="userSpaceOnUse">
            <stop offset="60%" stop-color="#1E293B" />
            <stop offset="100%" stop-color="#0F172A" />
        </radialGradient>

        {{-- Illuminated Pale Green LCD Screen Gradient --}}
        <linearGradient id="gc-lcd" x1="37" y1="36" x2="53" y2="45" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#E8F5E9" />
            <stop offset="50%" stop-color="#C8E6C9" />
            <stop offset="100%" stop-color="#A5D6A7" />
        </linearGradient>

        {{-- Stainless Steel Metallic Gradient (Tubing, Fittings, Manifold) --}}
        <linearGradient id="gc-steel" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#FFFFFF" />
            <stop offset="35%" stop-color="#E2E8F0" />
            <stop offset="70%" stop-color="#94A3B8" />
            <stop offset="100%" stop-color="#64748B" />
        </linearGradient>

        {{-- Dark Steel Gradient --}}
        <linearGradient id="gc-dark-steel" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#64748B" />
            <stop offset="100%" stop-color="#334155" />
        </linearGradient>

        {{-- Overall Cast Enclosure Drop Shadow --}}
        <filter id="gc-shadow" x="1" y="1" width="62" height="63" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
            <feDropShadow dx="0" dy="2.5" stdDeviation="2" flood-color="#082035" flood-opacity="0.38" />
        </filter>
    </defs>

    <g filter="url(#gc-shadow)">
        {{-- ================================================================= --}}
        {{-- 1. LEFT WALL MOUNTING FLANGE / BRACKET WITH BOLT HOLES           --}}
        {{-- ================================================================= --}}
        <path d="M 12 24 L 5 24 C 4 24 3.5 24.5 3.5 25.5 L 3.5 39 C 3.5 40 4 40.5 5 40.5 L 12 40.5 Z" 
              fill="url(#gc-bracket)" stroke="#1A619F" stroke-width="0.8" />
        {{-- Upper Mounting Bolt Hole --}}
        <ellipse cx="7" cy="28.5" rx="1.8" ry="2.2" fill="#0C3252" stroke="#6DB7F7" stroke-width="0.6" />
        <ellipse cx="7" cy="28.5" rx="1" ry="1.4" fill="#082238" />
        {{-- Lower Mounting Bolt Hole --}}
        <ellipse cx="7" cy="36" rx="1.8" ry="2.2" fill="#0C3252" stroke="#6DB7F7" stroke-width="0.6" />
        <ellipse cx="7" cy="36" rx="1" ry="1.4" fill="#082238" />

        {{-- ================================================================= --}}
        {{-- 2. TOP OVEN / COLUMN DOME (CYLINDRICAL WITH VERTICAL FLUTES)      --}}
        {{-- ================================================================= --}}
        {{-- Collar Ring at Base of Dome --}}
        <rect x="18" y="21.5" width="26" height="3" rx="1" fill="#1A619F" stroke="#144C7E" stroke-width="0.7" />
        <line x1="18.5" y1="22.5" x2="43.5" y2="22.5" stroke="#64B0F3" stroke-width="0.6" opacity="0.6" />

        {{-- Main Vertical Cylinder Body --}}
        <path d="M 19 6 C 19 3 24 2 31 2 C 38 2 43 3 43 6 L 43 22 L 19 22 Z" 
              fill="url(#gc-dome)" stroke="#165184" stroke-width="0.8" />

        {{-- Top Elliptical Crown Highlight --}}
        <ellipse cx="31" cy="5" rx="10.5" ry="2" fill="#64B0F3" opacity="0.4" />
        <ellipse cx="29" cy="4.5" rx="7" ry="1.2" fill="#FFFFFF" opacity="0.45" />

        {{-- Vertical Flutes / Stiffener Ribs on Cylinder --}}
        {{-- Rib 1 (Left) --}}
        <path d="M 21.5 7 L 21.5 19.5" stroke="#165184" stroke-width="0.9" stroke-linecap="round" />
        <path d="M 22.3 7 L 22.3 19.5" stroke="#64B0F3" stroke-width="0.5" opacity="0.6" stroke-linecap="round" />
        {{-- Rib 2 (Center-Left) --}}
        <path d="M 26 7 L 26 20" stroke="#195D99" stroke-width="0.9" stroke-linecap="round" />
        <path d="M 26.8 7 L 26.8 20" stroke="#FFFFFF" stroke-width="0.6" opacity="0.7" stroke-linecap="round" />
        {{-- Rib 3 (Center-Right) --}}
        <path d="M 31 7 L 31 20" stroke="#195D99" stroke-width="0.9" stroke-linecap="round" />
        <path d="M 31.8 7 L 31.8 20" stroke="#64B0F3" stroke-width="0.6" opacity="0.6" stroke-linecap="round" />
        {{-- Rib 4 (Right) --}}
        <path d="M 36 7 L 36 20" stroke="#165184" stroke-width="0.9" stroke-linecap="round" />
        <path d="M 36.8 7 L 36.8 19.5" stroke="#2D83D0" stroke-width="0.5" opacity="0.5" stroke-linecap="round" />
        {{-- Rib 5 (Far Right Shadow) --}}
        <path d="M 40.5 7.5 L 40.5 19" stroke="#0F3D66" stroke-width="0.9" stroke-linecap="round" />

        {{-- ================================================================= --}}
        {{-- 3. MAIN CAST ENCLOSURE BODY (BLUE CUBIC BASE)                     --}}
        {{-- ================================================================= --}}
        <rect x="11.5" y="24" width="46.5" height="34" rx="2.5" 
              fill="url(#gc-body)" stroke="#165184" stroke-width="0.9" />

        {{-- Top Edge Highlight --}}
        <line x1="13" y1="24.8" x2="56" y2="24.8" stroke="#64B0F3" stroke-width="0.8" opacity="0.7" />
        {{-- Left Edge Highlight --}}
        <line x1="12.3" y1="25" x2="12.3" y2="56" stroke="#4FA8F5" stroke-width="0.7" opacity="0.5" />

        {{-- Bottom Lower Skirt / Base Flange --}}
        <path d="M 10.5 56.5 L 57.5 56.5 L 57 58.5 L 11 58.5 Z" fill="#144C7E" />

        {{-- Bottom Right Drain / Calibration Hex Nut --}}
        <circle cx="33" cy="54" r="1.6" fill="url(#gc-steel)" stroke="#475569" stroke-width="0.5" />
        <circle cx="33" cy="54" r="0.8" fill="#1E293B" />

        {{-- ================================================================= --}}
        {{-- 4. LEFT SIDE CHROMATOGRAPHY SAMPLING TUBING & MODULES             --}}
        {{-- ================================================================= --}}
        {{-- Upper Sample Inlet/Outlet Hex Ports --}}
        <rect x="13" y="27" width="3.5" height="3" rx="0.5" fill="url(#gc-steel)" stroke="#475569" stroke-width="0.5" />
        <circle cx="14.8" cy="28.5" r="0.9" fill="#334155" />

        <rect x="19" y="27" width="3.5" height="3" rx="0.5" fill="url(#gc-steel)" stroke="#475569" stroke-width="0.5" />
        <circle cx="20.8" cy="28.5" r="0.9" fill="#334155" />

        {{-- Stainless Steel Fluidic Tubes Running Vertically --}}
        {{-- Outer Tube from upper left port down to manifold --}}
        <path d="M 14.8 30 L 14.8 48 C 14.8 52 11 52 11 55 L 11 57.5" 
              fill="none" stroke="url(#gc-steel)" stroke-width="0.9" stroke-linecap="round" />

        {{-- Inner Tube from upper right port down to manifold --}}
        <path d="M 20.8 30 L 20.8 38 C 20.8 41 18 41 18 45 L 18 56 L 16 57.5" 
              fill="none" stroke="url(#gc-steel)" stroke-width="0.8" stroke-linecap="round" />

        {{-- Tube Mounting Support Clamps Screwed to Casing --}}
        <rect x="14" y="36" width="2" height="1.8" rx="0.3" fill="url(#gc-steel)" stroke="#334155" stroke-width="0.4" />
        <circle cx="15" cy="36.9" r="0.3" fill="#0F172A" />

        <rect x="14" y="44" width="2" height="1.8" rx="0.3" fill="url(#gc-steel)" stroke="#334155" stroke-width="0.4" />
        <circle cx="15" cy="44.9" r="0.3" fill="#0F172A" />

        {{-- Dual Electronic Interface Module Blocks (AI 1-4, AI 5-8) --}}
        {{-- Upper Module: AI 1-4 --}}
        <rect x="23.5" y="26.5" width="4.5" height="11" rx="0.6" fill="#0F172A" stroke="#475569" stroke-width="0.5" />
        <rect x="24.2" y="27" width="3.1" height="2" rx="0.3" fill="url(#gc-steel)" />
        <text x="25.8" y="28.5" font-size="1.2" font-family="monospace" font-weight="900" fill="#0F172A" text-anchor="middle">AI 1-4</text>
        {{-- Pin Connectors Strip --}}
        <line x1="24.5" y1="30" x2="27" y2="30" stroke="#CBD5E1" stroke-width="0.4" />
        <line x1="24.5" y1="31.5" x2="27" y2="31.5" stroke="#CBD5E1" stroke-width="0.4" />
        <line x1="24.5" y1="33" x2="27" y2="33" stroke="#CBD5E1" stroke-width="0.4" />
        <line x1="24.5" y1="34.5" x2="27" y2="34.5" stroke="#CBD5E1" stroke-width="0.4" />
        <line x1="24.5" y1="36" x2="27" y2="36" stroke="#CBD5E1" stroke-width="0.4" />

        {{-- Lower Module: AI 5-8 --}}
        <rect x="23.5" y="39" width="4.5" height="11" rx="0.6" fill="#0F172A" stroke="#475569" stroke-width="0.5" />
        <rect x="24.2" y="39.5" width="3.1" height="2" rx="0.3" fill="url(#gc-steel)" />
        <text x="25.8" y="41" font-size="1.2" font-family="monospace" font-weight="900" fill="#0F172A" text-anchor="middle">AI 5-8</text>
        {{-- Pin Connectors Strip --}}
        <line x1="24.5" y1="42.5" x2="27" y2="42.5" stroke="#CBD5E1" stroke-width="0.4" />
        <line x1="24.5" y1="44" x2="27" y2="44" stroke="#CBD5E1" stroke-width="0.4" />
        <line x1="24.5" y1="45.5" x2="27" y2="45.5" stroke="#CBD5E1" stroke-width="0.4" />
        <line x1="24.5" y1="47" x2="27" y2="47" stroke="#CBD5E1" stroke-width="0.4" />
        <line x1="24.5" y1="48.5" x2="27" y2="48.5" stroke="#CBD5E1" stroke-width="0.4" />

        {{-- Bottom Gas Stream Multi-Port Sampling Manifold Rail --}}
        <rect x="3" y="57" width="24" height="2.5" rx="0.5" fill="url(#gc-steel)" stroke="#475569" stroke-width="0.6" />
        {{-- Precision Swagelok Compression Hex Fitting Nuts Below Rail --}}
        <rect x="4" y="59.5" width="1.8" height="2" rx="0.3" fill="url(#gc-dark-steel)" stroke="#334155" stroke-width="0.3" />
        <rect x="7" y="59.5" width="1.8" height="2" rx="0.3" fill="url(#gc-dark-steel)" stroke="#334155" stroke-width="0.3" />
        <rect x="10" y="59.5" width="1.8" height="2" rx="0.3" fill="url(#gc-dark-steel)" stroke="#334155" stroke-width="0.3" />
        <rect x="13" y="59.5" width="1.8" height="2" rx="0.3" fill="url(#gc-dark-steel)" stroke="#334155" stroke-width="0.3" />
        <rect x="16" y="59.5" width="1.8" height="2" rx="0.3" fill="url(#gc-dark-steel)" stroke="#334155" stroke-width="0.3" />
        <rect x="19" y="59.5" width="1.8" height="2" rx="0.3" fill="url(#gc-dark-steel)" stroke="#334155" stroke-width="0.3" />
        <rect x="22" y="59.5" width="1.8" height="2" rx="0.3" fill="url(#gc-dark-steel)" stroke="#334155" stroke-width="0.3" />

        {{-- Miniature Tube Loops into Bottom Manifold Ports --}}
        <path d="M 5 57 L 5 53.5 C 5 52 8 52 8 54 L 8 57" fill="none" stroke="url(#gc-steel)" stroke-width="0.7" />
        <path d="M 11 57 L 11 54 C 11 53 14 53 14 55 L 14 57" fill="none" stroke="url(#gc-steel)" stroke-width="0.7" />
        <path d="M 17 57 L 17 53 C 17 51.5 20 51.5 20 54 L 20 57" fill="none" stroke="url(#gc-steel)" stroke-width="0.7" />

        {{-- ================================================================= --}}
        {{-- 5. PROMINENT CIRCULAR FRONT EXPLOSION-PROOF DISPLAY BEZEL         --}}
        {{-- ================================================================= --}}
        {{-- Outer Cast Cylindrical Display Barrel Rim --}}
        <circle cx="45" cy="42" r="14.5" fill="url(#gc-bezel)" stroke="#165184" stroke-width="1.2" />

        {{-- Cast Notched Bezel Perimeter Grips (Top & Bottom Indentations) --}}
        <rect x="41" y="27.8" width="8" height="1.8" rx="0.6" fill="#165184" />
        <rect x="41" y="54.4" width="8" height="1.8" rx="0.6" fill="#0F3D66" />
        <line x1="41.5" y1="29.2" x2="48.5" y2="29.2" stroke="#6DB7F7" stroke-width="0.5" opacity="0.6" />

        {{-- Inner Stepped Bezel Lip --}}
        <circle cx="45" cy="42" r="12.8" stroke="#6DB7F7" stroke-width="0.7" opacity="0.7" />

        {{-- Recessed Dark Dial Face Plate (Under Glass) --}}
        <circle cx="45" cy="42" r="11.8" fill="url(#gc-dial-recess)" stroke="#0F172A" stroke-width="0.8" />

        {{-- 3 Status Diagnostic LEDs at Top of Dial --}}
        {{-- LED 1: ALM (Alarm - Red/Amber) --}}
        <circle cx="40.5" cy="33.8" r="0.9" fill="#EF4444" stroke="#991B1B" stroke-width="0.3" />
        <circle cx="40.2" cy="33.5" r="0.3" fill="#FFFFFF" opacity="0.7" />
        <text x="40.5" y="36.2" font-size="1.2" font-family="sans-serif" font-weight="700" fill="#94A3B8" text-anchor="middle">ALM</text>

        {{-- LED 2: RUN (Normal Operation - Green Active Glow) --}}
        <circle cx="45" cy="33.8" r="1.3" fill="#10B981" opacity="0.3" />
        <circle cx="45" cy="33.8" r="0.9" fill="#10B981" stroke="#047857" stroke-width="0.3" />
        <circle cx="44.7" cy="33.5" r="0.3" fill="#FFFFFF" opacity="0.8" />
        <text x="45" y="36.2" font-size="1.2" font-family="sans-serif" font-weight="700" fill="#34D399" text-anchor="middle">RUN</text>

        {{-- LED 3: COMM (Serial / Modbus Communication - Amber) --}}
        <circle cx="49.5" cy="33.8" r="0.9" fill="#F59E0B" stroke="#B45309" stroke-width="0.3" />
        <circle cx="49.2" cy="33.5" r="0.3" fill="#FFFFFF" opacity="0.7" />
        <text x="49.5" y="36.2" font-size="1.2" font-family="sans-serif" font-weight="700" fill="#94A3B8" text-anchor="middle">COMM</text>

        {{-- ================================================================= --}}
        {{-- 6. ILLUMINATED GRAPHICAL CHROMATOGRAM LCD DISPLAY                 --}}
        {{-- ================================================================= --}}
        <rect x="36.5" y="37" width="17" height="9.5" rx="0.8" 
              fill="url(#gc-lcd)" stroke="#059669" stroke-width="0.5" />

        {{-- LCD Graph Boundary Axis Lines --}}
        <path d="M 37.8 38 L 37.8 44.5 L 45.5 44.5" stroke="#065F46" stroke-width="0.35" opacity="0.5" />

        {{-- Real Gas Chromatography Waveform Curve (Separation Peaks) --}}
        {{-- Peak 1 (Methane/C1) -> Baseline -> Peak 2 (Ethane/C2) -> Major Peak 3 (C3/Propane) --}}
        <path d="M 38 43.5 
                 L 39.2 43.5 
                 Q 39.8 43.5 40.2 41.5 
                 Q 40.5 40 40.9 41.5 
                 Q 41.2 43.5 41.8 43.5 
                 L 42.5 43.5 
                 Q 43 43.5 43.5 39 
                 Q 44 38 44.4 39.2 
                 Q 45 43.5 45.8 43.5 
                 L 46.5 43.5" 
              fill="none" stroke="#064E3B" stroke-width="0.65" stroke-linecap="round" stroke-linejoin="round" />

        {{-- Digital Measurement Readout on Right Side of Screen --}}
        <text x="47.5" y="39.8" font-size="1.6" font-family="monospace" font-weight="800" fill="#064E3B">PV 12.34</text>
        <text x="47.5" y="42" font-size="1.6" font-family="monospace" font-weight="800" fill="#064E3B">mA  4.02</text>

        {{-- On-Screen Softkey Legend Bar (MENU, ▲, ▼, ENT) --}}
        <line x1="36.5" y1="44.2" x2="53.5" y2="44.2" stroke="#059669" stroke-width="0.3" opacity="0.6" />
        <text x="38.5" y="45.7" font-size="1" font-family="sans-serif" font-weight="800" fill="#065F46">MENU</text>
        <text x="42.5" y="45.7" font-size="1.1" font-family="sans-serif" font-weight="900" fill="#065F46">▲</text>
        <text x="45.5" y="45.7" font-size="1.1" font-family="sans-serif" font-weight="900" fill="#065F46">▼</text>
        <text x="49" y="45.7" font-size="1" font-family="sans-serif" font-weight="800" fill="#065F46">ENT</text>

        {{-- 4 Tactile Navigation Keypad Pushbuttons Below Screen --}}
        <rect x="38.5" y="47.5" width="2.4" height="2" rx="0.4" fill="#334155" stroke="#475569" stroke-width="0.3" />
        <path d="M 40 48.1 L 39.3 48.5 L 40 48.9 Z" fill="#F8FAFC" />

        <rect x="41.7" y="47.5" width="2.4" height="2" rx="0.4" fill="#334155" stroke="#475569" stroke-width="0.3" />
        <path d="M 42.5 48.8 L 42.9 48.1 L 43.3 48.8 Z" fill="#F8FAFC" />

        <rect x="44.9" y="47.5" width="2.4" height="2" rx="0.4" fill="#334155" stroke="#475569" stroke-width="0.3" />
        <path d="M 45.7 48.1 L 46.1 48.8 L 46.5 48.1 Z" fill="#F8FAFC" />

        <rect x="48.1" y="47.5" width="2.4" height="2" rx="0.4" fill="#334155" stroke="#475569" stroke-width="0.3" />
        <path d="M 49.8 48.1 L 48.8 48.5 L 49.3 48.5 L 49.3 48.9 Z" fill="#F8FAFC" />

        {{-- Glass Window Curved Reflection Glare Highlight --}}
        <path d="M 37 34 C 41 31 50 33 54 37 C 51 35 42 33 37 34 Z" fill="#FFFFFF" opacity="0.35" />
        <circle cx="53" cy="38" r="0.8" fill="#FFFFFF" opacity="0.45" />
    </g>
</svg>
