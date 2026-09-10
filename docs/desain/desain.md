---
name: Modern Executive Interface
colors:
  surface: '#f9f9fc'
  surface-dim: '#dadadc'
  surface-bright: '#f9f9fc'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f3f6'
  surface-container: '#eeeef0'
  surface-container-high: '#e8e8ea'
  surface-container-highest: '#e2e2e5'
  on-surface: '#1a1c1e'
  on-surface-variant: '#424656'
  inverse-surface: '#2f3133'
  inverse-on-surface: '#f0f0f3'
  outline: '#737687'
  outline-variant: '#c2c6d9'
  surface-tint: '#0053da'
  primary: '#004cca'
  on-primary: '#ffffff'
  primary-container: '#0062ff'
  on-primary-container: '#f3f3ff'
  inverse-primary: '#b4c5ff'
  secondary: '#006a62'
  on-secondary: '#ffffff'
  secondary-container: '#57fae9'
  on-secondary-container: '#007168'
  tertiary: '#ad1220'
  on-tertiary: '#ffffff'
  tertiary-container: '#d13035'
  on-tertiary-container: '#fff1ef'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174b'
  on-primary-fixed-variant: '#003ea8'
  secondary-fixed: '#57fae9'
  secondary-fixed-dim: '#2addcd'
  on-secondary-fixed: '#00201d'
  on-secondary-fixed-variant: '#005049'
  tertiary-fixed: '#ffdad7'
  tertiary-fixed-dim: '#ffb3ae'
  on-tertiary-fixed: '#410004'
  on-tertiary-fixed-variant: '#930015'
  background: '#f9f9fc'
  on-background: '#1a1c1e'
  surface-variant: '#e2e2e5'
typography:
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: '1.2'
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: '1.3'
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: '1.4'
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: '1.5'
  label-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: 0.05em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '500'
    lineHeight: '1.2'
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  container-margin: 2rem
  gutter: 1.5rem
  card-padding: 1.5rem
  stack-gap: 1rem
---

## Brand & Style

The design system is engineered to transform complex administrative data into a breath of fresh air. It pivots away from dense, dark interfaces toward an **Optimistic Modernism**—a style that feels authoritative yet approachable for high-level executives.

The brand personality is characterized by **Executive Clarity**: it is "kekinian" (contemporary) without being trendy, prioritizing speed of cognition through generous whitespace and a vibrant, professional palette. The visual language utilizes a mix of **Corporate Modern** structure and **Minimalist** breathing room. The emotional response should be one of calm control, transparency, and efficiency. Every element is designed to feel "soft" to the touch, reducing the cognitive load of governance and scheduling.

## Colors

The palette is anchored in a **Light Mode** foundation to maximize readability and energy. 

*   **Primary (Electric Blue):** Used for primary actions, active navigation states, and key highlights. It signals reliability and modern technology.
*   **Secondary (Soft Teal):** Reserved for secondary progress indicators, creative tasks, and success states. It provides a "fresh" contrast to the primary blue.
*   **Surface & Background:** We use a "Paper White" (#FFFFFF) for cards and a "Mist Grey" (#F8FAFC) for the canvas background to create a subtle but clear distinction between the workspace and the content.
*   **Functional Colors:** High-contrast neutrals ensure that typography remains sharp. Semantic colors (Red for "Bentrok" or "Tinggi") are used sparingly to maintain the clean aesthetic.

## Typography

The design system utilizes **Plus Jakarta Sans** across all levels. This typeface was chosen for its geometric yet friendly proportions, which embody the "kekinian" professional look.

To ensure executive readability:
- **Headlines** use a bold weight and slightly tighter letter spacing to create a strong visual anchor.
- **Body Text** maintains a generous line height (1.6) to prevent fatigue during long reading sessions.
- **Micro-copy** (labels and captions) utilizes medium weights and increased letter spacing to remain legible even at small scales on mobile devices.

## Layout & Spacing

The layout follows a **Fluid Grid** philosophy with fixed maximum widths for ultra-wide displays to maintain readability. 

- **The 8px Rule:** All spacing increments are multiples of 8px, creating a predictable visual rhythm.
- **White Space as a Separator:** Instead of heavy lines, this design system uses generous margins and padding to group related items. 
- **Responsive Behavior:** 
    - **Desktop:** 12-column grid with 24px gutters.
    - **Tablet:** 8-column grid with 16px gutters.
    - **Mobile:** 4-column grid with 16px margins. Content cards stack vertically, and horizontal navigation transforms into a bottom-anchored or side-drawer menu.

## Elevation & Depth

To achieve the "Soft & Friendly" look, the design system avoids harsh borders in favor of **Ambient Shadows** and **Tonal Layering**.

- **Level 0 (Background):** The base canvas (#F8FAFC).
- **Level 1 (Cards):** Pure white surfaces with a soft, diffused shadow (Y: 4px, Blur: 20px, Opacity: 4% Black).
- **Level 2 (Hover/Active):** Slightly more pronounced depth (Y: 8px, Blur: 24px, Opacity: 8% Blue-tinted shadow).
- **Glassmorphism:** Navigation bars and sticky headers should use a backdrop blur (20px) with 80% opacity white to maintain a sense of space and modern "glass" aesthetic.

## Shapes

The shape language is consistently **Rounded**. Sharp corners are strictly avoided to maintain the "approachable" brand personality.

- **Standard Elements:** Buttons and input fields use a 0.5rem (8px) radius.
- **Containers:** Large dashboard cards and sections use a 1rem (16px) or 1.5rem (24px) radius for a friendlier, modern look.
- **Interactive Pill:** Filter chips and status tags (like "Tinggi" or "Bupati") use full-rounded pill shapes to distinguish them from structural cards.

## Components

### Buttons
Primary buttons are vibrant Electric Blue with white text. They should have a subtle inner glow or a very soft drop shadow to appear "pressable." Secondary buttons use a light blue tint (5% opacity) with primary blue text.

### Cards
All dashboard data is encapsulated in white cards with high roundedness. Headlines within cards should be prominent. Use vertical accents (4px thick lines on the far left) to denote status or priority levels without cluttering the card surface.

### Input Fields
Inputs should have a light grey fill (#F1F5F9) and a 1px border that turns Electric Blue on focus. Use "Plus Jakarta Sans" for placeholder text to keep the look consistent.

### Chips & Tags
Status indicators (e.g., "Agenda Internal") should use soft-colored backgrounds with high-contrast text of the same hue (e.g., light teal background with dark teal text) to ensure they are readable but not visually aggressive.

### Lists & Timelines
Timeline markers should be geometric and clean. Use the Electric Blue for "current" or "active" time indicators, and soft greys for past events. Ensure a minimum 16px gap between list items to maintain the feeling of whitespace.