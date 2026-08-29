import { heroui } from "@heroui/react";

// Map HeroUI's interactive colors onto the Signal accent so focus rings and the
// active pagination item match the palette instead of the default blue.
export default heroui({
  layout: {
    radius: { small: "0.5rem", medium: "0.75rem", large: "1rem" },
  },
  themes: {
    light: {
      colors: {
        background: "#e6e8eb",
        foreground: "#17191e",
        focus: "#b7791f",
        primary: {
          DEFAULT: "#b7791f",
          foreground: "#ffffff",
        },
      },
    },
  },
});
