import { ProductBadge, VidraLogo, VidraSymbol } from '@/components/vidra';

/** Logo no menu: palavra com o tubo no "i" e selo da linha; recolhido, só o tubo. */
export default function AppLogo() {
    return (
        <>
            <span className="hidden size-8 shrink-0 items-center justify-center group-data-[collapsible=icon]:flex">
                <VidraSymbol className="h-7" />
            </span>
            <span className="flex min-w-0 items-center gap-2 group-data-[collapsible=icon]:hidden">
                <VidraLogo className="h-6" />
                <ProductBadge line="pet" />
            </span>
        </>
    );
}
