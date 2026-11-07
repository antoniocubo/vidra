import { ProductBadge } from '@/components/vidra';
import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center">
                <AppLogoIcon className="h-7 w-auto" />
            </div>
            <div className="ml-1 flex min-w-0 flex-1 items-center gap-2 text-left group-data-[collapsible=icon]:hidden">
                <span className="font-display truncate text-xl leading-none font-bold tracking-tight">vidra</span>
                <ProductBadge line="pet" />
            </div>
        </>
    );
}
