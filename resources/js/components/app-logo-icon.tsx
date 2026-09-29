import type { SVGAttributes } from 'react';

/** Frietzak in lijnstijl (zelfde idee als public/favicon.svg). Kleur via `currentColor`. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden
        >
            <path d="M8 3v6" />
            <path d="M11 2v7" />
            <path d="M14 3v6" />
            <path d="M17 5v4" />
            <path d="M5 5v4" />
            <path d="M4 9h16l-1.5 12h-13z" />
            <path d="M6.5 14h11" />
        </svg>
    );
}
