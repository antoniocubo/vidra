import { SVGAttributes } from 'react';

/**
 * Símbolo da Vidra: o tubo de ensaio que faz o "i" do logo.
 * Contorno na cor do texto (currentColor); tampa e líquido na cor da linha Pet.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg viewBox="0 0 14 44.3" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" {...props}>
            <rect x="0.5" y="1" width="13" height="6.7" rx="3.35" fill="var(--color-pet-500)" />
            <rect x="2.5" y="12.8" width="9" height="30" rx="4.5" stroke="currentColor" strokeWidth="3" fill="none" />
            <rect x="5.2" y="25.3" width="3.6" height="14.8" rx="1.8" fill="var(--color-pet-500)" />
        </svg>
    );
}
