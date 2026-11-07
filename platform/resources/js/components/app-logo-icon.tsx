import { VidraSymbol } from '@/components/vidra';
import { type SVGAttributes } from 'react';

/** Símbolo da Vidra (o tubo), usado onde o kit espera um ícone de app. */
export default function AppLogoIcon(props: Omit<SVGAttributes<SVGSVGElement>, 'children'>) {
    return <VidraSymbol {...props} />;
}
