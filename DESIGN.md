---
name: Real Rent Car
description: Nordic Precision & Polestar Luxury Automotive Mobility Platform
colors:
  canvas-dark: "#06090E"
  canvas-light: "#F8F9FA"
  surface-dark: "#0A101D"
  surface-light: "#FFFFFF"
  border-dark: "rgba(255, 255, 255, 0.08)"
  border-light: "rgba(0, 0, 0, 0.06)"
  ink-primary-dark: "#F8FAFC"
  ink-primary-light: "#0F172A"
  ink-secondary-dark: "#94A3B8"
  ink-secondary-light: "#64748B"
  accent-telemetry: "#38BDF8"
  accent-slate: "#58779D"
  accent-gold: "#F59E0B"
  accent-emerald: "#10B981"
typography:
  display:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: "3.5rem"
    fontWeight: 800
    lineHeight: 1.05
    letterSpacing: "-0.035em"
  telemetry:
    fontFamily: "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace"
    fontSize: "0.875rem"
    fontWeight: 600
    letterSpacing: "0.02em"
rounded:
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "24px"
components:
  button-primary:
    backgroundColor: "{colors.ink-primary-light}"
    textColor: "{colors.canvas-light}"
    rounded: "{rounded.md}"
    padding: "12px 24px"
  button-primary-hover:
    backgroundColor: "{colors.accent-slate}"
---

## Overview

Real Rent Car embodies **Nordic Precision & Polestar Luxury**: an architectural automotive interface characterized by calm technical discipline, matte obsidian surfaces, titanium hairline borders, and luminous telemetry feedback. Every viewport is engineered to feel like premium automotive software (e.g. Porsche PCM, Polestar, Genesis) rather than a generic rental website.

## Colors

- **Stage Grounds:**
  - Dark: `#06090E` (Matte Obsidian) / `#0A101D` (Space Surface).
  - Light: `#F8F9FA` (Titanium Alabaster) / `#FFFFFF` (Pristine Card).
- **Hairline Borders:** `rgba(255, 255, 255, 0.08)` (Dark) and `rgba(0, 0, 0, 0.06)` (Light).
- **Telemetry & Status:**
  - Telemetry Cyan: `#38BDF8` (Active range fill, live radar sweep).
  - Slate Accent: `#58779D` (Secondary brand voice).
  - Gold Accent: `#F59E0B` (VIP loyalty tiers, verified star ratings).
  - Emerald Green: `#10B981` (Confirmed bookings, PDI-passed inspections).

## Typography

- **Headings & Display:** Tight-tracked Swiss geometric styling (`tracking-[-0.03em]`, bold/extrabold) with deliberate hierarchy and zero generic kicker slogans.
- **Micro-Labels:** Uppercase tracking (`tracking-[0.08em]`, `text-[10px]` or `text-xs`) for telemetry captions, spec indicators, and hub coordinates.
- **Monospace Numbers:** Currency rates (`₹X,XXX / day`), odometer distances, timestamps, and booking reference numbers (`#RES-XXXXX`) are rendered in tabular monospace font for mathematical precision.

## Layout

- **Storefront Hero:** Full-bleed cinematic staging with ambient rim lighting and integrated Availability Search Capsule.
- **Bento Matrix:** Interactive widgets (Category switcher, CAD vehicle condition blueprint, and VIP loyalty calculator) rather than static text boxes.
- **Fleet Showcase:** 16:9 widescreen vehicle cards with floating spec pills (transmission, fuel/electric, seats, luggage).
- **Booking Studio:** Two-column split layout (Left = Guided 4-step journey; Right = Persistent dynamic glass order vault).
- **Client Passport:** Perforated boarding pass cards with live timeline status pulse dots and quick action ribbons.
- **Admin Mission Control:** Dark-room command center with interactive SVG regional hub radar and live transponder telemetry.

## Elevation & Depth

- Frosted glass containers utilize `backdrop-blur-2xl` with high transparency (`bg-white/75` or `bg-[#06090E]/80`).
- Hairline translucent borders (`border-white/10` or `border-black/5`) provide crisp separation without harsh box-shadows.
- Active tactile feedback (`active:scale-[0.98]`) across all interactive pills, buttons, and toggle controls.

## Shapes

- Containers use balanced radii: `rounded-2xl` for feature cards, `rounded-xl` for interactive inputs/buttons, and `rounded-full` for status chips.
- Booked/reserved dates in the calendar feature a distinct diagonal cross-hatching stroke pattern (`45deg` repeating lines).

## Components

- **Frosted Control Capsule:** Floating filter pill bars with search shortcut indicator (`/`) and active state pills.
- **Vehicle Stage Card:** 16:9 automotive presentation with ambient floor reflection and badge ribbons.
- **Sticky Order Vault:** Persistent glass card displaying mathematical breakdowns of base rates, add-ons, loyalty discounts, GST, and security deposits.
- **CAD Damage Blueprint:** Interactive top-down vehicle SVG allowing pin-drop damage logging and deposit reconciliation.

## Do's and Don'ts

### Do's:
- Use Lucide SVG icons exclusively.
- Use tabular monospace numerals for all prices, odometers, and booking numbers.
- Maintain high contrast and verify with `impeccable detect`.
- Provide real-time mathematical transparency across all discounts and deposits.

### Don'ts:
- Never use generic emojis (🚘, 📅, 🤝, etc.) in production UI.
- Never use low-contrast gray text on dark backgrounds.
- Never use harsh, saturated generic gradient text.
- Never remove test assertions or bypass existing Inertia controller contracts.
