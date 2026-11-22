import type { SVGProps } from 'react';

/** Несколько иконок в стиле Lucide (inline SVG, без зависимостей). */
function Svg(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            className="size-4"
            {...props}
        />
    );
}

export const SearchIcon = (p: SVGProps<SVGSVGElement>) => (
    <Svg {...p}>
        <circle cx="11" cy="11" r="8" />
        <path d="m21 21-4.3-4.3" />
    </Svg>
);

export const XIcon = (p: SVGProps<SVGSVGElement>) => (
    <Svg {...p}>
        <path d="M18 6 6 18M6 6l12 12" />
    </Svg>
);

export const MinusIcon = (p: SVGProps<SVGSVGElement>) => (
    <Svg {...p}>
        <path d="M5 12h14" />
    </Svg>
);

export const PlusIcon = (p: SVGProps<SVGSVGElement>) => (
    <Svg {...p}>
        <path d="M5 12h14M12 5v14" />
    </Svg>
);

export const CartIcon = (p: SVGProps<SVGSVGElement>) => (
    <Svg {...p}>
        <circle cx="8" cy="21" r="1" />
        <circle cx="19" cy="21" r="1" />
        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
    </Svg>
);

export const MenuIcon = (p: SVGProps<SVGSVGElement>) => (
    <Svg {...p}>
        <path d="M4 6h16M4 12h16M4 18h16" />
    </Svg>
);

export const SlidersIcon = (p: SVGProps<SVGSVGElement>) => (
    <Svg {...p}>
        <path d="M21 4h-7M10 4H3M21 12h-9M8 12H3M21 20h-5M12 20H3M14 2v4M8 10v4M16 18v4" />
    </Svg>
);
