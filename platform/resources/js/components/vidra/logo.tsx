import { cn } from '@/lib/utils';
import { type SVGAttributes } from 'react';

/** Cor da tampa e do líquido do tubo: assinatura de cada linha de produto. */
export type LogoLine = 'pet' | 'clinica' | 'marca';

const lineColor: Record<LogoLine, string> = {
    pet: 'var(--color-pet-500)',
    clinica: 'var(--color-clinic-500)',
    marca: 'var(--color-accent-400)',
};

type LogoProps = Omit<SVGAttributes<SVGSVGElement>, 'children'> & {
    line?: LogoLine;
};

/**
 * Logotipo da Vidra: a palavra "vidra" com o "i" desenhado como tubo de ensaio
 * (a tampa é o pingo, o líquido fica a 60%). Letras em curvas a partir da
 * Bricolage Grotesque 700, iguais ao arquivo oficial do design system.
 * Letras e contorno do tubo usam `currentColor`: defina a cor com `text-*`.
 */
export function VidraLogo({ line = 'pet', className, ...props }: LogoProps) {
    const accent = lineColor[line];

    return (
        <svg viewBox="0 0 247.59 86.82" role="img" aria-label="vidra" className={cn('h-6 w-auto', className)} {...props}>
            <path
                fill="currentColor"
                d="M25.55 76.82 8.63 24.34H24.16L34.82 64.97H35.58L46.48 24.34H61.24L44.3 76.82Z M115.43 78.2Q109.5 78.2 104.83 75.07Q100.15 71.93 97.45 65.7Q94.76 59.48 94.76 50.2Q94.76 41.47 97.19 35.38Q99.61 29.3 104.13 26.14Q108.65 22.97 114.87 22.97Q119.74 22.97 123.3 24.95Q126.86 26.94 129.15 31Q131.43 35.05 132.45 41.27H133.77Q133.21 37.7 132.7 34.38Q132.2 31.07 131.93 28.08Q131.66 25.09 131.66 22.59V6.75H146.12L146.09 55.4L146.14 76.82H133.64L133.81 60.61H132.7Q131.56 66.47 129.26 70.39Q126.97 74.3 123.53 76.25Q120.1 78.2 115.43 78.2ZM120.22 66.53Q122.87 66.53 125.01 65.32Q127.15 64.11 128.63 61.97Q130.12 59.83 130.93 57.07Q131.74 54.3 131.74 51.21V50.16Q131.74 47.7 131.19 45.43Q130.63 43.16 129.63 41.26Q128.62 39.36 127.19 37.94Q125.77 36.53 123.98 35.75Q122.19 34.97 120.15 34.97Q116.74 34.97 114.35 37.07Q111.96 39.18 110.74 42.76Q109.52 46.35 109.52 50.88Q109.52 55.35 110.79 58.91Q112.05 62.47 114.45 64.5Q116.86 66.53 120.22 66.53Z M152.47 76.82V49.84L152.45 24.31H164.86L164.6 44.94H165.76Q166.41 36.99 168.24 32.2Q170.08 27.42 173.06 25.27Q176.05 23.12 180.05 23.12Q181.52 23.12 183.14 23.47Q184.76 23.82 186.46 24.61L185.76 40.33Q183.85 39.3 181.94 38.83Q180.03 38.36 178.37 38.36Q174.82 38.36 172.32 40.36Q169.81 42.36 168.42 46.22Q167.04 50.08 166.85 55.61V76.82Z M202.73 78.2Q198.62 78.2 195.1 76.7Q191.59 75.2 189.44 72Q187.29 68.8 187.29 63.7Q187.29 59.04 189.08 56.06Q190.87 53.07 194.05 51.24Q197.23 49.41 201.31 48.38Q205.39 47.34 209.91 46.59Q214.59 45.75 217.06 45.19Q219.52 44.63 220.48 43.75Q221.43 42.86 221.43 41.24Q221.43 38.01 219.13 36.16Q216.83 34.32 212.83 34.32Q210.32 34.32 207.92 35.23Q205.52 36.15 203.92 38.28Q202.32 40.41 202.1 44.08L189.23 42.21Q189.56 36.75 191.67 33.05Q193.79 29.34 197.21 27.11Q200.63 24.89 204.78 23.9Q208.93 22.92 213.27 22.92Q220.74 22.92 225.67 25.6Q230.61 28.28 233.06 33.53Q235.52 38.78 235.52 46.57V55.91Q235.53 59.35 235.58 62.86Q235.63 66.38 235.71 69.87Q235.79 73.36 235.9 76.82H223.23Q223.18 73.36 223.13 69.7Q223.07 66.03 223.07 62.02H222.08Q221.33 66.48 218.73 70.17Q216.13 73.85 212.07 76.02Q208.01 78.2 202.73 78.2ZM208.52 67.57Q210.53 67.57 212.66 66.8Q214.8 66.03 216.69 64.42Q218.59 62.82 219.89 60.23Q221.19 57.65 221.51 54.07L221.47 49.95L224.03 49.78Q222.87 51.13 220.76 51.91Q218.64 52.7 216.1 53.15Q213.57 53.59 211.02 54.07Q208.47 54.55 206.33 55.37Q204.2 56.19 202.89 57.59Q201.59 59 201.59 61.45Q201.59 64.35 203.54 65.96Q205.5 67.57 208.52 67.57Z"
            />
            <rect x="66.69" y="8" width="22.62" height="10.86" rx="5.43" fill={accent} />
            <rect x="70.15" y="21.91" width="15.71" height="52.31" rx="7.85" fill="none" stroke="currentColor" strokeWidth="5.19" />
            <rect x="74.9" y="41.2" width="6.2" height="28.28" rx="3.1" fill={accent} />
        </svg>
    );
}

/** Símbolo: só o tubo, para quando o nome já aparece ao lado ou o espaço é estreito. */
export function VidraSymbol({ line = 'pet', className, ...props }: LogoProps) {
    const accent = lineColor[line];

    return (
        <svg viewBox="0 0 14 46" aria-hidden="true" className={cn('h-7 w-auto', className)} {...props}>
            <rect x="0.5" y="1" width="13" height="6.7" rx="3.35" fill={accent} />
            <rect x="2.5" y="12.8" width="9" height="30" rx="4.5" fill="none" stroke="currentColor" strokeWidth="3" />
            <rect x="5.2" y="25.3" width="3.6" height="14.8" rx="1.8" fill={accent} />
        </svg>
    );
}
